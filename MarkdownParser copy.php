<?php


class Node {
    public $type;
    public $content;
    public $attributes;

    public function __construct($type, $content = '', $attributes = []) {
        $this->type = $type;
        $this->content = is_array($content) ? $content : [$content];
        $this->attributes = $attributes;
    }
}

class MarkdownParser {
    private $config;
    private $pluginManager;
    private $footnotes = [];
    private $footnoteIndex = 1;

    public function __construct($config = [], PluginManager $pluginManager) {
        $this->config = array_merge([
            'headingLevel' => 1,
            'outputFormat' => 'html',
            'customStyles' => '',
        ], $config);
        $this->pluginManager = $pluginManager;
    }

    public function configure($options = []) {
        $this->config = array_merge($this->config, $options);
    }

    public function addPlugin($plugin) {
        $this->plugins[] = $plugin;
    }

    public function parseMarkdownToNodes($markdown) {
        // Apply plugins before parsing
        $markdown = $this->pluginManager->applyBeforeParse($markdown);

        $lines = explode("\n", $markdown);
        $nodes = [];
        $listStack = [];
        $footnotes = [];
        $codeBlock = false;
        $codeLang = '';
        $persianBlock = false;
        $persianCode = '';
        $currentBlockquote = null;
        $currentIndentLevel = 0;
        $footnoteIndex = 1;

        foreach ($lines as $line) {
            $indentLevel = strlen($line) - strlen(ltrim($line));

            // Code Blocks
            if (preg_match('/^```/', $line)) {
                if (!$codeBlock) {
                    $codeBlock = true;
                    $codeLang = trim(str_replace('```', '', $line));
                    $nodes[] = new Node('codeBlock', [], ['lang' => $codeLang]);
                } else {
                    $codeBlock = false;
                    $codeLang = '';
                }
                continue;
            }
            if ($codeBlock) {
                $nodes[count($nodes) - 1]->content[] = $line;
                continue;
            }

            // Persian Block (e.g., poetry with specific formatting)
            if (preg_match('/^\.{3}/', $line)) {
                if (!$persianBlock) {
                    $persianBlock = true;
                    $persianCode = trim(str_replace('...', '', $line));
                    $nodes[] = new Node('persianBlock', [], ['code' => $persianCode]);
                } else {
                    $persianBlock = false;
                    $persianCode = '';
                }
                continue;
            }
            if ($persianBlock) {
                $cells = array_map('trim', explode(' -- ', $line));
                $row = new Node('poetRow', array_map(fn($cell) => new Node('poetCell', $cell), $cells));
                $nodes[count($nodes) - 1]->content[] = $row;
                continue;
            }

            // Blockquotes
            if (preg_match('/^> (.*)/', $line, $matches)) {
                $content = trim($matches[1]);
                if (!$currentBlockquote) {
                    $currentBlockquote = new Node('blockquote', []);
                    $nodes[] = $currentBlockquote;
                }
                $currentBlockquote->content = array_merge(
                    $currentBlockquote->content,
                    $this->parseMarkdownToNodes($content)
                );
                continue;
            } else {
                $currentBlockquote = null;
            }

            // Headers
            if (preg_match('/^(#{1,6}) (.*)/', $line, $matches)) {
                $level = strlen($matches[1]);
                $content = $matches[2];
                $nodes[] = new Node('header', $content, ['level' => $level]);
                continue;
            }

            // Horizontal Rule
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', $line)) {
                $nodes[] = new Node('hr');
                continue;
            }

            // Footnotes
            if (preg_match('/^\[\^(\d+)\]:\s+(.*)$/', $line, $matches)) {
                $ref = $matches[1];
                $content = $matches[2];
                $this->footnotes[$ref] = $content;
                continue;
            }

            // Task List Items
            if (preg_match('/^\s*[-+*] \[( |x)\] (.*)/', $line, $matches)) {
                $isChecked = trim($matches[1]) === 'x';
                $content = $matches[2];
                $taskNode = new Node('taskItem', $this->parseMarkdownToNodes($content), ['checked' => $isChecked]);
                $this->handleList($nodes, $taskNode, 'ul');
                continue;
            }

            // Images
            if (preg_match('/!\[(.*?)\]\((.*?)\)/', $line, $matches)) {
                $alt = $matches[1];
                $src = $matches[2];
                $nodes[] = new Node('image', '', ['src' => $src, 'alt' => $alt]);
                continue;
            }

            // Inline HTML
            if (preg_match('/^\s*<[^>]+>/', $line)) {
                $nodes[] = new Node('html', $line);
                continue;
            }

            // Tables
            if (preg_match('/^\|(.+)\|$/', $line)) {
                $tableNode = new Node('table', []);
                $isFirstRow = true;

                while (preg_match('/^\|(.+)\|$/', $line, $tableRowMatches)) {
                    $cells = array_map('trim', explode('|', $tableRowMatches[1]));
                    if ($isFirstRow && preg_match('/^\|\s*[-=]+\s*\|(\s*[-=]+\s*\|)*$/', trim($lines[++$i] ?? ''))) {
                        $tableNode->content[] = new Node('tableRow', array_map(fn($cell) => new Node('tableHeaderCell', $cell), $cells));
                        $isFirstRow = false;
                    } else {
                        $tableNode->content[] = new Node('tableRow', array_map(fn($cell) => new Node('tableCell', $cell), $cells));
                    }
                    $line = $lines[++$i] ?? '';
                }
                $nodes[] = $tableNode;
                continue;
            }

            // Lists
            $listNode = $this->detectAndHandleList($line, $indentLevel);
            if ($listNode) {
                $this->handleList($nodes, $listNode, $listNode->attributes['type']);
                continue;
            }

            // Inline Styles and Links
            $parsedLine = $this->applyInlineStyles($line);

            // Paragraph
            if (trim($line)) {
                $nodes[] = new Node('paragraph', $parsedLine);
            }
        }

