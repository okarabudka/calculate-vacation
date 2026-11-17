# Task: yearly vacation days

Restated in my own words from the original assignment.

Build a PHP command-line application that calculates how many vacation days each employee of a fictional
company gets in a given year. The year is the only input argument; the output lists every employee with their
number of vacation days for that year. The design should be well thought out but pragmatic, and the work should
take no more than a few hours.

## Rules

- Every employee gets at least 26 vacation days per year.
- A special contract may set a different minimum.
- Employees aged 30 or older get one extra day for every 5 years of employment.
- Contracts start on the 1st or the 15th of a month.
- If a contract starts during the year, the employee gets 1/12 of the yearly days for each full month worked.

## Employees

| Name | Date of birth | Contract start | Special contract |
|---|---|---|---|
| Hans Müller | 30.12.1970 | 01.01.2001 | |
| Angelika Fringe | 09.06.1976 | 15.01.2001 | |
| Peter Klever | 12.07.1991 | 15.05.2016 | 27 minimum vacation days |
| Marina Helter | 26.01.1970 | 15.01.2018 | |
| Sepp Meier | 23.05.1980 | 01.12.2017 | |

## Constraints

- No web application and no database for the employee data.
- A README explains how to set up and run the command and its tests.
- Third-party packages are allowed; vendor packages are not part of the delivered code.
- Ambiguities in the rules are resolved by documented assumptions in the README.
