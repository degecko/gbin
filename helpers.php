<?php

session_start();

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

    if (isset($_POST['pw']) && ! empty($_POST['pw'])) {
        $toCheck = $_POST['pw'];
    } elseif (isset($_COOKIE['pw'])) {
        $toCheck = decrypt($_COOKIE['pw']);
    } else {
        return false;
    }

    $password = trim(file_get_contents(__DIR__ . '/.password'));

    if (password_verify(fu_password($toCheck), $password)) {
        setcookie('pw', encrypt($toCheck), time() * 2);

        return true;
    }

    return false;
}

function fu_password(string $password): string
{
    return " $password . $password ";
}

function password(string $plain): string
{
    return password_hash(fu_password($plain), CRYPT_BLOWFISH);
}

function encrypt($content): string
{
    if (! is_string($content)) {
        $content = json_encode($content);
    }

    $ivlen = openssl_cipher_iv_length('blowfish');
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encryted = openssl_encrypt($content, 'blowfish', sha1('gBin'), iv: $iv);

    return urlencode(sprintf('%s %s', base64_encode($encryted), base64_encode($iv)));
}

function decrypt(string $encrypted): mixed
{
    try {
        [$encrypted, $iv] = explode(' ', urldecode($encrypted));
        $encrypted = base64_decode($encrypted);
        $iv = base64_decode($iv);
        $decrypted = openssl_decrypt($encrypted, 'blowfish', sha1('gBin'), iv: $iv);
    } catch (\Exception $_) {
        return null;
    }

    if (preg_match('#[^a-z0-9 !@\#$%^&*()_+}{":<>?/,.;\[\]\'=\\\-]#i', $decrypted)) {
        return null;
    }

    try {
        return json_decode($decrypted) ?: $decrypted;
    } catch (\Exception $_) {
        return $decrypted;
    }
}

function validate_file_upload(): stdClass
{
    // When uploading via curl.
    if (isset($_POST['file'])) {
        $size = strlen($_POST['file']);

        $size > SIZE_LIMIT and go_back('Too large');

        return (object) [
            'name' => 'No name.',
            'type' => '',
            'size' => $size,
        ];
    }

    if (! isset($_FILES, $_FILES['file'])) {
        go_back('No file');
    }

    $file = (object) $_FILES['file'];

    if (! isset($file->error) || is_array($file->error)) {
        throw new RuntimeException('Bye now');
    }

    match ($file->error) {
        UPLOAD_ERR_OK => null,
        UPLOAD_ERR_NO_FILE => go_back('No file sent'),
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE => go_back('Exceeded filesize limit'),
        default => go_back('Unknown errors'),
    };

    if ($file->size > SIZE_LIMIT) {
        go_back('Too large');
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
        <link rel="stylesheet" href="//highlightjs.org/static/demo/styles/base16/atelier-plateau-light.css" media="(prefers-color-scheme: light)">
        <link rel="stylesheet" href="//highlightjs.org/static/demo/styles/base16/ros-pine-moon.css" media="(prefers-color-scheme: dark)">
        <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/highlight.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.8.0/highlightjs-line-numbers.min.js"></script>
    ';

    if ($lang) {
        $code = htmlentities($code);
        $result .= "<pre><code class=\"hljs $lang\">$code</code></pre><script>hljs.highlightAll()</script>";

        if (isset($_GET['ln'])) {
            return $result . '<script>hljs.initLineNumbersOnLoad()</script>';
        }

        header('content-type: text/html; charset=utf-8');

        return $result;
    } else {
        header('content-type: text/plain; charset=utf-8');

        die($code);
    }
}

function human_fs($bytes, $decimals = 2) {
    $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$decimals}f ", $bytes / pow(1024, $factor)) . $size[$factor];
}

function go_back(string $error): void
{
    $_SESSION['error'] = $error;

    header('location: /');
    exit;
}
