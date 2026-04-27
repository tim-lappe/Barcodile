<?php

declare(strict_types=1);

namespace App\Cart\Domain\Port;

final readonly class CartProviderRegistry
{
    /**
     * @param array<string, CartProviderInterface&CartProviderIndexContribution> $providers
     */
    public function __construct(
        private array $providers,
    ) {
    }

    public function get(string $providerKey): ?CartProviderInterface
    {
        return $this->providers[$providerKey] ?? null;
    }

    /**
     * @return list<CartProviderIndexEntry>
     */
    public function indexEntries(): array
    {
        $byId = $this->providers;
        ksort($byId);
        $out = [];
        foreach ($byId as $provider) {
            $entry = $provider->indexEntry();
            if (null !== $entry) {
                $out[] = $entry;
            }
        }

        return $out;
    }

    /**
     * @return iterable<string, CartProviderInterface&CartProviderIndexContribution>
     */
    public function providers(): iterable
    {
        foreach ($this->providers as $providerKey => $provider) {
            yield $providerKey => $provider;
        }
    }
}
