<?php

namespace Orryv\Path\Enums;

enum OSFamily: string
{
    case WINDOWS = 'Windows';
    case UNIX = 'Unix';

    public function toString(): string
    {
        return $this->value;
    }
}