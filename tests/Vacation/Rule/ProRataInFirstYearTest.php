<?php

declare(strict_types=1);

namespace Tests\Vacation\Rule;

use App\Employee\Employee;
use App\Vacation\Rule\ProRataInFirstYear;
use App\Vacation\Year;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProRataInFirstYearTest extends TestCase
{
    #[DataProvider('cases')]
    public function testGrantsATwelfthPerFullMonthInTheFirstYear(string $contractStart, int $days, int $expected): void
    {
        $employee = new Employee('Test', new DateTimeImmutable('1990-01-01'), new DateTimeImmutable($contractStart));

        self::assertSame($expected, (new ProRataInFirstYear())->apply($days, $employee, new Year(2025)));
    }

    /**
     * @return iterable<string, array{string, int, int}>
     */
    public static function cases(): iterable
    {
        yield 'started in an earlier year' => ['2020-06-15', 26, 26];
        yield 'starts on 1 January' => ['2025-01-01', 26, 26];
        yield 'starts on 15 January, February to December' => ['2025-01-15', 26, 23];
        yield 'starts on 1 May, eight months' => ['2025-05-01', 27, 18];
        yield 'starts on 15 May, seven months' => ['2025-05-15', 27, 15];
        yield 'starts on 1 December, one month' => ['2025-12-01', 26, 2];
        yield 'starts on 15 December, no full month' => ['2025-12-15', 26, 0];
    }
}
