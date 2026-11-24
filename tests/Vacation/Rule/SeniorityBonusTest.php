<?php

declare(strict_types=1);

namespace Tests\Vacation\Rule;

use App\Employee\Employee;
use App\Vacation\Rule\SeniorityBonus;
use App\Vacation\Year;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SeniorityBonusTest extends TestCase
{
    #[DataProvider('cases')]
    public function testAddsOneDayPerFiveYearsFromTheAgeOfThirty(string $birthDate, string $contractStart, int $year, int $expected): void
    {
        $employee = new Employee('Test', new DateTimeImmutable($birthDate), new DateTimeImmutable($contractStart));

        $this->assertSame($expected, (new SeniorityBonus())->apply(26, $employee, new Year($year)));
    }

    /**
     * @return iterable<string, array{string, string, int, int}>
     */
    public static function cases(): iterable
    {
        yield 'younger than 30, long employment' => ['1996-01-01', '2010-01-01', 2025, 26];
        yield 'turns 30 on the last day of the year' => ['1995-12-31', '2010-01-01', 2025, 29];
        yield '30 or older, under 5 years' => ['1980-01-01', '2021-01-02', 2025, 26];
        yield 'exactly 5 years on the last day of the year' => ['1980-01-01', '2020-12-31', 2025, 27];
        yield '14 years' => ['1980-01-01', '2011-01-01', 2025, 28];
        yield '15 years' => ['1980-01-01', '2010-01-01', 2025, 29];
    }
}
