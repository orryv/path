<?php

namespace Orryv\Path;

use Orryv\Path\AbsolutePath;
// use Orryv\Path\Web\AbsoluteWebURI;
// use Orryv\Path\Generic\AbsoluteGenericURI;
use Orryv\Path\Paths\AbsoluteUnixPath;
// use Orryv\Path\Path\AbsoluteWindowsPath;
// use Orryv\Path\Path\AbsoluteWindowsNetworkPath;
use Orryv\Path\Exceptions\InvalidAbsoluteReferencePathFormatException;
use Orryv\Path\Models\AbsoluteReferencePathFormat;

/**
 * URIFactory to handle URIs, URLs and file/folder paths
 */
class Path {

    /**
     * Create an AbsoluteURI object from a path.
     * 
     * @param string $path Absolute path to a file or folder (any format)
     * 
     * @return AbsoluteURI
     */
    public static function create(AbsoluteReferencePathFormat $path): AbsolutePath
    {
        $path = self::validateUTF8($path);
        $path = self::normalizePath($path);
        $path = new AbsoluteReferencePathFormat($path);
        
        return match(true) {
            // self::isWebURI($path) => new AbsoluteWebURI($path),
            // self::isGenericURI($path) => new AbsoluteGenericURI($path),
            // self::isWindowsPath($path) => new AbsoluteWindowsPath($path),
            // self::isWindowsNetworkPath($path) => new AbsoluteWindowsNetworkPath($path),
            self::isUnixPath($path) => new AbsoluteUnixPath($path),
            default => throw new InvalidAbsoluteReferencePathFormatException('Unrecognized URI format')
        };
    }

    /**
     * Converts a path to a URI or Reference Path.
     * 
     * @param string $path Absolute path (any format)
     * 
     * @return string
     * @throws InvalidAbsoluteReferencePathFormatException If the path is empty
     */
    private static function normalizePath(string $path): string
    {
        // Trim leading/trailing whitespace
        $path = trim($path);

        if(empty($path)) {
            throw new InvalidAbsoluteReferencePathFormatException('Empty path');
        }

        $path = str_replace('\\', '/', $path);
        
        return $path;
    }

    /**
     * Validate the path as a UTF-8 string.
     * 
     * @param string $path Absolute path (any format)
     * 
     * @return string
     */
    private static function validateUTF8(string $path): string
    {
        // Check if the string is valid UTF-8
        if (!mb_check_encoding($path, 'UTF-8')) {
            throw new InvalidAbsoluteReferencePathFormatException('The path is not a valid UTF-8 string');
        }

        // Optionally, normalize the string to NFC (Normalization Form C)
        // $path = \Normalizer::normalize($path, \Normalizer::FORM_C);

        return $path;
    }

    /**
     * Check if the path is a HTTP/HTTPS URI.
     * 
     * Checks if the $path starts with "http" or "https".
     * 
     * @param AbsoluteReferencePathFormat $path Absolute path (URI or Reference Path)
     * 
     * @return bool
     */
    public static function isWebURI(AbsoluteReferencePathFormat $path): bool 
    {
        $regex = '/^https?:\/\//';
        
        return preg_match($regex, $path) === 1;
    }

    /**
     * Check if the path is a generic URI (any URI except HTTP/HTTPS).
     * 
     * Checks if the $path starts with a valid URI scheme
     *  And if the URI scheme is not HTTP or HTTPS.
     * 
     * @param AbsoluteReferencePathFormat $path Absolute path (URI or Reference Path)
     * 
     * @return bool
     */
    public static function isGenericURI(AbsoluteReferencePathFormat $path): bool 
    {
        $regex = '/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//';

        return preg_match($regex, $path) === 1
            && !self::isWebURI($path);
    }

    /**
     * Check if the path is a Windows drive path.
     * 
     * Checks if the $path starts with a letter followed by ":/" or ":\".
     * 
     * @param AbsoluteReferencePathFormat $path Absolute path (URI or Reference Path)
     * 
     * @return bool
     */
    public static function isWindowsPath(AbsoluteReferencePathFormat $path): bool 
    {
        $regex = '/^[a-zA-Z]:[\\\\\/]/';

        return preg_match($regex, $path) === 1;
    }

    /**
     * Check if the path is a Windows network path (UNC path).
     * 
     * Validates paths starting with "\\" or "//", followed by a valid host or server name,
     * and ending with a backslash or slash. Supports Unicode hostnames.
     * 
     * @param AbsoluteReferencePathFormat $path Absolute path (URI or Reference Path)
     * 
     * @return bool
     */
    public static function isWindowsNetworkPath(AbsoluteReferencePathFormat $path): bool 
    {
        $regex = '/^'.            // Start of string
                 '[\\\\\/]{2}'.   // Start with "\\" or "//"
                 '[^\s\\\\\/]+'.  // Valid hostname (non-empty, no slashes or spaces)
                 '[\\\\\/]/u';    // Followed by "\" or "/"

        return preg_match($regex, $path) === 1;
    }

    /**
     * Check if the path is a Unix-style path.
     * 
     * Validates paths starting with "/" but not "//".
     * 
     * @param AbsoluteReferencePathFormat $path Absolute path (URI or Reference Path)
     * 
     * @return bool
     */
    public static function isUnixPath(AbsoluteReferencePathFormat $path): bool 
    {
        return str_starts_with($path, '/')
            && (mb_strlen($path) === 1 || mb_substr($path, 1, 1) !== '/');
    }
}

?>