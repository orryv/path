<?php

namespace Orryv\Path\Exceptions;

use Exception;

class FileNotFoundException extends Exception {
    public function __construct(string $uri) {
        parent::__construct("Invalid Path: $uri");
    }
}