# Path
 Handles URI, URL, and file/folder paths

 # TODO

 - create setBaseFolder method to work with cd()

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
    - `ftp://path/to/file.txt` => `ftp://`

- **Base Folder**: The folder used to indicate where `cd()` can't go above.

Note: While you could asume that relative paths are relative to the Base Folder, IT IS NOT. Relative paths are relative to `$path`. The Base Folder is only used to set a hard limit to the `cd()` method.

# Dev

Run tests

```bash
php ./vendor/bin/phpunit tests/Integration/AbsoluteURI/FileUnixTest.php
```