#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Clock\SystemClock;
use App\Console\CalculateVacationCommand;
use App\Employee\CsvEmployeeRepository;
use App\Vacation\Rule\ProRataInFirstYear;
use App\Vacation\Rule\SeniorityBonus;
use App\Vacation\VacationCalculator;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

// Composition root: the only place that knows the concrete classes.
$command = new CalculateVacationCommand(
    new CsvEmployeeRepository(dirname(__DIR__) . '/data/employees-list.csv'),
    new VacationCalculator(new SeniorityBonus(), new ProRataInFirstYear()),
    new SystemClock(),
);

$application = new Application('calculate-vacation');
$application->addCommand($command);
$application->run();
