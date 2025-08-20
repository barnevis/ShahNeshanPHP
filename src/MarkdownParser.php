<?php
declare(strict_types=1);

namespace ShahNeshan;

class MarkdownParser {
    private $config;
    private $pluginManager;
    private $footnotes = [];

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

    public function parseMarkdown($markdown) {
        $nodes = $this->markdownToNodes($markdown);
        $this->finalizeNodes($nodes);
        // plugin node transforms already applied inside markdownToNodes
        return $nodes;
    }

    private function markdownToNodes($markdown) {
        // Plugins: before-parse
        $markdown = $this->pluginManager->applyBeforeParse($markdown);

        $lines = explode("\n", $markdown);
        $nodes = [];
        $listStack = [];
        $codeBlock = false;
        $codeLang = '';
        $persianBlock = false;
        $persianCode = '';
        $currentBlockquote = null;
        $currentIndentLevel = 0;

        // Persian code keywords mapping (to match JS)
        $codeMap = [
            "شعر"   => "poet",
            "شعرنو" => "poet",
            "توجه"  => "note",
            "نکته"  => "tip",
            "مهم"   => "important",
            "هشدار" => "warning",
            "احتیاط" => "caution",
        ];
        $alertCodes = ["note","tip","important","warning","caution"];

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $indentLevel = strlen($line) - strlen(ltrim($line));

            // Fenced code blocks
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

            // Persian custom blocks starting with ...
            if (preg_match('/^\.{3}/u', $line)) {
                if (!$persianBlock) {
                    $persianBlock = true;
                    $raw = trim(mb_substr($line, 3));
                    $persianCode = $codeMap[$raw] ?? $raw;
                    $nodes[] = new Node('persianBlock', [], ['code' => $persianCode]);
                } else {
                    // Close persian block
                    $persianBlock = false;
                    $persianCode = '';
                }
                continue;
            }
            if ($persianBlock) {
                if ($persianCode === 'poet') {
                    $cells = array_map('trim', explode(' -- ', $line));
                    $row = new Node(
                        'poetRow',
                        array_values(array_filter(array_map(
                            fn($cell) => $cell === '' ? null : new Node('poetCell', $cell),
                            $cells
                        )))
                    );
                    $nodes[count($nodes)-1]->content[] = $row;
                } elseif (in_array($persianCode, $alertCodes, true)) {
                    // Parse inline markdown inside alert blocks
                    $nodes[count($nodes)-1]->content[] = new Node($persianCode, $this->markdownToNodes($line));
                } else {
                    // Unknown code → generic nested node
                    $nodes[count($nodes)-1]->content[] = new Node($persianCode, $line);
                }
                continue;
            }

            // Blockquotes (single leading `> ` per line; recursion gives nesting)
            if (preg_match('/^> (.*)/', $line, $m)) {
                $content = trim($m[1]);
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

            // ATX headers
            if (preg_match('/^(#{1,6}) (.*)/', $line, $m)) {
                $level = strlen($m[1]);
                $raw   = $m[2];
                $inline = $this->applyInlineStyles($raw);
                $clean  = $this->stripInlineMarkdown($raw);
                $nodes[] = new Node('header', $inline, ['level' => $level, 'raw' => $clean]);
                continue;
            }

            // Horizontal rule
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', trim($line))) {
                $nodes[] = new Node('hr');
                continue;
            }

            // Footnote definitions
            if (preg_match('/^\[\^(\d+)\]:\s+(.*)$/', $line, $m)) {
                $ref = $m[1];
                $content = $m[2];
                $this->footnotes[$ref] = $content;
                continue;
            }

            // Task list items
            if (preg_match('/^\s*[-+*] \[( |x)\] (.*)/', $line, $m)) {
                $isChecked = $m[1] === 'x';
                $txt = $m[2];
                $isRTL = $this->startsWithRTL($line);
                $taskNode = new Node('taskItem', $this->markdownToNodes($txt), ['checked' => $isChecked, 'isRTL' => $isRTL]);
                if (empty($listStack) || end($listStack)->type !== 'ul') {
                    $listNode = new Node('ul', [], ['isRTL' => $isRTL]);
                    $nodes[] = $listNode;
                    $listStack[] = $listNode;
                }
                end($listStack)->content[] = $taskNode;
                continue;
            }

            // Images (inline)
            if (preg_match('/!\[(.*?)\]\((.*?)\)/', $line, $m)) {
                $nodes[] = new Node('image', '', ['src' => $m[2], 'alt' => $m[1]]);
                continue;
            }

            // Inline HTML passthrough
            if (preg_match('/^\s*<[^>]+>/', $line)) {
                $nodes[] = new Node('html', $line);
                continue;
            }

            // GitHub-style tables with optional alignment row
            if (preg_match('/^\|\s*(.+)\s*\|\s*$/', $line)) {
                $rawRows = [];
                $j = $i;
                while ($j < count($lines) && preg_match('/^\|\s*(.+)\s*\|\s*$/', $lines[$j])) {
                    $rawRows[] = $lines[$j];
                    $j++;
                }

                // Detect alignment spec on second row
                $alignments = [];
                if (count($rawRows) >= 2 && preg_match('/^\|\s*[:\-]+(?:\s*\|\s*[:\-]+)*\s*\|\s*$/', trim($rawRows[1]))) {
                    $spec = trim($rawRows[1]);
                    $parts = array_map('trim', explode('|', mb_substr($spec, 1, -1)));
                    foreach ($parts as $p) {
                        $left  = str_starts_with($p, ':');
                        $right = str_ends_with($p, ':');
                        if ($left && $right) $alignments[] = 'center';
                        elseif ($right)      $alignments[] = 'right';
                        else                 $alignments[] = 'left';
                    }
                    array_splice($rawRows, 1, 1); // remove spec line
                }

                $tableNode = new Node('table', []);
                foreach ($rawRows as $rowIdx => $rowText) {
                    preg_match('/^\|\s*(.+)\s*\|\s*$/', $rowText, $mm);
                    $cells = array_map('trim', explode('|', $mm[1]));
                    $isHeader = ($rowIdx === 0 && count($alignments) > 0);

                    $cellNodes = [];
                    foreach ($cells as $colIdx => $cellText) {
                        $align = $alignments[$colIdx] ?? null;
                        $parsed = $this->markdownToNodes($cellText);
                        $cellNodes[] = $isHeader
                            ? new Node('tableHeaderCell', $parsed, $align ? ['align' => $align] : [])
                            : new Node('tableCell', $parsed, $align ? ['align' => $align] : []);
                    }
                    $tableNode->content[] = new Node('tableRow', $cellNodes);
                }

                $nodes[] = $tableNode;
                $i = $j - 1; // consume rows
                continue;
            }

            // Unordered lists (- or *)
            if (preg_match('/^\s*[-*]\s(.*)/', $line, $m)) {
                $txt = $m[1];
                $isRTL = $this->startsWithRTL($line);
                $li = new Node('li', $this->markdownToNodes($txt), ['isRTL' => $isRTL]);

                if ($indentLevel > $currentIndentLevel) {
                    $listNode = new Node('ul', [], ['isRTL' => $isRTL]);
                    if (!empty($listStack)) {
                        end($listStack)->content[] = $listNode;
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

                if (empty($listStack) || end($listStack)->type !== 'ul') {
                    $listNode = new Node('ul', [], ['isRTL' => $isRTL]);
                    $nodes[] = $listNode;
                    $listStack[] = $listNode;
                }
                end($listStack)->content[] = $li;
                $currentIndentLevel = $indentLevel;
                continue;
            }

            // Ordered lists (Arabic/Persian digits too)
            if (preg_match('/^\s*[\p{N}]+\.\s(.*)/u', $line, $m)) {
                $txt = $m[1];
                $isRTL = $this->startsWithRTL($line);
                $li = new Node('li', $this->markdownToNodes($txt), ['isRTL' => $isRTL]);

                if ($indentLevel > $currentIndentLevel) {
                    $listNode = new Node('ol', [], ['isRTL' => $isRTL]);
                    if (!empty($listStack)) {
                        end($listStack)->content[] = $listNode;
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

                if (empty($listStack) || end($listStack)->type !== 'ol') {
                    $listNode = new Node('ol', [], ['isRTL' => $isRTL]);
                    $nodes[] = $listNode;
                    $listStack[] = $listNode;
                }
                end($listStack)->content[] = $li;
                $currentIndentLevel = $indentLevel;
                continue;
            }

            // Close any open lists when we hit a non-list line
            while (!empty($listStack)) array_pop($listStack);

            // Paragraph / plain line (after inline formatting)
            if (trim($line) !== '') {
                $nodes[] = new Node('paragraph', $this->applyInlineStyles($line));
            }
        }

        // Plugin node transforms
        foreach ($nodes as &$n) {
            $n = $this->pluginManager->applyNodeTransform($n);
        }

        return $nodes;
    }

    // === Inline formatting (keeps plain-URL autolinks out of `code`) ===
    private function applyInlineStyles($line) {
        // 1) Code spans – escape HTML inside
        $line = preg_replace_callback('/`([^`]+?)`/', function($m) {
            return '<code>' . htmlspecialchars($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>';
        }, $line);

        // 2) Bold, italics, strike, highlight, sub/sup
        $line = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $line);
        $line = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $line);
        $line = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $line);
        $line = preg_replace('/==(.+?)==/', '<mark>$1</mark>', $line);
        $line = preg_replace('/~(.+?)~/', '<sub>$1</sub>', $line);
        $line = preg_replace('/\^(.+?)\^/', '<sup>$1</sup>', $line);

        // 3) Images and explicit links
        $line = preg_replace('/!\[(.*?)\]\((.*?)\)/', '<img alt="$1" src="$2">', $line);
        $line = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $line);

        // 4) Footnote refs
        $line = preg_replace('/\[\^(\d+)\]/', "<sup id='footnote-ref-$1'><a href='#footnote-$1'>[$1]</a></sup>", $line);

        // 5) Autolink bare URLs not inside backticks
        $line = preg_replace('/(?<!`)https?:\/\/[^\s`]+(?!`)/u', '<a href="$0" target="_blank">$0</a>', $line);

        return $line;
    }

