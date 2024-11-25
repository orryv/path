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

abstract class AbsoluteURIPath extends AbsolutePath
{
    protected ?int $port = null;
    protected ?array $query = null;
    protected ?string $fragment = null;

    public function asFile(): self
    {
        $clone = clone $this;

        $clone->path_type = PathType::FILE;

        if(!isset($clone->path[count($clone->path) - 1])){
            throw new FileNotFoundException("Called asFile() on a path with no file name.");
        }

        $file = $clone->path[count($clone->path) - 1];

        if(strpos($file, '.') === false){
            $file_name = $this->removeQueryAndFragment(pathinfo($file, PATHINFO_FILENAME));
            $file_extension = null;
        } else {
            $file_name = pathinfo($file, PATHINFO_FILENAME);
            $file_extension = $this->removeQueryAndFragment(pathinfo($file, PATHINFO_EXTENSION));
        }

        // @TODO This is probably not the right place to do this... (http has different encoding than ftp? 
        //  and http can have both rawurlencode and urlencode)
        $clone->access_uri_file_name = urlencode($file_name);
        $clone->access_uri_file_extension = urlencode($file_extension);

        $clone->access_path_file_name = $file_name;
        $clone->access_path_file_extension = $file_extension;

        $clone->reference_path_file_name = $file_name;
        $clone->reference_path_file_extension = $file_extension;

        $clone->folder_path = array_slice($clone->path, 0, count($clone->path) - 1);

        return $clone;
    }

    private function removeQueryAndFragment(string $string): string
    {
        $query_start = strpos($string, '?');
        
        if($query_start !== false){
            $this->query = explode('&', substr($string, $query_start + 1));
            $string = substr($string, 0, $query_start);
        }

        $fragment_start = strpos($string, '#');

        if($fragment_start !== false){
            $this->fragment = substr($string, $fragment_start + 1);
            $string = substr($string, 0, $fragment_start);
        }

        return $string;
    }
}