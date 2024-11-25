<?php

namespace Orryv\Path\Enums;

enum PathType: string
{
    case FOLDER = 'Folder';
    case FILE = 'File';
    case UNKNOWN = 'Unknown';

    public function toString(): string
    {
        return $this->value;
    }
}