    // Strip inline MD to make a slug/id for headers
    private function stripInlineMarkdown($str) {
        $s = $str;
        // un-escape \*
        $s = preg_replace('/\\\([\\\`*_\{\}\[\]\(\)#\+\-.!>])/', '$1', $s);
        // remove images
        $s = preg_replace('/!\[.*?\]\(.*?\)/', '', $s);
        // links → text
        $s = preg_replace('/\[([^\]]+?)\]\(.*?\)/', '$1', $s);
        // bold/italics/code/strike/highlight
        $s = preg_replace('/\*\*(.+?)\*\*/', '$1', $s);
        $s = preg_replace('/__(.+?)__/', '$1', $s);
        $s = preg_replace('/\*(.+?)\*/', '$1', $s);
        $s = preg_replace('/_(.+?)_/', '$1', $s);
        $s = preg_replace('/~~(.+?)~~/', '$1', $s);
        $s = preg_replace('/==(.+?)==/', '$1', $s);
        $s = preg_replace('/`([^`]+?)`/', '$1', $s);
        $s = preg_replace('/\[\^\d+\]/', '', $s);
        $s = preg_replace('/^\s*>+\s?/m', '', $s);
        $s = preg_replace('/[*_~`>#\[\]\(\)\-!]/', '', $s);
        $s = preg_replace('/\s{2,}/', ' ', $s);
        return trim($s);
    }

    private function startsWithRTL($line) {
        // Detect if the content after marker starts with RTL script char
        return (bool)preg_match('/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}]/u', $line);
    }

    private function finalizeNodes(&$nodes) {
        if (!empty($this->footnotes)) {
            $items = [];
            foreach ($this->footnotes as $ref => $txt) {
                $items[] = new Node('footnote', $txt, ['ref' => $ref]);
            }
            $nodes[] = new Node('footnotes', $items);
        }
    }
}
