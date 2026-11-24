<?php

declare(strict_types=1);

namespace App\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use App\Employee\CsvEmployeeRepository;
use App\Employee\InvalidEmployeeData;
use App\Vacation\Rule\ProRataInFirstYear;
use App\Vacation\Rule\SeniorityBonus;
use App\Vacation\VacationCalculator;
use App\Vacation\Year;
use InvalidArgumentException;

#[AsCommand(
    name: 'calculate:vacation',
    description: 'Calculates vacation.',
    hidden: false
)]

final class CalculateVacationCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Calculating vacation days for employees for a given year.')
            ->addArgument('year', InputArgument::REQUIRED, 'The year to calculate for');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $year = Year::fromString($input->getArgument('year'));
        } catch (InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::INVALID;
        }

        $repository = new CsvEmployeeRepository(__DIR__ . '/../../data/employees-list.csv');
        $calculator = new VacationCalculator(new SeniorityBonus(), new ProRataInFirstYear());

        try {
            $employees = $repository->findAll();
        } catch (InvalidEmployeeData $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        if (empty($employees)) {
            $output->writeln('Employees not found');
            return Command::FAILURE;
        }

        foreach ($employees as $employee) {
            $output->writeln(sprintf(
                '%s: %d vacation days in %d',
                $employee->name,
                $calculator->calculate($employee, $year),
                $year->value,
            ));
        }

        return Command::SUCCESS;
    }
}
