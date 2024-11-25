<?php

namespace Orryv\Path\Enums;

enum Encoder: string
{
    case RAWURLENCODE = 'rawurlencode';
    case URLENCODE = 'urlencode';
    case NONE = 'none';

    public function toString(): string
    {
        return $this->value;
    }
}