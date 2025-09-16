<?php

namespace Orryv\Path;

use Orryv\Path\Exceptions\UnknownIfFolderOrFileException;
use Orryv\Path\Exceptions\AboveBaseFolderException;
use Orryv\Path\Enums\PathType;
use Orryv\Path\Enums\Encoder;
use Orryv\Path\Models\AbsoluteReferencePathFormat;
use Orryv\Path\Models\AbsoluteAccessURIFormat;
use Orryv\Path\Models\AbsoluteAccessPathFormat;

abstract class AbsolutePath
{
    protected PathType $path_type;
    protected Encoder $use_encoding;
    protected string $ds; // For AccessPath directory separator
    protected bool $preserve_end_slash = false;
    protected string $scheme;
    protected ?array $host = null;


    protected array $path; // Not decoded
    protected ?array $folder_path = null; /// Not decoded. path without the file (applied after asFolder() or asFile())
    protected ?array $base_path = null; // Not decoded
    

    // protected ?array $current_path = null;
    

    // protected ?string $file_name = null;
    // protected ?string $file_extension = null;

    protected ?string $access_uri_file_name = null;
    protected ?string $access_uri_file_extension = null;
    protected string $access_uri_root_folder; // Always ends with a slash

    protected ?string $access_path_file_name = null;
    protected ?string $access_path_file_extension = null;
    protected string $access_path_root_folder; // Always ends with a slash

    protected ?string $reference_path_file_name = null;
    protected ?string $reference_path_file_extension = null;
    protected string $reference_path_root_folder; // Always ends with a slash

    public function __construct(AbsoluteReferencePathFormat $path) 
    {
        $this->parse($path);
    }
    
    abstract protected function parse(AbsoluteReferencePathFormat $path): void;

    /**
     * Returns the AccessPath directory separator.
     */
    public function ds(): string 
    {
        return $this->ds;
    }

    public function preserveEndSlash(bool $preserve = true): self
    {
        $clone = clone $this;

        $clone->preserve_end_slash = $preserve;
        if(!str_ends_with($clone->access_uri_root_folder, '/')) {
            $clone->access_uri_root_folder .= '/';
        }

        if(!str_ends_with($clone->access_path_root_folder, $clone->ds)) {
            $clone->access_path_root_folder .= $clone->ds;
        }

        if(!str_ends_with($clone->reference_path_root_folder, '/')) {
            $clone->reference_path_root_folder .= '/';
        }

        $clone->path = $clone->trimTrailingEmptySegments($clone->path);

        if($clone->folder_path !== null) {
            $clone->folder_path = $clone->trimTrailingEmptySegments($clone->folder_path);
        }

        return $clone;
    }

    public function asDot(): self
    {
        $lastSegment = $this->getLastPathSegment();

        if($lastSegment === null) {
            return $this->asFolder();
        }

        $lastSegment = $this->stripQueryAndFragment($lastSegment);

        if($lastSegment === '' || $lastSegment === '.' || $lastSegment === '..') {
            return $this->asFolder();
        }

        return str_contains($lastSegment, '.')
            ? $this->asFile()
            : $this->asFolder();
    }

    public function getNthFolder(int $nth, string $formatClassName = AbsoluteReferencePathFormat::class): AbsoluteReferencePathFormat|AbsoluteAccessPathFormat|AbsoluteAccessURIFormat|null
    {
        if($this->path_type === PathType::UNKNOWN) {
            throw new UnknownIfFolderOrFileException('Unknown if the path is a folder or a file, call asFolder() or asFile() first.');
        }

        $folders = $this->getSanitizedFolderPath();

        if($nth < 0 || $nth >= count($folders)) {
            return null;
        }

        $segments = array_slice($folders, 0, $nth + 1);

        return $this->buildFolderFormat($segments, $formatClassName);
    }

    public function getFirstFolder(string $formatClassName = AbsoluteReferencePathFormat::class): AbsoluteReferencePathFormat|AbsoluteAccessPathFormat|AbsoluteAccessURIFormat|null
    {
        return $this->getNthFolder(0, $formatClassName);
    }

    public function getLastFolder(string $formatClassName = AbsoluteReferencePathFormat::class): AbsoluteReferencePathFormat|AbsoluteAccessPathFormat|AbsoluteAccessURIFormat|null
    {
        if($this->path_type === PathType::UNKNOWN) {
            throw new UnknownIfFolderOrFileException('Unknown if the path is a folder or a file, call asFolder() or asFile() first.');
        }

        $folders = $this->getSanitizedFolderPath();

        if(empty($folders)) {
            return null;
        }

        return $this->buildFolderFormat($folders, $formatClassName);
    }

