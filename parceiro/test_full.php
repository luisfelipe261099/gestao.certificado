<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting full test...<br>";

require_once '../app/config/config.php';
echo "Config loaded.<br>";

$page_title = 'Test Page';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
</head>

<body>
    <h1>Test Body</h1>
    <p>Content goes here.</p>

    <?php
    echo "Including footer...<br>";
    require_once '../app/views/footer.php';
    echo "Footer included.<br>";
    ?>
</body>

</html>