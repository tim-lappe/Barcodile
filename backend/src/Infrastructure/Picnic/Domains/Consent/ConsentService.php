<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\Consent;

use App\Infrastructure\Picnic\PicnicHttpClient;

final class ConsentService
{
    public function __construct(private readonly PicnicHttpClient $http)
    {
    }

    public function getConsentSettings(ConsentSettingsPageKind $page = ConsentSettingsPageKind::Standard): mixed
    {
        $path = ConsentSettingsPageKind::General === $page
            ? '/consents/general/settings-page'
            : '/consents/settings-page';

        return $this->http->sendRequest('GET', $path);
    }

    /**
     * @param array<string, mixed> $consentSettingsInput
     */
    public function setConsentSettings(array $consentSettingsInput): mixed
    {
        return $this->http->sendRequest('PUT', '/consents', $consentSettingsInput);
    }

    /**
     * @param list<string> $consentTopics
     */
    public function getConsents(array $consentTopics, string $strategy): mixed
    {
        $params = [];
        foreach ($consentTopics as $topic) {
            $params[] = 'consent_topics='.rawurlencode($topic);
        }
        $params[] = 'strategy='.rawurlencode($strategy);
        $query = implode('&', $params);

        return $this->http->sendRequest('GET', '/consents?'.$query);
    }

    public function getGeneralConsents(): mixed
    {
        return $this->http->sendRequest('GET', '/consents/general');
    }

    /**
     * @param array<string, mixed> $declarations
     */
    public function setGeneralConsents(array $declarations): mixed
    {
        return $this->http->sendRequest('PUT', '/consents/general', $declarations);
    }
}
