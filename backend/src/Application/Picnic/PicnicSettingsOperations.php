<?php

declare(strict_types=1);

namespace App\Application\Picnic;

use App\Application\Picnic\Dto\PatchPicnicSettingsRequest;
use App\Application\Picnic\Dto\PicnicIntegrationSettingsResponse;
use App\Domain\Picnic\Entity\PicnicIntegrationSettings;
use App\Domain\Picnic\Port\PicnicCredentialCipherPort;
use App\Domain\Picnic\Repository\PicnicIntegrationSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class PicnicSettingsOperations
{
    public function __construct(
        private PicnicIntegrationSettingsRepository $settingsRepo,
        private PicnicCredentialCipherPort $credentialCipher,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function get(): PicnicIntegrationSettingsResponse
    {
        return $this->map($this->settingsRepo->getSingleton());
    }

    public function patch(PatchPicnicSettingsRequest $patch): PicnicIntegrationSettingsResponse
    {
        $settings = $this->settingsRepo->getSingleton();
        $this->applyUsername($settings, $patch);
        $this->applyCountry($settings, $patch);
        $this->applyPassword($settings, $patch);
        $this->applyAuthClear($settings, $patch);
        $violations = $this->validator->validate($settings);
        if (\count($violations) > 0) {
            $messages = [];
            foreach ($violations as $one) {
                $messages[] = $one->getMessage();
            }
            throw new BadRequestHttpException(implode(' ', $messages));
        }
        $this->entityManager->flush();

        return $this->map($settings);
    }

    private function applyUsername(PicnicIntegrationSettings $settings, PatchPicnicSettingsRequest $patch): void
    {
        if (!$patch->usernameSpecified) {
            return;
        }
        $raw = $patch->username;
        $settings->changeUsername(\is_string($raw) ? ('' === trim($raw) ? null : trim($raw)) : null);
    }

    private function applyCountry(PicnicIntegrationSettings $settings, PatchPicnicSettingsRequest $patch): void
    {
        if (!$patch->countryCodeSpecified || null === $patch->countryCode) {
            return;
        }
        $settings->changeCountryCode($patch->countryCode);
    }

    private function applyPassword(PicnicIntegrationSettings $settings, PatchPicnicSettingsRequest $patch): void
    {
        if (!$patch->passwordSpecified || !\is_string($patch->password) || '' === $patch->password) {
            return;
        }
        $settings->changePasswordCipher(
            $this->credentialCipher->encryptPasswordForStorage($patch->password),
        );
    }

    private function applyAuthClear(PicnicIntegrationSettings $settings, PatchPicnicSettingsRequest $patch): void
    {
        if (!$patch->authClearSpecified || true !== $patch->clearAuthSession) {
            return;
        }
        $settings->changeAuthKeyCipher(null);
    }

    private function map(PicnicIntegrationSettings $settings): PicnicIntegrationSettingsResponse
    {
        return new PicnicIntegrationSettingsResponse(
            (string) $settings->getId(),
            $settings->getUsername(),
            $settings->getCountryCode(),
            $settings->hasStoredPassword(),
            $settings->hasStoredAuthSession(),
        );
    }
}
