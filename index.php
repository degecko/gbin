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

    <title><?= $title ?? '' ?>gbin</title>

    <link rel="stylesheet/less" type="text/css" href="/app.less" />
    <script src="https://cdn.jsdelivr.net/npm/less@4.1.1"></script>
</head>
<body class="<?= URI === '/' ? 'home' : '' ?>">

<?php if (isset($code)): ?>
    <?= $code ?>

    <script>
        var code = document.querySelector('body > pre > code');

        code.innerText || (window.location.href = location.pathname);
    </script>
<?php else: ?>
    <div>
        <header>
            <h1>gbin</h1>
            <p>click <button type="button" onclick="document.upload.file.click()">Choose</button>
                or drag files onto this page
        </header>

        <?php if (isset($_SESSION['error'])): ?>
            <p class="error"><?= $_SESSION['error'] ?></p>
        <?php unset($_SESSION['error']); endif ?>

        <form name="upload" action="/new-paste.php" method="post" enctype="multipart/form-data">
            <input type="file" name="file">
            <div id="file-name" style="margin-bottom: 1rem;">No file chosen</div>
            <?php if (USE_PASSWORD): ?>
                <input type="password" name="pw" placeholder="password">
            <?php endif ?>
            <input type="submit" value="Upload">
        </form>

        <script>
            var fn = document.getElementById('file-name');

            document.upload.file.addEventListener('change', function (e) {
                try {
                    var file = e.target.files[0];
                    fn.innerText = 'Chosen file: ' + file.name;
                } catch (e) {
                    fn.innerText = 'No file chosen';
                }
            }, false);
        </script>
    </div>
<?php endif ?>

</body>
</html>
