<?php

declare(strict_types=1);

namespace Tests\Employee;

use App\Employee\CsvEmployeeRepository;
use App\Employee\Employee;
use App\Employee\InvalidEmployeeData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CsvEmployeeRepositoryTest extends TestCase
{
    private const string HEADER = "Name,Date of Birth,Contract Start Date,Special Contract\n";

    private string $file;

    protected function setUp(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'employees-');
        if ($file === false) {
            self::fail('Cannot create a temporary file.');
        }
        $this->file = $file;
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

    public function testReadsTheSampleFile(): void
    {
        $employees = (new CsvEmployeeRepository(__DIR__ . '/../../data/employees-list.csv'))->findAll();

        self::assertSame(
            ['Hans Müller', 'Angelika Fringe', 'Peter Klever', 'Marina Helter', 'Sepp Meier'],
            array_map(static fn(Employee $employee): string => $employee->name, $employees),
        );
        self::assertSame('1991-07-12', $employees[2]->birthDate->format('Y-m-d'));
        self::assertSame('2016-05-15', $employees[2]->contractStart->format('Y-m-d'));
        self::assertSame(27, $employees[2]->minimumVacationDays);
        self::assertNull($employees[0]->minimumVacationDays);
    }

    public function testSkipsBlankLinesAndAcceptsAByteOrderMark(): void
    {
        $this->write("\u{FEFF}" . self::HEADER . "\nAnna Berg,01.02.1985,01.03.2020\n\n");

        $employees = (new CsvEmployeeRepository($this->file))->findAll();

        self::assertCount(1, $employees);
        self::assertSame('Anna Berg', $employees[0]->name);
    }

    public function testReturnsNoEmployeesForAFileWithOnlyTheHeader(): void
    {
        $this->write(self::HEADER);

        self::assertSame([], (new CsvEmployeeRepository($this->file))->findAll());
    }

    public function testFailsForAMissingFile(): void
    {
        unlink($this->file);

        $this->expectException(InvalidEmployeeData::class);
        $this->expectExceptionMessage(sprintf('Cannot read the employee file "%s".', $this->file));

        (new CsvEmployeeRepository($this->file))->findAll();
    }

    #[DataProvider('invalidFiles')]
    public function testNamesTheLineAndTheProblem(string $content, string $message): void
    {
        $this->write($content);

        $this->expectException(InvalidEmployeeData::class);
        $this->expectExceptionMessage($this->file . ', ' . $message);

        (new CsvEmployeeRepository($this->file))->findAll();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidFiles(): iterable
    {
        yield 'empty file' => ['', 'line 1: expected the header'];
        yield 'wrong header' => ["Name,Birthday\n", 'line 1: expected the header'];
        yield 'too few columns' => [self::HEADER . "Anna Berg,01.02.1985\n", 'line 2: expected 3 or 4 columns, got 2.'];
        yield 'too many columns' => [self::HEADER . "Anna Berg,01.02.1985,01.03.2020,,x\n", 'line 2: expected 3 or 4 columns, got 5.'];
        yield 'empty name' => [self::HEADER . ",01.02.1985,01.03.2020\n", 'line 2: the name is empty.'];
        yield 'ISO date' => [self::HEADER . "Anna Berg,1985-02-01,01.03.2020\n", 'line 2: invalid date of birth "1985-02-01", expected dd.mm.yyyy.'];
        yield 'impossible date' => [self::HEADER . "Anna Berg,31.02.1985,01.03.2020\n", 'line 2: invalid date of birth "31.02.1985"'];
        yield 'contract start not on the 1st or 15th' => [self::HEADER . "Anna Berg,01.02.1985,10.03.2020\n", 'line 2: a contract starts on the 1st or the 15th of a month, got "10.03.2020".'];
        yield 'unknown special contract' => [self::HEADER . "Anna Berg,01.02.1985,01.03.2020,many days\n", 'line 2: unknown special contract "many days"'];
        yield 'error after a blank line' => [self::HEADER . "\nAnna Berg,01.02.1985,xx\n", 'line 3: invalid contract start date "xx"'];
    }

    private function write(string $content): void
    {
        file_put_contents($this->file, $content);
    }
}
