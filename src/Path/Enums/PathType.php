<?php

namespace Orryv\Path\Enums;

enum PathType: string
{
    case FOLDER = 'folder';
    case FILE = 'file';
    case UNKNOWN = 'unknown';

    public function toString(): string
    {
        return $this->value;
    }
}