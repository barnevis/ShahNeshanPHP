<?php

interface PluginInterface {
    public function beforeParse($markdown);
    public function transformNode($node);
    public function afterRender($html);
}