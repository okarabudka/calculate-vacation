<?php

declare(strict_types=1);

namespace Tests\Console;

use App\Console\CalculateVacationCommand;
use App\Employee\CsvEmployeeRepository;
use App\Employee\EmployeeRepository;
use App\Employee\InvalidEmployeeData;
use App\Vacation\Rule\ProRataInFirstYear;
use App\Vacation\Rule\SeniorityBonus;
use App\Vacation\VacationCalculator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\FrozenClock;

/**
 * Runs the command on the sample CSV in data/. The expected lines were pinned on the original code
 * before the refactoring and changed only by the documented bug fixes.
 */
final class CalculateVacationCommandTest extends TestCase
{
    private const array NAMES = ['Hans Müller', 'Angelika Fringe', 'Peter Klever', 'Marina Helter', 'Sepp Meier'];

    /**
     * @param list<int> $days
     */
    #[DataProvider('sampleOutputProvider')]
    public function testPrintsVacationDaysForTheSampleEmployees(int $year, array $days): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['year' => (string) $year]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame($this->lines($year, $days), $tester->getDisplay());
    }

    /**
     * @return iterable<string, array{int, list<int>}>
     */
    public static function sampleOutputProvider(): iterable
    {
        yield 'before any contract' => [2000, [0, 0, 0, 0, 0]];
        yield 'contract starting on 15 January skips January' => [2001, [26, 23, 0, 0, 0]];
        yield 'special contract starting on 15 May counts June to December' => [2016, [29, 29, 15, 0, 0]];
        yield 'contract starts in December' => [2017, [29, 29, 27, 0, 2]];
        yield 'contract starts on 15 January' => [2018, [29, 29, 27, 23, 26]];
        yield 'special contract gets a bonus day' => [2021, [30, 30, 28, 26, 26]];
        yield 'everybody has a bonus' => [2025, [30, 30, 28, 27, 27]];
        yield 'a year in the future' => [2030, [31, 31, 29, 28, 28]];
    }

    public function testUsesTheCurrentYearWithoutAnArgument(): void
    {
        $tester = $this->tester(new DateTimeImmutable('2030-06-15'));

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame($this->lines(2030, [31, 31, 29, 28, 28]), $tester->getDisplay());
    }

    #[DataProvider('invalidYearProvider')]
    public function testRejectsAYearThatIsNotAFourDigitNumber(string $year): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['year' => $year]);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString('four-digit year', $tester->getDisplay());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidYearProvider(): iterable
    {
        yield 'text' => ['abc'];
        yield 'number with text' => ['2025abc'];
        yield 'zero' => ['0'];
        yield 'negative' => ['-2025'];
    }

    public function testReportsInvalidEmployeeData(): void
    {
        $tester = $this->tester(repository: new CsvEmployeeRepository('/nonexistent/employees.csv'));

        $exitCode = $tester->execute(['year' => '2025']);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Cannot read the employee file "/nonexistent/employees.csv".', $tester->getDisplay());
    }

    public function testFailsWithoutEmployees(): void
    {
        $empty = new class implements EmployeeRepository {
            public function findAll(): array
            {
                return [];
            }
        };
        $tester = $this->tester(repository: $empty);

        $exitCode = $tester->execute(['year' => '2025']);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame('Employees not found' . PHP_EOL, $tester->getDisplay());
    }

    private function tester(?DateTimeImmutable $now = null, ?EmployeeRepository $repository = null): CommandTester
    {
        return new CommandTester(new CalculateVacationCommand(
            $repository ?? new CsvEmployeeRepository(__DIR__ . '/../../data/employees-list.csv'),
            new VacationCalculator(new SeniorityBonus(), new ProRataInFirstYear()),
            new FrozenClock($now ?? new DateTimeImmutable('2025-06-15')),
        ));
    }

    /**
     * @param list<int> $days
     */
    private function lines(int $year, array $days): string
    {
        $output = '';
        foreach (self::NAMES as $i => $name) {
            $output .= sprintf('%s: %d vacation days in %d', $name, $days[$i], $year) . PHP_EOL;
        }

        return $output;
    }
}
