<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

<div class="card">
    <form id="storyboard-form" enctype="multipart/form-data">

        <div class="field">
            <label for="prompt">Scene Description</label>
            <textarea
                id="prompt"
                name="prompt"
                placeholder="e.g. Hero enters a dark warehouse, low angle, dramatic shadows, tense mood"
                required></textarea>
        </div>

        <div class="field">
            <label for="character-image">Character Reference Image</label>
            <div class="upload-zone" id="upload-zone">
                <input
                    type="file"
                    name="character_image"
                    id="character-image"
                    accept="image/jpeg, image/png, image/webp"
                    required>
                <div class="upload-text">Click or drag an image here</div>
            </div>
            <img id="image-preview" alt="Uploaded character reference">
        </div>

        <button type="submit" id="submit-btn">Generate Storyboard Panel</button>

    </form>

    <div id="status"></div>
</div>

<div id="output">
    <h2>Generated Panel</h2>
    <img id="result-img" alt="Generated storyboard panel">
</div>

    
</body>
</html>

