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

namespace ShahNeshan;

// PluginManager.php
class PluginManager {
    private $plugins = [];

    // Register a plugin
    public function addPlugin(PluginInterface $plugin) {
        $this->plugins[] = $plugin;
    }

    // Apply beforeParse hook
    public function applyBeforeParse($markdown) {
        foreach ($this->plugins as $plugin) {
            $markdown = $plugin->beforeParse($markdown);
        }
        return $markdown;
    }

    // Apply transformNode hook to each node
    public function applyNodeTransform($node) {
        foreach ($this->plugins as $plugin) {
            $node = $plugin->transformNode($node);
        }
        return $node;
    }

    // Apply afterRender hook
    public function applyAfterRender($html) {
        foreach ($this->plugins as $plugin) {
            $html = $plugin->afterRender($html);
        }
        return $html;
    }
}
