![PrestaShop](https://img.shields.io/badge/PrestaShop-1.7%20%7C%208.x-blue)
![License](https://img.shields.io/badge/License-MIT-green)

# PrestaShop Hreflang x-default Selector

A minimal, update-proof module that injects a single hreflang entry in the `<head>`:

```html
<link rel="alternate" hreflang="x-default" href="..." />
```

Why? Many themes output hreflang tags for languages (`fr`, `en`, `nl`, …) but **omit `x-default`**.
`x-default` helps search engines understand what URL to use as a **fallback** when a user’s language/region doesn’t match any of your defined alternates.

✅ No theme edits (safe for theme updates)
✅ Back-office configuration (enable/disable + default language)
✅ Works with PrestaShop 1.7 / 8.x (and should remain compatible as long as `displayHeader` exists)

## Installation (30 seconds)

1. Download the latest release zip.
2. In PrestaShop Back Office: **Modules → Module Manager → Upload a module**
3. Upload the zip and install it.

## Setup

1. In PrestaShop: **Modules → PrestaShop Hreflang x-default Selector → Configure**
2. **Enable** the module.
3. Select which active language should be used for `x-default`.
4. Save.

The module will automatically generate the correct URL for the selected language.

## Notes

- The module **does not replace** your theme hreflang tags; it only adds **x-default**.
- It also avoids duplicates: if your theme already outputs `hreflang="x-default"`, the module does nothing.

## Hosting recommendation (optional)

If you're looking for a hosting provider with **1-click PrestaShop installation**, easy maintenance, and automated backups, Infomaniak offers a dedicated PrestaShop hosting plan:

- **1-click installation** + easy management
- **Easy updates** + protection against known security vulnerabilities
- **Automatic backups & restore**

👉 https://www.infomaniak.com/fr/creer-un-site/cms/hebergement-prestashop?utm_term=5f980010e7533

## License

MIT — see `LICENSE`

Copyright (c) 2026 ADLX
