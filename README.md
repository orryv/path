# Path
 Handles URI, URL, and file/folder paths. There are some @TODOs in the code, but it's working fine.

# Usage

IMPORTANT: all methods are immutable, they return a new instance of the object. (except for get methods, duh.)

```php
use Path\Path;

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
use Path\Path;
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
use Path\Path;

$base_folder = Path::create('C:/path/to/folder')
    ->asFolder();

$path = Path::create('C:/path/to/folder/file.txt')
    ->asFile()
    ->setBasePath($base_folder)
    ->cd('..'); // will throw an error
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