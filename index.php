<?php
require_once __DIR__ . '/backend/bootstrap.php';
require_once __DIR__ . '/backend/check_login.php';
require_once __DIR__ . '/backend/prompt.php';
require_once __DIR__ . '/backend/api.php';

if ($role === 'guest' || !isset($_SESSION['USER'])) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

define('REPLICATE_MODEL', 'sdxl-based/realvisxl-v3-multi-controlnet-lora:90a4a3604cd637cb9f1a2bdae1cfa9ed869362ca028814cdce310a78e27daade');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $prompt      = $_POST['prompt'];
        $mime        = validate_image($_FILES['character_image']);

        resize_to_4x3($_FILES['character_image']['tmp_name'], $mime);

        // tmp_name is just a temporary name php gives to the uploaded file ('character_image') e.g /tmp/'tmp_name'.
        $image_b64   = file_to_base64($_FILES['character_image']['tmp_name'], $mime);

        // here the build_params function in prompt.php is being called
        $params = build_params($prompt, $image_b64);
        $prediction = post_json('https://api.replicate.com/v1/predictions', $params, $REPLICATE_API_KEY);
        $prediction_id = $prediction['id'];
        $poll_url      = 'https://api.replicate.com/v1/predictions/' . $prediction_id;
        $max_attempts  = 60;
        $output_url    = null;


        for ($i = 0; $i < $max_attempts; $i++) {
            sleep(3);
            $result = get_json($poll_url, $REPLICATE_API_KEY);
            $status = $result['status'];

            if ($status === 'succeeded') {
                $output_url = $result['output'][2] ?? null;
                break;
            }
            if ($status === 'failed' || $status === 'canceled') {
                throw new Exception("Replicate: " . ($result['error'] ?? 'Generation failed.'));
            }
        }

        if ($output_url === null) {
            post_json('https://api.replicate.com/v1/predictions/' . $prediction_id . '/cancel',[],$REPLICATE_API_KEY);
            throw new Exception("We are experiencing high traffic. Try again in a moment.");

        }

        echo json_encode(['success' => true, 'image_url' => $output_url]);
    } catch (Exception $e) {
        http_response_code(400);
            // frontend and backend talk in json, so need to encode erorr message
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    exit;
}


