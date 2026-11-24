# Calculate Vacation

[![CI](https://github.com/okarabudka/calculate-vacation/actions/workflows/ci.yml/badge.svg)](https://github.com/okarabudka/calculate-vacation/actions/workflows/ci.yml)

A PHP console command that prints how many vacation days every employee gets in a given year. I wrote it in April and
May 2025 as a coding challenge for a PHP developer position (the task is restated in [TASK.md](TASK.md)) and
refactored it in 2026, step by step and in small commits, to the way I would build it today: three bugs fixed, small
vacation rules instead of a static service, a validating CSV reader, an injected clock, PHPUnit 12, PHPStan at level
max and CI on PHP 8.3 to 8.5.

## Why it exists

It is a refactoring case study. The 2025 code is kept as the tag
[`2025-original`](https://github.com/okarabudka/calculate-vacation/tree/2025-original); the full change is one click
away: [compare `2025-original` with `main`](https://github.com/okarabudka/calculate-vacation/compare/2025-original...main).

The interesting part is not the final code but the path to it. Every commit is one step that keeps the command
working: first a characterization test as a safety net, then the bugs it revealed, then the structure, then the
tooling. The table under [Refactoring steps](#refactoring-steps) links each problem in the 2025 code to the commit
that solved it and says why.

## Getting started

You need Docker and make.

```bash
git clone https://github.com/okarabudka/calculate-vacation.git && cd calculate-vacation
make install          # build the PHP 8.5 image and install the Composer dependencies
make run YEAR=2025    # without YEAR, the current year is used
```

Sample output for `data/employees-list.csv`:

```
Hans Müller: 30 vacation days in 2025
Angelika Fringe: 30 vacation days in 2025
Peter Klever: 28 vacation days in 2025
Marina Helter: 27 vacation days in 2025
Sepp Meier: 27 vacation days in 2025
```

With PHP 8.3 or newer installed locally, `composer install` and `php bin/console.php calculate:vacation 2025` work
without Docker. An invalid year exits with code 2, an invalid CSV with code 1 and a message naming the file and line.

| Target              | What it does                                           |
|---------------------|--------------------------------------------------------|
| `make install`      | Build the image and install the Composer dependencies  |
| `make run YEAR=...` | Print the vacation days for a year                     |
| `make test`         | Run the PHPUnit tests                                  |
| `make stan`         | Run PHPStan (level max)                                |
| `make cs`           | Check code style                                       |
| `make fix`          | Fix code style                                         |
| `make check`        | Code style, PHPStan and tests                          |
| `make help`         | List all targets                                       |

## Rules and assumptions

The rules come from [TASK.md](TASK.md). Where they leave room, the command assumes:

- The yearly minimum is 26 days, or the number from the special contract ("27 minimum vacation days").
- Age and years of employment are taken on 31 December of the requested year; only completed years count.
- The seniority bonus (one day per 5 years from age 30) is added before the pro rata cut.
- In the year a contract starts, the employee gets 1/12 of the days for each full month, rounded down. A contract
  starting on the 15th counts from the next month.
- Years before the contract start give 0 days. The CSV has no contract end, so every contract is open-ended.

## Refactoring steps

In order of the commits. Each step left the command working; from step 1 on, the tests proved it.

| # | Problem in the 2025 code | What was done | Why | Commit |
|---|--------------------------|---------------|-----|--------|
| 1 | One unit test for the service; nothing pinned what the command prints | Characterization test that runs the command for past years and compares the lines | Change behaviour only on purpose: a safety net before any fix or restructuring | [f5b492d](https://github.com/okarabudka/calculate-vacation/commit/f5b492ddb6a6464b721d950f6f415897f18fffec) |
| 2 | The repository passed `new DateTime()` as every employee's contract end: the current year was cut at today's month, later years printed 0 days | No contract end, since the CSV has none | The result must not depend on the day the command runs | [5f6dd6f](https://github.com/okarabudka/calculate-vacation/commit/5f6dd6fdc15a90f1e6f6c7c7032aaf1c5e949e47) |
| 3 | A contract starting on the 15th counted its first month as full, so 15 January gave a full year | The first counted month is the next one | The task grants days per full month worked | [0c41316](https://github.com/okarabudka/calculate-vacation/commit/0c413164904e06580e8e87541bdd13784a3f41ca) |
| 4 | The year was cast with `(int)`: `abc` printed 0 days for the year 0 and exited with success | Only a four-digit year is accepted, otherwise exit code 2 | Bad input must fail loudly, not produce plausible nonsense | [b56d609](https://github.com/okarabudka/calculate-vacation/commit/b56d609f20afef70a2a63ec1e0a831faf685a000) |
| 5 | PHPUnit 10, Symfony Console 7.2, `@dataProvider` annotations that PHPUnit 12 no longer reads | PHPUnit 12, Symfony Console 7.4, attributes, lock file resolved for PHP 8.3; tests fail on warnings and notices | Supported versions, and one lock file that installs on PHP 8.3 to 8.5 | [8f95f17](https://github.com/okarabudka/calculate-vacation/commit/8f95f17072f96ed4f269bf65e005d890b732abb6) |
| 6 | Mutable `DateTime` fields; a `Year` trait and a `YearDefinition` interface used as inheritance for two date helpers; an unreachable contract-end branch | **Replace Inheritance with Delegation:** `Employee` is a `final readonly` value object with `DateTimeImmutable` dates; trait, interface and dead branch removed | Values that cannot change cannot be changed by accident; behaviour belongs where the data is | [467df42](https://github.com/okarabudka/calculate-vacation/commit/467df42dceb62b73c83eef249b4d7563ffdb24be) |
| 7 | All rules in one static `EmployeeService::calculateVacation()` with `floor()` on floats | **Replace Conditional with Polymorphism:** `VacationCalculator` applies `VacationRule` objects (`SeniorityBonus`, `ProRataInFirstYear`), a `Year` value object, integer division | Each rule is one sentence of the task, tested on its own; a new rule is a new class, not another branch | [40375f5](https://github.com/okarabudka/calculate-vacation/commit/40375f52c9663298d7fbb8c8a3796858e84081b4) |
| 8 | A missing file read as empty, lenient `new DateTime()` parsing, the special contract read by casting "27 minimum vacation days" to int | `CsvEmployeeRepository` behind an `EmployeeRepository` interface: checks header, columns, strict `dd.mm.yyyy` dates without rollover, the 1st or 15th as contract start, the special contract text; errors name file and line | Wrong data must stop the run with a message that says what to fix | [ce21622](https://github.com/okarabudka/calculate-vacation/commit/ce21622d6adaeda5b08e093aab81f03e137b2e48) |
| 9 | The command built its own repository and calculator and knew the CSV path; tests depended on today | **Constructor injection** with `bin/console.php` as the single composition root; optional year from an injected PSR-20 clock | Dependencies become visible and replaceable; tests freeze time instead of waiting for a date | [108dec6](https://github.com/okarabudka/calculate-vacation/commit/108dec62b6bb86a85c73c9b4bf99088977f8fec4) |
| 10 | No static analysis, no code style | PHPStan level max with strict and PHPUnit rules over `bin`, `src` and `tests`; PHP CS Fixer with PER-CS | Machines check what reviewers should not have to | [098ef9d](https://github.com/okarabudka/calculate-vacation/commit/098ef9de5898b78ebb323f6674c294b04a0f443f) |
| 11 | An Apache image with a virtual host, Xdebug and upload limits for a console command; Composer from a download script | `php:8.5-cli` image with Composer from the official image, a Makefile (`make run YEAR=...`, `make check`) | The environment should match what the program is: no web server, one command to run it | [5f640e0](https://github.com/okarabudka/calculate-vacation/commit/5f640e08bf817e9f63c568967890eddb3f4e1ed2) |
| 12 | Nothing ran the checks automatically | GitHub Actions on PHP 8.3, 8.4 and 8.5 with code style, PHPStan, tests and the command itself; gitleaks; Dependabot | Every push is verified the same way on every supported version | [ebcbc3c](https://github.com/okarabudka/calculate-vacation/commit/ebcbc3cda8402aa8f7bafaa6178b6451d1231f52) |

Kept on purpose: the command name `calculate:vacation`, the output format and the CSV file, so the change stays a
refactoring and not a rewrite. Steps 2 and 3 change the printed numbers; the characterization test shows exactly
which ones.

## Architecture

```
bin/console.php ─▶ CalculateVacationCommand ─▶ EmployeeRepository (CSV) ─▶ list<Employee>
                                │
                                └─▶ VacationCalculator ─▶ SeniorityBonus ─▶ ProRataInFirstYear
```

| Path               | Contents                                                                    |
|--------------------|-----------------------------------------------------------------------------|
| `bin/console.php`  | Composition root: creates the services and runs the Symfony Console app     |
| `src/Console/`     | `CalculateVacationCommand`: reads the year, prints one line per employee    |
| `src/Employee/`    | `Employee` value object, `EmployeeRepository` and its CSV implementation    |
| `src/Vacation/`    | `VacationCalculator`, `VacationRule`, `Year` and the rules in `Rule/`       |
| `src/Clock/`       | `SystemClock` (PSR-20)                                                      |
| `tests/`           | Tests per rule, calculator, CSV reader; command tests with a frozen clock   |
| `docker/`          | PHP 8.5 CLI image                                                           |

## Status

Complete. Kept as a refactoring case study.

## License

MIT, see [LICENSE](LICENSE).
