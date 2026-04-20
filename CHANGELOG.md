# Changelog

All notable changes to Panth_RobotsSeo will be documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and the project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.1]

### Fixed
- ACL XML duplicate resource-id error that prevented the admin config
  page from loading (`Panth_X::config` was declared under both
  `Panth_Core::panth_extensions` and `Magento_Config::config`). The
  redundant declaration under `Panth_Core::panth_extensions` has been
  removed; the menu link continues to gate on the real system-config
  resource.

## [1.0.0] — 2026-04-20

### Added

- Initial release, extracted from Panth_AdvancedSEO.
- `Panth\RobotsSeo\Api\RobotsPolicyInterface` with meta / header /
  robots.txt body / LLM-bot policy methods.
- `Panth\RobotsSeo\Model\Robots\PolicyResolver` — aggregates LLM-bot
  toggles + `panth_seo_robots_policy` rows + sitemap references into a
  per-store robots.txt body.
- `Panth\RobotsSeo\Model\Robots\MetaResolver` — per-entity robots meta
  resolver with URL-pattern overrides (layered nav, search results).
- `Panth\RobotsSeo\Plugin\Response\XRobotsTagPlugin` — X-Robots-Tag
  header on frontend responses, with hard noindex for error pages,
  document assets, and private paths.
- Admin Robots Policies grid (CRUD) + robots.txt preview page.
- System config at `panth_robots_seo/*` (general / llm_bots / robots_txt).
- URL rewrite patch that maps `/robots.txt` to the module controller.
- `DirectiveValidator` service that rejects CRLF injection and enforces a
  strict token whitelist for every directive string that reaches a
  response header or the robots.txt body.

### Security

- Admin Policy `Save` controller rejects user-agent strings outside the
  printable-ASCII whitelist (`/^[A-Za-z0-9._\-+*\/ ]+$/`).
- Every directive written to `X-Robots-Tag` is sanitised so CR / LF /
  NUL bytes cannot smuggle additional headers.
- `path` column values are validated (must start with `/`, no control
  bytes) before being written into the robots.txt response.

### Cross-module dependency

- `MetaResolver::fetchStored()` reads `panth_seo_resolved.robots` WHEN
  the table exists (Panth_AdvancedSEO populates it via its indexer). If
  the table is absent, the resolver falls back to
  `panth_robots_seo/general/default_directive` system config. Both
  modules can coexist, and Panth_RobotsSeo is fully functional on its
  own without AdvancedSEO.

### Notes

- Table name `panth_seo_robots_policy` is preserved unchanged from
  Panth_AdvancedSEO to make migration a no-op data-wise.
- Admin route `panth_robots_seo`, frontend route `seo_robots`.
- Sequence in module.xml: `Panth_Core`, `Magento_Store`, `Magento_Backend`,
  `Magento_Catalog`, `Magento_Cms`, `Magento_Robots` — so the frontend
  DI override of the core robots router runs in the correct order.
