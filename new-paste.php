<?php

require_once __DIR__ . '/helpers.php';

valid_password() or go_back('Invalid password!');

$file = validate_file_upload();
$name = substr($file->name ?? '', 0, 128);
$mime = substr($file->type ?? '', 0, 128);
$ext = explode('.', $name);
$ext = strtolower(substr(end($ext), 0, 24));
$size = human_fs($file->size);
$date = date('Y-m-d H:i:s');
$id = get_new_id();
$path = __DIR__ . "/uploads/$id";
$prefix = serialize((object) compact('name', 'mime', 'ext', 'size', 'date')) . UPLOADED_FILE_SEP;

// When uploading via curl.
if (isset($_POST['file'])) {
    file_put_contents($path, $prefix . $_POST['file']);

    die('http://' . getenv('HTTP_HOST') . "/$id?$ext&ln\n");
}

// Uploading through the browser.
else {
    move_uploaded_file($file->tmp_name, $path);

    file_put_contents($path, $prefix . file_get_contents($path));

    header("location: /$id?$ext&ln");
    exit;
}
