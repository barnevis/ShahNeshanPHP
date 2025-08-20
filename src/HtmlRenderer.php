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

class HtmlRenderer {
    private $pluginManager;

    public function __construct(PluginManager $pluginManager) {
        $this->pluginManager = $pluginManager;
    }

    /** Always escape as string */
    private function esc($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Render an array that may contain Node objects and/or strings
    private function renderChildren(array $items): string {
        $out = '';
        foreach ($items as $item) {
            if ($item instanceof Node) {
                $out .= $this->nodeToHtml($item);
            } else {
                // strings (already inline-formatted HTML) pass through
                $out .= (string)$item;
            }
        }
        return $out;
    }

    public function nodesToHtml($nodes) {
        // IMPORTANT: use $this, not self::
        $html = $this->renderChildren($nodes);
        return $this->pluginManager->applyAfterRender($html);
    }

    public function nodeToHtml($node) {
        switch ($node->type) {
            case 'header': {
                $level = (int)($node->attributes['level'] ?? 1);
                $raw   = $node->attributes['raw'] ?? $this->renderChildren($node->content);
                $id    = $this->slugify($raw);
                return "<h{$level} id=\"{$id}\" dir=\"auto\">".$this->renderChildren($node->content)."</h{$level}>";
            }

            case 'paragraph':
                return "<p dir=\"auto\">".$this->renderChildren($node->content)."</p>";

            case 'blockquote':
                return "<blockquote>".$this->renderChildren($node->content)."</blockquote>";

            case 'ul': {
                $dir = !empty($node->attributes['isRTL']) ? 'rtl' : 'ltr';
                return "<ul dir=\"{$dir}\">".$this->renderChildren($node->content)."</ul>";
            }

            case 'ol': {
                $dir = !empty($node->attributes['isRTL']) ? 'rtl' : 'ltr';
                return "<ol dir=\"{$dir}\">".$this->renderChildren($node->content)."</ol>";
            }

            case 'li': {
                $dir = !empty($node->attributes['isRTL']) ? 'rtl' : 'ltr';
                return "<li dir=\"{$dir}\">".$this->renderChildren($node->content)."</li>";
            }

            case 'codeBlock': {
                $code = implode("\n", $node->content);
                $langClass = $this->esc($node->attributes['lang'] ?? '');
                return "<pre><code class=\"{$langClass}\">".$this->esc($code)."</code></pre>";
            }

            case 'persianBlock': {
                $codeClass = $this->esc($node->attributes['code'] ?? '');
                return "<div class=\"persian {$codeClass}\">".$this->renderChildren($node->content)."</div>";
            }

            case 'poetRow':
                return $this->renderChildren($node->content);

            case 'poetCell':
                return "<div class=\"stanza\">".$this->renderChildren($node->content)."</div>";

            case 'note':
                return "<div class=\"alert note\"><h1>توجه</h1><p>".$this->renderChildren($node->content)."</p></div>";
            case 'tip':
                return "<div class=\"alert tip\"><h1>نکته</h1><p>".$this->renderChildren($node->content)."</p></div>";
            case 'important':
                return "<div class=\"alert important\"><h1>مهم</h1><p>".$this->renderChildren($node->content)."</p></div>";
            case 'warning':
                return "<div class=\"alert warning\"><h1>هشدار</h1><p>".$this->renderChildren($node->content)."</p></div>";
            case 'caution':
                return "<div class=\"alert caution\"><h1>احتیاط</h1><p>".$this->renderChildren($node->content)."</p></div>";

            case 'hr':
                return "<hr />";

            case 'taskItem': {
                $checked = !empty($node->attributes['checked']) ? 'checked' : '';
                $dir = !empty($node->attributes['isRTL']) ? 'rtl' : 'ltr';
                return "<li class=\"task\" dir=\"{$dir}\"><input type=\"checkbox\" {$checked} disabled> ".$this->renderChildren($node->content)."</li>";
            }

            case 'table':
                return "<table>".$this->renderChildren($node->content)."</table>";

            case 'tableRow':
                return "<tr>".$this->renderChildren($node->content)."</tr>";

            case 'tableHeaderCell': {
                $align = $node->attributes['align'] ?? null;
                $attr  = $align ? ' align="'.$this->esc($align).'"' : '';
                return "<th{$attr}>".$this->renderChildren($node->content)."</th>";
            }

            case 'tableCell': {
                $align = $node->attributes['align'] ?? null;
                $attr  = $align ? ' align="'.$this->esc($align).'"' : '';
                return "<td{$attr}>".$this->renderChildren($node->content)."</td>";
            }

            case 'image': {
                $src = $this->esc($node->attributes['src'] ?? '');
                $alt = $this->esc($node->attributes['alt'] ?? '');
                return "<img src=\"{$src}\" alt=\"{$alt}\">";
            }

            case 'html':
                // passthrough
                return $this->renderChildren($node->content);

            case 'footnotes':
                return "<section class=\"footnotes\" dir=\"auto\"><h2>Footnotes</h2><ol>".$this->renderChildren($node->content)."</ol></section>";

            case 'footnote': {
                $ref    = (string)($node->attributes['ref'] ?? '');
                $refEsc = $this->esc($ref);
                return "<li id=\"footnote-{$refEsc}\">".$this->renderChildren($node->content)." <a href=\"#footnote-ref-{$refEsc}\">↩</a></li>";
            }

            default:
                return '';
        }
    }

    private function slugify($text) {
        $t = trim($text);
        $t = preg_replace('/\s+/u', '-', $t);
        $t = preg_replace('/[^\p{L}\p{N}\-_]+/u', '', $t);
        return strtolower($t);
    }
}
