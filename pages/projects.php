<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/check_login.php';

if ($role === 'guest' || !isset($_SESSION['USER'])) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

$user_id = (int)$_SESSION['USER']['user_id'];

// Fetch all projects for this user with first panel thumbnail and count
$stmt = $dbh->prepare("
    SELECT
        p.project_id,
        p.name,
        p.hero_image_url,
        (SELECT image_url
         FROM storyboard_panels
         WHERE project_id = p.project_id
         ORDER BY shot_number ASC
         LIMIT 1
        ) AS auto_thumbnail,
        (SELECT COUNT(*) FROM storyboard_panels WHERE project_id = p.project_id) AS panel_count
    FROM projects p
    WHERE p.user_id = ?
    ORDER BY p.project_id DESC
");
$stmt->execute([$user_id]);
$projects = $stmt->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>

<section class="main-section">
    <div class="card" style="max-width:1000px; width:100%">
        <div class="field">
            <h2 style="font-family:var(--font-head); font-weight:normal;"><?= htmlspecialchars(t('projects.heading')) ?></h2>
        </div>

        <?php if (empty($projects)) : ?>
            <p style="color:var(--muted);"><?= htmlspecialchars(t('projects.empty')) ?> <a href="<?= BASE_URL ?>/"><?= htmlspecialchars(t('projects.start_generating')) ?></a></p>
        <?php else : ?>
        <div class="projects-grid">
            <?php foreach ($projects as $proj) :
                // A manually-uploaded hero image always wins over the auto-picked
                // first-panel thumbnail.
                $thumbnail = $proj->hero_image_url ?: $proj->auto_thumbnail;
                // Resolve thumbnail display URL (stored paths are relative)
                $thumb_url = '';
                if ($thumbnail) {
                    $thumb_url = str_starts_with($thumbnail, '/storage/')
                        ? BASE_URL . $thumbnail
                        : $thumbnail;
                }
                ?>
            <div class="project-card" id="card-<?= $proj->project_id ?>"
                 data-open-url="<?= BASE_URL ?>/?project_id=<?= $proj->project_id ?>"
                 onclick="openProjectCard(event, this)">

                <!-- Thumbnail. onerror covers the case where the DB still has a path
                     but the actual file was removed (e.g. by orphan cleanup) — without
                     this the browser would show a broken-image icon instead of a clean placeholder. -->
                <?php if ($thumb_url) : ?>
                    <img class="project-thumbnail" src="<?= htmlspecialchars($thumb_url) ?>" alt="Project thumbnail"
                         onerror="this.replaceWith(Object.assign(document.createElement('div'), {className:'project-thumb-placeholder', textContent:'No images yet'}))">
                <?php else : ?>
                    <div class="project-thumb-placeholder">No images yet</div>
                <?php endif; ?>

                <div class="project-info">
                    <!-- Name — toggled to an input during rename -->
                    <div class="project-name" id="name-<?= $proj->project_id ?>">
                        <?= htmlspecialchars($proj->name) ?>
                    </div>
                    <input
                        class="project-name-input"
                        id="rename-input-<?= $proj->project_id ?>"
                        type="text"
                        value="<?= htmlspecialchars($proj->name) ?>"
                        maxlength="50"
                        onclick="event.stopPropagation()"
                        style="display:none">

                    <div class="project-meta"><?= htmlspecialchars(t($proj->panel_count == 1 ? 'projects.panel_singular' : 'projects.panel_plural', ['n' => (int)$proj->panel_count])) ?></div>
                </div>

                <div class="project-actions">
                    <a class="btn-open" href="<?= BASE_URL ?>/?project_id=<?= $proj->project_id ?>" onclick="event.stopPropagation()"><?= htmlspecialchars(t('projects.open')) ?></a>
                    <button class="btn-rename" onclick="event.stopPropagation(); startRename(<?= $proj->project_id ?>)"><?= htmlspecialchars(t('projects.rename')) ?></button>
                    <button class="btn-rename" onclick="event.stopPropagation(); document.getElementById('hero-input-<?= $proj->project_id ?>').click()"><?= htmlspecialchars(t('projects.change_image')) ?></button>
                    <button class="btn-delete" onclick="event.stopPropagation(); deleteProject(<?= $proj->project_id ?>)"><?= htmlspecialchars(t('projects.delete')) ?></button>
                </div>

                <!-- Hidden file input for manually picking a hero image; triggered by the "Change Image" button above -->
                <input
                    type="file"
                    id="hero-input-<?= $proj->project_id ?>"
                    accept="image/png, image/jpeg, image/webp"
                    style="display:none"
                    onclick="event.stopPropagation()"
                    onchange="uploadHeroImage(event, <?= $proj->project_id ?>)">

            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

        <hr style="margin:2rem 0">
        <a href="<?= BASE_URL ?>/" class="btn-new-storyboard"><?= htmlspecialchars(t('projects.new_storyboard')) ?></a>
    </div>
</section>

<script>
    const BASE_URL  = '<?= BASE_URL ?>'
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content
    const i18n = {
        renameFailed: <?= json_encode(t('projects.rename_failed')) ?>,
        heroFailed:   <?= json_encode(t('projects.hero_failed')) ?>,
        deleteConfirm: <?= json_encode(t('projects.delete_confirm')) ?>,
        deleteFailed:  <?= json_encode(t('projects.delete_failed')) ?>,
        networkError: <?= json_encode(t('projects.network_error')) ?>
    }

    // Phase 9: clicking anywhere on a project card opens it. Buttons and the
    // rename input call event.stopPropagation() so they don't trigger this.
    function openProjectCard(event, card) {
        window.location.href = card.dataset.openUrl
    }

    // Toggle a project name into an editable input, commit on Enter or blur
    function startRename(projectId) {
        const nameEl  = document.getElementById('name-' + projectId)
        const inputEl = document.getElementById('rename-input-' + projectId)

        nameEl.style.display  = 'none'
        inputEl.style.display = 'block'
        inputEl.focus()
        inputEl.select()

        function commit() {
            const newName = inputEl.value.trim()
            if (!newName || newName === nameEl.textContent.trim()) {
                // No change — just toggle back
                inputEl.style.display = 'none'
                nameEl.style.display  = 'block'
                return
            }
            fetch(BASE_URL + '/backend/rename_project.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ project_id: projectId, name: newName })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    nameEl.textContent = newName
                } else {
                    alert(data.error || i18n.renameFailed)
                    inputEl.value = nameEl.textContent.trim()
                }
                inputEl.style.display = 'none'
                nameEl.style.display  = 'block'
            })
            .catch(() => {
                inputEl.style.display = 'none'
                nameEl.style.display  = 'block'
            })
        }

        inputEl.addEventListener('keydown', function handler(e) {
            if (e.key === 'Enter') { inputEl.removeEventListener('keydown', handler); commit() }
            if (e.key === 'Escape') { inputEl.style.display = 'none'; nameEl.style.display = 'block'; inputEl.removeEventListener('keydown', handler) }
        })
        inputEl.addEventListener('blur', function handler() {
            inputEl.removeEventListener('blur', handler)
            commit()
        }, { once: true })
    }

    // Manually override a project's hero image (thumbnail) with a user-picked file
    function uploadHeroImage(event, projectId) {
        const file = event.target.files[0]
        if (!file) return

        const formData = new FormData()
        formData.append('hero_image', file)
        formData.append('project_id', projectId)

        fetch(BASE_URL + '/backend/upload_hero_image.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert(data.error || i18n.heroFailed)
                return
            }
            const card = document.getElementById('card-' + projectId)
            let img = card.querySelector('.project-thumbnail')
            if (!img) {
                // First image for a project that previously showed the placeholder
                const placeholder = card.querySelector('.project-thumb-placeholder')
                img = document.createElement('img')
                img.className = 'project-thumbnail'
                img.alt = 'Project thumbnail'
                if (placeholder) placeholder.replaceWith(img)
            }
            // Cache-bust so the new image shows immediately even if the filename were ever reused
            img.src = BASE_URL + data.image_url + '?t=' + Date.now()
        })
        .catch(() => alert(i18n.networkError))
        .finally(() => { event.target.value = '' })
    }

    // Confirm and delete a project, then remove its card from the DOM
    function deleteProject(projectId) {
        if (!confirm(i18n.deleteConfirm)) return
        fetch(BASE_URL + '/backend/delete_project.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ project_id: projectId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const card = document.getElementById('card-' + projectId)
                if (card) card.remove()
            } else {
                alert(data.error || i18n.deleteFailed)
            }
        })
        .catch(() => alert(i18n.networkError))
    }
</script>
</body>
</html>
