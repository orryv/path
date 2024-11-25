<?php

namespace Orryv\Path\Models;

use Orryv\Path\Exceptions\InvalidAbsoluteReferencePathFormatException;
use Orryv\Path\Utils;

class AbsoluteReferencePathFormat
{
    const WINDOWS_DRIVE_REGEX = '/^(?:file:\/\/\/)?[a-zA-Z]:[\\\\\/]/'; // C:/ or C:\
    const WINDOWS_NETWORK_REGEX = '/^(?:file:\/\/|\\\\\\\\|\/\/)[^\s\\\\\/]+[\\\\\/]?/u';
    const UNIX_PATH_REGEX = '/^(?:file:\/\/\/)?[\/]/'; // /


    private string $path;
    private bool $preserve_end_slash;

    public function __construct(string $path, $preserve_end_slash = false)
    {
        $this->preserve_end_slash = $preserve_end_slash;
        $path = $this->normalize($path);

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

    /**
     * Converts any path to an absolute reference path.
     */
    private function normalize(string $path): string
    {
        // echo 'Input: ' . $path . PHP_EOL;

        $input_path = $path;
        $path = [];
        $prefix = '';
        if(preg_match(self::WINDOWS_DRIVE_REGEX, $input_path) === 1) {
            # Windows drive path (file:///C:/, C:/ or C:\)
            [$prefix, $path] = $this->extractWindowsDrivePath($input_path);
        } else if(preg_match(self::WINDOWS_NETWORK_REGEX, $input_path) === 1) {
            # Windows network path (file://host/, //host/ or \\host\)
            [$prefix, $path] = $this->extractWindowsNetworkPath($input_path);
        } else if(preg_match(self::UNIX_PATH_REGEX, $input_path) === 1) {
            # Unix path (file:/// or /)
            [$prefix, $path] = $this->extractUnixPath($input_path); 
        } else {
            throw new \Exception("Input path not recognized: $input_path");
        }

        // echo 'Path: ' . ($prefix . implode('/', $path)) . PHP_EOL;
        return (
            $prefix . 
            implode('/', $path) . 
            ( 
                // If we need to preserve the end slash AND the last element is not an empty string (would result in another trailing slash)
                (
                    $this->preserve_end_slash 
                    && isset($path[count($path) - 1]) 
                    && $path[count($path) - 1] !== ''
                ) 
                    ? '/' 
                    : '' 
            )
        );
    }

    private function extractWindowsDrivePath(string $input_path): array
    {
        if(substr($input_path, 0, 8) === 'file:///'){
            $path = Utils::splitPathAndTrimSlashes(substr($input_path, 11), $this->preserve_end_slash); // Remove "file:///C:/"
            $prefix = substr($input_path, 8, 2) . '/'; // Extract "C:"
        } else {
            $path = Utils::splitPathAndTrimSlashes(substr($input_path, 3), $this->preserve_end_slash); // Remove "C:/"
            $prefix = substr($input_path, 0, 2) . '/'; // Extract "C:"
        }

        return [$prefix, $path];
    }

    public function extractWindowsNetworkPath(string $input_path): array
    {
        if(substr($input_path, 0, 7) === 'file://'){
            $pos = strpos($input_path, '/', 7);
            if($pos === false){
                $pos = strpos($input_path, '\\', 7);
            }
            if($pos === false){
                throw new \Exception("Invalid widnows network path, format: (file://|//)host/, you likely missed the trailing slash.");
            }
            $path = Utils::splitPathAndTrimSlashes(substr($input_path, $pos + 1), $this->preserve_end_slash); // Remove "file://host/"
            $prefix = substr($input_path, 5, $pos + 1); // Extract "//host/"
        } else {
            $pos = strpos($input_path, '/', 2);
            if($pos === false){
                $pos = strpos($input_path, '\\', 2);
            }
            if($pos === false){
                throw new \Exception("Invalid widnows network path, format: (file://|//)host/, you likely missed the trailing slash.");
            }
            $path = Utils::splitPathAndTrimSlashes(substr($input_path, $pos + 1), $this->preserve_end_slash); // Remove "//"
            $prefix = substr($input_path, 0, $pos + 1); // Extract "//host/"
        }

        return [$prefix, $path];
    }

    public function extractUnixPath(string $input_path): array
    {
        if(substr($input_path, 0, 8) === 'file:///'){
            $path = Utils::splitPathAndTrimSlashes(substr($input_path, 8), $this->preserve_end_slash); // Remove "file:///"
            $prefix = '/';
        } else {
            $path = Utils::splitPathAndTrimSlashes(substr($input_path, 1), $this->preserve_end_slash); // Remove "/"
            $prefix = '/';
        }

        return [$prefix, $path];
    }

    public function __toString(): string
    {
        return $this->path;
    }
}