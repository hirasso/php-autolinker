# Agent skills

Autolink urls and email addresses in your HTML using PHP and a fluent API.

## Key commands

| Command | Description |
|---|---|
| `composer test` | Run the test suite |
| `composer test:watch` | Run the tests in watch mode |
| `composer test:coverage` | Run tests with local HTML coverage report |
| `composer analyse` | Run PHPStan static analysis |
| `composer format` | Format the code with Pint |

## Code style

- Use `/** */` block comments (not `//`) for class / method / function explanations

## Writing new code

- add matching tests for each new feature
- run `composer test` and `composer analyse`
- If it makes sense for a change, suggest a changeset message and level (patch/minor/major) and write it into the `./.changeset` folder. Commit it together with the changes. Respect the package name `php-prose`

## Pull requests

- Do not mention yourself in PRs
