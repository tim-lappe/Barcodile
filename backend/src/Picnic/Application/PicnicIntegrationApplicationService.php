<?php

declare(strict_types=1);

namespace App\Picnic\Application;

use App\Picnic\Api\Dto\PatchPicnicSettingsRequest;
use App\Picnic\Api\Dto\PicnicCatalogProductSummaryResponse;
use App\Picnic\Api\Dto\PicnicCatalogSearchHitResponse;
use App\Picnic\Api\Dto\PicnicIntegrationSettingsResponse;
use App\Picnic\Api\Dto\PostPicnicLoginRequest;
use App\Picnic\Api\Dto\PostPicnicRequestTwoFactorCodeRequest;
use App\Picnic\Domain\Entity\PicnicCatalogItemProductLink;
use App\Picnic\Domain\Entity\PicnicIntegrationSettings;
use App\Picnic\Domain\Port\PicnicAnonymousAuthenticationPort;
use App\Picnic\Domain\Port\PicnicCatalogProductLookupPort;
use App\Picnic\Domain\Port\PicnicCatalogSearchPort;
use App\Picnic\Domain\Port\PicnicCredentialCipherPort;
use App\Picnic\Domain\Port\PicnicPendingLoginTokenPort;
use App\Picnic\Domain\Repository\PicnicCatalogItemProductLinkRepository;
use App\Picnic\Domain\Repository\PicnicIntegrationSettingsRepository;
use App\Picnic\Domain\ValueObject\PicnicPasswordLoginResult;
use App\SharedKernel\Domain\Id\CatalogItemId;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 * @SuppressWarnings("PHPMD.ExcessiveClassLength")
 */
