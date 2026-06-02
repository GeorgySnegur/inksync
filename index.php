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
        $max_attempts  = 30;
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
            throw new Exception("Generation timed out. Try again in a moment.");
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
    </div>

    <div class="storyboard">
        <div class="panel" id="panel1">
            <button type="button" class="pick-btn" id="pick-btn1">Pick Image</button>
            <img src="" alt="" class="panel-image" id="panel-image1">
            <div class="panel-text" id="panel-text1"><p></p></div>
        </div>
        <div class="panel" id="panel2">
            <button type="button" class="pick-btn" id="pick-btn2">Pick Image</button>
            <img src="" alt="" class="panel-image" id="panel-image2">
            <div class="panel-text" id="panel-text1"><p></p></div>
        </div>
        <!-- <div class="panel" id="panel-3"></div>
        <div class="panel" id="panel-4"></div>
        <div class="panel" id="panel-5"></div>
        <div class="panel" id="panel-6"></div>
        <div class="panel" id="panel-7"></div>
        <div class="panel" id="panel-8"></div> -->
    </div>

</section>

<div id="output">
    <h2>Generated Panel</h2>
    <img id="result-img" alt="Generated storyboard panel">
</div>

<script>
    const fileInput = document.getElementById('character-image')
    const preview = document.getElementById('image-preview')
    const uploadZone = document.getElementById('upload-zone')

    // file upload zone
    fileInput.addEventListener('change', function() {
        const file = this.files[0]
        if (!file) return

        // warm up the cold model, for fast image generation
        window.addEventListener('load', () => {
        fetch('<?= BASE_URL ?>/warmup.php')
        });


        // https://developer.mozilla.org/en-US/docs/Web/API/FileReader/readAsDataURL -> uploded file (e.target(FileReader)) into getElementById('image-preview')
        const reader = new FileReader()
        reader.onload = e => {
            preview.src = e.target.result
            preview.style.display = 'block'
        };

        // converts image into a base64 string
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
        status.innerHTML = type === 'loading' ?
            '<span class="spinner"></span>' + message :
            message
    }

    // debugger;



    storyboardForm.addEventListener('submit', function(e) {
        e.preventDefault()

        // .value accesses string value, .trim() creates new string, sanitizes input and removes spaces
        const promptText = document.getElementById('prompt').value.trim()
        if (promptText.length < 20) {
            showStatus('error', 'Please enter a longer scene description.')
            return
        }
        if (!fileInput.files[0]) {
            showStatus('error', 'Please upload a photo reference.')
            showStatus('error', 'Please enter a longer scene description.')
            return
        }
        if (!fileInput.files[0]) {
            showStatus('error', 'Please upload a photo reference.')
            return
        }

        submitBtn.disabled = true
        submitBtn.textContent = 'Generating…'
        output.style.display = 'none'
        showStatus('loading', 'Sending request to Replicate… (this takes 10–30 seconds)')

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
                        console.log(`Panel ---->${panel}`)

                        pickBtn.style.display = 'block'
                        const imagePanel = panel.querySelector('.panel-image')
                        const panelText = panel.querySelector('p')
                        console.log(`imgePanel ---->${imagePanel}`)

                        pickBtn.addEventListener('click', (e) => {

                            console.log(`prompt Text---->${pickBtn}`)
                            pickBtn.textContent = 'Image Picked'
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