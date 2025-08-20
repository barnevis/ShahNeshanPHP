<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ShahNeshan\ShahNeshanPHP;

$config = [
    'headingLevel' => 2,
    'customStyles' => 'h1 { color: blue; } mark { background-color: yellow; }',
];

$engine = new ShahNeshanPHP($config);

// README.md and statics live one level up from /examples
$html = $engine->renderFile(__DIR__ . '/sample.md'); // or '../README.md'

?>
<!doctype html>
<meta charset="utf-8">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="../statics/style.css">

<?= $html ?>
