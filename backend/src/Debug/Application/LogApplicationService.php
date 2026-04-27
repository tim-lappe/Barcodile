<?php

declare(strict_types=1);

namespace App\Debug\Application;

use App\Debug\Application\Dto\LogEntryResponse;
use App\Debug\Application\Dto\LogListResponse;
use SplFileObject;

final readonly class LogApplicationService
{
    private const int DEFAULT_LIMIT = 200;
    private const int MAX_LIMIT = 1000;

    public function __construct(
        private string $logsDir,
        private string $environment,
    ) {
    }

    public function listRecentLogs(?int $limit = null, ?string $level = null, ?string $query = null, ?string $channel = null): LogListResponse
    {
        $filePath = $this->logFilePath();
        $source = basename($filePath);
        if (!is_file($filePath)) {
            return new LogListResponse($source, []);
        }

        $entries = [];
        $tailLimit = $this->normalizeLimit($limit);
        foreach ($this->readTailLines($filePath, $tailLimit) as $line) {
            $entry = $this->parseLine($line['number'], $line['raw']);
            if (!$this->matchesFilters($entry, $level, $query, $channel)) {
                continue;
            }
            $entries[] = $entry;
        }

        return new LogListResponse($source, $entries);
    }

    private function logFilePath(): string
    {
        return \sprintf('%s/%s.log', rtrim($this->logsDir, '/'), $this->environment);
    }

    private function normalizeLimit(?int $limit): int
    {
        if (null === $limit || $limit < 1) {
            return self::DEFAULT_LIMIT;
        }

        return min($limit, self::MAX_LIMIT);
    }

    /**
     * @return list<array{number: int, raw: string}>
     */
    private function readTailLines(string $filePath, int $limit): array
    {
        $file = new SplFileObject($filePath, 'r');
        $file->seek(\PHP_INT_MAX);
        $lastLine = $file->key();
        $lines = [];
        $linesFound = 0;

        for ($lineNumber = $lastLine; $lineNumber >= 0 && $linesFound < $limit; --$lineNumber) {
            $line = $this->tailLineFromFile($file, $lineNumber, $lastLine);
            if (null === $line) {
                continue;
            }

            $lines[] = $line;
            ++$linesFound;
        }

        return $lines;
    }

    /**
     * @return array{number: int, raw: string}|null
     */
    private function tailLineFromFile(SplFileObject $file, int $lineNumber, int $lastLine): ?array
    {
        $file->seek($lineNumber);
        $raw = $file->current();
        if (!\is_string($raw)) {
            return null;
        }

        $raw = rtrim($raw, "\r\n");
        if ('' === $raw && $lineNumber === $lastLine) {
            return null;
        }

        return [
            'number' => $lineNumber + 1,
            'raw' => $raw,
        ];
    }

    private function parseLine(int $lineNumber, string $raw): LogEntryResponse
    {
        $matches = [];
        $loggedAt = null;
        $channel = null;
        $level = null;
        $message = null;

        if (1 === preg_match('/^\[(?<loggedAt>[^\]]+)] (?<channel>.+)\.(?<level>[A-Z]+): (?<message>.*)$/', $raw, $matches)) {
            $loggedAt = $matches['loggedAt'];
            $channel = $matches['channel'];
            $level = $matches['level'];
            $message = $matches['message'];
        }

        return new LogEntryResponse(
            entryIdentifier: sha1($lineNumber.':'.$raw),
            lineNumber: $lineNumber,
            raw: $raw,
            loggedAt: $loggedAt,
            channel: $channel,
            level: $level,
            message: $message,
        );
    }

    private function matchesFilters(LogEntryResponse $entry, ?string $level, ?string $query, ?string $channel): bool
    {
        return $this->matchesLevel($entry, $level)
            && $this->matchesChannelFilter($entry, $channel)
            && $this->matchesQuery($entry, $query);
    }

    private function matchesLevel(LogEntryResponse $entry, ?string $level): bool
    {
        $normalizedLevel = $this->normalizeNullableString($level);

        return null === $normalizedLevel || $entry->level === $normalizedLevel;
    }

    private function matchesChannelFilter(LogEntryResponse $entry, ?string $channel): bool
    {
        $normalizedChannel = $this->normalizeNullableString($channel);

        return null === $normalizedChannel || $this->matchesChannel($entry, $normalizedChannel);
    }

    private function matchesQuery(LogEntryResponse $entry, ?string $query): bool
    {
        $normalizedQuery = $this->normalizeNullableString($query);
        if (null === $normalizedQuery) {
            return true;
        }

        return str_contains(strtolower($entry->raw), strtolower($normalizedQuery));
    }

    private function matchesChannel(LogEntryResponse $entry, string $channel): bool
    {
        if (null === $entry->channel) {
            return false;
        }

        return strtolower($entry->channel) === strtolower($channel);
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $trimmed = trim($value);
        if ('' === $trimmed) {
            return null;
        }

        return strtoupper($trimmed);
    }
}
