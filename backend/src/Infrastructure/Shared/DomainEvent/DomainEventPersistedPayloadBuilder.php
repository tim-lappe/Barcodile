<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\DomainEvent;

use App\Domain\Shared\Id\AbstractUuidId;
use BackedEnum;
use DateTimeInterface;
use ReflectionClass;
use ReflectionProperty;
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
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[$key] = $this->toSerializableData($item, $depth + 1);
            }

            return $out;
        }
        if (null === $value || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
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
        if (is_callable([$value, 'getId'])) {
            $id = $value->getId();

            $shortName = (new ReflectionClass($value))->getShortName();

            return [
                'entity' => $shortName,
                'id' => $this->idValueToString($id),
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

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return $reflection->getName();
    }

    private function idValueToString(mixed $id): string
    {
        if ($id instanceof \Stringable) {
            return (string) $id;
        }
        if (is_string($id)) {
            return $id;
        }
        if (is_int($id) || is_float($id)) {
            return (string) $id;
        }

        return (string) json_encode($id, \JSON_THROW_ON_ERROR);
    }
}