require_once __DIR__ . '/templates/header.php';
?>
<section class="main-section">

    <div class="card">
        <!-- multipart/form-data: encodes <form> into several MIME messages. needed if form contains <input type="file" -->
        <form id="storyboard-form" enctype="multipart/form-data">

            <div class="field">
                <label for="prompt">Scene Description</label>
                <textarea
                    id="prompt"
                    name="prompt"
                    placeholder="(Input in english) e.g. Hero enters a dark warehouse, low angle, dramatic shadows, tense mood"
                    required></textarea>
            </div>

            <div class="field">
                <label for="character-image">Scene Reference Image</label>
                <div class="upload-zone" id="upload-zone">
                    <input
                        type="file"
                        name="character_image"
                        id="character-image"
                        accept="image/jpeg, image/png, image/webp"
                        required>
                    <div class="upload-text">Click or drag an image here (4:3 aspect ratio)</div>
                </div>
                <img id="image-preview" alt="Uploaded character reference">
            </div>

            <button type="submit" id="submit-btn">Generate Storyboard Panel</button>
        </form>

        <div id="status"></div>

        <div id="output">
            <h2>Generated Panel</h2>
            <img id="result-img" alt="Generated storyboard panel">
        </div>

    </div>

    
    <div class="storyboard-section">

        <div class="storyboard-header">
                <!-- https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Global_attributes/contenteditable -->
            <div class="storyboard-team">Team Name:<span contenteditable="true"></span></div>
                <div class="storyboard-meta">
                    <div>PRODUCT: <span contenteditable="true"></span></div>
                    <div>TITLE: <span contenteditable="true"></span></div>
                    <div>SHEET: <span contenteditable="true"></span></div>
                    <div>SEQUENCE: <span contenteditable="true"></span></div>
                </div>        
            </div>

        <div class="storyboard">
            <div class="panel" id="panel1">
                <button type="button" class="pick-btn" id="pick-btn1">Insert Image</button>

                <!-- crossOrigina rule exists, so that malicious webites couldnt draw a "cross-origin" image on their site and steal private images -->
                <img alt="" class="panel-image" id="panel-image1" crossOrigin="anonymous">
                <div class="panel-text" id="panel-text1"><span contenteditable="true" aria-label="Panel description"></span></div> <!-- https://github.com/WordPress/gutenberg/issues/5981 -->
            </div>
            <div class="panel" id="panel2">
                <button type="button" class="pick-btn" id="pick-btn2">Insert Image</button>
                <img alt="" class="panel-image" id="panel-image2" crossOrigin="anonymous">
                <div class="panel-text" id="panel-text2"><span contenteditable="true" aria-label="Panel description"></span></div>
            </div>
            <div class="panel" id="panel3">
                <button type="button" class="pick-btn" id="pick-btn3">Insert Image</button>
                <img alt="" class="panel-image" id="panel-image3" crossOrigin="anonymous">
                <div class="panel-text" id="panel-text3"><span contenteditable="true" aria-label="Panel description"></span></div>
            </div>
            <div class="panel" id="panel4">
                <button type="button" class="pick-btn" id="pick-btn4">Insert Image</button>
                <img alt="" class="panel-image" id="panel-image4" crossOrigin="anonymous">
                <div class="panel-text" id="panel-text4"><span contenteditable="true" aria-label="Panel description"></span></div>
            </div>
            <div class="panel" id="panel5">
                <button type="button" class="pick-btn" id="pick-btn5">Insert Image</button>
                <img alt="" class="panel-image" id="panel-image5" crossOrigin="anonymous">
                <div class="panel-text" id="panel-text5"><span contenteditable="true" aria-label="Panel description"></span></div>
            </div>
            <div class="panel" id="panel6">
                <button type="button" class="pick-btn" id="pick-btn6">Insert Image</button>
                <img alt="" class="panel-image" id="panel-image6" crossOrigin="anonymous">
                <div class="panel-text" id="panel-text6"><span contenteditable="true" aria-label="Panel description"></span></div>
            </div>
            <div class="panel" id="panel7">
                <button type="button" class="pick-btn" id="pick-btn7">Insert Image</button>
                <img alt="" class="panel-image" id="panel-image7" crossOrigin="anonymous">
                <div class="panel-text" id="panel-text7"><span contenteditable="true" aria-label="Panel description"></span></div>
            </div>
            <div class="panel" id="panel8">
                <button type="button" class="pick-btn" id="pick-btn8">Insert Image</button>
                <img alt="" class="panel-image" id="panel-image8" crossOrigin="anonymous">
                <div class="panel-text" id="panel-text8"><span contenteditable="true" aria-label="Panel description"></span></div>
            </div>
        </div>

        <button type="button" id="export-btn">Export Storyboard Page</button>

    </div>
</section>



