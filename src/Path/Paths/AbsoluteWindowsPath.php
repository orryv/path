<?php

namespace Orryv\Path\Paths;

use Orryv\Path\AbsoluteSystemPath;
use Orryv\Path\Utils;
use Orryv\Path\Models\AbsoluteReferencePathFormat;
use Orryv\Path\Enums\PathType;
use Orryv\Path\Enums\OSFamily;
use Orryv\Path\Enums\SystemPathLocationCategory;

class AbsoluteWindowsPath extends AbsoluteSystemPath
{
    private $drive;

    protected function parse(AbsoluteReferencePathFormat $path): void
    {
        $this->drive = substr($path, 0, 1);

        $this->os_family = OSFamily::WINDOWS;
        $this->location_category = SystemPathLocationCategory::LOCAL;
        $this->path_type = PathType::UNKNOWN;
        $this->ds = '\\';
        $this->scheme = 'FILE';
        $this->path = Utils::splitPathAndTrimSlashes($path, $this->preserve_end_slash);
        $this->current_path = $this->path;
        $this->host = null;
        $this->access_uri_root_folder = 'file:///' . $this->drive . ':/';
        $this->access_path_root_folder = $this->drive . ':\\';
        $this->reference_path_root_folder = $this->drive . ':/';

        // print_r($this->path);
    }
}