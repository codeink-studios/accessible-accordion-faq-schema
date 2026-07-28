# Tests

Dependency-free harnesses for the v1.x legacy recovery paths. No WordPress, no
npm, no PHPUnit — both run standalone so there is no excuse to skip them.

```sh
php tests/test-legacy-recovery.php     # server-side rescue (render.php helpers)
node tests/test-legacy-migration.js    # editor-side deprecation (isEligible + migrate)
```

Both exit non-zero on failure.

## What they cover

`test-legacy-recovery.php` stubs the few WordPress functions the helpers touch
and exercises `cis_aafs_normalize_legacy_faqs()` and
`cis_aafs_render_legacy_items()`: both v1 answer shapes (single string and
paragraph array), rows v1 itself would have refused to render, open vs
collapsible markup, paragraph wrapping, and escaping of hostile stored content.

`test-legacy-migration.js` loads the real `src/faq-block/index.js` against a
stubbed `window.wp`, captures the registered block, and drives the deprecation's
`isEligible` and `migrate` directly. The most important cases are the negative
ones: `isEligible` must never fire on a working v2/v3 block, or the migration
would replace live children with nothing.

## Excluded from releases

`.github/workflows/release.yml` omits `tests/` from the built ZIP, alongside
`specs/`. Neither belongs in an installed plugin.

## Why these exist

The stylesheet-leak fix shipped twice before it worked (3.0.2, then 3.0.3), and
the v1 to v2 restructure shipped with no migration at all — silently emptying
every FAQ built before v2.0.0. Both were reasoning errors that a cheap
assertion would have caught. Add cases here when touching either path.
