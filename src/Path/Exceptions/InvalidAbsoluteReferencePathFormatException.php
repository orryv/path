<?php

namespace Orryv\Path\Exceptions;

use Exception;

class InvalidAbsoluteReferencePathFormatException extends Exception {
    public function __construct(string $uri) {
        parent::__construct("Invalid Path: $uri");
    }
}