        $this->finalizeNodes($nodes);

        // Apply transformNode for each node
        foreach ($nodes as &$node) {
            $node = $this->pluginManager->applyNodeTransform($node);
        }

        return $nodes;
    }

    private function detectAndHandleList($line, $indentLevel) {
        // Check for ordered or unordered lists
        if (preg_match('/^\s*-\s+(.*)/', $line, $matches)) {
            return new Node('li', $this->parseMarkdownToNodes($matches[1]), ['type' => 'ul']);
        } elseif (preg_match('/^\s*[0-9]+\.\s+(.*)/', $line, $matches)) {
            return new Node('li', $this->parseMarkdownToNodes($matches[1]), ['type' => 'ol']);
        }
        return null;
    }

    private function handleList(&$nodes, $listNode, $type) {
        $lastNode = end($nodes);
        if ($lastNode && $lastNode->type === $type) {
            $lastNode->content[] = $listNode;
        } else {
            $nodes[] = new Node($type, [$listNode]);
        }
    }

    private function applyInlineStyles($line) {
        // Inline style regex patterns for bold, italics, links, etc.
        return preg_replace([
            '/\*\*(.*?)\*\*/',
            '/\*(.*?)\*/',
            '/`([^`]+)`/',
            '/!\[(.*?)\]\((.*?)\)/',
            '/\[(.*?)\]\((.*?)\)/'
        ], [
            '<strong>$1</strong>',
            '<em>$1</em>',
            '<code>$1</code>',
            '<img alt="$1" src="$2">',
            '<a href="$2">$1</a>'
        ], $line);
    }

    private function finalizeNodes(&$nodes) {
        if (!empty($this->footnotes)) {
            $footnoteSection = new Node('footnotes', array_map(fn($ref) => new Node('footnote', $this->footnotes[$ref], ['ref' => $ref]), array_keys($this->footnotes)));
            $nodes[] = $footnoteSection;
        }
    }
}


