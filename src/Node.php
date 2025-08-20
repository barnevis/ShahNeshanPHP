<?php
declare(strict_types=1);

namespace ShahNeshan;

final class Node {
    public string $type;
    /** @var array<int,string|Node> */
    public array $content;
    /** @var array<string,mixed> */
    public array $attributes;

    public function __construct(string $type, $content = '', array $attributes = []) {
        $this->type = $type;
        $this->content = is_array($content) ? $content : [$content];
        $this->attributes = $attributes;
    }
}