final readonly class PicnicIntegrationApplicationService
{
    public function __construct(
        private PicnicIntegrationSettingsRepository $settingsRepo,
        private PicnicCatalogItemProductLinkRepository $productLinkRepo,
        private PicnicCredentialCipherPort $credentialCipher,
        private PicnicPendingLoginTokenPort $pendingLoginToken,
        private PicnicAnonymousAuthenticationPort $picnicAuth,
        private PicnicCatalogProductLookupPort $catalogLookup,
        private PicnicCatalogSearchPort $catalogSearch,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function productIdForCatalogItem(string $catalogItemId): ?string
    {
        return $this->productLinkRepo->findOneByCatalogItemId(CatalogItemId::fromString($catalogItemId))?->getProductId();
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function syncProductLinkForCatalogItem(string $catalogItemId, ?string $rawProductId): void
    {
        $catalogItemIdObject = CatalogItemId::fromString($catalogItemId);
        $existing = $this->productLinkRepo->findOneByCatalogItemId($catalogItemIdObject);
        if (null === $rawProductId || '' === trim($rawProductId)) {
            if (null !== $existing) {
                $this->entityManager->remove($existing);
            }

            return;
        }
        if (!$existing instanceof PicnicCatalogItemProductLink) {
            $this->entityManager->persist(new PicnicCatalogItemProductLink($catalogItemIdObject, trim($rawProductId)));

            return;
        }
        $existing->changeProductId(trim($rawProductId));
    }

    /**
     * @param list<string> $catalogItemIds
     *
     * @return array<string, string>
     */
    public function mapProductIdsByCatalogItemIds(array $catalogItemIds): array
    {
        $ids = array_map(static fn (string $catalogItemId): CatalogItemId => CatalogItemId::fromString($catalogItemId), $catalogItemIds);

        return $this->productLinkRepo->mapProductIdByCatalogItemId($ids);
    }

    public function getSettings(): PicnicIntegrationSettingsResponse
    {
        return $this->mapSettingsResponse($this->mapSettings($this->settingsRepo->getSingleton()));
    }

    public function patchSettings(PatchPicnicSettingsRequest $patch): PicnicIntegrationSettingsResponse
    {
        $settings = $this->settingsRepo->getSingleton();
        $this->applyUsername($settings, $patch->usernameSpecified, $patch->username);
        $this->applyCountry($settings, $patch->countryCodeSpecified, $patch->countryCode);
        $this->applyPassword($settings, $patch->passwordSpecified, $patch->password);
        $this->applyAuthClear($settings, $patch->authClearSpecified, true === $patch->clearAuthSession);
        $violations = $this->validator->validate($settings);
        if (\count($violations) > 0) {
            $messages = [];
            foreach ($violations as $one) {
                $messages[] = $one->getMessage();
            }
            throw new BadRequestHttpException(implode(' ', $messages));
        }
        $this->entityManager->flush();

        return $this->mapSettingsResponse($this->mapSettings($settings));
    }

    /**
     * @return array<string, mixed>
     */
    private function loginFromValues(
        mixed $username,
        mixed $password,
        mixed $countryCode,
        ?string $pendingToken,
        ?string $otpCode,
    ): array {
        if (null !== $pendingToken && null !== $otpCode) {
            return $this->loginWithOtp($pendingToken, $otpCode);
        }

        return $this->loginWithUsernamePassword($username, $password, $countryCode);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestTwoFactorCodeFromValues(string $pendingToken, string $channel): array
    {
        if ('' === $pendingToken) {
            return ['ok' => false, 'message' => 'Missing pending token.'];
        }
        try {
            $decoded = $this->pendingLoginToken->decode($pendingToken);
        } catch (InvalidArgumentException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
        try {
            $this->picnicAuth->requestTwoFactorCode($decoded, strtoupper($channel));
        } catch (RuntimeException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }

        return ['ok' => true, 'message' => 'Two-factor code requested.'];
    }

    private function productSummaryView(string $productId): PicnicCatalogProductView
    {
        $summary = $this->catalogLookup->lookupByProductId($productId);

        return new PicnicCatalogProductView(
            $summary->productId,
            $summary->name,
            null,
            null,
            $summary->unitQuantity,
            $summary->brand,
        );
    }

    /**
     * @return list<PicnicCatalogProductView>
     */
    private function searchCatalog(string $query): array
    {
        $hits = [];
        foreach ($this->catalogSearch->search($query) as $unit) {
            $hits[] = new PicnicCatalogProductView(
                $unit->productId,
                $unit->name,
                $unit->imageId,
                $unit->displayPrice,
                $unit->unitQuantity,
            );
        }

        return $hits;
    }

    /**
     * @return array<string, mixed>
     */
    private function loginWithUsernamePassword(mixed $username, mixed $password, mixed $countryCode): array
    {
        $creds = $this->passwordLoginCredentials($username, $password, $countryCode);
        if (null === $creds) {
            return ['ok' => false, 'message' => 'Username and password are required.'];
        }
        [$resolvedUsername, $resolvedCountryCode, $resolvedPassword] = $creds;
        try {
            $result = $this->picnicAuth->loginWithPassword($resolvedUsername, $resolvedCountryCode, $resolvedPassword);
        } catch (RuntimeException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }

        return $this->completePasswordLogin($resolvedUsername, $resolvedCountryCode, $resolvedPassword, $result);
    }

    /**
     * @return array{0: string, 1: string, 2: string}|null
     */
    private function passwordLoginCredentials(mixed $username, mixed $password, mixed $countryCode): ?array
    {
        $username = self::stringOrEmpty($username);
        $password = self::stringOrEmpty($password);
        if ('' === $username || '' === $password) {
            return null;
        }

        return [$username, \is_string($countryCode) ? $countryCode : 'NL', $password];
    }

    private static function stringOrEmpty(mixed $value): string
    {
        return \is_string($value) ? $value : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function completePasswordLogin(
        string $username,
        string $countryCode,
        string $password,
        PicnicPasswordLoginResult $result,
    ): array {
        if ($result->secondFactorRequired) {
            $pending = $this->pendingLoginToken->encode($username, $countryCode, $password, $result->authKey);

            return [
                'ok' => true,
                'secondFactorAuthenticationRequired' => true,
                'pendingToken' => $pending,
                'message' => 'Two-factor authentication required.',
            ];
        }
        $this->persistSuccessfulSession($username, $countryCode, $result->authKey);

        return [
            'ok' => true,
            'secondFactorAuthenticationRequired' => false,
            'message' => 'Logged in to Picnic.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loginWithOtp(string $pendingToken, string $otp): array
    {
        try {
            $decoded = $this->pendingLoginToken->decode($pendingToken);
        } catch (InvalidArgumentException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
        try {
            $authKey = $this->picnicAuth->verifyTwoFactorCode($decoded, $otp);
        } catch (RuntimeException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
        $this->persistSuccessfulSession($decoded->username, $decoded->countryCode, $authKey);

        return [
            'ok' => true,
            'secondFactorAuthenticationRequired' => false,
            'message' => 'Logged in to Picnic.',
        ];
    }

    private function persistSuccessfulSession(string $username, string $countryCode, string $authKey): void
    {
        $settings = $this->settingsRepo->getSingleton();
        $settings->changeUsername($username);
        $settings->changeCountryCode($countryCode);
        $settings->changeAuthKeyCipher(
            $this->credentialCipher->encryptAuthKeyForStorage($authKey),
        );
        $this->entityManager->flush();
    }

    private function applyUsername(PicnicIntegrationSettings $settings, bool $specified, mixed $raw): void
    {
        if (!$specified) {
            return;
        }
        $settings->changeUsername(\is_string($raw) ? ('' === trim($raw) ? null : trim($raw)) : null);
    }

    private function applyCountry(PicnicIntegrationSettings $settings, bool $specified, ?string $countryCode): void
    {
        if (!$specified || null === $countryCode) {
            return;
        }
        $settings->changeCountryCode($countryCode);
    }

    private function applyPassword(PicnicIntegrationSettings $settings, bool $specified, mixed $password): void
    {
        if (!$specified || !\is_string($password) || '' === $password) {
            return;
        }
        $settings->changePasswordCipher(
            $this->credentialCipher->encryptPasswordForStorage($password),
        );
    }

    private function applyAuthClear(PicnicIntegrationSettings $settings, bool $specified, bool $clearAuthSession): void
    {
        if (!$specified || true !== $clearAuthSession) {
            return;
        }
        $settings->changeAuthKeyCipher(null);
    }

    private function mapSettings(PicnicIntegrationSettings $settings): PicnicSettingsView
    {
        return new PicnicSettingsView(
            (string) $settings->getId(),
            $settings->getUsername(),
            $settings->getCountryCode(),
            $settings->hasStoredPassword(),
            $settings->hasStoredAuthSession(),
        );
    }

    private function mapSettingsResponse(PicnicSettingsView $settings): PicnicIntegrationSettingsResponse
    {
        return new PicnicIntegrationSettingsResponse(
            $settings->resourceId,
            $settings->username,
            $settings->countryCode,
            $settings->hasStoredPassword,
            $settings->hasStoredAuthSession,
        );
    }

    /**
     * @return list<PicnicCatalogSearchHitResponse>
     */
    public function catalogSearch(string $query): array
    {
        $hits = [];
        foreach ($this->searchCatalog($query) as $unit) {
            $hits[] = new PicnicCatalogSearchHitResponse(
                $unit->productId,
                $unit->name,
                $unit->imageId,
                $unit->displayPrice,
                $unit->unitQuantity,
            );
        }

        return $hits;
    }

    public function catalogProductSummary(string $productId): PicnicCatalogProductSummaryResponse
    {
        $summary = $this->productSummaryView($productId);

        return new PicnicCatalogProductSummaryResponse(
            $summary->productId,
            $summary->name,
            $summary->brand ?? '',
            $summary->unitQuantity ?? '',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function login(PostPicnicLoginRequest $body): array
    {
        return $this->loginFromValues($body->username, $body->password, $body->countryCode, $body->pendingToken, $body->otp);
    }

    /**
     * @return array<string, mixed>
     */
    public function requestTwoFactorCode(PostPicnicRequestTwoFactorCodeRequest $body): array
    {
        return $this->requestTwoFactorCodeFromValues($body->pendingToken, $body->channel);
    }
}
