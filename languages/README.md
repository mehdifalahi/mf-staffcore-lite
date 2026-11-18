# MF StaffCore Translations

This plugin uses WordPress i18n with text domain `mf-staffcore-lite` (matches the WordPress.org plugin slug).

## Files

- Template: `mf-staffcore-lite.pot`
- Locale translation: `mf-staffcore-lite-<locale>.po`
- Compiled binary: `mf-staffcore-lite-<locale>.mo`

Examples:

- German: `mf-staffcore-lite-de_DE.po` and `mf-staffcore-lite-de_DE.mo`
- French: `mf-staffcore-lite-fr_FR.po` and `mf-staffcore-lite-fr_FR.mo`

## How to translate

1. Open `mf-staffcore-lite.pot` in Poedit (or Loco Translate / WP-CLI workflow).
2. Save as `mf-staffcore-lite-<locale>.po`.
3. Translate strings.
4. Compile/save `.mo` in the same `languages/` directory.

WordPress loads files automatically via `load_plugin_textdomain()` in plugin boot.

## Notes for developers

- Keep PHP UI strings wrapped in i18n functions: `__()`, `_e()`, `_n()`, `esc_html__()`, `esc_attr__()`, etc.
- Avoid hardcoded user-facing strings in JavaScript; pass translatable strings from PHP to JS when needed.
- Update `mf-staffcore-lite.pot` whenever new translatable strings are added.
