# Path v3 Overview

This document describes the current v3 API for the `orryv/path` library. It covers the
supported path formats, the available path objects, navigation helpers, query string
behaviour, Unicode handling, and the bundled `XString` sanitisation helpers that power the
filesystem encoding defaults outlined in `v3.0.md`.

## Path formats

All path objects render themselves into three canonical formats via `toString()`:

| `PathFormat` value        | Description |
| ------------------------- | ----------- |
| `PathFormat::ACCESS_PATH` | Native filesystem string with platform specific separators. |
| `PathFormat::REFERENCE_PATH` | Cross-platform logical representation that always uses `/` separators and keeps human-readable characters. |
| `PathFormat::ACCESS_URI`  | Percent-encoded URI (uses `rawurlencode` for path segments). |

Conversions are lossless. Creating a path from any supported format and converting back to
that same format returns the original spelling (see `Tests\Integration\RoundTripConversionTest`).

## Creating path instances

```php
use Orryv\Path;
use Orryv\Path\Enums\PathFormat;

$file = Path::file('C:/Projects/app/index.php', PathFormat::REFERENCE_PATH);
$dir  = Path::dir('\\\\server\\share\\', PathFormat::ACCESS_PATH);
$url  = Path::url('https://example.com/app', PathFormat::ACCESS_URI);
```

- `Path::file()` returns a `FilePath`.
- `Path::dir()` returns a `DirectoryPath` and keeps track of whether the input had a trailing
  slash so it can be preserved in renders.
- `Path::url()` normalises and parses URLs, including user-info, ports, queries, and fragments.

## Rendering and Windows long path support

All path objects share the following behaviour:

```php
$file->toString(PathFormat::ACCESS_PATH);       // Native path (e.g. C:\Projects\app\index.php)
$file->toString(PathFormat::REFERENCE_PATH);    // Slash-separated logical path
$file->toString(PathFormat::ACCESS_URI);        // file:/// URI with encoded segments
```

`FilePath`, `DirectoryPath`, and `UrlPath` extend a common `FilesystemPath` base that tracks
whether the path originated from a Windows drive, UNC share, POSIX root, or URI. The base class
also exposes:

- `withWindowsLongPathSupport(bool $enabled)` – preserves or toggles the long-path prefix style
  detected by the parser. When enabled the renderer re-applies the detected `\\\\?\\` or
  `\\\\?\UNC\` prefix if needed.

## Directory preservation and mutation

`DirectoryPath` instances remember if they ended with a separator. Use
`withPreserveEndSlash(false)` to render without a trailing slash while leaving the path data
untouched. `FilePath::withDirectory()` produces a new `FilePath` rooted beneath a different
`DirectoryPath` (or string). Origin checks ensure both locations share the same drive/share.

## Base directories and navigation

All filesystem paths understand relative navigation:

- `withBaseDir()` pins a path to a base directory. Attempts to `cd()` above that root throw an
  `OutOfBoundsException` (see `Tests\Integration\NavigationTest`).
- `cd($relative)` resolves `.` and `..` segments, collapses redundant separators, and honours UNC
  and drive roots. Passing an empty string to `FilePath::cd()` returns the parent directory.

## Relative operations

The `FilesystemOperations` helper powers several methods:

- `getRelativePathFrom($base, $format)`
- `getRelativePathTo($target, $format)`
- `getCommonBasePath($other, $format)`

These methods compare normalised segments and ensure both operands originate from the same
filesystem location. `DifferentOriginException` is thrown when drives, UNC shares, or hosts do
not match. See `Tests\Unit\RelativeOperationsTest` for usage examples.

## URL navigation and queries

`UrlPath` shares the `cd()`, `withBaseDir()`, `getRelativePathFrom()`, and
`getRelativePathTo()` APIs. Additional URL-specific behaviour includes:

- RFC 3986 compatible resolution of relative hrefs (tested in `Tests\Integration\UrlResolutionTest`).
- `withQuery(array|string|null $query)` – accepts associative arrays or query strings. Arrays
  are encoded using `http_build_query()` semantics with RFC 3986 encoding (`%20` for spaces).
- `withoutQuery($keys = null)` – removes specific keys or clears the entire query string.
- Fragments are dropped when navigating with `cd('')` to mirror browser behaviour.

`QueryString` automatically keeps both encoded and decoded representations so `toString()` can
return both human-readable reference URLs and percent-encoded access URIs (see
`Tests\Unit\QueryEncodingTest`).

## Unicode normalisation

Every segment is normalised to NFC using the `Unicode` support helper. Mixed composed/decomposed
inputs compare equal, and retrieved reference paths maintain the canonical form encountered first
(`Tests\Integration\UnicodeNormalizationTest`).

## Safe path helpers (`Orryv\XString`)

The bundled `Orryv\XString` utility implements the sanitisation primitives referenced in
`v3.0.md`:

- `XString::toSafePath($value)` – collapse separators to `/`, neutralise reserved device names,
  trim trailing dots/spaces, and replace empty segments with `_`.
- `XString::encodeSafePath($value, bool $doubleEncode = false)` – percent-encode each segment
  according to Windows filesystem rules while preserving `/` separators.
- `XString::decodeSafePath($value)` – reverse `encodeSafePath()` and restore literal characters.
- `XString::encodeSafeFileName()` / `decodeSafeFileName()` and the corresponding folder helpers
  operate on individual segments.

These helpers make it straightforward to convert between raw access paths, safe reference paths,
and URI representations while respecting the constraints listed in the v3 concept document.

## Running the test suite

Install dependencies and execute the PHPUnit suite:

```bash
composer install
composer test
```

The tests cover round-trip conversions between formats, UNC handling, base directory enforcement,
query encoding rules, Unicode normalisation, and the new `XString` helpers.
