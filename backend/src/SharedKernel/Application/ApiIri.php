<?php

declare(strict_types=1);

namespace App\SharedKernel\Application;

use InvalidArgumentException;

final class ApiIri
{
    public const string PREFIX_LOCATION = '/api/locations/';

    public const string PREFIX_CATALOG_ITEM = '/api/catalog_items/';

    public const string PREFIX_INVENTORY_ITEM = '/api/inventory_items/';

    public const string PREFIX_SHOPPING_CART = '/api/shopping_carts/';

    public static function tailAfterPrefix(string $prefix, string $iri): string
    {
        if (!str_starts_with($iri, $prefix)) {
            throw new InvalidArgumentException('Unexpected IRI format.');
        }

        return substr($iri, \strlen($prefix)) ?: throw new InvalidArgumentException('Invalid IRI.');
    }

    public static function location(string $resourceId): string
    {
        return '/api/locations/'.$resourceId;
    }

    public static function catalogItem(string $resourceId): string
    {
        return '/api/catalog_items/'.$resourceId;
    }

    public static function inventoryItem(string $resourceId): string
    {
        return '/api/inventory_items/'.$resourceId;
    }

    public static function shoppingCart(string $resourceId): string
    {
        return '/api/shopping_carts/'.$resourceId;
    }

    public static function cartAutomationRule(string $catalogItemId, string $ruleId): string
    {
        return '/api/inventory/catalog_items/'.$catalogItemId.'/cart_automation_rules/'.$ruleId;
    }
}
