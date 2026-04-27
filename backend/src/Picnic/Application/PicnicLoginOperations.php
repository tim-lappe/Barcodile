<?php

declare(strict_types=1);

namespace App\Picnic\Application;

use App\Picnic\Api\Dto\PostPicnicLoginRequest;
use App\Picnic\Api\Dto\PostPicnicRequestTwoFactorCodeRequest;
use App\Picnic\Domain\Facade\PicnicFacade;

final readonly class PicnicLoginOperations
{
    public function __construct(
        private PicnicFacade $picnic,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function login(PostPicnicLoginRequest $body): array
    {
        return $this->picnic->login($body->username, $body->password, $body->countryCode, $body->pendingToken, $body->otp);
    }

    /**
     * @return array<string, mixed>
     */
    public function requestTwoFactorCode(PostPicnicRequestTwoFactorCodeRequest $body): array
    {
        return $this->picnic->requestTwoFactorCode($body->pendingToken, $body->channel);
    }
}
