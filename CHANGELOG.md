# Changelog

## 1.0.1 (2026-10-21)
- Back Office strings now use the standard PrestaShop translation system (`$this->l()`): English by default, translatable from International > Translations. `translations/fr.php` regenerated with correct keys (the previous file was never matched); unused `translations/en.php` removed.
- The template can be overridden by a theme again (`themes/<theme>/modules/pshreflangxdefault/views/templates/hook/xdefault.tpl`).
- Uninstall now asks for confirmation.
- Compatibility declared up to PrestaShop 8.99.99 (was: any installed version).
- Module attributes set before `parent::__construct()`, as in the official example.
- `config.xml` shipped with the module.
- Added the missing `index.php` files in `translations/` and `views/`.

## 1.0.0
- Initial release
