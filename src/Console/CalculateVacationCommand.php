<?php

declare(strict_types=1);

namespace App\Console;

use App\Employee\EmployeeRepository;
use App\Employee\InvalidEmployeeData;
use App\Vacation\VacationCalculator;
use App\Vacation\Year;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'calculate:vacation', description: 'Prints the vacation days of every employee for a year.')]
final class CalculateVacationCommand extends Command
{
    public function __construct(
        private readonly EmployeeRepository $employees,
        private readonly VacationCalculator $calculator,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('year', InputArgument::OPTIONAL, 'Four-digit year, defaults to the current year');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $year = $this->year($input->getArgument('year'));
            $employees = $this->employees->findAll();
        } catch (InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::INVALID;
        } catch (InvalidEmployeeData $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        if ($employees === []) {
            $output->writeln('Employees not found');
            return Command::FAILURE;
        }

        foreach ($employees as $employee) {
            $output->writeln(sprintf(
                '%s: %d vacation days in %d',
                $employee->name,
                $this->calculator->calculate($employee, $year),
                $year->value,
            ));
        }

        return Command::SUCCESS;
    }

    private function year(mixed $argument): Year
    {
        if ($argument === null) {
            return new Year((int) $this->clock->now()->format('Y'));
        }

        if (!is_string($argument)) {
            throw new InvalidArgumentException('The year must be a four-digit year, e.g. 2025.');
        }

        return Year::fromString($argument);
    }
}
