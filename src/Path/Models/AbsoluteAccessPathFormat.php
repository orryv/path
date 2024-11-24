<?php

namespace Orryv\Path\Models;

use Orryv\Path\Exceptions\InvalidAbsoluteAccessPathFormatException;

class AbsoluteAccessPathFormat
{
    private string $path;

    public function __construct(string $path)
    {
        if(
            !preg_match("/^[a-zA-Z]:[\\\\]/", $path) // Windows drive path
            && !preg_match("/^[\\\\]{2}[a-zA-Z0-9]+/", $path) // Windows network path
            && substr($path, 0, 1) !== "/" // Unix
        ){
            throw new InvalidAbsoluteAccessPathFormatException("Invalid (absolute) AccessPath: $path");
        }

        $this->path = $path;
    }

    public function __toString(): string
    {
        return $this->path;
    }
}