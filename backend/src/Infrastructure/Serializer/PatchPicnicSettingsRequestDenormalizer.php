<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Application\Picnic\Dto\PatchPicnicSettingsRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PatchPicnicSettingsRequestDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        DenormalizerArgTrace::noteSupports($format, $context);

        return PatchPicnicSettingsRequest::class === $type && \is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        DenormalizerArgTrace::noteTypes($format);

        return [
            PatchPicnicSettingsRequest::class => true,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PatchPicnicSettingsRequest
    {
        if (!\is_array($data)) {
            throw new NotNormalizableValueException('Expected an object to patch Picnic settings.');
        }
        DenormalizerArgTrace::noteDenormalize($type, $format, $context);

        return $this->requestFromArray($data);
    }

    /**
     * @param array<mixed> $data
     */
    private function requestFromArray(array $data): PatchPicnicSettingsRequest
    {
        $usernameSpecified = \array_key_exists('username', $data);
        $username = $usernameSpecified ? ($data['username'] ?? null) : null;
        [$countryCodeSpecified, $countryCode] = $this->readCountryCodeField($data);
        [$passwordSpecified, $password] = $this->readPasswordFields($data);
        [$clearSpecified, $clear] = $this->readClearSessionFields($data);

        return new PatchPicnicSettingsRequest(
            $usernameSpecified,
            $username,
            $countryCodeSpecified,
            $countryCode,
            $passwordSpecified,
            $password,
            $clearSpecified,
            $clear,
        );
    }

    /**
     * @param array<mixed> $data
     *
     * @return array{0: bool, 1: ?string}
     */
    private function readPasswordFields(array $data): array
    {
        $passwordSpecified = \array_key_exists('password', $data);
        $password = $passwordSpecified && \is_string($data['password'] ?? null) ? $data['password'] : null;

        return [$passwordSpecified, $password];
    }

    /**
     * @param array<mixed> $data
     *
     * @return array{0: bool, 1: ?bool}
     */
    private function readClearSessionFields(array $data): array
    {
        $clearSpecified = \array_key_exists('clearPicnicAuthSession', $data);
        $clear = $clearSpecified ? (bool) ($data['clearPicnicAuthSession'] ?? false) : null;

        return [$clearSpecified, $clear];
    }

    /**
     * @param array<mixed> $data
     *
     * @return array{0: bool, 1: ?string}
     */
    private function readCountryCodeField(array $data): array
    {
        $countryCodeSpecified = \array_key_exists('countryCode', $data);
        if (!$countryCodeSpecified) {
            return [false, null];
        }
        $countryCodeRaw = $data['countryCode'];
        if (!\is_string($countryCodeRaw)) {
            throw new BadRequestHttpException('countryCode must be a string.');
        }

        return [true, $countryCodeRaw];
    }
}
