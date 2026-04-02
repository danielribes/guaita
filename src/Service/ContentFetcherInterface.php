<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Snapshot;
use App\Domain\Url;

interface ContentFetcherInterface
{
    public function fetch(Url $url): Snapshot;
}
