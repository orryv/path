<?php

namespace Orryv\Path;

use Orryv\Path\Exceptions\FileNotFoundException;
use Orryv\Path\AbsolutePath;
use Orryv\Path\Enums\PathType;
use Orryv\Path\Enums\OSFamily;
use Orryv\Path\Enums\SystemPathLocationCategory;
use Orryv\Path\Models\AbsoluteReferencePathFormat;
use Orryv\Path\Models\AbsoluteAccessURIFormat;
use Orryv\Path\Models\AbsoluteAccessPathFormat;

abstract class AbsoluteSystemPath extends AbsolutePath
{
    protected OSFamily $os_family;
    protected SystemPathLocationCategory $location_category;

    public function asFile(): self
    {
        $this->path_type = PathType::FILE;

        if(!isset($this->path[count($this->path) - 1])){
            throw new FileNotFoundException("Called asFile() on a path with no file name.");
        }

        $file = $this->path[count($this->path) - 1];

        if(strpos($file, '.') === false){
            $file_name = $file;
            $file_extension = null;
        } else {
            $file_name = substr($file, 0, strrpos($file, '.'));
            $file_extension = substr($file, strrpos($file, '.') + 1);
        }

        $this->access_uri_file_name = rawurlencode($file_name);
        $this->access_uri_file_extension = rawurlencode($file_extension);

        $this->access_path_file_name = $file_name;
        $this->access_path_file_extension = $file_extension;

        $this->reference_path_file_name = $file_name;
        $this->reference_path_file_extension = $file_extension;

        $this->folder_path = array_slice($this->path, 0, count($this->path) - 1);

        return $this;
    }

    public function asFolder(): self
    {
        $this->path_type = PathType::FOLDER;

        $this->access_uri_file_name = null;
        $this->access_uri_file_extension = null;

        $this->access_path_file_name = null;
        $this->access_path_file_extension = null;

        $this->reference_path_file_name = null;
        $this->reference_path_file_extension = null;

        $this->folder_path = $this->path;

        return $this;
    }

    public function getOSFamily(): OSFamily
    {
        return $this->os_family;
    }

    public function getSystemPathLocationCategory(): SystemPathLocationCategory
    {
        return $this->location_category;
    }

    public function getReferencePath(): AbsoluteReferencePathFormat
    {
        return new AbsoluteReferencePathFormat($this->reference_path_root_folder . implode('/', $this->path));
    }

    public function getAccessURI(): AbsoluteAccessURIFormat
    {
        $path = implode('/', array_map('rawurldecode', $this->path));

        return new AbsoluteAccessURIFormat($this->access_uri_root_folder . $path);
    }

    public function getAccessPath(): AbsoluteAccessPathFormat
    {
        return new AbsoluteAccessPathFormat($this->access_path_root_folder . implode($this->ds, $this->path));
    }
}