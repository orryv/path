<?php

namespace Orryv\Path\Exceptions;

use Exception;

class UnknownIfFolderOrFileException extends Exception {
    public function __construct(string $text) {
        parent::__construct($text);
    }
}