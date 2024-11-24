<?php

namespace Orryv\Path\Exceptions;

use Exception;

class AboveBaseFolderException extends Exception {
    public function __construct(string $text) {
        parent::__construct($text);
    }
}