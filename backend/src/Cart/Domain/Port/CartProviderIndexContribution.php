<?php

declare(strict_types=1);

namespace App\Cart\Domain\Port;

interface CartProviderIndexContribution
{
    public function indexEntry(): ?CartProviderIndexEntry;
}
