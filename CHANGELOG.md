# php-autolinker

## 1.0.0

### Major Changes

- f15a388: Stable release 🎉

### Minor Changes

- f15a388: Accept full and partial HTML documents in `::link()`: only the body gets linked, anything around it is preserved verbatim

### Patch Changes

- f15a388: Don't autolink inside `button`, `kbd`, `samp`, `var` and `math`
- 099ede6: Preserve whitespace around and inside the body of string input verbatim. Leading whitespace was dropped, and whitespace after `</body>` was duplicated into the body.

## 0.0.1

### Patch Changes

- 40e5aa3: Initial Release
