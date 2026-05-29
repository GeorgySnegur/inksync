<?php
require_once __DIR__ . '/bootstrap.php';


function post_json(string $url, array $data, string $api_key): array
{
    $options = [
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n"
                . "Authorization: Token $api_key\r\n",
            'content'       => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        throw new Exception("Network error: could not reach Replicate.");
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Could not decode API response: " . json_last_error_msg());
    }
    return $decoded;
}

function get_json(string $url, string $api_key): array
{
    $options = [
        'http' => [
            'method'        => 'GET',
            'header'        => "Authorization: Token $api_key\r\n",
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    return json_decode($response, true);
}

// https://www.php.net/manual/en/function.base64-encode.php
// converting image to base64 (binary string) is necessary to 

function file_to_base64(string $tmp_path, string $mime_type): string
{
    $raw = file_get_contents($tmp_path);
    return 'data:' . $mime_type . ';base64,' . base64_encode($raw);
}

// $_FILES['character_image'] is an array with name, type, tmp_name etc
function validate_image(array $file): string
{
    $max_bytes = 5 * 1024 * 1024;
    if ($file['size'] > $max_bytes) {
        throw new Exception("Image must be under 5 MB.");
    }

    // finfo(FILEINFO_MIME_TYPE) checks real file data, it location of tmp file 'tmp_name' 
    
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    // https://www.pentesttesting.com/unrestricted-file-upload-in-wordpress/
    if (!in_array($mime, $allowed, true)) {
        throw new Exception("Only JPEG, PNG, or WebP images are allowed.");
    }

    return $mime;
}
