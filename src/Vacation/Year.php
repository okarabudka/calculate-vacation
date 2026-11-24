<?php

declare(strict_types=1);

namespace App\Vacation;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class Year
{
    public function __construct(public int $value)
    {
        if ($value < 1000 || $value > 9999) {
            throw new InvalidArgumentException(sprintf('The year must be a four-digit year, e.g. 2025; got %d.', $value));
        }
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^[1-9]\d{3}$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('The year must be a four-digit year, e.g. 2025; got "%s".', $value));
        }

        return new self((int) $value);
    }

    public function lastDay(): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%d-12-31', $this->value), new DateTimeZone('UTC'));
    }

    public function contains(DateTimeImmutable $date): bool
    {
        return (int) $date->format('Y') === $this->value;
    }

    public function isBefore(DateTimeImmutable $date): bool
    {
        return $this->value < (int) $date->format('Y');
    }
}