<script src="<?= BASE_URL ?>/scripts/node_modules/html2canvas/dist/html2canvas.min.js" defer></script>
<script>
    const fileInput = document.getElementById('character-image')
    const preview = document.getElementById('image-preview')
    const uploadZone = document.getElementById('upload-zone')

    // file upload zone
    fileInput.addEventListener('change', function() {
        const file = this.files[0]
        if (!file) return

        // https://developer.mozilla.org/en-US/docs/Web/API/FileReader/readAsDataURL -> uploded file (e.target(FileReader)) into getElementById('image-preview')
        const reader = new FileReader()
        reader.onload = e => {
            // SECONDly  onload loads base64 string into preview
            preview.src = e.target.result   // e.target is the FileReader()
            preview.style.display = 'block'

            // warm up the cold model, for fast image generation
            fetch('<?= BASE_URL ?>/warmup.php')
        };
        // FIRSTLY, this converts image into a base64 string and displays in <img>. (intead of loading image into databaase)
        reader.readAsDataURL(file)
    });

    uploadZone.addEventListener('dragover', () => uploadZone.classList.add('dragging'))
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragging'))
    uploadZone.addEventListener('drop', () => uploadZone.classList.remove('dragging'))

    const storyboardForm = document.getElementById('storyboard-form')
    const submitBtn = document.getElementById('submit-btn')
    const status = document.getElementById('status')
    const output = document.getElementById('output')
    const resultImg = document.getElementById('result-img')

    function showStatus(type, message) {
        status.className = type
        status.style.display = 'block'

        if (type === 'loading') {
            status.innerHTML = '<span class="spinner"></span>' + message
        } else {
            status.innerHTML = message
        }
    }

    const storyboard = document.querySelector('.storyboard')
    const storyboardSection = document.querySelector('.storyboard-section')
    const exportBtn = document.getElementById('export-btn')
    let sectionExportReady = false


    exportBtn.addEventListener('click', () =>{

        if (sectionExportReady === true) {
            exportBtn.innerText = 'Export Storyboard Page'
            const pickBtns = document.querySelectorAll('.pick-btn')
                pickBtns.forEach(pickBtn=> {
                    pickBtn.style.display = 'block'
                });
            sectionExportReady = false
        } else {
            const pickBtns = document.querySelectorAll('.pick-btn')
            pickBtns.forEach(pickBtn=> {
                pickBtn.style.display = 'none'
            });
            sectionExportReady = true

            // https://dev.to/bibekkakati/take-screenshot-of-html-element-using-javascript-13b7
            // load html2canvas
            exportBtn.style.display = 'none'
            html2canvas(storyboardSection, {
            allowTaint: true,
            useCORS: true,
            }).then(function (canvas) {

            // https://stackoverflow.com/questions/61861295/downloading-a-tainted-canvas-as-a-png
            // it will return a canvas element
            const downloadLink = document.createElement('a')
            downloadLink.href = canvas.toDataURL("image/png", 0.5)
            downloadLink.download = 'storyboard_panel.png'
            downloadLink.click()
            exportBtn.style.display = 'block'
            exportBtn.innerText = 'Back to Edit Mode'
            })
            .catch((e) => {
            console.log(e)
            })
        }
    })

    storyboardForm.addEventListener('submit', function(e) {
        e.preventDefault() //prevents browser from reloading

        // .value accesses string value, .trim() creates new string, sanitizes input and removes spaces
        const promptText = document.getElementById('prompt').value.trim()
        if (promptText.length < 20) {
            showStatus('error', 'Please enter a longer scene description.')
            return
        }
        if (!fileInput.files[0]) {
            showStatus('error', 'Please upload a photo reference.')
            return
        }
        // https://www.w3schools.com/jsref/jsref_tolowercase.asp
        if (promptText.toLowerCase().includes('<script>') || promptText.toUpperCase().includes('DROP TABLE')) {
            showStatus('error', 'nuh-uh, nicht so einfach Freundchen☝️ ;) ')
            return
        }

        submitBtn.disabled = true
        submitBtn.textContent = 'Generating…'
        output.style.display = 'none'
        showStatus('loading', 'Sending request to Replicate… (this usually takes 10–30 seconds)')

        // sends a promise object
        fetch('<?= BASE_URL ?>/index.php', {
                method: 'POST',
                body: new FormData(storyboardForm)
            })
            // all PHP responses (echo) are run through here, if its an error
            // each .then returns a promise (asynchron)
            // data ist zum Beipsiel: { success: false, error: 'etwas etwas' }
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showStatus('info', '✓ Panel generated successfully!')
                    resultImg.src = data.image_url
                    output.style.display = 'block'
                    const panels = document.querySelectorAll('.panel')
                    panels.forEach(panel => {
                        const pickBtn = panel.querySelector('.pick-btn')
                        pickBtn.style.display = 'block'
                        const imagePanel = panel.querySelector('.panel-image')
                        const panelText = panel.querySelector('span')

                        pickBtn.addEventListener('click', (e) => {
                            pickBtn.textContent = 'Image Inserted'
                            imagePanel.src = data.image_url
                            panelText.innerText = promptText
                        })
                    })

                } else {
                    showStatus('error', '✗ Error: ' + data.error)
                }
            })
            .catch(err => showStatus('error', '✗ Network error: ' + err.message))
            .finally(() => {
                submitBtn.disabled = false
                submitBtn.textContent = 'Generate Storyboard Panel';
            })
    })
</script>
</body>

</html>