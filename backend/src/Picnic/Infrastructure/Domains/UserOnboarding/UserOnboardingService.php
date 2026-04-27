<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure\Domains\UserOnboarding;

use App\Picnic\Infrastructure\PicnicHttpClient;

final class UserOnboardingService
{
    public function __construct(private readonly PicnicHttpClient $http)
    {
    }

    /**
     * @param array<string, mixed> $details
     */
    public function setHouseholdDetails(array $details): mixed
    {
        return $this->http->sendRequest('POST', '/user-onboarding/household-details', $details);
    }

    /**
     * @param array<string, mixed> $details
     */
    public function setBusinessDetails(array $details): mixed
    {
        return $this->http->sendRequest('POST', '/user-onboarding/business-details', $details);
    }

    /**
     * @param list<string> $topics
     */
    public function subscribePush(array $topics): mixed
    {
        return $this->http->sendRequest('POST', '/user-onboarding/subscribe-push', ['topics' => $topics]);
    }
}
