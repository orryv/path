<?php

namespace Orryv\Path;

use Orryv\Path\Exceptions\FileNotFoundException;
use Orryv\Path\AbsolutePath;
use Orryv\Path\Enums\PathType;
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

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function withPort(?int $port): self
    {
        $clone = clone $this;

        $clone->port = $port;

        return $clone;
    }

    public function getQuery(): ?array
    {
        return $this->query;
    }

    public function withQuery(array $query): self
    {
        $clone = clone $this;

        $clone->query = $query;

        return $clone;
    }

    public function getQueryString(): ?string
    {
        $text = '';
        foreach($this->query as $key => $value){
            $text .= $key . ( ($value !== null) ? '='.$value : '' ) . '&';
        }

        return substr($text, 0, -1);
    }

    public function withQueryString(string $query): self
    {
        $clone = clone $this;

        $query = explode('&', $query);
        $elements = [];
        foreach($query as $element){
            $key_value = explode('=', $element);
            $elements[$key_value[0]] = (isset($key_value[1])) ? $key_value[1] : null;
        }

        $clone->query = $elements;

        return $clone;
    }

    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    public function withFragment(string $fragment): self
    {
        $clone = clone $this;

        $clone->fragment = $fragment;

        return $clone;
    }
}