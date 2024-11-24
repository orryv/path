<?php

namespace Orryv\Path;

abstract class AbsoluteURIPath
{
    protected ?int $port = null;
    protected ?array $query = null;
    protected ?string $fragment = null;
}