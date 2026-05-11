<?php

declare(strict_types=1);

namespace App\Validator;

class InputValidator
{
    public static function username(string $value): string
    {
        $clean = trim($value);

        if ($clean === '' || strlen($clean) > 50) {
            throw new \InvalidArgumentException('Username must be 1–50 characters.');
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $clean)) {
            throw new \InvalidArgumentException('Username may only contain letters, numbers, and underscores.');
        }

        return $clean;
    }

    public static function email(string $value): string
    {
        $clean = trim(strtolower($value));

        if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address.');
        }

        if (strlen($clean) > 150) {
            throw new \InvalidArgumentException('Email too long.');
        }

        return $clean;
    }
}
