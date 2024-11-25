<?php

namespace Orryv\Path\Paths;

use Orryv\Path\AbsoluteSystemPath;
use Orryv\Path\Utils;
use Orryv\Path\Models\AbsoluteReferencePathFormat;
use Orryv\Path\Enums\PathType;
use Orryv\Path\Enums\OSFamily;
use Orryv\Path\Enums\SystemPathLocationCategory;

class AbsoluteUnixPath extends AbsoluteSystemPath
{
    protected function parse(AbsoluteReferencePathFormat $path): void
    {
        $this->os_family = OSFamily::UNIX;
        $this->location_category = SystemPathLocationCategory::LOCAL;
        $this->path_type = PathType::UNKNOWN;
        $this->ds = '/';
        $this->scheme = 'FILE';
        $this->path = Utils::splitPathAndTrimSlashes($path, $this->preserve_end_slash);
        // $this->current_path = $this->path;
        $this->host = null;
        $this->access_uri_root_folder = 'file:///';
        $this->access_path_root_folder = '/';
        $this->reference_path_root_folder = '/';

        // print_r($this->path);
    }
}
