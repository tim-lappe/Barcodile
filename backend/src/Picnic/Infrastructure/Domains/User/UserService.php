<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure\Domains\User;

use App\Picnic\Infrastructure\PicnicHttpClient;
use App\Picnic\Infrastructure\PicnicHttpHeaderMode;

final class UserService
{
    public function __construct(private readonly PicnicHttpClient $http)
    {
    }

    public function getUserDetails(): mixed
    {
        return $this->http->sendRequest('GET', '/user');
    }

    public function getUserInfo(): mixed
    {
        return $this->http->sendRequest('GET', '/user-info');
    }

    public function getProfileMenu(): mixed
    {
        return $this->http->sendRequest('GET', '/profile-menu?fetch_mgm=true', null, PicnicHttpHeaderMode::WithPicnicAgent);
    }

    public function submitSuggestion(string $suggestion): mixed
    {
        return $this->http->sendRequest('POST', '/user/suggestion', ['suggestion' => $suggestion]);
    }

    public function registerPushToken(string $pushToken, string $platform): mixed
    {
        return $this->http->sendRequest('POST', '/user/device/register_push', [
            'push_token' => $pushToken,
            'platform' => $platform,
        ]);
    }

    public function checkForUpdates(): mixed
    {
        return $this->http->sendRequest('POST', '/update_check', [], PicnicHttpHeaderMode::WithPicnicAgent);
    }
}
