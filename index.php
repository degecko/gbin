<?php

require __DIR__ . '/helpers.php';

IS_POST and die(require __DIR__ . '/new-paste.php');

if ($id = substr(URI, 1, 4)) {
    if (file_exists($path = __DIR__ . "/uploads/$id")) {
        $code = get_highlighted_code($path);
    }
    else {
        die('Invalid ID');
    }
}

?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $title ?? '' ?>g's paste bin</title>

    <link rel="stylesheet/less" type="text/css" href="/app.less" />
    <script src="https://cdn.jsdelivr.net/npm/less@4.1.1"></script>
</head>
<body>

<?php if (isset($code)): ?>

    <?= $code ?>

<?php else: ?>

    <form action="/new-paste.php" method="post" enctype="multipart/form-data">
        <input type="file" name="file">
        <?php if (USE_PASSWORD): ?>
            <input type="password" name="pw" value="<?= $_COOKIE['pw'] ?? '' ?>" placeholder="password">
        <?php endif ?>
        <input type="submit" value="Upload">
    </form>

<?php endif ?>

</body>
</html>
