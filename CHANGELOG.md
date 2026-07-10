# php-prose

## 0.1.1

### Patch Changes

- 1a80a27: Initial Release

## 0.1.0

### Minor Changes

- 5235fc1: `Prose::format()` now throws an `InvalidArgumentException` when passed a full HTML document (`<!doctype>`, `<html>`, `<head>` or `<body>`) instead of trying to preserve the wrapper. Prose only formats prose fragments.
