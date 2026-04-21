<?php

declare(strict_types=1);

namespace App\Application\Catalog;

use App\Application\Catalog\Dto\PatchCatalogItemRequest;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Picnic\Entity\PicnicCatalogItemProductLink;
use App\Domain\Picnic\Repository\PicnicCatalogItemProductLinkRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CatalogItemPicnicLinkPatcher
{
    public function __construct(
        private PicnicCatalogItemProductLinkRepository $picnicLinkRepo,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function applyFromPatch(CatalogItem $item, PatchCatalogItemRequest $request): void
    {
        if (!$request->relations->picnicLinkSpecified) {
            return;
        }
        $this->syncFromPatchBody($item, $request);
    }

    private function syncFromPatchBody(CatalogItem $item, PatchCatalogItemRequest $request): void
    {
        $existing = $this->picnicLinkRepo->findOneByCatalogItemId($item->getId());
        $this->syncPicnicLink($item, $existing, $request->relations->picnicProductId);
    }

    private function syncPicnicLink(CatalogItem $item, ?PicnicCatalogItemProductLink $existing, ?string $raw): void
    {
        if (null === $raw) {
            $this->removePicnicLinkIfPresent($existing);

            return;
        }
        $productId = trim($raw);
        if ('' === $productId) {
            $this->removePicnicLinkIfPresent($existing);

            return;
        }
        $this->upsertProductLink($item, $existing, $productId);
    }

    private function upsertProductLink(CatalogItem $item, ?PicnicCatalogItemProductLink $existing, string $productId): void
    {
        if (null === $existing) {
            $this->entityManager->persist(new PicnicCatalogItemProductLink($item, $productId));

            return;
        }
        $existing->changeProductId($productId);
    }

    private function removePicnicLinkIfPresent(?PicnicCatalogItemProductLink $existing): void
    {
        if (null !== $existing) {
            $this->entityManager->remove($existing);
        }
    }
}
