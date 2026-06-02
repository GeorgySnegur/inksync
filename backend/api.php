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
// converting image to base64 (binary string) is necessary to prove wether

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

    // https://www.php.net/manual/es/function.finfo-file.php
    if (!in_array($mime, $allowed, true)) {
        throw new Exception("Only JPEG, PNG, or WebP images are allowed.");
    }

    return $mime;
}


function resize_to_4x3(string $image_temp){
    // https://stackoverflow.com/a/31656257

    $max_width  = 800;
    $max_height = 600;

    $image_size_info = getimagesize($image_temp); //gets original image dimensions
    $image_width     = $image_size_info[0];
    $image_height    = $image_size_info[1];
    $image_res       = imagecreatefromjpeg($image_temp);

    $new_width  = $image_height * $max_width / $max_height;
    $new_height = $image_width  * $max_height / $max_width; //figures out ths largest  4:3 rectangle that fits inside the original

    $canvas = imagecreatetruecolor($max_width, $max_height); //blank canvas

    if ($new_width > $image_width) {
        $cut_x              = 0;
        $cut_y              = ($image_height - $new_height) / 2;
        $new_width_canvas   = $image_width;
        $new_height_canvas  = $new_height;
    } else {
        $cut_x              = ($image_width - $new_width) / 2;
        $cut_y              = 0;
        $new_width_canvas   = $new_width;
        $new_height_canvas  = $image_height;
    }

    imagecopyresampled($canvas, $image_res, 0, 0, $cut_x, $cut_y, $max_width, $max_height, $new_width_canvas, $new_height_canvas);
    imagejpeg($canvas, sys_get_temp_dir() . '/resized_01', 85);
    imagedestroy($image_res);
}