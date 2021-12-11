<?php

define('URI', getenv('REQUEST_URI'));
define('IS_POST', getenv('REQUEST_METHOD') === 'POST');
define('SIZE_LIMIT', 10e6);
define('USE_PASSWORD', true);
define('RANDOM_ID_LENGTH', 4);
define('UPLOADED_FILE_SEP', '::#$#$#$&&&::');

function dd(): void
{
    echo '<pre>';

    foreach (func_get_args() as $arg) {
        print_r($arg);
    }

    echo '</pre>';
    die;
}

function valid_password(): bool
{
    if (! USE_PASSWORD) {
        return true;
    }

    $toCheck = $_COOKIE['pw'] ?? sha1($_REQUEST['pw'] ?? '');
    $isValid = $toCheck === $pw = trim(file_get_contents(__DIR__ . '/.password'));

    $isValid and setcookie('pw', $pw, time() * 2);

    return $isValid;
}

function validate_file_upload(): stdClass
{
    // When uploading via curl.
    if (isset($_POST['file'])) {
        $size = strlen($_POST['file']);

        $size > SIZE_LIMIT and die('Too large');

        return (object) [
            'name' => 'No name.',
            'type' => '',
            'size' => $size,
        ];
    }

    if (! isset($_FILES, $_FILES['file'])) {
        die('No file');
    }

    $file = (object) $_FILES['file'];

    if (! isset($file->error) || is_array($file->error)) {
        throw new RuntimeException('Bye now');
    }

    match ($file->error) {
        UPLOAD_ERR_OK => null,
        UPLOAD_ERR_NO_FILE => die('No file sent'),
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE => die('Exceeded filesize limit'),
        default => die('Unknown errors'),
    };

    if ($file->size > SIZE_LIMIT) {
        die('Too large');
    }

    return $file;
}

function get_new_id(): string
{
    do {
        $id = get_random_str();
    } while(file_exists(__DIR__ . "/uploads/$id"));

    return $id;
}

function get_random_str(): string
{
    $charset = str_shuffle('abcdefghijklkmnopqrstuvwxyz-ABCDEFGHIJKLMNOPRQSTUVWXYZ_0123456789');
    $length = RANDOM_ID_LENGTH;
    $randomStr = '';

    while ($length--) {
        $randomStr .= $charset[rand(0, strlen($charset) - 1)];
    }

    return $randomStr;
}

function get_highlighted_code(string $path): string
{
    global $title;

    [$info, $code] = explode(UPLOADED_FILE_SEP, file_get_contents($path), 2);
    $info = unserialize($info);
    $code = htmlentities($code);
    $title = implode(' &bull; ', [
        htmlentities($info->name),
        $info->size,
        $info->date,
        htmlentities($info->mime),
        '', // Keep this to append a &bull; at the end.
    ]);
    $lang = current(array_keys($_GET ?? ['invalid']));
    $result = '
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/styles/default.min.css">
        <link rel="stylesheet" href="//highlightjs.org/static/demo/styles/base16/atelier-plateau-light.css">
        <style>
            @media (prefers-color-scheme: dark) {
               @import url(//highlightjs.org/static/demo/styles/base16/ros-pine-moon.css);
            }
        </style>
        <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/highlight.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.8.0/highlightjs-line-numbers.min.js"></script>
    ';

    try {
        $result .= "<pre><code class=\"hljs $lang\">$code</code></pre><script>hljs.highlightAll()</script>";

        if (isset($_GET['ln'])) {
            return $result . '<script>hljs.initLineNumbersOnLoad()</script>';
        }

        header('content-type: text/html; charset=utf-8');

        return $result;
    } catch (DomainException $e) {
        header('content-type: text/plain; charset=utf-8');

        die($code);
    }
}

function human_fs($bytes, $decimals = 2) {
    $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$decimals}f ", $bytes / pow(1024, $factor)) . $size[$factor];
}
