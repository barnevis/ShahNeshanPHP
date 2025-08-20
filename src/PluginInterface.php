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

interface PluginInterface {
  public function beforeParse($markdown);
  public function transformNode($node);
  public function afterRender($html);
}