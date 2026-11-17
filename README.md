# Calculate Vacation

A PHP console application that calculates the number of vacation days per employee for a given year.

## Context

Built in April and May 2025 as a coding challenge (test assignment) for a PHP developer position. The task description is included in the repository as a PDF. After the first working version the code was refactored: a Symfony Console command, separate service and repository layers, PHP 8.3, `final` classes and strict types.

## Features

- Console command `calculate:vacation <year>`
- Reads employees from `data/employees-list.csv` (name, date of birth, contract start date, special contract)
- Vacation rules implemented in `EmployeeService::calculateVacation()`:
  - 26 days per year by default, or the number of days from a special contract
  - employees aged 30 or older get one extra day for every 5 years of employment
  - a contract starting on the 1st or 15th of a month counts that month, otherwise counting starts with the next month
  - partial years are calculated pro rata (1/12 of the yearly days per month)
- Prints one line per employee: `<name>: <days> vacation days in <year>`

## Tech stack

- PHP 8.3 (Docker image `php:8.3-apache`)
- Symfony Console 7.2
- PHPUnit 10.5
- Composer, Docker Compose

## Architecture

- `bin/console.php`: Symfony Console application entry point
- `src/Commands/CalculateVacationCommand.php`: reads the year argument and prints the result
- `src/Services/EmployeeService.php`: vacation calculation logic
- `src/Repositories/EmployeeRepository.php`: loads employees from the CSV file
- `src/Models/Employee.php`: immutable employee model (readonly properties); year boundaries come from the `Year` trait and the `YearDefinition` interface

## Getting started

```bash
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app php bin/console.php calculate:vacation 2025
```

Replace `2025` with the year you want to calculate.

## Testing

```bash
docker compose exec app vendor/bin/phpunit tests
```

The tests in `tests/EmployeeTest.php` use a data provider with several employee and year combinations.

## Status

Finished test assignment (2025), not maintained.

## License

MIT, see [LICENSE](LICENSE).
