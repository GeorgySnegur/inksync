<?php
require_once __DIR__ . '/backend/bootstrap.php';
require_once __DIR__ . '/backend/check_login.php';
require_once __DIR__ . '/backend/prompt.php';
require_once __DIR__ . '/backend/api.php';
require_once __DIR__ . '/backend/storage.php';

if ($role === 'guest' || !isset($_SESSION['USER'])) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

define('REPLICATE_MODEL', 'sdxl-based/realvisxl-v3-multi-controlnet-lora:90a4a3604cd637cb9f1a2bdae1cfa9ed869362ca028814cdce310a78e27daade');


// ── Phase 4: POST now only starts the prediction; polling is done by JS ───────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    // Reject requests with a missing or wrong CSRF token
    csrf_verify_header();

    try {
        $user_id = $_SESSION['USER']['user_id'];

        // Rate-limit: max 40 generations per user per day (admins are exempt)
        if ($role !== 'admin') {
            $stmt = $dbh->prepare("SELECT COUNT(*) FROM image_generations WHERE user_id = ? AND generated_at::date = CURRENT_DATE");
            $stmt->execute([$user_id]);
            if ($stmt->fetchColumn() >= 40) {
                throw new Exception("Daily limit of 40 generations reached. Try again tomorrow.");
            }
        }

        // Server-side prompt validation -- the frontend already enforces a
        // 20-char minimum, but that's trivially bypassable by calling this
        // endpoint directly, so re-check here too. Cap the max length to
        // keep generation requests (and Replicate costs) bounded.
        $prompt = trim((string)($_POST['prompt'] ?? ''));
        if (mb_strlen($prompt) < 20) {
            throw new Exception("Scene description must be at least 20 characters.");
        }
        if (mb_strlen($prompt) > 1000) {
            throw new Exception("Scene description must be 1000 characters or fewer.");
        }

        $mime   = validate_image($_FILES['character_image']);

        resize_to_4x3($_FILES['character_image']['tmp_name'], $mime);

        // tmp_name is the temp path PHP gave the uploaded file, e.g. /tmp/phpXXXXXX
        $image_b64 = file_to_base64($_FILES['character_image']['tmp_name'], $mime);

        // ── Phase 5: read slider values from POST (clamped to valid range) ────
        $prompt_strength  = max(0.0, min(1.0, (float)($_POST['prompt_strength']  ?? 0.9)));
        $controlnet_scale = max(0.0, min(1.0, (float)($_POST['controlnet_scale'] ?? 0.2)));
        $lora_scale       = max(0.0, min(1.0, (float)($_POST['lora_scale']       ?? 0.9)));
        $colorful         = isset($_POST['colorful']) && $_POST['colorful'] === '1';
        $realistic        = isset($_POST['realistic']) && $_POST['realistic'] === '1';

        $params = build_params($prompt, $image_b64, $prompt_strength, $controlnet_scale, $lora_scale, $colorful, $realistic);

        // Start the Replicate prediction — return its ID immediately.
        // JS will poll /backend/poll_prediction.php every 3 s for status.
        $prediction = post_json('https://api.replicate.com/v1/predictions', $params, $REPLICATE_API_KEY);

        if (empty($prediction['id'])) {
            throw new Exception("Replicate did not return a prediction ID.");
        }

        echo json_encode(['success' => true, 'prediction_id' => $prediction['id']]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    exit;
}


// ── Phase 6: load panels from DB if a project is being opened ─────────────────
$active_project_id = null;
$active_project_name = '';
$panels_by_shot = [];   // shot_number => panel row

// ── Phase 9: pagination — projects can grow past 8 panels, so we only
// load/render one "page" of 8 panels at a time instead of the whole project.
$PANELS_PER_PAGE   = 8;
$total_panel_count = 0;
$total_pages       = 1;
$current_page      = 1;
$page_offset       = 0;

if (isset($_GET['project_id'])) {
    $pid = (int)$_GET['project_id'];
    $user_id = $_SESSION['USER']['user_id'];

    // IDOR check: project must belong to this user
    $stmt = $dbh->prepare("SELECT project_id, name FROM projects WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$pid, $user_id]);
    $project_row = $stmt->fetch();

    if ($project_row) {
        $active_project_id   = $pid;
        $active_project_name = $project_row->name;

        // Count every panel in the project so we know how many pages exist
        $stmt = $dbh->prepare("SELECT COUNT(*) FROM storyboard_panels WHERE project_id = ?");
        $stmt->execute([$pid]);
        $total_panel_count = (int)$stmt->fetchColumn();

        // Always offer one page past the last full page, so there's always
        // room to add new panels without manually creating a new page first.
        $total_pages    = intdiv($total_panel_count, $PANELS_PER_PAGE) + 1;
        $requested_page = (int)($_GET['page'] ?? 1);

        // "+ Add Page" lets the user manually create one new page beyond
        // what's computed above (e.g. when the current page isn't full yet
        // but they want to start a new one anyway). It links to exactly
        // total_pages+1, so that's the only page beyond the computed max
        // we allow navigating to — anything further still gets clamped.
        if ($requested_page === $total_pages + 1) {
            $total_pages = $requested_page;
        }

        $current_page = max(1, min($total_pages, $requested_page));
        $page_offset  = ($current_page - 1) * $PANELS_PER_PAGE;

        // Only fetch the 8 panels belonging to the current page
        $stmt = $dbh->prepare(
            "SELECT panel_id, shot_number, prompt, image_url, notes
             FROM storyboard_panels
             WHERE project_id = ? AND shot_number > ? AND shot_number <= ?
             ORDER BY shot_number"
        );
        $stmt->execute([$pid, $page_offset, $page_offset + $PANELS_PER_PAGE]);
        foreach ($stmt->fetchAll() as $p) {
            // Keyed by page-relative slot (1..8), not absolute shot_number,
            // since the render loop below looks panels up by slot index $i.
            // (Bug: this used to key by absolute shot_number, so any page
            // past page 1 never showed its saved panels.)
            $panels_by_shot[(int)$p->shot_number - $page_offset] = $p;
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>
<section class="main-section">

    <div class="card" id="main-card">
        <!-- multipart/form-data encodes the form as MIME parts so the file upload works -->
        <form id="storyboard-form" enctype="multipart/form-data">

            <div class="field">
                <label for="prompt"><?= htmlspecialchars(t('storyboard.scene_description_label')) ?></label>
                <textarea
                    id="prompt"
                    name="prompt"
                    placeholder="<?= htmlspecialchars(t('storyboard.scene_description_placeholder')) ?>"
                    required></textarea>
            </div>

            <div class="field">
                <label for="character-image"><?= htmlspecialchars(t('storyboard.scene_image_label')) ?></label>
                <div class="upload-zone" id="upload-zone">
                    <input
                        type="file"
                        name="character_image"
                        id="character-image"
                        accept="image/jpeg, image/png, image/webp, image/heic, image/heif"
                        required>
                    <div class="upload-text"><?= htmlspecialchars(t('storyboard.upload_hint')) ?></div>
                </div>
                <img id="image-preview" alt="Uploaded reference">
            </div>

            <button type="submit" id="submit-btn"><?= htmlspecialchars(t('storyboard.generate_btn')) ?></button>

            <!-- ── Phase 5: advanced generation controls ── -->
            <details class="advanced-options" id="advanced-options">
                <summary><?= htmlspecialchars(t('storyboard.advanced_options')) ?></summary>
                <div class="sliders-inner">

                    <div class="slider-row">
                        <label for="slider-prompt-strength"><?= htmlspecialchars(t('storyboard.slider_destruction')) ?></label>
                        <input type="range"  id="slider-prompt-strength" name="prompt_strength"  min="0" max="1" step="0.01" value="0.9">
                        <input type="number" id="num-prompt-strength"                             min="0" max="1" step="0.01" value="0.9">
                    </div>

                    <div class="slider-row">
                        <label for="slider-controlnet-scale"><?= htmlspecialchars(t('storyboard.slider_structure')) ?></label>
                        <!-- One slider controls both controlnet_1 and controlnet_2 at once -->
                        <input type="range"  id="slider-controlnet-scale" name="controlnet_scale"  min="0" max="1" step="0.01" value="0.2">
                        <input type="number" id="num-controlnet-scale"                              min="0" max="1" step="0.01" value="0.2">
                    </div>

                    <div class="slider-row">
                        <label for="slider-lora-scale"><?= htmlspecialchars(t('storyboard.slider_style')) ?></label>
                        <input type="range"  id="slider-lora-scale" name="lora_scale"  min="0" max="1" step="0.01" value="0.9">
                        <input type="number" id="num-lora-scale"                        min="0" max="1" step="0.01" value="0.9">
                    </div>

                    <div class="slider-row checkbox-row">
                        <label for="checkbox-colorful"><?= htmlspecialchars(t('storyboard.colorful_label')) ?></label>
                        <input type="checkbox" id="checkbox-colorful" name="colorful" value="1">
                    </div>

                    <div class="slider-row checkbox-row">
                        <label for="checkbox-realistic"><?= htmlspecialchars(t('storyboard.realistic_label')) ?></label>
                        <input type="checkbox" id="checkbox-realistic" name="realistic" value="1">
                    </div>

                    <p class="slider-hint"><?= htmlspecialchars(t('storyboard.slider_hint')) ?></p>
                </div>
            </details>

        </form>

        <!-- ── Phase 4: real progress bar ── -->
        <div id="progress-wrap" style="display:none">
            <div class="progress-track">
                <div id="progress-fill"></div>
            </div>
            <p id="progress-label"></p>
        </div>

        <div id="status"></div>

        <div id="output">
            <h2><?= htmlspecialchars(t('storyboard.generated_panel')) ?></h2>
            <img id="result-img" alt="Generated storyboard panel">
        </div>

    </div>


    <div class="storyboard-section">

        <?php if ($active_project_id): ?>
        <p style="display:flex; align-items:center; justify-content:space-between; gap:16px; font-size:0.8rem; color:var(--muted); margin-bottom:12px;">
            <span><?= htmlspecialchars(t('storyboard.project_label')) ?> <strong><?= htmlspecialchars($active_project_name) ?></strong></span>
            <a class="page-nav-btn" href="<?= BASE_URL ?>/projects"><?= htmlspecialchars(t('storyboard.back_to_projects')) ?></a>
        </p>
        <?php endif; ?>

        <?php
        // ── Phase 9: pagination nav — shown whenever a project is open, so
        // the "+ Add Page" control is always reachable. Prev/page-indicator/
        // Next only render once there's more than one page. Reused above and
        // below the panel list.
        if ($active_project_id):
            $prev_url     = BASE_URL . '/?project_id=' . $active_project_id . '&page=' . ($current_page - 1);
            $next_url     = BASE_URL . '/?project_id=' . $active_project_id . '&page=' . ($current_page + 1);
            $add_page_url = BASE_URL . '/?project_id=' . $active_project_id . '&page=' . ($total_pages + 1);
            ob_start();
        ?>
        <div class="storyboard-pagination">
            <?php if ($total_pages > 1): ?>
                <?php if ($current_page > 1): ?>
                    <a class="page-nav-btn" href="<?= htmlspecialchars($prev_url) ?>"><?= htmlspecialchars(t('storyboard.prev')) ?></a>
                <?php else: ?>
                    <span class="page-nav-btn disabled"><?= htmlspecialchars(t('storyboard.prev')) ?></span>
                <?php endif; ?>

                <span class="page-indicator"><?= htmlspecialchars(t('storyboard.page_of', ['current' => $current_page, 'total' => $total_pages])) ?></span>

                <?php if ($current_page < $total_pages): ?>
                    <a class="page-nav-btn" href="<?= htmlspecialchars($next_url) ?>"><?= htmlspecialchars(t('storyboard.next')) ?></a>
                <?php else: ?>
                    <span class="page-nav-btn disabled"><?= htmlspecialchars(t('storyboard.next')) ?></span>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Lets the user start a new page on demand, even if the current
                 page isn't full yet, instead of only auto-unlocking after 8
                 panels are saved. -->
            <a class="page-nav-btn" href="<?= htmlspecialchars($add_page_url) ?>"><?= htmlspecialchars(t('storyboard.add_page')) ?></a>
        </div>
        <?php
            $pagination_nav_html = ob_get_clean();
            echo $pagination_nav_html;
            echo '<p class="pagination-save-hint">' . htmlspecialchars(t('storyboard.save_hint')) . '</p>';
        endif;
        ?>

        <div class="storyboard-header">
            <div class="storyboard-team"><?= htmlspecialchars(t('storyboard.team_name')) ?><span contenteditable="true"></span></div>
            <div class="storyboard-meta">
                <div><?= htmlspecialchars(t('storyboard.product')) ?>: <span contenteditable="true"></span></div>
                <div><?= htmlspecialchars(t('storyboard.title')) ?>: <span contenteditable="true"></span></div>
                <div><?= htmlspecialchars(t('storyboard.sheet')) ?>: <span contenteditable="true"></span></div>
                <div><?= htmlspecialchars(t('storyboard.sequence')) ?>: <span contenteditable="true"></span></div>
            </div>
        </div>

        <div class="storyboard">
            <?php for ($i = 1; $i <= 8; $i++):
                // Page-relative slot $i maps to absolute shot_number $page_offset + $i,
                // so page 2 slot 1 is shot 9, page 3 slot 1 is shot 17, etc.
                $shot_number = $page_offset + $i;
                $p        = $panels_by_shot[$i] ?? null;
                $db_url   = $p ? ($p->image_url ?? '') : '';
                // For display we need the full URL; relative paths get BASE_URL prepended
                $disp_url = ($db_url && str_starts_with($db_url, '/storage/'))
                            ? BASE_URL . $db_url
                            : $db_url;  // external / empty
                $prompt_val = htmlspecialchars($p->prompt ?? '');
                $notes_val  = htmlspecialchars($p->notes  ?? '');
                $panel_id   = $p->panel_id ?? null;
            ?>
            <div class="panel" id="panel<?= $i ?>"
                 data-panel-id="<?= $panel_id ?? '' ?>">

                <div class="panel-image-wrap">
                    <!-- crossOrigin="anonymous" stops the canvas from being tainted by same-origin images -->
                    <img
                        alt=""
                        class="panel-image"
                        id="panel-image<?= $i ?>"
                        crossOrigin="anonymous"
                        <?= $disp_url ? 'src="' . htmlspecialchars($disp_url) . '"' : '' ?>
                        data-db-url="<?= htmlspecialchars($db_url) ?>">

                    <!-- Drag handle: press and drag to swap this panel's position with
                         whichever panel you drag over. Revealed on hover/tap, same as
                         the Insert/Cut/Paste toolbar below. -->
                    <div class="drag-handle" id="drag-handle<?= $i ?>"
                         title="<?= htmlspecialchars(t('storyboard.drag_handle')) ?>"
                         aria-label="<?= htmlspecialchars(t('storyboard.drag_handle')) ?>">⠿</div>

                    <!-- Insert/Cut/Paste overlay the image, revealed on hover (desktop) or
                         tap (mobile, via JS toggling .show-actions on .panel) -->
                    <div class="panel-toolbar">
                        <button type="button" class="pick-btn" id="pick-btn<?= $i ?>"><?= htmlspecialchars(t('storyboard.insert_image')) ?></button>
                        <button type="button" class="cut-btn"   id="cut-btn<?= $i ?>">✂️ <?= htmlspecialchars(t('storyboard.cut_panel')) ?></button>
                        <button type="button" class="paste-btn" id="paste-btn<?= $i ?>">📋 <?= htmlspecialchars(t('storyboard.paste_panel')) ?></button>
                    </div>
                </div>

                <div class="panel-body">
                    <div class="panel-shot-label"><?= htmlspecialchars(t('storyboard.shot')) ?> <?= $shot_number ?></div>

                    <div class="panel-text" id="panel-text<?= $i ?>">
                        <span contenteditable="true" aria-label="<?= htmlspecialchars(t('storyboard.panel_description_label')) ?>"><?= $prompt_val ?></span>
                    </div>

                    <textarea
                        class="panel-notes"
                        placeholder="<?= htmlspecialchars(t('storyboard.notes_placeholder')) ?>"
                        aria-label="<?= htmlspecialchars(t('storyboard.panel_notes_label')) ?>"><?= $notes_val ?></textarea>
                </div>

            </div>
            <?php endfor; ?>
        </div>

        <?php if (!empty($pagination_nav_html)) echo $pagination_nav_html; ?>

        <div class="buttons-card">
            <button type="button" id="export-png-btn"><?= htmlspecialchars(t('storyboard.export_png')) ?></button>
            <button type="button" id="export-pdf-btn"><?= htmlspecialchars(t('storyboard.export_pdf')) ?></button>
            <button type="button" id="save-btn"><?= htmlspecialchars(t('storyboard.save_panel')) ?></button>
        </div>

    </div>
</section>


<!-- html2canvas for PNG export -->
<script src="<?= BASE_URL ?>/scripts/node_modules/html2canvas/dist/html2canvas.min.js" defer></script>

<!-- jsPDF for PDF export (Phase 7) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" defer></script>

<!-- heic2any for client-side HEIC to PNG conversion (Phase 8) -->
<script src="https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js" defer></script>

<script>
    // constants
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const BASE_URL  = '<?= BASE_URL ?>';
    // project_id or null -- JS uses this when saving panels
    const PROJECT_ID = <?= $active_project_id ? $active_project_id : 'null' ?>;
    // Phase 9: pagination -- absolute shot_number = PAGE_OFFSET + (slot index + 1)
    const PAGE_OFFSET  = <?= (int)$page_offset ?>;
    const CURRENT_PAGE = <?= (int)$current_page ?>;
    const TOTAL_PAGES  = <?= (int)$total_pages ?>;

    // Translated strings for client-side dynamic text (button states, alerts).
    const i18n = {
        generateBtn:        <?= json_encode(t('storyboard.generate_btn')) ?>,
        generateBtnStarting:   <?= json_encode(t('storyboard.generate_btn_starting')) ?>,
        generateBtnGenerating: <?= json_encode(t('storyboard.generate_btn_generating')) ?>,
        exportPng:          <?= json_encode(t('storyboard.export_png')) ?>,
        backToEdit:         <?= json_encode(t('storyboard.back_to_edit')) ?>,
        exportPdf:          <?= json_encode(t('storyboard.export_pdf')) ?>,
        exporting:          <?= json_encode(t('storyboard.exporting')) ?>,
        insertImage:        <?= json_encode(t('storyboard.insert_image')) ?>,
        imageInserted:      <?= json_encode(t('storyboard.image_inserted')) ?>,
        uploadHint:         <?= json_encode(t('storyboard.upload_hint')) ?>,
        convertingHeic:     <?= json_encode(t('storyboard.converting_heic')) ?>,
        noProjectAlert:     <?= json_encode(t('storyboard.no_project_alert')) ?>,
        newProjectNamePrompt: <?= json_encode(t('storyboard.new_project_name_prompt')) ?>,
        noPanelsAlert:      <?= json_encode(t('storyboard.no_panels_alert')) ?>,
        networkError:       <?= json_encode(t('storyboard.network_error')) ?>,
        networkErrorPrefix: <?= json_encode(t('storyboard.network_error_prefix')) ?>,
        pdfLibLoading:      <?= json_encode(t('storyboard.pdf_lib_loading')) ?>,
        shortPromptAlert:   <?= json_encode(t('storyboard.short_prompt_alert')) ?>,
        noImageAlert:       <?= json_encode(t('storyboard.no_image_alert')) ?>,
        generationSuccess:  <?= json_encode(t('storyboard.generation_success')) ?>,
        generationFailed:   <?= json_encode(t('storyboard.generation_failed')) ?>,
        generationTimeout:  <?= json_encode(t('storyboard.generation_timeout')) ?>,
        exportPanelsError:  <?= json_encode(t('storyboard.export_panels_error')) ?>,
        saveFailed:         <?= json_encode(t('storyboard.save_failed')) ?>,
        saveSuccess:        <?= json_encode(t('storyboard.save_success')) ?>,
        shotLabel:          <?= json_encode(t('storyboard.shot')) ?>,
        cutSuccess:         <?= json_encode(t('storyboard.cut_success')) ?>,
        pasteEmpty:         <?= json_encode(t('storyboard.paste_empty')) ?>,
        pasteSuccess:       <?= json_encode(t('storyboard.paste_success')) ?>,
        autosaveSuccess:    <?= json_encode(t('storyboard.autosave_success')) ?>
    };

    // element refs
    const fileInput         = document.getElementById('character-image')
    const preview           = document.getElementById('image-preview')
    const uploadZone        = document.getElementById('upload-zone')
    const storyboardForm    = document.getElementById('storyboard-form')
    const submitBtn         = document.getElementById('submit-btn')
    const statusEl          = document.getElementById('status')
    const outputEl          = document.getElementById('output')
    const resultImg         = document.getElementById('result-img')
    const progressWrap      = document.getElementById('progress-wrap')
    const progressFill      = document.getElementById('progress-fill')
    const progressLabel     = document.getElementById('progress-label')
    const storyboard        = document.querySelector('.storyboard')
    const storyboardSection = document.querySelector('.storyboard-section')
    const mainCard          = document.getElementById('main-card')

    // Phase 5: sync slider <-> number inputs
    ;[
        ['slider-prompt-strength',  'num-prompt-strength'],
        ['slider-controlnet-scale', 'num-controlnet-scale'],
        ['slider-lora-scale',       'num-lora-scale'],
    ].forEach(([sliderId, numId]) => {
        const slider = document.getElementById(sliderId)
        const num    = document.getElementById(numId)
        if (!slider || !num) return
        slider.addEventListener('input', () => { num.value = slider.value })
        num.addEventListener('input',   () => {
            const v = Math.max(0, Math.min(1, parseFloat(num.value) || 0))
            slider.value = v
            num.value    = v
        })
    })

    // Phase 8: HEIC conversion
    fileInput.addEventListener('change', async function () {
        let file = this.files[0]
        if (!file) return

        // Convert HEIC/HEIF (iPhone photos) to PNG before uploading
        const isHeic = file.type === 'image/heic' || file.type === 'image/heif'
                    || file.name.toLowerCase().endsWith('.heic')
                    || file.name.toLowerCase().endsWith('.heif')

        if (isHeic && typeof heic2any !== 'undefined') {
            try {
                uploadZone.querySelector('.upload-text').textContent = i18n.convertingHeic
                const pngBlob = await heic2any({ blob: file, toType: 'image/png' })
                file = new File([pngBlob], file.name.replace(/\.heic$/i, '.png'), { type: 'image/png' })
                // Replace the FileList in the input
                const dt = new DataTransfer()
                dt.items.add(file)
                fileInput.files = dt.files
                uploadZone.querySelector('.upload-text').textContent = i18n.uploadHint
            } catch (e) {
                console.error('HEIC conversion failed:', e)
            }
        }

        // Warm up the model when user picks their image
        fetch(BASE_URL + '/warmup.php')

        const reader = new FileReader()
        reader.onload = e => {
            preview.src = e.target.result
            preview.style.display = 'block'
        }
        reader.readAsDataURL(file)
    })

    // Phase 8: dragover on upload zone + whole card
    uploadZone.addEventListener('dragover',  () => { uploadZone.classList.add('dragging');    mainCard.classList.add('drag-active') })
    uploadZone.addEventListener('dragleave', () => { uploadZone.classList.remove('dragging'); mainCard.classList.remove('drag-active') })
    uploadZone.addEventListener('drop',      () => { uploadZone.classList.remove('dragging'); mainCard.classList.remove('drag-active') })

    // Also activate drag-active when dragging over the textarea
    document.getElementById('prompt').addEventListener('dragover',  () => mainCard.classList.add('drag-active'))
    document.getElementById('prompt').addEventListener('dragleave', () => mainCard.classList.remove('drag-active'))

    // helpers
    let statusHideTimer = null
    function showStatus(type, html) {
        clearTimeout(statusHideTimer)
        statusEl.className     = type
        statusEl.style.display = 'block'
        statusEl.innerHTML     = html
        // Success confirmations ("Project saved.", "Panel generated
        // successfully!") are transient -- auto-dismiss them so they don't
        // linger forever. Errors/loading stay until something replaces them.
        if (type === 'info') {
            statusHideTimer = setTimeout(hideStatus, 4000)
        }
    }
    function hideStatus() { statusEl.style.display = 'none' }

    function showProgress(pct, label) {
        progressWrap.style.display = 'block'
        progressFill.style.width   = pct + '%'
        progressLabel.textContent  = label
    }
    function hideProgress() { progressWrap.style.display = 'none' }

    function resetGenerateBtn() {
        submitBtn.disabled    = false
        submitBtn.textContent = i18n.generateBtn
    }

    // Wire up "Insert Image" buttons after a successful generation
    function activatePickButtons(imageUrl) {
        document.querySelectorAll('.panel').forEach(panel => {
            const pickBtn    = panel.querySelector('.pick-btn')
            const img        = panel.querySelector('.panel-image')
            const promptSpan = panel.querySelector('.panel-text span')
            pickBtn.style.display = 'block'
            // Clone the button to clear any previous click listeners
            const fresh = pickBtn.cloneNode(true)
            pickBtn.replaceWith(fresh)
            fresh.addEventListener('click', () => {
                fresh.textContent    = i18n.imageInserted
                img.src              = BASE_URL + imageUrl
                img.dataset.dbUrl    = imageUrl
                promptSpan.innerText = document.getElementById('prompt').value.trim()
                autosavePanels()
            })
        })
    }

    // Mobile/touch support: hovering doesn't exist on touch devices, so tapping
    // a panel toggles a .show-actions class that the CSS also reveals the
    // toolbar for. Tapping a button/field/text inside the panel must NOT
    // toggle it back off (and clicking the buttons themselves should not
    // bubble up to the panel-level tap handler and immediately re-hide it).
    document.querySelectorAll('.panel').forEach(panel => {
        panel.addEventListener('click', (e) => {
            if (e.target.closest('.panel-toolbar, .panel-text, .panel-notes')) return
            panel.classList.toggle('show-actions')
        })
    })

    // Cut/Paste: move both image and text/notes content between panels,
    // including across different pages of the same project (full-page-reload
    // pagination means only persistent storage can carry the clipboard across
    // a page load, hence localStorage rather than an in-memory variable).
    // "True cut" semantics: cutting immediately clears the source panel; the
    // clipboard is single-use, cleared again once pasted.
    const CLIPBOARD_KEY = 'inksync_clipboard'

    function wireCutPasteButtons() {
        document.querySelectorAll('.panel').forEach(panel => {
            const cutBtn   = panel.querySelector('.cut-btn')
            const pasteBtn = panel.querySelector('.paste-btn')
            const img      = panel.querySelector('.panel-image')
            const textSpan = panel.querySelector('.panel-text span')
            const notes    = panel.querySelector('.panel-notes')
            if (!cutBtn || !pasteBtn) return

            cutBtn.addEventListener('click', () => {
                const clip = {
                    imageUrl: img.dataset.dbUrl || null,
                    imageSrc: img.src || '',
                    text:     textSpan ? textSpan.innerText : '',
                    notes:    notes ? notes.value : ''
                }
                localStorage.setItem(CLIPBOARD_KEY, JSON.stringify(clip))

                // Clear the source panel
                img.removeAttribute('src')
                img.dataset.dbUrl = ''
                if (textSpan) textSpan.innerText = ''
                if (notes) notes.value = ''

                showStatus('info', i18n.cutSuccess)
            })

            pasteBtn.addEventListener('click', () => {
                const raw = localStorage.getItem(CLIPBOARD_KEY)
                if (!raw) {
                    showStatus('error', i18n.pasteEmpty)
                    return
                }
                const clip = JSON.parse(raw)

                if (clip.imageSrc) {
                    img.src = clip.imageSrc
                } else {
                    img.removeAttribute('src')
                }
                img.dataset.dbUrl = clip.imageUrl || ''
                if (textSpan) textSpan.innerText = clip.text || ''
                if (notes) notes.value = clip.notes || ''

                // Single-use "move" semantics: clear the clipboard once pasted
                localStorage.removeItem(CLIPBOARD_KEY)

                showStatus('info', i18n.pasteSuccess)
                if (clip.imageUrl) autosavePanels()
            })
        })
    }
    wireCutPasteButtons()

    // Drag-to-reorder: press and drag a panel by its handle to swap it with
    // whichever panel you drag over -- the panel you cross into jumps into
    // the dragged panel's old spot, all other panels adjust automatically
    // since the storyboard is a CSS grid and position follows DOM order.
    // Pointer Events unify mouse, touch, and pen so the same code drives
    // dragging on desktop and mobile.
    function wireDragHandles() {
        document.querySelectorAll('.panel').forEach(panel => {
            const handle = panel.querySelector('.drag-handle')
            if (!handle) return

            let placeholder = null
            let offsetX = 0, offsetY = 0

            handle.addEventListener('pointerdown', (e) => {
                e.preventDefault()
                const rect = panel.getBoundingClientRect()
                offsetX = e.clientX - rect.left
                offsetY = e.clientY - rect.top

                // Leave a same-sized placeholder in the panel's original grid
                // slot so the grid doesn't collapse while it's lifted out.
                placeholder = document.createElement('div')
                placeholder.className = 'panel-placeholder'
                placeholder.style.width  = rect.width + 'px'
                placeholder.style.height = rect.height + 'px'
                panel.parentNode.insertBefore(placeholder, panel)

                // Lift the panel out of the grid and follow the pointer.
                document.body.appendChild(panel)
                panel.classList.add('dragging')
                panel.style.position      = 'fixed'
                panel.style.width         = rect.width + 'px'
                panel.style.left          = rect.left + 'px'
                panel.style.top           = rect.top + 'px'
                panel.style.pointerEvents = 'none' // let elementFromPoint see what's underneath

                handle.setPointerCapture(e.pointerId)
            })

            handle.addEventListener('pointermove', (e) => {
                if (!placeholder) return
                panel.style.left = (e.clientX - offsetX) + 'px'
                panel.style.top  = (e.clientY - offsetY) + 'px'

                // Find whichever panel the pointer is currently over by directly
                // testing bounding rects, rather than document.elementFromPoint --
                // the dragged panel (or its own overlaid toolbar/handle) can still
                // be the topmost hit-test result in some browsers even with
                // pointer-events:none, which silently broke the swap entirely.
                let target = null
                for (const sib of storyboard.querySelectorAll('.panel')) {
                    const r = sib.getBoundingClientRect()
                    if (e.clientX >= r.left && e.clientX <= r.right &&
                        e.clientY >= r.top  && e.clientY <= r.bottom) {
                        target = sib
                        break
                    }
                }
                if (target) {
                    const parent = target.parentNode
                    // Swap the placeholder with whichever panel is now under the
                    // pointer -- that panel jumps into the dragged panel's old spot.
                    if (placeholder.nextSibling === target) {
                        parent.insertBefore(placeholder, target.nextSibling)
                    } else {
                        parent.insertBefore(placeholder, target)
                    }
                }
            })

            function endDrag(e) {
                if (!placeholder) return
                placeholder.parentNode.insertBefore(panel, placeholder)
                placeholder.remove()
                placeholder = null

                panel.classList.remove('dragging')
                panel.style.position      = ''
                panel.style.width         = ''
                panel.style.left          = ''
                panel.style.top           = ''
                panel.style.pointerEvents = ''

                try { handle.releasePointerCapture(e.pointerId) } catch (err) { /* already released */ }

                // Persist the new panel order immediately, same as inserting an image.
                autosavePanels()
            }

            handle.addEventListener('pointerup', endDrag)
            handle.addEventListener('pointercancel', endDrag)
        })
    }
    wireDragHandles()

    // Phase 4: polling
    function startPolling(predictionId) {
        showProgress(15, 'Queued...')
        let done = false

        const interval = setInterval(async () => {
            if (done) return
            try {
                const r = await fetch(BASE_URL + '/backend/poll_prediction.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ prediction_id: predictionId })
                })
                const data = await r.json()

                if (data.status === 'starting') {
                    showProgress(30, 'Model warming up...')
                } else if (data.status === 'processing') {
                    showProgress(65, 'Generating panel...')
                } else if (data.status === 'succeeded') {
                    done = true
                    clearInterval(interval)
                    showProgress(100, 'Done!')
                    setTimeout(hideProgress, 1200)
                    showStatus('info', i18n.generationSuccess)
                    resultImg.src = BASE_URL + data.image_url
                    outputEl.style.display = 'block'
                    activatePickButtons(data.image_url)
                    resetGenerateBtn()
                } else if (data.status === 'failed' || data.status === 'canceled') {
                    done = true
                    clearInterval(interval)
                    hideProgress()
                    showStatus('error', (data.error || i18n.generationFailed))
                    resetGenerateBtn()
                }
            } catch (err) { /* network hiccup -- keep polling */ }
        }, 3000)

        // Safety timeout after 3 min
        setTimeout(() => {
            if (!done) {
                done = true
                clearInterval(interval)
                hideProgress()
                showStatus('error', i18n.generationTimeout)
                resetGenerateBtn()
            }
        }, 3 * 60 * 1000)
    }

    // Generation form submit
    storyboardForm.addEventListener('submit', function (e) {
        e.preventDefault()
        const promptText = document.getElementById('prompt').value.trim()
        if (promptText.length < 20) { showStatus('error', i18n.shortPromptAlert); return }
        if (!fileInput.files[0])    { showStatus('error', i18n.noImageAlert); return }

        submitBtn.disabled     = true
        submitBtn.textContent  = i18n.generateBtnStarting
        outputEl.style.display = 'none'
        hideStatus()
        showProgress(5, 'Sending to Replicate...')

        fetch(BASE_URL + '/index.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken },
            body: new FormData(storyboardForm)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                submitBtn.textContent = i18n.generateBtnGenerating
                startPolling(data.prediction_id)
            } else {
                hideProgress()
                showStatus('error', data.error)
                resetGenerateBtn()
            }
        })
        .catch(err => {
            hideProgress()
            showStatus('error', i18n.networkErrorPrefix + err.message)
            resetGenerateBtn()
        })
    })

    // PNG export
    const exportPngBtn = document.getElementById('export-png-btn')
    let exportReady = false
    exportPngBtn.addEventListener('click', () => {
        if (exportReady) {
            exportPngBtn.innerText = i18n.exportPng
            document.querySelectorAll('.pick-btn').forEach(b => b.style.display = 'block')
            exportReady = false
        } else {
            document.querySelectorAll('.pick-btn').forEach(b => b.style.display = 'none')
            exportReady = true
            exportPngBtn.style.display = 'none'
            html2canvas(storyboardSection, {
                allowTaint: true,
                useCORS: true,
                // Only capture the header table + panel grid -- skip the
                // "Project: ..." line, pagination nav, save hint, and the
                // export/save buttons that also live inside .storyboard-section.
                onclone: clonedDoc => {
                    const section = clonedDoc.querySelector('.storyboard-section')
                    Array.from(section.children).forEach(child => {
                        if (!child.classList.contains('storyboard-header') && !child.classList.contains('storyboard')) {
                            child.remove()
                        }
                    })
                }
            })
                .then(canvas => {
                    const a = document.createElement('a')
                    a.href     = canvas.toDataURL('image/png', 0.5)
                    a.download = 'storyboard_panel.png'
                    a.click()
                    exportPngBtn.style.display = 'block'
                    exportPngBtn.innerText = i18n.backToEdit
                })
                .catch(e => console.error(e))
        }
    })

    // Escapes text before it's inserted via innerHTML/attribute strings below.
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
    }

    // Phase 7/9/10: PDF export
    // Renders each page of panels as a screenshot of the *real*
    // .storyboard-header + .storyboard markup (via html2canvas) -- exactly
    // like PNG export -- instead of jsPDF's own hand-drawn grid, which had
    // drifted from the actual on-screen layout. In project mode every page
    // of the project becomes its own PDF page, built from a hidden
    // off-screen clone so we don't have to navigate away from the page
    // the user is currently viewing.
    document.getElementById('export-pdf-btn').addEventListener('click', async () => {
        if (typeof window.jspdf === 'undefined' || typeof html2canvas === 'undefined') {
            alert(i18n.pdfLibLoading)
            return
        }

        const exportPdfBtn = document.getElementById('export-pdf-btn')
        exportPdfBtn.disabled    = true
        exportPdfBtn.textContent = i18n.exporting

        try {
            const { jsPDF } = window.jspdf
            const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })
            const PAGE_W = 297, PAGE_H = 210 // landscape A4, mm

            // Builds a hidden off-screen copy of .storyboard-header + .storyboard
            // for one chunk of up to 8 panels, screenshots it, and adds the
            // screenshot as one full PDF page (scaled to fit, aspect preserved).
            async function addPanelsPageToPdf(panelsChunk, isFirstPage) {
                const liveHeader = storyboardSection.querySelector('.storyboard-header')

                const wrapper = document.createElement('div')
                wrapper.style.position   = 'fixed'
                wrapper.style.left       = '-99999px'
                wrapper.style.top        = '0'
                wrapper.style.width      = storyboard.offsetWidth + 'px'
                wrapper.style.background = '#fff'

                const headerClone = liveHeader.cloneNode(true)
                // Drop contenteditable so html2canvas just renders the current text
                headerClone.querySelectorAll('[contenteditable]').forEach(el => el.removeAttribute('contenteditable'))

                const grid = document.createElement('div')
                grid.className = 'storyboard'
                grid.innerHTML = panelsChunk.map(p => `
                    <div class="panel">
                        <img class="panel-image" crossorigin="anonymous" ${p.imgSrc ? `src="${escapeHtml(p.imgSrc)}"` : ''}>
                        <div class="panel-body">
                            <div class="panel-shot-label">${escapeHtml(i18n.shotLabel)} ${p.shot_number}</div>
                            <div class="panel-text"><span>${escapeHtml(p.prompt)}</span></div>
                            <textarea class="panel-notes" readonly>${escapeHtml(p.notes)}</textarea>
                        </div>
                    </div>
                `).join('')

                wrapper.appendChild(headerClone)
                wrapper.appendChild(grid)
                document.body.appendChild(wrapper)

                // Wait for this chunk's images to finish loading before the screenshot
                const imgs = Array.from(wrapper.querySelectorAll('img'))
                await Promise.all(imgs.map(img => img.complete
                    ? Promise.resolve()
                    : new Promise(res => { img.onload = img.onerror = res })))

                try {
                    const canvas = await html2canvas(wrapper, { allowTaint: true, useCORS: true })
                    if (!isFirstPage) doc.addPage()
                    const imgData = canvas.toDataURL('image/jpeg', 0.85)
                    const ratio   = Math.min(PAGE_W / canvas.width, PAGE_H / canvas.height)
                    const w = canvas.width * ratio, h = canvas.height * ratio
                    doc.addImage(imgData, 'JPEG', (PAGE_W - w) / 2, (PAGE_H - h) / 2, w, h)
                } finally {
                    wrapper.remove()
                }
            }

            let panelsData = []

            if (PROJECT_ID) {
                // Project mode: export every panel across every page of the project
                const r = await fetch(BASE_URL + '/backend/get_project_panels.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ project_id: PROJECT_ID })
                })
                const data = await r.json()
                if (!data.success) {
                    alert(i18n.exportPanelsError + (data.error || 'unknown error'))
                    return
                }
                panelsData = data.panels.map(p => ({
                    shot_number: p.shot_number,
                    imgSrc: p.image_url
                        ? (p.image_url.startsWith('/storage/') ? BASE_URL + p.image_url : p.image_url)
                        : null,
                    prompt: p.prompt || '',
                    notes:  p.notes  || ''
                }))
            } else {
                // No project loaded: export the fixed 8 on-screen panels as one sheet
                panelsData = Array.from(document.querySelectorAll('.panel')).map((panel, i) => {
                    const img = panel.querySelector('.panel-image')
                    return {
                        shot_number: i + 1,
                        imgSrc:      img.getAttribute('src') || null,
                        prompt:      panel.querySelector('.panel-text span').innerText.trim(),
                        notes:       panel.querySelector('.panel-notes').value.trim()
                    }
                })
            }

            if (panelsData.length === 0) {
                alert(i18n.noPanelsAlert)
                return
            }

            const PANELS_PER_PAGE = 8
            for (let i = 0; i < panelsData.length; i += PANELS_PER_PAGE) {
                await addPanelsPageToPdf(panelsData.slice(i, i + PANELS_PER_PAGE), i === 0)
            }

            doc.save('storyboard.pdf')
        } catch (err) {
            console.error(err)
            alert(i18n.networkError)
        } finally {
            exportPdfBtn.disabled    = false
            exportPdfBtn.textContent = i18n.exportPdf
        }
    })

    // Build the panels payload from the live DOM.
    function collectPanelsPayload() {
        const panels = []
        storyboard.querySelectorAll('.panel').forEach((panel, index) => {
            const img = panel.querySelector('.panel-image')
            panels.push({
                // Phase 9: add PAGE_OFFSET so saving page 2+ doesn't overwrite page 1's panels
                shot_number: PAGE_OFFSET + index + 1,
                image_url:   img.dataset.dbUrl || null,
                prompt:      panel.querySelector('.panel-text span').innerText.trim(),
                notes:       panel.querySelector('.panel-notes').value.trim()
            })
        })
        return panels
    }

    // POST the current panels to an existing project. Returns the parsed
    // JSON response. Used by both the manual Save button and autosave.
    async function savePanelsToServer(projectId) {
        const r = await fetch(BASE_URL + '/backend/save_panels.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ project_id: projectId, panels: collectPanelsPayload() })
        })
        return r.json()
    }

    // Autosave: fires a few hundred ms after an image is inserted/pasted into
    // a panel, so users who don't know to hit "Save Panel" before moving to
    // another page don't lose their work. Only runs once there's a real
    // saved project to autosave into -- a brand-new, never-saved storyboard
    // still requires one manual Save (which prompts for a project name).
    // Debounced so several quick inserts only trigger one request, and
    // failures stay silent (best-effort) since the manual Save button
    // remains the reliable fallback.
    let autosaveTimer = null
    function autosavePanels() {
        if (!PROJECT_ID) return
        clearTimeout(autosaveTimer)
        autosaveTimer = setTimeout(async () => {
            try {
                const data = await savePanelsToServer(PROJECT_ID)
                if (data.success) showStatus('info', i18n.autosaveSuccess)
            } catch (err) { /* best-effort -- ignore */ }
        }, 500)
    }

    // Save panels to DB. If there's no active project yet (a brand-new
    // storyboard started from "+ New Storyboard"), prompt for a project
    // name, create it, then save the panels into it and switch the page
    // over to that project so future saves/exports work normally.
    document.getElementById('save-btn').addEventListener('click', async () => {
        const saveBtn = document.getElementById('save-btn')
        let projectId = PROJECT_ID

        if (!projectId) {
            const name = (prompt(i18n.newProjectNamePrompt) || '').trim()
            if (!name) return // user cancelled -- don't save into nowhere

            saveBtn.disabled = true
            try {
                const r = await fetch(BASE_URL + '/backend/create_project.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ name })
                })
                const data = await r.json()
                if (!data.success) {
                    showStatus('error', data.error || i18n.saveFailed)
                    saveBtn.disabled = false
                    return
                }
                projectId = data.project_id
            } catch (err) {
                showStatus('error', i18n.networkErrorPrefix + err.message)
                saveBtn.disabled = false
                return
            }
        }

        saveBtn.disabled = true
        try {
            const data = await savePanelsToServer(projectId)
            if (data.success) {
                if (!PROJECT_ID) {
                    // Newly created project -- reload into project mode so
                    // PROJECT_ID, pagination, and the "Back to Projects" link
                    // all reflect the saved project from here on.
                    window.location.href = BASE_URL + '/?project_id=' + projectId
                    return
                }
                showStatus('info', i18n.saveSuccess)
            } else {
                showStatus('error', i18n.saveFailed + (data.error || ''))
            }
        } catch (err) {
            showStatus('error', i18n.networkErrorPrefix + err.message)
        } finally {
            saveBtn.disabled = false
        }
    })

</script>
</body>
</html>
