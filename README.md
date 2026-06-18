<!-- SEO Meta -->
<!--
  Title: Magento 2 Robots SEO Extension: robots.txt, X-Robots-Tag and LLM Bot Policy Control (Hyva + Luma)
  Description: Panth Robots SEO gives Magento 2 stores full control over robots.txt per store view, X-Robots-Tag HTTP response headers, noindex path matching, and one-click allow/disallow toggles for 14 AI and LLM crawlers including GPTBot, ClaudeBot, PerplexityBot, Google-Extended, and Bytespider. Works on Hyva and Luma. Built by Top Rated Plus Magento developer Kishan Savaliya.
  Keywords: magento 2 robots.txt, magento 2 x-robots-tag, magento 2 llm bot policy, magento 2 ai crawler control, magento gptbot block, magento claudebot, magento perplexitybot, magento 2 noindex, magento 2 seo headers, magento 2 robots seo, hyva robots seo, luma robots seo, magento 2 crawl delay, magento 2 noindex layered nav
  Author: Kishan Savaliya (Panth Infotech)
  Canonical: https://kishansavaliya.com/magento-2-robots-seo.html
-->

# Magento 2 Robots SEO Extension: robots.txt, X-Robots-Tag and LLM Bot Policy Control (Hyva + Luma)

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange?logo=magento&logoColor=white)](https://magento.com)
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue?logo=php&logoColor=white)](https://php.net)
[![Hyva + Luma](https://img.shields.io/badge/Themes-Hyva%20%2B%20Luma-14b8a6)](https://www.hyva.io)
[![Live Demo & Details](https://img.shields.io/badge/Live%20Demo%20%26%20Details-magento--2--robots--seo-0D9488?style=flat)](https://kishansavaliya.com/magento-2-robots-seo.html)
[![Packagist](https://img.shields.io/badge/Packagist-mage2kishan%2Fmodule--robots--seo-orange?logo=packagist&logoColor=white)](https://packagist.org/packages/mage2kishan/module-robots-seo)
[![Upwork Top Rated Plus](https://img.shields.io/badge/Upwork-Top%20Rated%20Plus-14a800?logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)
[![Website](https://img.shields.io/badge/Website-kishansavaliya.com-0D9488)](https://kishansavaliya.com)

> **Full robots and crawler policy control for Magento 2.** Panth Robots SEO takes over `/robots.txt` at the router layer, emits an `X-Robots-Tag` HTTP header on every frontend response, and gives you one-click toggles for 14 AI and LLM crawlers including GPTBot, ClaudeBot, PerplexityBot, and Google-Extended. Every directive passes a CRLF-safe validator before it reaches the wire. Works identically on **Hyva** and **Luma**.

**Product page:** [kishansavaliya.com/magento-2-robots-seo.html](https://kishansavaliya.com/magento-2-robots-seo.html)

---

## Quick Answer

**What is Panth Robots SEO?** It is a Magento 2 extension that replaces Magento's limited robots handling with a dedicated controller for `/robots.txt`, a per-response `X-Robots-Tag` HTTP header, and an admin UI for managing per-user-agent path policies and AI crawler access.

**What does it add to my store?**

- A **dynamic `/robots.txt` per store view** built from LLM bot toggles, admin policy rows, crawl-delay, and sitemap references. No static file is ever read from disk.
- **14 LLM and AI crawler toggles** for GPTBot, ClaudeBot, ChatGPT-User, PerplexityBot, Google-Extended, Cohere-AI, Bytespider, and more. One click blocks or allows each bot.
- An **`X-Robots-Tag` HTTP response header** on every frontend HTML page, with automatic noindex for error pages, private paths, layered-nav filters, and search result pages.
- An **admin CRUD grid** for per-user-agent, per-path, per-store-view allow/disallow rows, plus a live **robots.txt Preview** page so you can verify output before it goes public.

**Which themes are supported?** Both **Hyva** and **Luma**. The module works at the controller and plugin layer, so no theme-specific template is needed.

**What does it need?** Magento 2.4.4 to 2.4.8, PHP 8.1 to 8.4, and the free `mage2kishan/module-core` package.

---

## Need Custom Magento 2 Development?

> **Get a free quote for your project in 24 hours** for custom modules, Hyva themes, performance work, M1 to M2 migrations, and Adobe Commerce Cloud.

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/Get%20a%20Free%20Quote%20%E2%86%92-Reply%20within%2024%20hours-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<table>
<tr>
<td width="50%" align="center">

### Kishan Savaliya
**Top Rated Plus on Upwork**

[![Hire on Upwork](https://img.shields.io/badge/Hire%20on%20Upwork-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)

100% Job Success - 10+ Years Magento Experience
Adobe Certified - Hyva Specialist

</td>
<td width="50%" align="center">

### Panth Infotech Agency
**Magento Development Team**

[![Visit Agency](https://img.shields.io/badge/Visit%20Agency-Panth%20Infotech-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/agencies/1881421506131960778/)

Custom Modules - Theme Design - Migrations
Performance - SEO - Adobe Commerce Cloud

</td>
</tr>
</table>

**Visit our website:** [kishansavaliya.com](https://kishansavaliya.com) &nbsp;|&nbsp; **Get a quote:** [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote)

---

## Table of Contents

- [Who Is It For](#who-is-it-for)
- [Key Features](#key-features)
- [Screenshots](#screenshots)
- [Compatibility](#compatibility)
- [Installation](#installation)
- [Configuration](#configuration)
- [Supported LLM Bots](#supported-llm-bots)
- [How It Works](#how-it-works)
- [FAQ](#faq)
- [Support](#support)
- [About Panth Infotech](#about-panth-infotech)
- [Quick Links](#quick-links)

---

## Who Is It For

- **Stores worried about AI training scrapers** that want to block GPTBot, Bytespider, CCBot, or other data-collection bots in one click rather than hand-editing a file on every deploy.
- **SEO-conscious merchants** who need layered-nav pages, search result pages, and customer account paths excluded from indexing through proper HTTP headers, not just a meta tag.
- **Multi-store setups** where each store view needs its own robots.txt body, noindex path list, and LLM bot policy.
- **Stores upgrading from Panth_AdvancedSEO** that want robots handling as a standalone, self-contained module without pulling in the full SEO suite.
- **Developers** who need a structured, schema-backed policy grid instead of a single admin textarea with no validation.

---

## Key Features

### Dynamic robots.txt Per Store View
- **Router-level controller** takes over `/robots.txt` so the response is built from live config every time. No static file is ever served.
- **LLM and AI bot blocks** are written as `User-agent: <bot>\nDisallow: /` sections when their toggle is set to No.
- **Admin policy rows** from the `panth_seo_robots_policy` table are merged under the matching user-agent block.
- **Crawl-delay, Sitemap, and Host** lines are appended automatically from config.
- **Custom body override** lets you paste your own robots.txt verbatim and skip the generation pipeline entirely.

### X-Robots-Tag HTTP Response Header
- **Added to every frontend HTML response** by `Plugin\Response\XRobotsTagPlugin` before the response is sent.
- **Automatic noindex for error pages** (404, 410, 500, 503), non-HTML assets (.pdf, .doc, .xls), layered-nav filter pages, and search result pages.
- **Configurable noindex path list** with wildcard `*` support for private paths like `/customer/*`, `/checkout`, `/wishlist`, and more.
- **max-image-preview and max-snippet** are appended to every header value, including the `large` setting recommended for Google Discover.

### 14 LLM and AI Crawler Toggles
- **One Yes/No toggle per bot** in the LLM Bot Policy config group. Turning a bot to No writes a `Disallow: /` block for that user-agent in robots.txt.
- **Bots covered:** GPTBot, ChatGPT-User, OAI-SearchBot, ClaudeBot (covers Claude-Web too), Anthropic-AI, Google-Extended (covers GoogleOther), PerplexityBot, Cohere-AI, CCBot, Bytespider, Amazonbot, Applebot-Extended, FacebookBot, Meta-ExternalAgent.
- **CCBot and Bytespider are blocked by default** because they feed large-scale training pipelines and are known to ignore partial disallows.

### Admin Policy Grid and robots.txt Preview
- **`panth_seo_robots_policy` table** stores per-user-agent, per-path, per-store-view allow/disallow rows with a priority column.
- **Full CRUD grid** at Admin - Panth Infotech - Robots & LLM Bots - Robots Policies with mass enable, disable, and delete actions.
- **robots.txt Preview page** renders the live output for any store view so you can check the result before it goes public.
- **Store-view scope** on every row and config value so each store can have its own policy.

### Security Built In
- **CRLF-injection-safe** validator runs on every directive string before it reaches a response header or the robots.txt body. `\r`, `\n`, and `\0` are rejected outright.
- **User-agent and path validation** on every policy save. UAs must match `/^[A-Za-z0-9._\-+*\/ ]+$/`; paths must start with `/` and contain no control bytes.
- **ACL on every admin controller.** All routes require a valid admin session and declare their own `ADMIN_RESOURCE`.
- **XSS-safe Preview page** renders the robots.txt body through `escapeHtml()` so a hostile custom body cannot execute script in the admin browser.

### Built to Last
- **Constructor DI only** across all classes. No ObjectManager calls.
- **Full Page Cache friendly.** The robots.txt controller and the X-Robots-Tag plugin do not break Varnish or Fastly.
- **Translation ready.** All admin labels use Magento's `__()` function.
- **Zero data loss on upgrade from Panth_AdvancedSEO.** The `panth_seo_robots_policy` table name is preserved and the schema shapes match exactly.

---

## Screenshots

### Live Walkthrough

End-to-end admin flow: enable the module, toggle a few LLM bots, add a policy row, preview the generated robots.txt, curl `/robots.txt` on both Hyva and Luma, and confirm the `X-Robots-Tag` header on a customer account page.

![Panth Robots SEO demo](docs/images/demo.gif)

### Admin Configuration

Global configuration: toggle the module, set the default meta robots value, configure layered-nav and catalogsearch noindex, edit the noindex path list, and set max-image-preview, max-snippet, and Crawl-delay.

![Admin configuration](docs/images/admin-config.png)

### Robots Policies Grid

One row per user-agent, path, directive, and store view combination. Filter by store, mass-enable, disable or delete, and set priority so the evaluator knows which rule wins when two patterns overlap.

![Admin grid](docs/images/admin-grid.png)

### Policy Edit Form

Pick a user-agent (`*` for the default block, or `GPTBot`, `ClaudeBot`, a custom crawler), pick allow or disallow, enter a path, scope to a store view, and set priority and active flag.

![Admin edit form](docs/images/admin-edit.png)

### robots.txt Preview

Dedicated Panth Infotech - Robots & LLM Bots - robots.txt Preview page renders the live body for the selected store view, exactly as the frontend controller will serve it.

![robots.txt preview](docs/images/robots-txt-preview.png)

---

## Compatibility

| Requirement | Versions Supported |
|---|---|
| Magento Open Source | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce Cloud | 2.4.4 to 2.4.8 |
| PHP | 8.1.x, 8.2.x, 8.3.x, 8.4.x |
| Hyva Theme | 1.0+ (fully compatible) |
| Luma Theme | Native support |
| Required Dependency | `mage2kishan/module-core` (free) |

---

## Installation

### Composer Installation (Recommended)

```bash
composer require mage2kishan/module-robots-seo
bin/magento module:enable Panth_Core Panth_RobotsSeo
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual Installation via ZIP

1. Download the latest release from [Packagist](https://packagist.org/packages/mage2kishan/module-robots-seo) or from the [product page](https://kishansavaliya.com/magento-2-robots-seo.html).
2. Extract it to `app/code/Panth/RobotsSeo/` in your Magento install.
3. Make sure `Panth_Core` is installed too (required dependency).
4. Run the commands above starting from `bin/magento module:enable`.

### Verify Installation

```bash
bin/magento module:status Panth_RobotsSeo
# Expected: Module is enabled

curl -s -o /dev/null -w '%{http_code}\n' https://your-store.test/robots.txt
# 200

curl -sI https://your-store.test/customer/account/login | grep -i x-robots-tag
# X-Robots-Tag: noindex, nofollow, max-image-preview:large, max-snippet:-1
```

After install, open:
```
Admin -> Panth Infotech -> Robots & LLM Bots
```

---

## Configuration

Go to **Stores -> Configuration -> Panth Infotech -> Robots & LLM Bots**.

### General

| Setting | Group | Default | Description |
|---|---|---|---|
| Enable Module | General | Yes | Master switch. When No, the X-Robots-Tag plugin is a no-op and `/robots.txt` serves a stock `User-agent: *\nAllow: /`. |
| Debug Logging | General | No | When Yes, every header and meta decision is written to `var/log/panth_robots_seo.log`. |
| Default Meta Robots | General | `index,follow` | Baseline directive applied when no per-entity or per-path override fires. |
| Noindex Layered-Nav Filtered Pages | General | Yes | Emit `noindex, follow` when a catalog listing has layered-nav or sort/limit/page query parameters. |
| Noindex Search Result Pages | General | Yes | Emit `noindex, follow` on `/catalogsearch/result/*` pages. |
| Noindex URL Paths (one per line) | General | 18-line seeded list | Private path patterns that must emit `noindex, nofollow`. Wildcards supported: `*` matches anything. |
| max-image-preview Directive | General | `large` | Appended to every X-Robots-Tag. `large` is recommended for Google Discover eligibility. |
| max-snippet Directive | General | `-1` | `-1` = unlimited. A positive integer caps the SERP snippet length. |
| Crawl-delay (seconds) | General | `0` | Emitted under `User-agent: *` in robots.txt. `0` omits the directive. |

### LLM Bot Policy

| Setting | Group | Default | Description |
|---|---|---|---|
| Allow GPTBot (OpenAI) | LLM Bot Policy | Yes | No = emits `User-agent: GPTBot\nDisallow: /`. |
| Allow ClaudeBot (Anthropic) | LLM Bot Policy | Yes | Covers both `ClaudeBot` and `Claude-Web`. |
| Allow Google-Extended | LLM Bot Policy | Yes | Covers both `Google-Extended` and `GoogleOther`. |
| Allow CCBot (Common Crawl) | LLM Bot Policy | No | Blocked by default. CCBot feeds dataset-scale training pipelines. |
| Allow PerplexityBot | LLM Bot Policy | Yes | |
| Allow Bytespider (ByteDance) | LLM Bot Policy | No | Blocked by default. Bytespider is known to ignore partial disallows. |
| Allow ChatGPT-User | LLM Bot Policy | Yes | |
| Allow OAI-SearchBot | LLM Bot Policy | Yes | |
| Allow Anthropic-AI | LLM Bot Policy | Yes | |
| Allow Cohere-AI | LLM Bot Policy | Yes | |
| Allow Amazonbot | LLM Bot Policy | Yes | |
| Allow Applebot-Extended | LLM Bot Policy | Yes | |
| Allow Facebookbot | LLM Bot Policy | Yes | |
| Allow Meta-ExternalAgent | LLM Bot Policy | Yes | |

### robots.txt Override

| Setting | Group | Default | Description |
|---|---|---|---|
| Use Custom robots.txt Body | robots.txt Override | No | When Yes, the custom body REPLACES the generated output. All LLM toggles and policy rows are ignored. |
| Custom robots.txt Body | robots.txt Override | (empty) | Pasted verbatim into the response. CRLF is normalised to LF. Leave empty to use the generated output. |

Every setting resolves at store-view scope, so each store can have a different LLM policy, noindex path list, or custom body.

---

## Supported LLM Bots

| Bot | UA string(s) | Default |
|---|---|---|
| GPTBot (OpenAI) | `GPTBot` | Allow |
| ChatGPT-User | `ChatGPT-User` | Allow |
| OAI-SearchBot | `OAI-SearchBot` | Allow |
| ClaudeBot (Anthropic) | `ClaudeBot`, `Claude-Web` | Allow |
| Anthropic-AI | `anthropic-ai` | Allow |
| Google-Extended | `Google-Extended`, `GoogleOther` | Allow |
| PerplexityBot | `PerplexityBot` | Allow |
| Cohere-AI | `cohere-ai` | Allow |
| CCBot (Common Crawl) | `CCBot` | **Disallow** |
| Bytespider (ByteDance) | `Bytespider` | **Disallow** |
| Amazonbot | `Amazonbot` | Allow |
| Applebot-Extended | `Applebot-Extended` | Allow |
| FacebookBot | `FacebookBot` | Allow |
| Meta-ExternalAgent | `meta-externalagent` | Allow |

Bots not listed here (YouBot, PetalBot, Diffbot, AI2Bot, etc.) are not blocked by default. To block them, add a `Disallow: /` row in the Robots Policies grid with the UA as the user-agent string.

---

## How It Works

1. **`Controller\Robots\Index`** serves `GET /robots.txt` with the generated or override body at `Content-Type: text/plain; charset=utf-8`. The core Magento robots router is disabled in `etc/frontend/di.xml` so this controller always wins.
2. **`Setup\Patch\Data\InstallRobotsTxtRewrite`** writes the `url_rewrite` row that maps `/robots.txt` to the module controller at install time. `RefreshRobotsTxtRewrite` re-points stale rows left behind by `Panth_AdvancedSEO`, so upgrades need no manual DB work.
3. **`Plugin\Response\XRobotsTagPlugin`** runs `beforeSendResponse` on `Magento\Framework\App\Response\Http`. It reads the request path, HTTP status code, and Content-Type, then sets `X-Robots-Tag` once per response using this precedence order:
   - Self-skip on `/robots.txt`.
   - Error-code override (404, 410, 500, 503) to `noindex, nofollow`.
   - Non-HTML asset override (.pdf, .doc, .xls, .xlsx) to `noindex, nofollow`.
   - Catalogsearch noindex when `noindex_search_results = Yes`.
   - Configured `noindex_paths` match via `Service\NoindexPathMatcher`.
   - Layered-nav or sort-filter parameters to `noindex, follow`.
   - Default directive from `panth_robots_seo/general/default_directive`.
4. **`Model\Robots\PolicyResolver`** aggregates LLM-bot toggles, rows from `panth_seo_robots_policy`, the configured crawl-delay, and sitemap references into the final robots.txt body for a given store.
5. **`Service\DirectiveValidator`** is the single chokepoint every directive string passes through before it reaches a response header or the robots.txt body. It rejects any string containing `\r`, `\n`, `\0`, or bytes outside printable ASCII.

---

## FAQ

### Does it work on Hyva themes?

Yes. The module works at the controller and plugin layer, not through layout or template. Both Hyva and Luma stores get the same robots.txt output and X-Robots-Tag header with no extra configuration.

### Magento already has a robots.txt textarea in Content -> Design -> Configuration. Why replace it?

Magento's built-in option is a single store-wide textarea with no header control, no LLM bot awareness, and no path-level noindex logic. Panth Robots SEO adds per-store-view generation, 14 AI crawler toggles, a structured policy grid, and the X-Robots-Tag header that Magento does not provide at all.

### Will it affect my existing robots.txt content?

When you first install the module, the generated robots.txt starts from your LLM bot toggles and any policy rows you create. If you want to keep your existing content exactly, paste it into the Custom robots.txt Body field and enable the override.

### Can I block just one path for a specific bot, not the whole site?

Yes. Open Admin - Panth Infotech - Robots & LLM Bots - Robots Policies and add a row with the user-agent, `disallow`, and the path you want blocked. You can scope the row to a specific store view and set a priority.

### Does it set the HTML `<meta name="robots">` tag too?

The module sets the `X-Robots-Tag` HTTP response header on every page, which search engines treat as equivalent to the meta tag. The HTML meta tag is updated too if `Panth_AdvancedSEO` is installed alongside this module. Without AdvancedSEO, only the HTTP header is set.

### Is the noindex path list configurable?

Yes. Go to Stores - Configuration - Panth Infotech - Robots & LLM Bots - General - Noindex URL Paths. Enter one path per line. The `*` wildcard matches anything. The seeded default covers `/customer/*`, `/checkout`, `/wishlist`, `/sales/*`, and about 14 other private patterns.

### What happens on a 404 page?

The X-Robots-Tag plugin hard-overrides to `noindex, nofollow` for HTTP status codes 404, 410, 500, and 503, regardless of any other config. Error pages can never appear in the index.

### Does it need Panth_AdvancedSEO?

No. The module is fully standalone. If AdvancedSEO is also installed, they share the `panth_seo_robots_policy` table and do not conflict with each other.

### Is it multi-store safe?

Yes. Every config value, every policy row, and every X-Robots-Tag decision resolves at store-view scope. A setting on one store view never affects another.

---

## Support

| Channel | Contact |
|---|---|
| Product Page | [kishansavaliya.com/magento-2-robots-seo.html](https://kishansavaliya.com/magento-2-robots-seo.html) |
| Email | kishansavaliyakb@gmail.com |
| Website | [kishansavaliya.com](https://kishansavaliya.com) |
| WhatsApp | +91 84012 70422 |
| GitHub Issues | [github.com/mage2sk/module-robots-seo/issues](https://github.com/mage2sk/module-robots-seo/issues) |
| Upwork (Top Rated Plus) | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| Upwork Agency | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |

Response time: 1-2 business days.

### Need Custom Magento Development?

Looking for **custom Magento module development**, **Hyva theme work**, **store migrations**, or **performance tuning**? Get a free quote in 24 hours:

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
  <a href="https://kishansavaliya.com/magento-2-robots-seo.html">
    <img src="https://img.shields.io/badge/View%20Product%20Page-magento--2--robots--seo-0D9488?style=for-the-badge" alt="View Product Page" />
  </a>
</p>

---

## About Panth Infotech

Built and maintained by **Kishan Savaliya** ([kishansavaliya.com](https://kishansavaliya.com)), a **Top Rated Plus** Magento developer on Upwork with 10+ years of eCommerce experience.

**Panth Infotech** is a Magento 2 development agency that builds high quality, security focused extensions and themes for both Hyva and Luma storefronts. The extension suite covers SEO, performance, checkout, product presentation, customer engagement, and store management, with each module built to MEQP standards and tested across Magento 2.4.4 to 2.4.8.

Browse the full extension catalog on our [Magento extensions page](https://kishansavaliya.com/magento-extensions.html) or on [Packagist](https://packagist.org/packages/mage2kishan/).

---

## Quick Links

| Resource | Link |
|---|---|
| **Product Page** | [magento-2-robots-seo.html](https://kishansavaliya.com/magento-2-robots-seo.html) |
| **Packagist** | [mage2kishan/module-robots-seo](https://packagist.org/packages/mage2kishan/module-robots-seo) |
| **GitHub** | [mage2sk/module-robots-seo](https://github.com/mage2sk/module-robots-seo) |
| **Website** | [kishansavaliya.com](https://kishansavaliya.com) |
| **Free Quote** | [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote) |
| **Upwork (Top Rated Plus)** | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| **Upwork Agency** | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |
| **Email** | kishansavaliyakb@gmail.com |
| **WhatsApp** | +91 84012 70422 |

---

<p align="center">
  <strong>Ready to take control of how bots and crawlers see your store?</strong><br/>
  <a href="https://kishansavaliya.com/magento-2-robots-seo.html">
    <img src="https://img.shields.io/badge/%F0%9F%9A%80%20See%20Robots%20SEO%20%E2%86%92-Product%20Page%20%26%20Details-DC2626?style=for-the-badge" alt="See Robots SEO" />
  </a>
</p>

---

**SEO Keywords:** magento 2 robots.txt, magento 2 robots seo, magento 2 x-robots-tag, magento 2 llm bot policy, magento 2 ai crawler control, magento 2 block gptbot, magento 2 block claudebot, magento 2 block perplexitybot, magento 2 block bytespider, magento 2 google-extended, magento 2 noindex, magento 2 noindex layered nav, magento 2 noindex search results, magento 2 crawl delay, magento 2 robots meta, magento 2 seo headers, hyva robots seo, luma robots seo, magento 2 robots extension, magento 2 robots module, mage2kishan robots seo, panth robots seo, panth infotech, hire magento developer, top rated plus upwork, kishan savaliya magento, custom magento development, magento 2.4.8 robots, php 8.4 magento seo
