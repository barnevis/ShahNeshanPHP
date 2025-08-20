<?php


class HtmlRenderer {
    private $pluginManager;

    public function __construct(PluginManager $pluginManager) {
        $this->pluginManager = $pluginManager;
    }

    public function nodesToHtml($nodes) {
         $html = implode('', array_map([self::class, 'nodeToHtml'], $nodes));

        // Apply plugins after rendering HTML
        return $this->pluginManager->applyAfterRender($html);
    }

    public function nodeToHtml($node) {
        switch ($node->type) {
            case 'header':
                $level = $node->attributes['level'];
                $id = strtolower(str_replace(' ', '-', $node->content[0]));
                return "<h$level id=\"$id\">{$node->content[0]}</h$level>";

            case 'paragraph':
                return "<p>" . implode('', $node->content) . "</p>";
            
            case 'blockquote':
                return "<blockquote>"  . self::nodesToHtml($node->content) . "</blockquote>";
            
            case 'ul':
                return "<ul>"  . self::nodesToHtml($node->content) . "</ul>";
            
            case 'ol':
                return "<ol>"  . self::nodesToHtml($node->content) . "</ol>";
            
            case 'li':
                return "<li>"  . self::nodesToHtml($node->content) . "</li>";
            
            case 'codeBlock':
                $code = implode("\n", $node->content);
                $langClass = htmlspecialchars($node->attributes['lang']);
                return "<pre><code class=\"$langClass\">" . htmlspecialchars($code) . "</code></pre>";
            
            case 'persianBlock':
                $code = htmlspecialchars($node->attributes['code']);
                return "<div class=\"persian poet $code\">" . self::nodesToHtml($node->content) . "</div>";
            
            case 'poetRow':
                return self::nodesToHtml($node->content);
            
            case 'poetCell':
                return "<div class=\"stanza\">{$node->content[0]}</div>";
            
            case 'hr':
                return "<hr />";
            
            case 'taskItem':
                $checked = $node->attributes['checked'] ? 'checked' : '';
                return "<li class=\"task\"><input type=\"checkbox\" $checked disabled> "  . self::nodesToHtml($node->content) . "</li>";
            
            case 'table':
                return "<table>"  . self::nodesToHtml($node->content) . "</table>";
            
            case 'tableRow':
                return "<tr>"  . self::nodesToHtml($node->content) . "</tr>";
            
            case 'tableHeaderCell':
                return "<th>{$node->content[0]}</th>";
            
            case 'tableCell':
                return "<td>{$node->content[0]}</td>";
            
            case 'image':
                $src = htmlspecialchars($node->attributes['src']);
                $alt = htmlspecialchars($node->attributes['alt']);
                return "<img src=\"$src\" alt=\"$alt\">";
            
            case 'html':
                return $node->content[0];
            
            case 'footnotes':
                return "<section class=\"footnotes\"><h2>Footnotes</h2><ol>"  . self::nodesToHtml($node->content) . "</ol></section>";
            
            case 'footnote':
                $ref = htmlspecialchars($node->attributes['ref']);
                return "<li id=\"footnote-$ref\">{$node->content[0]} <a href=\"#footnote-ref-$ref\">↩</a></li>";
            
            default:
                return '';
        }
    }
}