    public function getFolderCount(): int 
    {
        if($this->path_type === PathType::UNKNOWN) {
            throw new UnknownIfFolderOrFileException('Unknown if the path is a folder or a file, call asFolder() or asFile() first.');
        }

        return count($this->getSanitizedFolderPath());
    }

    public function getScheme(): string 
    {
        return mb_strtoupper($this->scheme);
    }

    public function withScheme(string $scheme): self 
    {
        $clone = clone $this;

        $clone->scheme = $scheme;

        return $clone;
    }

    public function getPath(): array 
    {
        return $this->path;
    }

    public function withPath(array $path): self 
    {
        $clone = clone $this;

        $clone->path = $path;

        return $clone;
    }

    public function getFolderPath(): array 
    {
        if($this->path_type === PathType::UNKNOWN) {
            throw new UnknownIfFolderOrFileException('Unknown if the path is a folder or a file, call asFolder() or asFile() first.');
        }

        return $this->getSanitizedFolderPath();
    }

    /**
     * Returns true if the instance know wether the path is a file or a folder.
     */
    public function isKnownPathType(): bool 
    {
        return $this->path_type !== PathType::UNKNOWN;
    }

    public function getHost(): ?array 
    {
        return $this->host;
    }

    public function withHost(?array $host): self 
    {
        $clone = clone $this;

        $clone->host = $host;

        return $clone;
    }

    public function getHostString(): ?string 
    {
        return $this->host ? implode('.', $this->host) : null;
    }

    public function withHostString(?string $host): self 
    {
        $clone = clone $this;

        $clone->host = $host ? explode('.', $host) : null;

        return $clone;
    }

    public function setBasePath(AbsolutePath $base_folder): self 
    {
        $clone = clone $this;

        try{
            $clone->base_path = $base_folder->getFolderPath();
        } catch(UnknownIfFolderOrFileException $e) {
            throw new \Exception('Unknown if $base_folder is a folder or a file, call asFolder() or asFile() on the $base_folder first.');
        }

        return $clone;
    }

    abstract public function asFile(): self;
    // abstract public function asFolder(): self;

    ##########################
    ###### REFERENCE PATH ####
    ##########################

    public function getAccessURIFileName(): ?string 
    {
        return $this->access_uri_file_name;
    }

    public function getAccessURIFileExtension(): ?string 
    {
        return $this->access_uri_file_extension;
    }

    public function getAccessURIRootFolder(): string 
    {
        return $this->access_uri_root_folder;
    }

    abstract public function getReferencePath(): AbsoluteReferencePathFormat;

    ##########################
    ####### ACCESS PATH ######
    ##########################

    public function getAccessPathFileName(): ?string 
    {
        return $this->access_path_file_name;
    }

    public function getAccessPathFileExtension(): ?string 
    {
        return $this->access_path_file_extension;
    }

    public function getAccessPathRootFolder(): string 
    {
        return $this->access_path_root_folder;
    }

    abstract public function getAccessURI(): AbsoluteAccessURIFormat;

    ##########################
    ####### ACCESS URI #######
    ##########################

    public function getReferencePathFileName(): ?string 
    {
        return $this->reference_path_file_name;
    }

    public function getReferencePathFileExtension(): ?string 
    {
        return $this->reference_path_file_extension;
    }

    public function getReferencePathRootFolder(): string 
    {
        return $this->reference_path_root_folder;
    }

    abstract public function getAccessPath(): AbsoluteAccessPathFormat;

    ########################
    ###### NAVIGATION ######
    ########################

    /**
     * Tell the class we're dealing with a file. (immutable)
     */
    public function asFolder(): self
    {
        $clone = clone $this;

        $clone->path_type = PathType::FOLDER;

        $clone->access_uri_file_name = null;
        $clone->access_uri_file_extension = null;

        $clone->access_path_file_name = null;
        $clone->access_path_file_extension = null;

        $clone->reference_path_file_name = null;
        $clone->reference_path_file_extension = null;

        $clone->folder_path = $clone->path;

        return $clone;
    }

