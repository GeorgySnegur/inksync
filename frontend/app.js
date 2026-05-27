
    const fileInput = document.getElementById('character-image');
    const preview = document.getElementById('image-preview');
    const uploadZone = document.getElementById('upload-zone');

    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });

    uploadZone.addEventListener('dragover', () => uploadZone.classList.add('dragging'));
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragging'));
    uploadZone.addEventListener('drop', () => uploadZone.classList.remove('dragging'));

    const form = document.getElementById('storyboard-form');
    const submitBtn = document.getElementById('submit-btn');
    const status = document.getElementById('status');
    const output = document.getElementById('output');
    const resultImg = document.getElementById('result-img');

    function showStatus(type, message) {
        status.className = type;
        status.style.display = 'block';
        status.innerHTML = type === 'loading' ?
            '<span class="spinner"></span>' + message :
            message;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const promptText = document.getElementById('prompt').value.trim();
        if (promptText.length < 5) {
            showStatus('error', 'Please enter a longer scene description.');
            return;
        }
        if (!fileInput.files[0]) {
            showStatus('error', 'Please upload a character reference image.');
            showStatus('error', 'Please enter a longer scene description.');
            return;
        }
        if (!fileInput.files[0]) {
            showStatus('error', 'Please upload a character photo image.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Generating…';
        output.style.display = 'none';
        showStatus('loading', 'Sending request to Replicate… (this takes 10–30 seconds)');

        fetch('<?= BASE_URL ?>/index.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showStatus('info', '✓ Panel generated successfully!');
                    resultImg.src = data.image_url;
                    output.style.display = 'block';
                } else {
                    showStatus('error', '✗ Error: ' + data.error);
                }
            })
            .catch(err => showStatus('error', '✗ Network error: ' + err.message))
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Generate Storyboard Panel';
            });
    });
