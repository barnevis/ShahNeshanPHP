<?php
/**
 * Copyright 2025 Shahrooz
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 */

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
