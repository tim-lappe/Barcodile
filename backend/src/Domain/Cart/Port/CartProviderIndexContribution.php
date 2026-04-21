<?php

declare(strict_types=1);

namespace App\Domain\Cart\Port;

interface CartProviderIndexContribution
{
    public function indexEntry(): ?CartProviderIndexEntry;
}
