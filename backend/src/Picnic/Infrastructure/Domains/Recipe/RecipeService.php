<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure\Domains\Recipe;

use App\Picnic\Infrastructure\PicnicHttpClient;
use App\Picnic\Infrastructure\PicnicHttpHeaderMode;
use DateTimeImmutable;
use DateTimeZone;

final class RecipeService
{
    public function __construct(private readonly PicnicHttpClient $http)
    {
    }

    public function getRecipesPage(): mixed
    {
        return $this->http->sendRequest('GET', '/pages/meals-page-root', null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function getRecipeDetailsPage(string $recipeId): mixed
    {
        return $this->http->sendRequest(
            'GET',
            '/pages/recipe-details-page-root?recipe_id='.rawurlencode($recipeId),
            null,
            PicnicHttpHeaderMode::WithPicnicAgent,
        );
    }

    public function saveRecipe(string $recipeId): mixed
    {
        return $this->http->sendRequest('POST', '/pages/task/recipe-saving', [
            'payload' => [
                'recipe_id' => $recipeId,
                'saved_at' => $this->nowIso8601Utc(),
            ],
        ], PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function unsaveRecipe(string $recipeId): mixed
    {
        return $this->http->sendRequest('POST', '/pages/task/recipe-saving', [
            'payload' => [
                'recipe_id' => $recipeId,
                'saved_at' => null,
            ],
        ], PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function assignSellingGroupToBasket(string $sellingGroupId, ?int $dayOffset = null, ?int $portions = null): mixed
    {
        $payload = ['selling_group_id' => $sellingGroupId];
        if (null !== $dayOffset) {
            $payload['day_offset'] = $dayOffset;
        }
        if (null !== $portions) {
            $payload['portions'] = $portions;
        }

        return $this->http->sendRequest('POST', '/pages/task/assign-selling-group-to-basket', ['payload' => $payload], PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function updateSellingGroupPortions(string $sellingGroupId, int $dayOffset, int $portions): mixed
    {
        return $this->http->sendRequest('POST', '/pages/task/update-selling-group-number-of-portions-task', [
            'payload' => [
                'selling_group_id' => $sellingGroupId,
                'day_offset' => $dayOffset,
                'portions' => $portions,
            ],
        ], PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function removeSellingGroupFromBasket(string $sellingGroupId): mixed
    {
        return $this->http->sendRequest('POST', '/pages/task/remove-selling-group-from-basket', [
            'payload' => [
                'selling_group_id' => $sellingGroupId,
            ],
        ], PicnicHttpHeaderMode::WithPicnicAgent);
    }

    private function nowIso8601Utc(): string
    {
        $utcNow = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $utcNow->format('Y-m-d\TH:i:s.v\Z');
    }
}
