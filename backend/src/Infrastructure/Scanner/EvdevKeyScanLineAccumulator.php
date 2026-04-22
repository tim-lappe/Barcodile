<?php

declare(strict_types=1);

namespace App\Infrastructure\Scanner;

final class EvdevKeyScanLineAccumulator
{
    private const int EV_KEY = 1;

    private const int KEY_ENTER = 28;

    private const int KEY_KPENTER = 96;

    private const int KEY_LEFTSHIFT = 42;

    private const int KEY_RIGHTSHIFT = 54;

    private const int KEY_BACKSPACE = 14;

    private const int KEY_LEFTCTRL = 29;

    private string $line = '';

    private bool $leftShift = false;

    private bool $rightShift = false;

    /**
     * @var array<int, array{0: string, 1: string}>
     */
    private const KEY_TO_CHAR_PAIRS = [
        2 => ['1', '!'], 3 => ['2', '@'], 4 => ['3', '#'], 5 => ['4', '$'], 6 => ['5', '%'],
        7 => ['6', '^'], 8 => ['7', '&'], 9 => ['8', '*'], 10 => ['9', '('], 11 => ['0', ')'],
        12 => ['-', '_'], 13 => ['=', '+'], 15 => [' ', ' '],
        16 => ['q', 'Q'], 17 => ['w', 'W'], 18 => ['e', 'E'], 19 => ['r', 'R'], 20 => ['t', 'T'],
        21 => ['y', 'Y'], 22 => ['u', 'U'], 23 => ['i', 'I'], 24 => ['o', 'O'], 25 => ['p', 'P'],
        26 => ['[', '{'], 27 => [']', '}'], 30 => ['a', 'A'], 31 => ['s', 'S'],
        32 => ['d', 'D'], 33 => ['f', 'F'], 34 => ['g', 'G'], 35 => ['h', 'H'], 36 => ['j', 'J'],
        37 => ['k', 'K'], 38 => ['l', 'L'], 39 => [';', ':'], 40 => ['\'', '"'], 41 => ['`', '~'],
        43 => ['\\', '|'], 44 => ['z', 'Z'], 45 => ['x', 'X'], 46 => ['c', 'C'], 47 => ['v', 'V'],
        48 => ['b', 'B'], 49 => ['n', 'N'], 50 => ['m', 'M'], 51 => [',', '<'], 52 => ['.', '>'],
        53 => ['/', '?'], 57 => [' ', ' '],
        55 => ['*', '*'], 71 => ['7', '7'], 72 => ['8', '8'], 73 => ['9', '9'], 74 => ['-', '-'],
        75 => ['4', '4'], 76 => ['5', '5'], 77 => ['6', '6'], 78 => ['+', '+'], 79 => ['1', '1'],
        80 => ['2', '2'], 81 => ['3', '3'],         82 => ['0', '0'], 83 => ['.', '.'],
    ];

    public function process(int $type, int $code, int $value): ?string
    {
        if (self::EV_KEY !== $type) {
            return null;
        }
        if (self::KEY_LEFTSHIFT === $code) {
            $this->leftShift = $value > 0;

            return null;
        }
        if (self::KEY_RIGHTSHIFT === $code) {
            $this->rightShift = $value > 0;

            return null;
        }
        if (0 === $value) {
            return null;
        }
        if (self::KEY_ENTER === $code || self::KEY_KPENTER === $code) {
            if (1 !== $value) {
                return null;
            }
            $out = $this->line;
            $this->line = '';
            $this->leftShift = false;
            $this->rightShift = false;

            return $out;
        }
        if (1 !== $value && 2 !== $value) {
            return null;
        }
        if (self::KEY_BACKSPACE === $code) {
            if ('' !== $this->line) {
                $this->line = substr($this->line, 0, -1);
            }

            return null;
        }
        if (self::KEY_LEFTCTRL === $code) {
            return null;
        }
        if (!isset(self::KEY_TO_CHAR_PAIRS[$code])) {
            return null;
        }
        $pair = self::KEY_TO_CHAR_PAIRS[$code];
        $shift = $this->leftShift || $this->rightShift;
        $ch = $shift ? $pair[1] : $pair[0];
        $this->line .= $ch;

        return null;
    }
}
