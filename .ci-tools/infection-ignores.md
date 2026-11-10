# Infection ignore rules

- `CastString` in `PhpRuntimeInfo::getExportData`: the integer size is concatenated into a string, and the listed PHP configuration directives normally return strings. Removing these casts does not change the exported values under that contract. The casts still make the intended output type explicit.
- The caret, dollar and regex-flag mutations in `KeyFigures::parseDate` are ignored only in that method. Even if the regular expression accepts a partial date, `createFromFormat('!Y-m-d', ...)` and the subsequent exact round-trip comparison reject it. Date validation is also covered through public query tests.
- `PregMatchRemoveCaret` in `FixArtextendsItems::initialValue` affects only database type patterns; `SHOW FULL COLUMNS` supplies canonical SQL type names rather than arbitrary prefixed strings. Tests should exercise actual column types rather than invent impossible database metadata.
- `FalseValue` in `KeyFigures::parseDate`: after the fixed-width numeric date pattern has matched, the PHP date parser can parse the supplied fields; invalid calendar dates are normalized and rejected by the round-trip comparison. The `false` check remains defensive for parser failure.

- `DecrementInteger` and `IncrementInteger` in `Markdown::lines`: the only integer literal is the start index of `array_fill(0, count($headers), '---')`. Changing it to `-1` or `1` changes only array keys. The following `implode()` uses only the values, so the separator row is identical. Column count and separator content remain covered by the renderer tests. The `FalseValue` mutations of `iterator_to_array()` are deliberately not ignored: preserving repeated generator keys would discard columns, which is covered for both buffered and streaming output.

The rules apply only to the named methods, not to those mutators throughout the package. Revisit them if the methods' contracts change, or if new integer literals are added to `Markdown::lines`.
