<?php

declare(strict_types=1);

namespace App\Application\Picnic;

use App\Application\Picnic\Dto\PatchPicnicSettingsRequest;
use App\Application\Picnic\Dto\PicnicIntegrationSettingsResponse;
use App\Domain\Picnic\Facade\PicnicFacade;
use App\Domain\Picnic\Facade\PicnicSettingsView;

final readonly class PicnicSettingsOperations
{
    public function __construct(
        private PicnicFacade $picnic,
    ) {
    }

    public function get(): PicnicIntegrationSettingsResponse
    {
        return $this->map($this->picnic->getSettings());
    }

    public function patch(PatchPicnicSettingsRequest $patch): PicnicIntegrationSettingsResponse
    {
        return $this->map($this->picnic->patchSettings(
            $patch->usernameSpecified,
            $patch->username,
            $patch->countryCodeSpecified,
            $patch->countryCode,
            $patch->passwordSpecified,
            $patch->password,
            $patch->authClearSpecified,
            true === $patch->clearAuthSession,
        ));
    }

    private function map(PicnicSettingsView $settings): PicnicIntegrationSettingsResponse
    {
        return new PicnicIntegrationSettingsResponse(
            $settings->resourceId,
            $settings->username,
            $settings->countryCode,
            $settings->hasStoredPassword,
            $settings->hasStoredAuthSession,
        );
    }
}