    /**
     * Change the current folder with ordinary cd commands.
     * 
     * @param string|array $commands
     */
    public function cd(string|array $commands): self 
    {
        $commands = is_string($commands) ? [$commands] : $commands;
        $current_path = $this->getFolderPath();

        $clone = clone $this;

        // if($base_folder !== null) {
        //     try{
        //         $clone->base_path = $base_folder->getFolderPath();
        //     } catch(UnknownIfFolderOrFileException $e) {
        //         throw new \Exception('Unknown if $base_folder is a folder or a file, call asFolder() or asFile() on the $base_folder first.');
        //     }
        // }

        $last_is_folder = false; // to track if last command was directory change
        $no_folder_change = true; // to track if no folder change was made
        foreach ($commands as $command) {
            $cmds = explode('/', $command);

            if(substr($command, 0, 1) === '/') {
                $current_path = $this->base_path === null
                    ? []
                    : $this->base_path;

                // Remove first element (empty string)
                array_shift($cmds);

                // Remove last element (empty string)
                if($cmds[count($cmds) - 1] === '') {
                    array_pop($cmds);
                }

            }

            foreach ($cmds as $cmd) {
                if ($cmd === '.') {
                    continue;
                }

                if ($cmd === '..') {
                    // Check if current path is root folder
                    if ($current_path === null || count($current_path) === 0) {
                        throw new \Exception('Cannot go above the base folder');
                    }

                    $current_path = array_slice($current_path, 0, -1);

                    // Check if the previous folder is above the base folder or another folder
                    $valid = true;
                    foreach($clone->base_path ?? [] as $i => $folder) {
                        if (!isset($current_path[$i]) || $folder !== $current_path[$i]) {
                            $valid = false;
                            break;
                        }
                    }
                    

                    if(!$valid) {
                        throw new AboveBaseFolderException('Not allowed to go above the $base_folder');
                    }
                    
                    $last_is_folder = true;
                } else {
                    $current_path[] = $cmd;
                    $last_is_folder = false;
                    $no_folder_change = false;
                }
            }
        }

        if($last_is_folder || $no_folder_change) {
            $clone->path_type = PathType::FOLDER;
            $clone->folder_path = $current_path;
        } else {
            $clone->folder_path = null;
            $clone->path_type = PathType::UNKNOWN;
        }
        
        $clone->path = $current_path;
        // $clone->current_path = $current_path;

        return $clone;
    }

    private function getSanitizedFolderPath(): array
    {
        if($this->folder_path === null) {
            return [];
        }

        return $this->trimTrailingEmptySegments($this->folder_path);
    }

    private function trimTrailingEmptySegments(array $segments): array
    {
        while (($lastKey = array_key_last($segments)) !== null && $segments[$lastKey] === '') {
            array_pop($segments);
        }

        return array_values($segments);
    }

    private function buildFolderFormat(array $segments, string $formatClassName): AbsoluteReferencePathFormat|AbsoluteAccessPathFormat|AbsoluteAccessURIFormat
    {
        $path = $this->composeFolderPath($segments, $formatClassName);

        if(is_a($formatClassName, AbsoluteReferencePathFormat::class, true)) {
            /** @var class-string<AbsoluteReferencePathFormat> $formatClassName */
            return new $formatClassName($path, $this->preserve_end_slash);
        }

        if(is_a($formatClassName, AbsoluteAccessPathFormat::class, true)) {
            /** @var class-string<AbsoluteAccessPathFormat> $formatClassName */
            if($this->preserve_end_slash && !str_ends_with($path, $this->ds)) {
                $path .= $this->ds;
            }

            return new $formatClassName($path);
        }

        if(is_a($formatClassName, AbsoluteAccessURIFormat::class, true)) {
            /** @var class-string<AbsoluteAccessURIFormat> $formatClassName */
            if($this->preserve_end_slash && !str_ends_with($path, '/')) {
                $path .= '/';
            }

            return new $formatClassName($path);
        }

        throw new \InvalidArgumentException(sprintf('Unsupported folder format class "%s".', $formatClassName));
    }

    private function composeFolderPath(array $segments, string $formatClassName): string
    {
        $root = match(true) {
            is_a($formatClassName, AbsoluteAccessPathFormat::class, true) => $this->access_path_root_folder,
            is_a($formatClassName, AbsoluteAccessURIFormat::class, true) => $this->access_uri_root_folder,
            default => $this->reference_path_root_folder,
        };

        $separator = match(true) {
            is_a($formatClassName, AbsoluteAccessPathFormat::class, true) => $this->ds,
            default => '/',
        };

        $segments = $this->trimTrailingEmptySegments($segments);

        return $root . ($segments === [] ? '' : implode($separator, $segments));
    }

    private function getLastPathSegment(): ?string
    {
        for($i = count($this->path) - 1; $i >= 0; $i--) {
            if($this->path[$i] !== '') {
                return $this->path[$i];
            }
        }

        return null;
    }

    private function stripQueryAndFragment(string $segment): string
    {
        $cutPosition = strcspn($segment, '?#');

        if($cutPosition < strlen($segment)) {
            $segment = substr($segment, 0, $cutPosition);
        }

        return $segment;
    }

}
