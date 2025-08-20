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

    public function parseMarkdown($markdown) {
        $nodes = [];
        $nodes = $this->markdownToNodes($markdown);
        $this->finalizeNodes($nodes);
        return $nodes;
    }

    public function markdownToNodes($markdown) {
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

        for ($i = 0; $i < count($lines); $i++) { 
            $line = $lines[$i];
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
                $row = new Node('poetRow', array_filter( array_map(fn($cell) => $cell ? new Node('poetCell', $cell) : null, $cells) ));
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
                    $this->markdownToNodes($content)
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

            // Task List
            if (preg_match('/^\s*[-+*] \[( |x)\] (.*)/', $line, $taskMatch)) {
                $isChecked = $taskMatch[1] === 'x';
                $content = $taskMatch[2];
                $taskNode = new Node('taskItem', $this->markdownToNodes($content), ['checked' => $isChecked]);
                if (empty($listStack) || $listStack[count($listStack) - 1]->type !== 'ul') {
                    $listNode = new Node('ul', []);
                    $nodes[] = $listNode;
                    $listStack[] = $listNode;
                }
                $listStack[count($listStack) - 1]->content[] = $taskNode;
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
            
                while (preg_match('/^\|(.+)\|$/', $line, $tableRowMatch)) {
                    $cells = array_map('trim', explode('|', $tableRowMatch[1]));
            
                    if ($isFirstRow) {
                        $isFirstRow = false;
                        $nextLine = trim($lines[$i+1] ?? '');
                        // Check for header underline (like '--' or '==')
                        if (preg_match('/^\|\s*[-=]+\s*\|(\s*[-=]+\s*\|)*$/', $nextLine)) {
                            // Convert the first row's cells to header cells if underline is detected
                            $tableRow = new Node('tableRow', array_map(fn($cell) => new Node('tableHeaderCell', $cell), $cells));
                            $line = $lines[++$i] ?? ''; // Skip the underline line
                        } else {
                            // Treat the first row as a regular row if there's no underline
                            $tableRow = new Node('tableRow', array_map(fn($cell) => new Node('tableCell', $cell), $cells));
                        }
                    } else {
                        // Add regular table row (not the first row)
                        $tableRow = new Node('tableRow', array_map(fn($cell) => new Node('tableCell', $cell), $cells));
                    }
            
                    // Add the constructed row to the tableNode content
                    $tableNode->content[] = $tableRow;
            
                    // Advance to the next line
                    $line = $lines[++$i] ?? '';
                }
            
                // Add the complete table node to nodes array
                $nodes[] = $tableNode;
                continue;
            }

            // Detect unordered lists
            if (preg_match('/^\s*-\s(.*)/', $line, $matches)) {
                $content = trim($matches[1]);
                $listItemNode = new Node('li', $this->markdownToNodes($content));
    
                // Handle nesting for unordered list
                if ($indentLevel > $currentIndentLevel) {
                    $listNode = new Node('ul', []);
                    if (!empty($listStack)) {
                        $listStack[count($listStack) - 1]->content[] = $listNode;
                    } else {
                        $nodes[] = $listNode;
                    }
                    $listStack[] = $listNode;
                } elseif ($indentLevel < $currentIndentLevel) {
                    while (!empty($listStack) && $indentLevel < $currentIndentLevel) {
                        array_pop($listStack);
                        $currentIndentLevel -= 4;
                    }
                }
    
                // Add list item to the current list
                if (empty($listStack) || end($listStack)->type !== 'ul') {
                    $listNode = new Node('ul', []);
                    $nodes[] = $listNode;
                    $listStack[] = $listNode;
                }
                end($listStack)->content[] = $listItemNode;
                $currentIndentLevel = $indentLevel;
                continue; // Skip to the next line
            }

            // Detect ordered lists (including Persian/Arabic numerals)
            if (preg_match('/^\s*[\p{N}]+\.\s(.*)/u', $line, $matches)) {
                $content = trim($matches[1]);
                $listItemNode = new Node('li', $this->markdownToNodes($content));
    
                // Handle nesting for unordered list
                if ($indentLevel > $currentIndentLevel) {
                    $listNode = new Node('ol', []);
                    if (!empty($listStack)) {
                        $listStack[count($listStack) - 1]->content[] = $listNode;
                    } else {
                        $nodes[] = $listNode;
                    }
                    $listStack[] = $listNode;
                } elseif ($indentLevel < $currentIndentLevel) {
                    while (!empty($listStack) && $indentLevel < $currentIndentLevel) {
                        array_pop($listStack);
                        $currentIndentLevel -= 4;
                    }
                }
    
                // Add list item to the current list
                if (empty($listStack) || end($listStack)->type !== 'ol') {
                    $listNode = new Node('ol', []);
                    $nodes[] = $listNode;
                    $listStack[] = $listNode;
                }
                end($listStack)->content[] = $listItemNode;
                $currentIndentLevel = $indentLevel;
                continue; // Skip to the next line
            } 
            
            while (!empty($listStack)) {
                array_pop($listStack);
            }


            // // Reset indentation level if no list item is found
            // $currentIndentLevel = 0;
            // $listStack = []; // Reset the list stack if the line is not a list item


            // Inline Styles and Links
            $parsedLine = $this->applyInlineStyles($line);

            // Paragraph
            if (trim($line)) {
                $nodes[] = new Node('paragraph', $parsedLine);
            }
        }

        
        // Apply transformNode for each node
        foreach ($nodes as &$node) {
            $node = $this->pluginManager->applyNodeTransform($node);
        }

        // $this->finalizeNodes($nodes);


        return $nodes;
    }


    private function applyInlineStyles($line) {
        // Inline style regex patterns for bold, italics, links, etc.
        return preg_replace([
            '/\*\*(.*?)\*\*/',
            '/\*(.*?)\*/',
            '/`([^`]+)`/',
            '/!\[(.*?)\]\((.*?)\)/',
            '/\[(.*?)\]\((.*?)\)/',
            '/\[\^(\d+)\]/',
            '/\^(.+?)\^/',
            '/~~(.+?)~~/',
            '/~(.+?)~/',
            '/==(.+?)==/',
        ], [
            '<strong>$1</strong>',
            '<em>$1</em>',
            '<code>$1</code>',
            '<img alt="$1" src="$2">',
            '<a href="$2">$1</a>',
            "<sup id='footnote-ref-$1'><a href='#footnote-$1'>[$1]</a></sup>",
            '<sup>$1</sup>',
            '<del>$1</del>',
            '<sub>$1</sub>',
            '<mark>$1</mark>'
        ], $line);
    }

    private function finalizeNodes(&$nodes) {
        if (!empty($this->footnotes)) {
            $footnoteSection = new Node('footnotes', array_map(fn($ref) => new Node('footnote', $this->footnotes[$ref], ['ref' => $ref]), array_keys($this->footnotes)));
            $nodes[] = $footnoteSection;
        }
    }
}


