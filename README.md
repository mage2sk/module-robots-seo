# Panth_RobotsSeo

Dedicated robots.txt, X-Robots-Tag response header, and LLM-bot user-agent
policy control for Magento 2. Extracted from Panth_AdvancedSEO so it can
be installed independently.

## What it does

- **Dynamic, per-store `robots.txt`** — replaces Magento's static file with a
  per-store body aggregated from:
  - system.xml LLM-bot toggles (GPTBot, ClaudeBot, Google-Extended, CCBot,
    PerplexityBot, Bytespider, ChatGPT-User, OAI-SearchBot, anthropic-ai,
    Cohere-AI, Amazonbot, Applebot-Extended, FacebookBot, Meta-ExternalAgent)
  - `panth_seo_robots_policy` rows (admin CRUD)
  - sitemap references (`/panth-sitemap.xml`, `/sitemap.xml`)
  - configurable `Crawl-delay:` for the `User-agent: *` block.
- **X-Robots-Tag response header** on every frontend HTML response, with
  hard-coded `noindex, nofollow` enforcement for:
  - 404, 410, 500, 503 status codes
  - `.pdf`, `.doc`, `.docx`, `.xls`, `.xlsx` asset URLs
  - matched private-path patterns (customer, checkout, wishlist, sales, ...).
- **Per-entity `<meta name="robots">` resolver** with a strict token
  whitelist (index, noindex, follow, nofollow, noarchive, nosnippet,
  noimageindex, max-snippet, max-image-preview, max-video-preview,
  unavailable_after, none, all).
- **Admin-managed custom robots.txt override** for merchants who need to
  paste their own body verbatim.

## Installation

```bash
composer require mage2kishan/module-robots-seo
bin/magento module:enable Panth_RobotsSeo
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

Stores &rsaquo; Configuration &rsaquo; Panth Infotech &rsaquo;
**Robots &amp; LLM Bots**.

Sections:

| Group           | Purpose                                               |
|-----------------|-------------------------------------------------------|
| General         | Enable, default meta robots, noindex toggles, paths   |
| LLM Bot Policy  | Allow / disallow per AI crawler                       |
| robots.txt Override | Custom robots.txt body (bypasses everything)      |

## Admin URLs

- Robots Policies grid: `/admin/panth_robots_seo/policy/index`
- robots.txt preview: `/admin/panth_robots_seo/robots/index`
- Configuration: `/admin/stores/system_config/edit/section/panth_robots_seo`

## Frontend URL

- `/robots.txt` — dynamic per-store output.

## Cross-module compatibility

When **both** Panth_AdvancedSEO and Panth_RobotsSeo are installed the shared
table `panth_seo_robots_policy` is owned by whichever module is enabled.
Data is never lost — both modules use the same schema and seeded rows.
See CHANGELOG.md for notes on the `panth_seo_resolved.robots` fallback.

## Hyva / Luma compatibility

The module emits HTTP headers and a text body — no JS, no CSS, no theme
dependencies. Works identically on Hyva, Luma, and headless PWA.
