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
