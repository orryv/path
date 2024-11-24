<?php

namespace Orryv\Path\Enums;

enum SystemPathLocationCategory: string
{
    /**
     *  File location (Windows and Unix)
     *   - Unix Prefix: /
     *   - Windows Prefix: C:\, D:\, E:\, ...
     */
    case LOCAL = 'Local storage';

    /**
     *  Network location (Windows)
     *   - Windows Prefix: \\*server*\
     */
    case NETWORK = 'Local network';

    public function toString(): string
    {
        return $this->value;
    }
}