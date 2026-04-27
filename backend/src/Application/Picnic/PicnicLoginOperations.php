<?php

declare(strict_types=1);

namespace App\Application\Picnic;

use App\Application\Picnic\Dto\PostPicnicLoginRequest;
use App\Application\Picnic\Dto\PostPicnicRequestTwoFactorCodeRequest;
use App\Domain\Picnic\Facade\PicnicFacade;

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
