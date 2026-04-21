<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\CustomerService;

use App\Infrastructure\Picnic\PicnicHttpClient;
use App\Infrastructure\Picnic\PicnicHttpHeaderMode;

final class CustomerServiceService
{
    public function __construct(private readonly PicnicHttpClient $http)
    {
    }

    public function getContactInfo(): mixed
    {
        return $this->http->sendRequest('GET', '/cs-contact-info', null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    /**
     * @param list<string>|null $displayPositions
     */
    public function getMessages(?array $displayPositions = null): mixed
    {
        $query = '';
        if (null !== $displayPositions && [] !== $displayPositions) {
            $parts = [];
            foreach ($displayPositions as $position) {
                $parts[] = 'display_position='.rawurlencode((string) $position);
            }
            $query = '?'.implode('&', $parts);
        }

        return $this->http->sendRequest('GET', '/messages'.$query, null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function getReminders(): mixed
    {
        return $this->http->sendRequest('GET', '/reminders', null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    /**
     * @param list<array<string, mixed>> $reminders
     */
    public function setReminders(array $reminders): mixed
    {
        return $this->http->sendRequest('PUT', '/reminders', $reminders, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function getParcels(): mixed
    {
        return $this->http->sendRequest('GET', '/parcels', null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function getUnauthenticatedContactInfo(string $countryCode): mixed
    {
        $publicUrl = preg_replace('#/api/#', '/public-api/', $this->http->apiBaseUrl(), 1);
        $response = $this->http->innerHttpClient()->request('GET', $publicUrl.'/cs-contact-info', [
            'headers' => [
                'picnic-country' => $countryCode,
            ],
        ]);

        $content = $response->getContent(false);

        return json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
    }
}
