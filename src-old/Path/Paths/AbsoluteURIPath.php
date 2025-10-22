<?php

namespace Orryv\Path\Paths;

use Orryv\Path\AbsoluteURIPath as AUP;
use Orryv\Path\Utils;
use Orryv\Path\Models\AbsoluteReferencePathFormat;
use Orryv\Path\Models\AbsoluteAccessURIFormat;
use Orryv\Path\Models\AbsoluteAccessPathFormat;
use Orryv\Path\Enums\PathType;
use Orryv\Path\Enums\Encoder;

class AbsoluteURIPath extends AUP
{
    protected function parse(AbsoluteReferencePathFormat $path): void
    {
        $this->path_type = PathType::UNKNOWN;
        $this->use_encoding = Encoder::NONE;
        $this->ds = '/';
        $this->scheme = strtoupper(substr($path, 0, strpos($path, ':')));
        $this->path = $this->parsePath($path);
        $this->parseData($path);
        $this->port = parse_url($path, PHP_URL_PORT);
        $this->query = $this->parseQuery(parse_url($path, PHP_URL_QUERY) ?? '');
        $this->fragment = parse_url($path, PHP_URL_FRAGMENT);
        $this->username = parse_url($path, PHP_URL_USER);
        $this->password = parse_url($path, PHP_URL_PASS);
        $this->composeRootFolder();
    }

    protected function composeRootFolder(): void
    {
        $root = strtolower($this->scheme) . '://';

        if($this->username !== null && $this->username !== ''){
            $root .= $this->username;

            if(!empty($this->password)){
                $root .= ':' . $this->password;
            }

            $root .= '@';
        }

        $root .= implode('.', $this->host);

        if($this->port){
            $root .= ':' . $this->port;
        }

        $root .= '/';

        $this->access_uri_root_folder = $root;
        $this->access_path_root_folder = $this->access_uri_root_folder;
        $this->reference_path_root_folder = $this->access_uri_root_folder;

    }

    public function getReferencePath(): AbsoluteReferencePathFormat
    {
        $url = substr($this->access_uri_root_folder, 0, -1);

        if(empty($this->path)){
            return new AbsoluteReferencePathFormat($url, $this->preserve_end_slash ? '/' : '');
        }

        if($this->use_encoding !== Encoder::NONE){
            $decoding = $this->use_encoding->toString() === Encoder::RAWURLENCODE ? 'rawurldecode' : 'urldecode';

            $url .= '/' . implode('/', array_map($decoding, $this->path));
        } else {
            $url .= '/' . implode('/', $this->path);
        }

        if(!empty($this->query)){
            $url .= '?' . $this->getQueryString();
        }

        if(!empty($this->fragment)){
            $url .= '#' . $this->fragment;
        }

        return new AbsoluteReferencePathFormat($url, $this->preserve_end_slash ? '/' : '');
    }

    public function getAccessPath(): AbsoluteAccessPathFormat
    {
        $url = rtrim($this->access_uri_root_folder, '/');

        if(!empty($this->path)){
            $url .= '/' . implode('/', $this->path);
        }

        if($this->preserve_end_slash && $this->path_type !== PathType::FILE && !str_ends_with($url, '/')){
            $url .= '/';
        }

        if(!empty($this->query)){
            $url .= '?' . $this->getQueryString();
        }

        if(!empty($this->fragment)){
            $url .= '#' . $this->fragment;
        }

        return new AbsoluteAccessPathFormat($url);
    }

    public function getAccessURI(): AbsoluteAccessURIFormat
    {
        $url = rtrim($this->access_uri_root_folder, '/');

        if(!empty($this->path)){
            $url .= '/' . implode('/', $this->path);
        }

        if($this->preserve_end_slash && $this->path_type !== PathType::FILE && !str_ends_with($url, '/')){
            $url .= '/';
        }

        if(!empty($this->query)){
            $url .= '?' . $this->getQueryString();
        }

        if(!empty($this->fragment)){
            $url .= '#' . $this->fragment;
        }

        return new AbsoluteAccessURIFormat($url);
    }

    
}
