<?php

declare(strict_types=1);

namespace Tests;

use App\Commands\CalculateVacationCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Characterization tests: pin the output for the sample CSV in data/ before the code is restructured.
 */
final class CalculateVacationCommandTest extends TestCase
{
    /**
     * @dataProvider sampleOutputProvider
     */
    public function testPrintsVacationDaysForTheSampleEmployees(int $year, array $days): void
    {
        $tester = new CommandTester(new CalculateVacationCommand());

        $exitCode = $tester->execute(['year' => (string) $year]);

        $expected = '';
        foreach ($days as $name => $count) {
            $expected .= "$name: $count vacation days in $year" . PHP_EOL;
        }

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame($expected, $tester->getDisplay());
    }

    public static function sampleOutputProvider(): array
    {
        $names = ['Hans Müller', 'Angelika Fringe', 'Peter Klever', 'Marina Helter', 'Sepp Meier'];

        return [
            'before any contract' => [2000, array_combine($names, [0, 0, 0, 0, 0])],
            'contract starting on 15 January skips January' => [2001, array_combine($names, [26, 23, 0, 0, 0])],
            'special contract starting on 15 May counts June to December' => [2016, array_combine($names, [29, 29, 15, 0, 0])],
            'contract starts in December' => [2017, array_combine($names, [29, 29, 27, 0, 2])],
            'contract starts on 15 January' => [2018, array_combine($names, [29, 29, 27, 23, 26])],
            'special contract gets a bonus day' => [2021, array_combine($names, [30, 30, 28, 26, 26])],
            'everybody has a bonus' => [2025, array_combine($names, [30, 30, 28, 27, 27])],
            'a year in the future' => [2030, array_combine($names, [31, 31, 29, 28, 28])],
        ];
    }
}
