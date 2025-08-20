<?php

require_once 'ShahNeshanPHP.php';

// Define configuration settings
$config = [
    'headingLevel' => 2,
    'customStyles' => 'h1 { color: blue; } mark { background-color: yellow; }'
];

// Instantiate the ShahNeshanPHP
$renderer = new ShahNeshanPHP($config);

// Generate HTML from the markdown file
$html = $renderer->renderFile(__DIR__ . '/README.md');

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Markdown Viewer</title>
  <link rel="stylesheet" href="statics/style.css">
  <link rel="stylesheet" href="statics/style_s.css">
</head>
<body>
  <div id="content">
    <?php echo $html; ?>
  </div>
</body>
</html>
