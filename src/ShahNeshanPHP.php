<?php
// src/ShahNeshanPHP.php
declare(strict_types=1);

namespace ShahNeshan;

use ShahNeshan\Plugins\EmojiPlugin;


class ShahNeshanPHP {
    private $parser;
    private $renderer;
    private $pluginManager;

    public function __construct(array $config = []) {
        $this->pluginManager = new PluginManager();
        $this->pluginManager->addPlugin(new EmojiPlugin());

        $this->parser = new MarkdownParser($config, $this->pluginManager);
        $this->renderer = new HtmlRenderer($this->pluginManager);
    }

    public function renderFile(string $filePath): string {
        if (!file_exists($filePath)) {
            return "<p>Error: File not found.</p>";
        }
        $markdownContent = file_get_contents($filePath);
        return $this->renderMarkdown($markdownContent);
    }

    public function renderMarkdown(string $markdownContent): string {
        $nodes = $this->parser->parseMarkdown($markdownContent);
        return $this->renderer->nodesToHtml($nodes);
    }
}
