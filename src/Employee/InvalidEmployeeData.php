<?php

declare(strict_types=1);

namespace App\Employee;

use RuntimeException;

final class InvalidEmployeeData extends RuntimeException
{
    public static function unreadableFile(string $path): self
    {
        return new self(sprintf('Cannot read the employee file "%s".', $path));
    }

    public static function inLine(string $path, int $line, string $problem): self
    {
        return new self(sprintf('%s, line %d: %s', $path, $line, $problem));
    }
}
