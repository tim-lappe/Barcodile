<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\DomainEvent;

use App\SharedKernel\Domain\Id\AbstractUuidId;
use BackedEnum;
use DateTimeInterface;
use ReflectionClass;
use ReflectionProperty;
use Stringable;
use UnitEnum;

final class DomainEventPersistedPayloadBuilder
{
    private const int MAX_DEPTH = 32;

    /**
     * @return array{eventClass: class-string, data: mixed}
     */
    public function build(object $event): array
    {
        return [
            'eventClass' => $event::class,
            'data' => $this->toSerializableData($event, 0),
        ];
    }

    private function toSerializableData(mixed $value, int $depth): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            return null;
        }
        if (\is_array($value)) {
            return $this->serializeArray($value, $depth);
        }

        return $this->serializeNonArrayValue($value, $depth);
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    private function serializeNonArrayValue(mixed $value, int $depth): mixed
    {
        if (null === $value || \is_bool($value) || \is_int($value) || \is_float($value) || \is_string($value)) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(\DATE_ATOM);
        }
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        if ($value instanceof UnitEnum) {
            return $value->name;
        }
        if ($value instanceof AbstractUuidId) {
            return (string) $value;
        }
        if (!\is_object($value)) {
            return null;
        }

        return $this->serializeObject($value, $depth);
    }

    /**
     * @param array<mixed> $value
     *
     * @return array<mixed>
     */
    private function serializeArray(array $value, int $depth): array
    {
        $out = [];
        foreach ($value as $key => $item) {
            $out[$key] = $this->toSerializableData($item, $depth + 1);
        }

        return $out;
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    private function serializeObject(object $value, int $depth): mixed
    {
        if (\is_callable([$value, 'getId'])) {
            $entityId = $value->getId();
            $shortName = (new ReflectionClass($value))->getShortName();

            return [
                'entity' => $shortName,
                'id' => $this->idValueToString($entityId),
            ];
        }

        $reflection = new ReflectionClass($value);
        $data = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            if (!$property->isInitialized($value)) {
                $data[$property->getName()] = null;

                continue;
            }
            $data[$property->getName()] = $this->toSerializableData($property->getValue($value), $depth + 1);
        }
        if ([] !== $data) {
            return $data;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        return $reflection->getName();
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function idValueToString(mixed $rawId): string
    {
        if ($rawId instanceof Stringable) {
            return (string) $rawId;
        }
        if (\is_string($rawId) || \is_int($rawId) || \is_float($rawId)) {
            return (string) $rawId;
        }
        $encoded = json_encode($rawId);

        return false === $encoded ? '' : $encoded;
    }
}
