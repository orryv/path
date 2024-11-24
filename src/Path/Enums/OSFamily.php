<?php

namespace Orryv\Path\Enums;

enum OSFamily: string
{
    case WINDOWS = 'windows';
    case UNIX = 'unix';

    public function toString(): string
    {
        return $this->value;
    }
}