# Path
 Handles URI, URL, and file/folder paths. There are some @TODOs in the code, but it's working fine.

## TODO
- [x] Implement asDot() which resolves to asFile() or asFolder() depending if there is a dot in the last part of the path (after last / but before ? and #). helpful for html hrefs.
- [x] Implement preserveEndSlash() properly.
- [x] Implement getNthFolder(), getFirstFolder(), getLastFolder() and find a way to deal with what it returns (accessPath, referencePath, accessURI)
- [ ] Other URIs probably parse ? and # when they shouldn't... (so it would be nice to have one method to parse them all, where we only pass the path (and $parseQuery, $parseFragment, and encoding) and it returns the parts)
- [ ] Fix `AbsoluteURIPath::parseData()` so that usernames and passwords are extracted before the host string is overwritten.
- [ ] Ensure `withHost()` and `withHostString()` recompute the root folder for URI paths so `getReferencePath()` reflects host changes.
- [ ] Relax `AbsoluteAccessURIFormat` validation so schemes containing digits, plus signs or dots remain valid.
- [ ] Add regression tests that cover credential parsing, host mutations, and other URI edge cases.
- [ ] Add dedicated tests for the folder helper accessors (`getNthFolder()`, `getFirstFolder()`, `getLastFolder()`, `getFolderCount()`).
- [ ] Check if we use monolog? if not remove it from composer.json
- [ ] add script to composer.json to run phpunit tests: "test": "php ./vendor/bin/phpunit", test:unit and test:integration
- [ ] add docblock everywhere
- [ ] make sure all relevant methods are included in readme and vice versa
- [ ] make sure readme is up to date and has no typos and is easy to read and understand and is comprehensive.

## Improvements
- Prefer a single `guessPathType()`/`determinePathType()` helper that returns the existing `PathType` enum over adding separate `isFile()`, `isFolder()`, and `isDot()` booleans; it would leverage the tracked `$path_type` state and avoid inconsistent heuristics.
- Consider injecting a pluggable segment classifier that inspects the normalized reference path array (for example via `Utils::splitPathAndTrimSlashes()`) to decide whether a final token represents a file, folder, or dot entry, making custom rules easier to maintain.
- Introduce a small strategy or service that encapsulates root-folder recomposition after changes to scheme, host, credentials, or port so all mutators stay in sync.
- Split encoding responsibilities into dedicated helpers so URI schemes and filesystem paths can opt into the right encoder without keeping TODO comments inside core methods.

## v2.1
- Support relative path inputs by introducing a dedicated `RelativePath` format or extending `AbsoluteReferencePathFormat` with a base-path context instead of throwing an exception for non-absolute strings.
- Expand scheme handling to cover providers such as `s3://` or `git+ssh://`, including validation updates and encoding presets for less common protocols.
- Provide chainable helpers that wrap host, credential, and port mutations to keep derived access/reference outputs synchronized automatically.
- Offer richer query manipulation utilities (e.g., typed key/value objects or merge helpers) on top of the existing array-based query handling to simplify complex URI modifications.

## Objects
### Path factory
`Orryv\Path::create()` normalizes a string (including validation) into an `AbsoluteReferencePathFormat` and returns the correct concrete path implementation (URI, Unix, Windows drive, or Windows network) based on the detected scheme or prefix. Use it whenever you start from an unknown absolute path and want an immutable object with navigation helpers.

### AbsolutePath
`AbsolutePath` is the base class for all path types. It tracks the current `PathType`, directory separator, scheme, host, and normalized path tokens while exposing immutable helpers such as `asFile()`, `asFolder()`, `cd()`, `setBasePath()`, and the access/reference getters. Create paths through the factory and call `asFile()`/`asFolder()` to set intent before navigating or reading folder metadata.

```php
$path = Path::create('C:/projects/demo/file.txt')
    ->asFile();

$path->getAccessURI();      // file:///C:/projects/demo/file.txt
$path->getReferencePath();  // C:/projects/demo/file.txt
```

### AbsoluteSystemPath
`AbsoluteSystemPath` specializes `AbsolutePath` for filesystem locations. It records the operating system family and location category (local vs. network), splits filenames into name/extension, and provides encoded access-URI/access-path outputs using the correct directory separator. It powers both Unix (`AbsoluteUnixPath`) and Windows variants (`AbsoluteWindowsPath`, `AbsoluteWindowsNetworkPath`).

### AbsoluteURIPath
`AbsoluteURIPath` adds URI-specific behavior: it parses usernames, passwords, ports, query strings, and fragments; offers immutable mutators (`withUsername()`, `withPassword()`, `withQuery()`, `rmQuery()`, `withFragment()`, etc.); and rebuilds reference/access strings with the right encoding strategy. Use it for HTTP(S), FTP/SFTP, and other scheme-based resources where query manipulation or credential management matters.

```php
$url = Path::create('https://user:old@example.com/path/file.txt?debug=1')
    ->asFile()
    ->rmQuery()
    ->withPassword('secret')
    ->cd('images/logo.png');

$url->getAccessURI();
```

### Format value objects
`AbsoluteReferencePathFormat`, `AbsoluteAccessPathFormat`, and `AbsoluteAccessURIFormat` validate and encapsulate normalized strings for their respective contexts. They centralize parsing logic for different platforms/schemes and ensure only well-formed absolute paths flow through the rest of the API.

### Enumerations
The enums `PathType`, `Encoder`, `OSFamily`, and `SystemPathLocationCategory` describe key aspects of a path—whether it targets a file or folder, which encoding to apply, the operating-system family, and whether the location is local or networked. They keep method signatures expressive and make it easier to branch logic without magic strings.

# Usage

IMPORTANT: all methods are immutable, they return a new instance of the object. (except for get methods, duh.)

```php
use Orryv\Path

$path = Path::create('C:/path/to/file.txt');
// OR
$path = Path::create('file:///C:/path/to/file.txt');
// OR
$path = Path::create('https://website.com/path/to/page?query=string#fragment');
// OR (any URI)
$path = Path::create('ftp://path/to/file.txt');

// Return the path
$path->getReferencePath();
$path->getAccessPath();
$path->getAccessURI();

// Navigation
// Make sure to set it as file or as folder first:
$path = $path->asFile();
$path = $path->asFolder();
$path = $path->cd('path/to/another/folder');
```

### Usage with html

```php
use Orryv\Path
// when you find a href on a page
$url = Path::create('https://website.com/path/to/page?query=string#fragment') // current page
    ->asFile() // set current page as a file
    ->rmQuery() // remove query from the url
    ->rmFragment() // remove fragment from the url
    ->cd('path/to/another/file.jpg'); // href
```

### Usage with a base path
A base path means cd can't go above it.

```php
use Orryv\Path

$base_folder = Path::create('C:/path/to/folder')
    ->asFolder();

$path = Path::create('C:/path/to/folder/file.txt')
    ->asFile()
    ->setBasePath($base_folder)
    ->cd('..'); // will throw an error
```

### Encoding
In default, system paths are rawurlencoded for AccessURI. URIs (http://, ftp://, etc) are not DECODED. But you can change it if you want.

```php
use Orryv\Path
use Orryv\Path\Enums\Encoder;

// This will only affect AccessURI
$path = Path::create('C:/path/to/file#.txt')
    ->asFile()
    ->setEncoding(Encoder::RAWURLENCODE); // this will ENCODE the # when getting AccessURI()

// For URIs (except file://) it will only affect ReferencePath
$path = Path::create('https://website.com/path/to/page?query=string#fragment')
    ->asFile()
    ->setEncoding(Encoder::URLENCODE); // this will DECODE the # when getting ReferencePath()
```


# Glossary

## Output types

- **AccessPath**: To be used in PHP functions. Used to access a path in PHP file functions, or curl if the input was an URL (or any URI not starting with file://). Examples:
    - `/path/to/unix/file.txt`
    - `C:\path\to\windows\file.txt`
    - `\\\\serverName\path\to\windows\network\share\file.txt`
    - `https://website.com/path/to/page?query=string#fragment`
    - `ftp://path/to/file.txt`
    
- **AccessURI**: Always returns an URI. Returns the URI of a path, if a file/folder path was given, it will return the file:// URI. Examples:
    - `file:///path/to/unix/file.txt`
    - `file:///C:/path/to/windows/file.txt`
    - `file://serverName/path/to/windows/network/share/file.txt`
    - `https://website.com/path/to/page?query=string#fragment`
    - `ftp://path/to/file.txt`

- **ReferencePath**: Returns the access path but with forward slashes. Used to make paths uniform, it will never have backslashes (windows paths). Examples:
    - `/path/to/unix/file.txt`
    - `C:/path/to/windows/file.txt`
    - `//serverName/path/to/windows/network/share/file.txt`
    - `https://website.com/path/to/page?query=string#fragment`
    - `ftp://path/to/file.txt`

## Directory & Files
- **Path**: The input `$path` (file or folder.) Must be a ReferencePath. It's like the "working directory" but also applies to files.
- **Root Folder**: The folder at the root of a path. It is the first folder in the path. Examples:
    - `/path/to/unix/file.txt` => `/`
    - `C:\path\to\windows\file.txt` => `C:\`
    - `\\\\serverName\path\to\windows\network\share\file.txt` => `\\\\serverName\`
    - `https://website.com/path/to/page?query=string#fragment` => `https://website.com/`
    - `ftp://path/to/file.txt` => `ftp://` OR `ftp://user@host/`

- **Base Path/Folder**: The folder used to indicate where `cd()` can't go above.

Note: While you could asume that relative paths are relative to the Base Folder, IT IS NOT. Relative paths are relative to `$path`. The Base Folder is only used to set a hard limit to the `cd()` method.

# Dev

Run tests

```bash
php ./vendor/bin/phpunit tests/Integration
```
