<?php

declare(strict_types=1);

namespace Tests\Vacation;

use App\Employee\Employee;
use App\Vacation\Rule\ProRataInFirstYear;
use App\Vacation\Rule\SeniorityBonus;
use App\Vacation\VacationCalculator;
use App\Vacation\VacationRule;
use App\Vacation\Year;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VacationCalculatorTest extends TestCase
{
    public function testStartsWithTheDefaultMinimum(): void
    {
        self::assertSame(26, (new VacationCalculator())->calculate($this->employee('2010-01-01'), new Year(2025)));
    }

    public function testStartsWithTheMinimumOfASpecialContract(): void
    {
        self::assertSame(32, (new VacationCalculator())->calculate($this->employee('2010-01-01', 32), new Year(2025)));
    }

    public function testGrantsNothingBeforeTheContractStarts(): void
    {
        self::assertSame(0, (new VacationCalculator())->calculate($this->employee('2026-01-01'), new Year(2025)));
    }

    public function testAppliesTheRulesInOrder(): void
    {
        $double = new class implements VacationRule {
            public function apply(int $days, Employee $employee, Year $year): int
            {
                return $days * 2;
            }
        };
        $addOne = new class implements VacationRule {
            public function apply(int $days, Employee $employee, Year $year): int
            {
                return $days + 1;
            }
        };

        self::assertSame(53, (new VacationCalculator($double, $addOne))->calculate($this->employee('2010-01-01'), new Year(2025)));
    }

    #[DataProvider('policyCases')]
    public function testCombinesTheBonusAndThePartialFirstYear(string $contractStart, ?int $minimum, int $expected): void
    {
        $calculator = new VacationCalculator(new SeniorityBonus(), new ProRataInFirstYear());

        self::assertSame($expected, $calculator->calculate($this->employee($contractStart, $minimum), new Year(2024)));
    }

    /**
     * @return iterable<string, array{string, ?int, int}>
     */
    public static function policyCases(): iterable
    {
        yield 'bonus for 14 years' => ['2010-01-01', null, 28];
        yield 'special contract, no bonus yet' => ['2020-01-01', 32, 32];
        yield 'starts on 15 March' => ['2024-03-15', null, 19];
    }

    private function employee(string $contractStart, ?int $minimum = null): Employee
    {
        return new Employee('Test', new DateTimeImmutable('1980-01-01'), new DateTimeImmutable($contractStart), $minimum);
    }
}
