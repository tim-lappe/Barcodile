<?php

declare(strict_types=1);

namespace App\Application\Catalog;

use App\Application\Catalog\Dto\CatalogItemAttributeRowInput;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Catalog\Entity\CatalogItemAttribute;
use App\Domain\Shared\CatalogItemAttributeKey;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CatalogItemAttributeRowsApplier
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<CatalogItemAttributeRowInput>|null $rows
     */
    public function apply(CatalogItem $item, ?array $rows): void
    {
        if (null === $rows) {
            return;
        }
        $this->syncConfiguredRows($item, $rows);
    }

    /**
     * @param list<CatalogItemAttributeRowInput> $rows
     */
    private function syncConfiguredRows(CatalogItem $item, array $rows): void
    {
        $indexed = $this->indexAttributesById($item);
        $keep = [];
        foreach ($rows as $row) {
            $this->applyRow($item, $row, $indexed, $keep);
        }
        foreach ($item->getCatalogItemAttributes()->toArray() as $attr) {
            $this->removeIfNotKept($item, $attr, $keep);
        }
    }

    /**
     * @return array<string, CatalogItemAttribute>
     */
    private function indexAttributesById(CatalogItem $item): array
    {
        $indexed = [];
        foreach ($item->getCatalogItemAttributes()->toArray() as $attr) {
            $indexed[(string) $attr->getId()] = $attr;
        }

        return $indexed;
    }

    /**
     * @param array<string, CatalogItemAttribute> $indexed
     * @param list<string>                        $keep
     */
    private function applyRow(CatalogItem $item, CatalogItemAttributeRowInput $row, array $indexed, array &$keep): void
    {
        $key = CatalogItemAttributeKey::from($row->attribute);
        $rowId = $row->rowId;
        $value = $row->value;
        if (null === $rowId || !isset($indexed[$rowId])) {
            $newAttribute = new CatalogItemAttribute();
            $newAttribute->changeCatalogItem($item);
            $newAttribute->changeAttribute($key);
            $newAttribute->changeValue($value);
            $item->addCatalogItemAttribute($newAttribute);
            $keep[] = (string) $newAttribute->getId();

            return;
        }
        $existingAttribute = $indexed[$rowId];
        $existingAttribute->changeAttribute($key);
        $existingAttribute->changeValue($value);
        $keep[] = $rowId;
    }

    /**
     * @param list<string> $keep
     */
    private function removeIfNotKept(CatalogItem $item, CatalogItemAttribute $attr, array $keep): void
    {
        $sid = (string) $attr->getId();
        if (!\in_array($sid, $keep, true)) {
            $item->removeCatalogItemAttribute($attr);
            $this->entityManager->remove($attr);
        }
    }
}
