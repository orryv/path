<?php

namespace Orryv\Path\Models;

use Orryv\Path\Exceptions\InvalidAbsoluteAccessURIFormatException;

class AbsoluteAccessURIFormat
{
    private string $path;

    public function __construct(string $path)
    {
        // regex pattern to match [a-zA-Z]:// (URI)
        $uri_regex = "/^[a-zA-Z]+:[\/\/]/";

        if(!preg_match($uri_regex, $path) || strpos($path, "\\") !== false || strpos($path, " ") !== false){
            throw new InvalidAbsoluteAccessURIFormatException("Invalid (absolute) AccessURI: $path");
        }

        $this->path = $path;
    }

    public function __toString(): string
    {
        return $this->path;
    }
}