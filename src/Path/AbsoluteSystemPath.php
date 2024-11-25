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
        $clone = clone $this;

        $clone->path_type = PathType::FILE;

        if(!isset($clone->path[count($clone->path) - 1])){
            throw new FileNotFoundException("Called asFile() on a path with no file name.");
        }

        $file = $clone->path[count($clone->path) - 1];

        if(strpos($file, '.') === false){
            $file_name = $file;
            $file_extension = null;
        } else {
            $file_name = substr($file, 0, strrpos($file, '.'));
            $file_extension = substr($file, strrpos($file, '.') + 1);
        }

        $clone->access_uri_file_name = rawurlencode($file_name);
        $clone->access_uri_file_extension = rawurlencode($file_extension);

        $clone->access_path_file_name = $file_name;
        $clone->access_path_file_extension = $file_extension;

        $clone->reference_path_file_name = $file_name;
        $clone->reference_path_file_extension = $file_extension;

        $clone->folder_path = array_slice($clone->path, 0, count($clone->path) - 1);

        return $clone;
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
        return new AbsoluteReferencePathFormat($this->reference_path_root_folder . implode('/', $this->path), $this->preserve_end_slash);
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