<?php

namespace Orryv\Path\Paths;

use Orryv\Path\AbsoluteSystemPath;
use Orryv\Path\Utils;
use Orryv\Path\Models\AbsoluteReferencePathFormat;
use Orryv\Path\Enums\PathType;
use Orryv\Path\Enums\OSFamily;
use Orryv\Path\Enums\SystemPathLocationCategory;

class AbsoluteWindowsNetworkPath extends AbsoluteSystemPath
{
    private $drive;

    protected function parse(AbsoluteReferencePathFormat $path): void
    {
        $host = substr($path, 2, strpos($path, '/', 2) - 2);
        $path = substr($path, strpos($path, '/', 2) + 1);

        $this->os_family = OSFamily::WINDOWS;
        $this->location_category = SystemPathLocationCategory::NETWORK;
        $this->path_type = PathType::UNKNOWN;
        $this->ds = '\\';
        $this->scheme = 'FILE';
        $this->path = Utils::splitPathAndTrimSlashes($path, $this->preserve_end_slash);
        // $this->current_path = $this->path;
        $this->host = explode('.', $host);
        $this->access_uri_root_folder = 'file://' . $host . '/';
        $this->access_path_root_folder = '\\\\' . $host . '\\';
        $this->reference_path_root_folder = '//' . $host . '/';

        // print_r($this->path);
    }
}