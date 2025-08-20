<?php


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
