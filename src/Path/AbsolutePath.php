<?php

namespace Orryv\Path;

use Orryv\Path\Exceptions\UnknownIfFolderOrFileException;
use Orryv\Path\Exceptions\AboveBaseFolderException;
use Orryv\Path\Enums\PathType;
use Orryv\Path\Models\AbsoluteReferencePathFormat;
use Orryv\Path\Models\AbsoluteAccessURIFormat;
use Orryv\Path\Models\AbsoluteAccessPathFormat;

abstract class AbsolutePath
{
    protected PathType $path_type;
    protected string $ds; // For AccessPath directory separator
    protected string $scheme;
    protected array $path;
    protected ?array $folder_path = null; // path without the file (applied after asFolder() or asFile())
    protected ?array $host = null;

    protected ?array $current_path = null;
    protected ?array $base_path = null;

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
    
    abstract protected function parse(string $path): void;

    /**
     * Returns the AccessPath directory separator.
     */
    public function ds(): string 
    {
        return $this->ds;
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

        return $this->folder_path;
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
    abstract public function asFolder(): self;

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
     * Change the current folder with ordinary cd commands.
     * 
     * @param string|array $commands
     * @param AbsolutePath|null $base_folder If provided, you can't go above this folder.
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
        foreach ($commands as $command) {
            $cmds = explode('/', $command);

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
                }
            }
        }

        if($last_is_folder) {
            $clone->path_type = PathType::FOLDER;
            $clone->folder_path = $current_path;
        } else {
            $clone->folder_path = null;
            $clone->path_type = PathType::UNKNOWN;
        }
        
        $clone->path = $current_path;
        $clone->current_path = $current_path;
        
        return $clone;
    }
}