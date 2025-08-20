# GS Recruitment Task (Symfony Components)

CLI application that reads a JSON file of messages and outputs three JSON files:

- `inspections.json` — inspections
- `incident-reports.json` — incident reports
- `unprocessable.json` — duplicates / invalid

## Requirements
- PHP 8.1+
- Composer

## Install
```bash
composer install
```

## Run
```bash
php bin/console app:process:messages /path/to/recruitment-task-source-EN.json --outdir build --log var/log/app.log
```
## Composer
```bash
composer app /path/to/recruitment-task-source-EN.json --outdir build --log var/log/app.log
```

After execution:
- Outputs appear in the `build/` directory
- A summary is printed to the console
- A log is saved to `var/log/app.log`## Scripts


## Tests
```bash
./vendor/bin/phpunit --testdox
```
