<!-- SEO Meta -->
<!--
  Title: Panth Image SEO - Template-Based Alt/Title for Magento 2 Product Images | Panth Infotech
  Description: Panth Image SEO auto-generates SEO-optimized alt and title attributes for every product image in Magento 2 from configurable templates. Tokens for name, SKU, store, category. Filters for truncate, title-case, strip, default. Works across category grids, product galleries, widgets, cross-sells. Compatible with Magento 2.4.4 - 2.4.8, PHP 8.1 - 8.4, Hyva and Luma themes.
  Keywords: magento 2 image seo, magento 2 image alt text, magento 2 alt attribute, magento 2 image title, magento 2 product image seo, magento 2 seo extension, magento 2 image optimization, hyva image seo, luma image seo, magento 2 bulk alt text, magento 2 auto alt text, magento 2 image alt generator
  Author: Kishan Savaliya (Panth Infotech)
  Canonical: https://github.com/mage2sk/module-image-seo
-->

# Panth Image SEO — Template-Based Alt/Title for Magento 2 Product Images | Panth Infotech

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange?logo=magento&logoColor=white)](https://magento.com)
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue?logo=php&logoColor=white)](https://php.net)
[![Hyva Compatible](https://img.shields.io/badge/Hyva-Compatible-0D9488)](https://www.hyva.io)
[![Packagist](https://img.shields.io/badge/Packagist-mage2kishan%2Fmodule--image--seo-orange?logo=packagist&logoColor=white)](https://packagist.org/packages/mage2kishan/module-image-seo)
[![GitHub](https://img.shields.io/badge/GitHub-mage2sk%2Fmodule--image--seo-181717?logo=github&logoColor=white)](https://github.com/mage2sk/module-image-seo)
[![Upwork Top Rated Plus](https://img.shields.io/badge/Upwork-Top%20Rated%20Plus-14a800?logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)
[![Panth Infotech Agency](https://img.shields.io/badge/Agency-Panth%20Infotech-14a800?logo=upwork&logoColor=white)](https://www.upwork.com/agencies/1881421506131960778/)
[![Website](https://img.shields.io/badge/Website-kishansavaliya.com-0D9488)](https://kishansavaliya.com)

> **Auto-generate SEO-optimized alt and title attributes for every product image on your storefront** — from configurable templates with tokens (`{{name}}`, `{{sku}}`, `{{store}}`, `{{category}}`) and filters (truncate, title-case, strip, default, upper, lower). One setting, every image on every theme, every store view.

**Panth Image SEO** replaces Magento 2's empty or generic image alt/title attributes with fully rendered, merchant-controlled templates. The module runs everywhere images show up — **category grids, product galleries, related products, upsells, cross-sells, widgets, search results** — so the HTML your shoppers (and search engines) see always includes meaningful, keyword-rich alt text. Compatible with both **Hyva** and **Luma** themes, scope-aware (store-view granularity), and tested with Magento sample data.

---

## 🚀 Need Custom Magento 2 Development?

> **Get a free quote for your project in 24 hours** — custom modules, Hyva themes, performance optimization, M1→M2 migrations, and Adobe Commerce Cloud.

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/Get%20a%20Free%20Quote%20%E2%86%92-Reply%20within%2024%20hours-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<table>
<tr>
<td width="50%" align="center">

### 🏆 Kishan Savaliya
**Top Rated Plus on Upwork**

[![Hire on Upwork](https://img.shields.io/badge/Hire%20on%20Upwork-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)

100% Job Success • 10+ Years Magento Experience
Adobe Certified • Hyva Specialist

</td>
<td width="50%" align="center">

### 🏢 Panth Infotech Agency
**Magento Development Team**

[![Visit Agency](https://img.shields.io/badge/Visit%20Agency-Panth%20Infotech-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/agencies/1881421506131960778/)

Custom Modules • Theme Design • Migrations
Performance • SEO • Adobe Commerce Cloud

</td>
</tr>
</table>

**Visit our website:** [kishansavaliya.com](https://kishansavaliya.com) &nbsp;|&nbsp; **Get a quote:** [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote)

---

## Table of Contents

- [Key Features](#key-features)
- [Why Image SEO Matters](#why-image-seo-matters)
- [Screenshot](#screenshot)
- [Compatibility](#compatibility)
- [Installation](#installation)
- [Configuration](#configuration)
- [Token Reference](#token-reference)
- [Filter Reference](#filter-reference)
- [Template Examples](#template-examples)
- [Coverage — Where Alt/Title Gets Injected](#coverage--where-alttitle-gets-injected)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)
- [Support](#support)
- [About Panth Infotech](#about-panth-infotech)
- [Quick Links](#quick-links)

---

## Key Features

### Template-Based Alt & Title Generation

- **Tokens** — `{{name}}`, `{{sku}}`, `{{store}}`, `{{category}}` rendered per image in real time
- **Filters** — chain `|truncate:N`, `|title`, `|lower`, `|upper`, `|strip`, `|default:'value'` inside any token
- **Separate templates** — configure alt text and title text independently
- **Graceful fallbacks** — empty templates fall back to product name so no image ever renders with a blank alt attribute

### Store View Aware

- **Scope-respecting** — every setting is configurable at default / website / store view level
- **Multi-language stores** — different alt text per store view for localized storefronts
- **Multi-brand stores** — different brand name in the alt text per website

### Complete Surface Coverage

- **Product detail page** — main gallery image alt + caption + title
- **Product gallery** — fotorama / Hyva gallery captions with multi-image position suffix (`— Image 2 of 5`)
- **Category grid tiles** — alt text on every product card in category listings
- **Related / upsell / cross-sell widgets** — template applied to every widget image
- **Search results** — alt text on search result product thumbnails
- **CMS product widgets** — alt text on featured-product and new-arrival widgets

### Safe Merchant Overrides

- **Preserves merchant-authored labels** — if a product has a custom image label set in admin, that takes precedence
- **Replaces Magento defaults** — the generic `Image` / `main product photo` / filename placeholders get upgraded automatically
- **One config toggle** — disable the gallery injection separately if you only want alt text on tiles, not captions

### Performance Conscious

- **Runs inline** — no external API calls, no DB writes, pure template rendering
- **Scope-cached config reads** — uses Magento's cached scope config, zero DB overhead after warm-up
- **Template caching** — rendered alt text is cached alongside the native image block cache
- **Zero JavaScript** — fully server-side, no client-side rendering overhead

### Clean & Extensible

- **MEQP compliant** — follows Adobe's Magento Extension Quality Program standards
- **Pluggable vision adapter** — `VisionAdapterInterface` lets you swap in OpenAI Vision / Claude Vision for AI-generated fallback alt text
- **Optional Panth_ProductGallery wire** — integrates automatically when the gallery module is installed
- **Well-scoped plugins** — four targeted plugins (ImageFactory, Helper\Image, Gallery, Uploader) rather than sweeping preferences

---

## Why Image SEO Matters

Image alt text is one of the most under-optimized and highest-leverage SEO signals for eCommerce:

1. **Image Search Traffic** — Google Images is the second-largest search engine. Product images with descriptive alt text rank; images with `alt=""` or `alt="Image"` are invisible.
2. **On-Page SEO** — search engines use alt text to understand page context. A category listing page with 48 products but 48 blank alt attributes ranks worse than one with 48 descriptive alts.
3. **Accessibility Compliance** — WCAG 2.1 AA and the European Accessibility Act (EAA) both require meaningful alt text on product imagery. Blank alt attributes on eCommerce sites are a legal-risk category.
4. **Click-Through Rate** — when a product appears in Google Images, the alt text is what gets picked up in the result snippet. Better alt = more qualified clicks.

Manually writing alt text for every product (or every image within every product) doesn't scale past a small catalog. Panth Image SEO lets one merchant-controlled template render correct, scope-aware alt text across the entire catalog in one pass — and refresh instantly when you change the template.

---

## Screenshot

### Admin Configuration — Stores → Configuration → Panth Extensions → Image SEO

![Panth Image SEO admin configuration screen](docs/screenshots/admin-config.png)

*The admin UI exposes every setting at store-view scope: master toggle, alt template, title template, and gallery-injection toggle. Token and filter reference is inlined under each field so the merchant never has to leave the page to find the syntax.*

---

## Compatibility

| Requirement | Versions Supported |
|---|---|
| Magento Open Source | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce Cloud | 2.4.4 — 2.4.8 |
| PHP | 8.1.x, 8.2.x, 8.3.x, 8.4.x |
| MySQL | 8.0+ |
| MariaDB | 10.4+ |
| Hyva Theme | 1.0+ (native support) |
| Luma Theme | Native support |
| Required Dependency | `mage2kishan/module-core` ^1.0 |
| Suggested Integration | `mage2kishan/module-productgallery` (auto-wires into the custom gallery) |

---

## Installation

### Composer Installation (Recommended)

```bash
composer require mage2kishan/module-image-seo
bin/magento module:enable Panth_Core Panth_ImageSeo
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual Installation via ZIP

1. Download the latest release ZIP from [Packagist](https://packagist.org/packages/mage2kishan/module-image-seo) or the [Adobe Commerce Marketplace](https://commercemarketplace.adobe.com)
2. Extract the contents to `app/code/Panth/ImageSeo/` in your Magento installation
3. Ensure `Panth_Core` is installed (required dependency)
4. Run the same commands as above starting from `bin/magento module:enable`

### Verify Installation

```bash
bin/magento module:status Panth_ImageSeo
# Expected output: Module is enabled
```

---

## Configuration

Navigate to **Admin → Stores → Configuration → Panth Extensions → Image SEO** to configure the module.

| Setting | Default | Scope | Description |
|---|---|---|---|
| Enable Template-Based Image Alt/Title | Yes | Store View | Master toggle. When **No**, Magento's native alt/title behavior is used and all plugins are no-ops. |
| Alt Text Template | `{{name}} - {{store}}` | Store View | Template rendered into every `<img alt="">` attribute. Supports tokens and filters. |
| Title Text Template | `{{name}}` | Store View | Template rendered into every `<img title="">` attribute. Same token syntax; falls back to alt text when empty. |
| Apply to Gallery Images JSON | Yes | Store View | Also injects alt/title into the product gallery images JSON (used by fotorama and Hyva gallery widgets). |

All four settings are scope-aware. Configure a shorter alt template on a mobile-focused store view, a different brand suffix on a wholesale store, or different language alt text per localized store — Magento's scope hierarchy handles the rest.

---

## Token Reference

Every token is written as `{{tokenName}}` and can be combined with filters using `|filterName:arg`.

| Token | Value | Example Output |
|---|---|---|
| `{{name}}` | Product name | `Push It Messenger Bag` |
| `{{sku}}` | Product SKU | `24-WB04` |
| `{{store}}` | Current store view name | `Luma Store View`, `Default Store View`, `English`, `Wholesale` |
| `{{category}}` | Current category name (when resolvable) | `Bags`, `Women's Tops` |

Unknown tokens render as empty strings, so a template like `Brand: {{brand}} / {{name}}` safely falls back to `Brand: / Push It Messenger Bag` if the brand token isn't wired up yet. Trailing separators are auto-cleaned.

---

## Filter Reference

Filters are chained with `|` and run left-to-right.

| Filter | Syntax | Effect | Example |
|---|---|---|---|
| `truncate` | `\|truncate:N` | Clip to N characters, append `…` | `{{name\|truncate:20}}` → `Push It Messenger B…` |
| `title` | `\|title` | Title-case (first letter of each word uppercase) | `{{name\|lower\|title}}` → `Push It Messenger Bag` |
| `upper` | `\|upper` | UPPERCASE the value | `{{sku\|upper}}` → `24-WB04` |
| `lower` | `\|lower` | lowercase the value | `{{name\|lower}}` → `push it messenger bag` |
| `strip` | `\|strip` | Remove HTML tags and collapse whitespace | `{{name\|strip}}` |
| `default` | `\|default:'Fallback'` | Use the argument when the value is empty | `{{category\|default:'Catalog'}}` → `Catalog` |

Filters can be chained: `{{name|lower|title|truncate:40}}` is valid.

---

## Template Examples

### Basic name + store view

```
Alt:   {{name}} - {{store}}
Title: {{name}}
```

Renders as:
> `Push It Messenger Bag - Luma Store View`

### Brand-suffixed with truncation (safe for long names)

```
Alt:   {{name|truncate:80}} | Buy Online at {{store}}
Title: {{name}} — {{store}}
```

### Category-scoped alt (when category context is available)

```
Alt:   {{name}} in {{category|default:'our catalog'}}
Title: {{name}}
```

### SKU-prefixed alt (useful for B2B catalogs)

```
Alt:   [{{sku|upper}}] {{name}} | {{store}}
Title: {{name|truncate:60}}
```

### Minimal SEO-only (category pages)

```
Alt:   {{name}}
Title: {{name}}
```

### Multi-store with localized brand in each alt

Configure `Alt Text Template` at the store-view scope:
- **English store view**: `{{name}} | Premium Quality from {{store}}`
- **French store view**: `{{name}} | Qualité Premium - {{store}}`
- **Wholesale store view**: `[WHOLESALE] {{name}} - {{store}}`

---

## Coverage — Where Alt/Title Gets Injected

The module hooks every surface where Magento renders an `<img>` tag for a product:

| Surface | Plugin | What gets injected |
|---|---|---|
| **Category page tiles** | `ImageFactoryPlugin` | `<img alt="…">` on every product card |
| **Related / Upsell / Cross-sell** | `ImageFactoryPlugin` | Widget product image alt |
| **Search results grid** | `ImageFactoryPlugin` | Thumbnail alt on every result |
| **Product gallery (main image)** | `GalleryImageSeoPlugin` | `caption` field in gallery JSON |
| **Product gallery (thumbnails)** | `GalleryImageSeoPlugin` | `caption` + position suffix (`— Image 2 of 5`) |
| **Product page label** | `ImageAttributesPlugin` + `ProductImagePlugin` | Helper-level `getLabel()` return value |
| **Admin image uploader** | `UploaderPlugin` | Default label on newly uploaded images |
| **CMS widgets (New, Featured, …)** | `ImageFactoryPlugin` | Widget image alt |
| **Panth_ProductGallery** | Soft DI wire | When installed, injects `ImageTemplateResolver` into the custom gallery |

This breadth matters because Magento 2 has **four different code paths** that eventually render a product `<img>`:

1. `Magento\Catalog\Block\Product\ImageFactory::create` — used by category grids, widgets, related/upsell/cross-sell
2. `Magento\Catalog\Helper\Image::getLabel` — used by some themes directly
3. `Magento\Catalog\Block\Product\View\Gallery::getGalleryImagesJson` — the product gallery JSON
4. Custom gallery blocks (Panth_ProductGallery, some premium themes)

Missing any one of these leaves a gap where alt text stays blank or reverts to the Magento default. Panth Image SEO plugs all four, so the rendered HTML across your entire storefront is uniformly template-driven.

---

## Troubleshooting

| Issue | Cause | Resolution |
|---|---|---|
| Alt text not changing on the storefront | FPC holding pre-install HTML | Run `bin/magento cache:flush` once after install |
| Category tile alt shows only product name, no store suffix | Old version (<1.0.1) without `ImageFactoryPlugin` | Upgrade to v1.0.1+ via `composer update mage2kishan/module-image-seo` |
| Gallery main image caption shows "Image" | Old version (<1.0.1) with restricted placeholder detection | Upgrade to v1.0.1+ |
| Token renders as empty string | Unknown token name, or context variable not available | Check token spelling; see [Token Reference](#token-reference) |
| Different alt text across store views not respected | Setting edited at default scope | Switch scope dropdown to the specific store view before editing |
| Admin config page shows but options don't save | Admin user lacks permission on `Panth_ImageSeo::config` | Grant the ACL resource under System → Permissions → User Roles |
| Gallery caption shows name only, no position suffix | Single-image product | Position suffix is only added for galleries with 2+ images (intentional) |
| Site slow after install | Missing `bin/magento setup:di:compile` in production mode | Run DI compile + static content deploy in production mode |

---

## FAQ

### Will this overwrite my custom image labels?

No. Merchant-authored labels (anything other than empty, the product name, `Image`, `main product photo`, or the raw filename) are preserved. Only Magento's default placeholders get upgraded to template output.

### Does it work with Hyva?

Yes. The module injects into `getGalleryImagesJson`, which Hyva reads for its Alpine.js gallery. Category tile alt attributes go through `ImageFactory`, which Hyva's `product/list.phtml` also uses.

### Does it work with Luma?

Yes. Luma's fotorama gallery reads from the same `caption` field, and Luma's category grid uses the same `ImageFactory` path.

### Is Panth_Core required?

Yes. `mage2kishan/module-core` is a required dependency and is pulled in automatically by Composer. Core provides the admin tab layout and common utilities.

### Can I use AI-generated alt text?

Yes. The module ships with a `VisionAdapterInterface` and a `NullVisionAdapter` default. You can swap the preference in your `di.xml` to a custom adapter that calls OpenAI Vision, Claude Vision, or Google Cloud Vision for a vision-generated alt as the fallback when templates render empty. The vision adapter only fires when no template output was produced.

### Does it support multi-store / multi-language?

Yes. Every configuration setting respects Magento's standard scope hierarchy (default → website → store view). Configure a different alt template per store view and the plugins automatically honor it.

### What about the `title` attribute on `<img>` tags?

The module renders a separate `title` template so you can produce short, branded titles (`{{name}}`) while the alt attribute carries the SEO keyword copy (`{{name}} | {{store}} | {{category}}`).

### Can I disable it temporarily?

Yes. Flip the master toggle to **No** in admin config and all plugins become no-ops instantly. The module's index data is not deleted, so re-enabling is instant.

### Does it touch the database?

No. The module is pure template rendering — no DB tables created, no writes. All state lives in Magento's cached scope config.

### What if I want to disable only the gallery injection but keep tile alt working?

Set **Apply to Gallery Images JSON** to No. The master toggle stays on, so category tiles and widgets still get template alt; only the gallery JSON is left untouched.

### Is it compatible with Varnish / Redis FPC?

Yes. The module doesn't cache anything itself — it relies on Magento's native block cache and FPC. Template rendering is idempotent, so cache hit rate is unaffected.

---

## Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | [kishansavaliya.com](https://kishansavaliya.com) |
| WhatsApp | +91 84012 70422 |
| GitHub Issues | [github.com/mage2sk/module-image-seo/issues](https://github.com/mage2sk/module-image-seo/issues) |
| Upwork (Top Rated Plus) | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| Upwork Agency | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |

Response time: 1-2 business days.

### 💼 Need Custom Magento Development?

Looking for **custom Magento module development**, **Hyva theme customization**, **store migrations**, or **performance optimization**? Get a free quote in 24 hours:

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/%F0%9F%92%AC%20Get%20a%20Free%20Quote-kishansavaliya.com%2Fget--quote-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<p align="center">
  <a href="https://www.upwork.com/freelancers/~016dd1767321100e21">
    <img src="https://img.shields.io/badge/Hire%20Kishan-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Hire on Upwork" />
  </a>
  &nbsp;&nbsp;
  <a href="https://www.upwork.com/agencies/1881421506131960778/">
    <img src="https://img.shields.io/badge/Visit-Panth%20Infotech%20Agency-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Visit Agency" />
  </a>
  &nbsp;&nbsp;
  <a href="https://kishansavaliya.com">
    <img src="https://img.shields.io/badge/Visit%20Website-kishansavaliya.com-0D9488?style=for-the-badge" alt="Visit Website" />
  </a>
</p>

**Specializations:**

- 🛒 **Magento 2 Module Development** — custom extensions following MEQP standards
- 🎨 **Hyva Theme Development** — Alpine.js + Tailwind CSS, lightning-fast storefronts
- 🖌️ **Luma Theme Customization** — pixel-perfect designs, responsive layouts
- ⚡ **Performance Optimization** — Core Web Vitals, page speed, caching strategies
- 🔍 **Magento SEO** — structured data, hreflang, sitemaps, AI-generated meta
- 🛍️ **Checkout Optimization** — one-page checkout, conversion rate optimization
- 🚀 **M1 to M2 Migrations** — data migration, custom feature porting
- ☁️ **Adobe Commerce Cloud** — deployment, CI/CD, performance tuning
- 🔌 **Third-party Integrations** — payment gateways, ERP, CRM, marketing tools

---

## License

Panth Image SEO is licensed under a proprietary license — see `LICENSE.txt`. One license per Magento installation.

---

## About Panth Infotech

Built and maintained by **Kishan Savaliya** — [kishansavaliya.com](https://kishansavaliya.com) — a **Top Rated Plus** Magento developer on Upwork with 10+ years of eCommerce experience.

**Panth Infotech** is a Magento 2 development agency specializing in high-quality, security-focused extensions and themes for both Hyva and Luma storefronts. Our extension suite covers SEO, performance, checkout, product presentation, customer engagement, and store management — over 34 modules built to MEQP standards and tested across Magento 2.4.4 to 2.4.8.

Browse the full extension catalog on the [Adobe Commerce Marketplace](https://commercemarketplace.adobe.com) or [Packagist](https://packagist.org/packages/mage2kishan/).

### Quick Links

- 🌐 **Website:** [kishansavaliya.com](https://kishansavaliya.com)
- 💬 **Get a Quote:** [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote)
- 👨‍💻 **Upwork Profile (Top Rated Plus):** [upwork.com/freelancers/~016dd1767321100e21](https://www.upwork.com/freelancers/~016dd1767321100e21)
- 🏢 **Upwork Agency:** [upwork.com/agencies/1881421506131960778](https://www.upwork.com/agencies/1881421506131960778/)
- 📦 **Packagist:** [packagist.org/packages/mage2kishan/module-image-seo](https://packagist.org/packages/mage2kishan/module-image-seo)
- 🐙 **GitHub:** [github.com/mage2sk/module-image-seo](https://github.com/mage2sk/module-image-seo)
- 🛒 **Adobe Marketplace:** [commercemarketplace.adobe.com](https://commercemarketplace.adobe.com)
- 📧 **Email:** kishansavaliyakb@gmail.com
- 📱 **WhatsApp:** +91 84012 70422

---

<p align="center">
  <strong>Ready to fix your image SEO across the entire catalog?</strong><br/>
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/%F0%9F%9A%80%20Get%20Started%20%E2%86%92-Free%20Quote%20in%2024h-DC2626?style=for-the-badge" alt="Get Started" />
  </a>
</p>

---

**SEO Keywords:** magento 2 image seo, magento 2 image alt text, magento 2 alt attribute, magento 2 image title, magento 2 product image seo, magento 2 seo extension, magento 2 image optimization, magento 2 auto alt text, magento 2 bulk alt text, magento 2 image alt generator, magento 2 seo alt tags, magento 2 category image seo, magento 2 gallery alt text, magento 2 widget image alt, magento 2 wcag compliance, magento 2 accessibility alt, hyva image seo, hyva alt text, hyva image optimization, luma image seo, luma alt text, magento 2 image search seo, magento 2 google images, magento 2 multi-store alt text, magento 2 localized alt text, magento 2 alt template, magento 2 alt tokens, magento 2 image label extension, magento 2 AI alt text, magento 2 vision alt text, mage2kishan image seo, panth infotech image seo, kishan savaliya magento, magento 2.4.8 image seo, magento 2 PHP 8.4 image seo, hire magento developer upwork, top rated plus magento freelancer, custom magento development, adobe commerce image seo, magento 2 alt automation, magento 2 bulk image optimization
