# Agent skills

Automatically link URLs, email addresses, @mentions and #hashtags
in a given block of text/HTML. The public API is a single static method,
`Autolinker::link()`.

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
- If it makes sense for a change, suggest a changeset message and level (patch/minor/major) and write it into the `./.changeset` folder. Commit it together with the changes.

## Pull requests

- Do not mention yourself in PRs
