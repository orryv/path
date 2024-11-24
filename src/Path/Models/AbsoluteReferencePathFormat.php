<?php

namespace Orryv\Path\Models;

use Orryv\Path\Exceptions\InvalidAbsoluteReferencePathFormatException;

class AbsoluteReferencePathFormat
{
    private string $path;

    public function __construct(string $path)
    {
        if(strpos($path, "\\") !== false){
            throw new \Exception("Invalid (absolute) reference path: $path");
        } else if(
            !preg_match("/^[a-zA-Z]:[\/]/", $path) &&
            substr($path, 0, 1) != "/" // for both / (Unix) and // (Windows network)
        ){
            throw new InvalidAbsoluteReferencePathFormatException("Invalid (absolute) ReferencePath: $path");
        }
        
        $this->path = $path;
    }

    public function __toString(): string
    {
        return $this->path;
    }
}