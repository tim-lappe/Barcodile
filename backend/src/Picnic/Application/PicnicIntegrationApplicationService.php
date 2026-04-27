<?php

declare(strict_types=1);

namespace App\Picnic\Application;

use App\Picnic\Api\Dto\PatchPicnicSettingsRequest;
use App\Picnic\Api\Dto\PicnicCatalogProductSummaryResponse;
use App\Picnic\Api\Dto\PicnicCatalogSearchHitResponse;
use App\Picnic\Api\Dto\PicnicIntegrationSettingsResponse;
use App\Picnic\Api\Dto\PostPicnicLoginRequest;
use App\Picnic\Api\Dto\PostPicnicRequestTwoFactorCodeRequest;

final readonly class PicnicIntegrationApplicationService
{
    public function __construct(
        private PicnicSettingsOperations $settingsOps,
        private PicnicCatalogOperations $catalogOps,
        private PicnicLoginOperations $loginOps,
    ) {
    }

    public function getSettings(): PicnicIntegrationSettingsResponse
    {
        return $this->settingsOps->get();
    }

    public function patchSettings(PatchPicnicSettingsRequest $patch): PicnicIntegrationSettingsResponse
    {
        return $this->settingsOps->patch($patch);
    }

    /**
     * @return list<PicnicCatalogSearchHitResponse>
     */
    public function catalogSearch(string $query): array
    {
        return $this->catalogOps->search($query);
    }

    public function catalogProductSummary(string $productId): PicnicCatalogProductSummaryResponse
    {
        return $this->catalogOps->productSummary($productId);
    }

    /**
     * @return array<string, mixed>
     */
    public function login(PostPicnicLoginRequest $body): array
    {
        return $this->loginOps->login($body);
    }

    /**
     * @return array<string, mixed>
     */
    public function requestTwoFactorCode(PostPicnicRequestTwoFactorCodeRequest $body): array
    {
        return $this->loginOps->requestTwoFactorCode($body);
    }
}
