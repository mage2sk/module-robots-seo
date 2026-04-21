# Changelog

All notable changes to Panth_RobotsSeo will be documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and the project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.2]

### Fixed

- **X-Robots-Tag plugin DI wiring.** `XRobotsTagPlugin::__construct`
  declared `?MetaResolver = null` and `?NoindexPathMatcher = null`, so
  Magento's ObjectManager silently injected `null` for both and the
  plugin fell through to the "default, index,follow" branch for every
  request. Consequences: `/customer/*`, `/checkout`, `/wishlist`, etc.
  never received `noindex, nofollow`; `max-image-preview` and
  `max-snippet` were never appended to any response. The defaults have
  been removed so the dependencies are now required — matching the
  intent in the original design. Flush caches + regenerate DI after
  upgrading (`bin/magento cache:flush && bin/magento setup:di:compile`).
- **/robots.txt self-overriding X-Robots-Tag.** The controller sets
  `X-Robots-Tag: noindex` on its own response, but the
  `beforeSendResponse` plugin ran afterwards and always overwrote it
  with the default `index,follow,max-image-preview:large,max-snippet:-1`.
  The plugin now short-circuits when the request path is `/robots.txt`
  so the controller's directive wins.
- **Stale url_rewrite on upgrade from Panth_AdvancedSEO.** The original
  `InstallRobotsTxtRewrite` patch was idempotent on insert (skipped
  when a row for `robots.txt` already existed) so upgrading sites that
  had Panth_AdvancedSEO's legacy rewrite (`target_path=seo/robots/index`)
  kept their stale row and `/robots.txt` returned a 404 HTML page. New
  `RefreshRobotsTxtRewrite` data patch upserts the target to
  `seo_robots/robots/index` regardless of current state.

### Added

- **Admin CRUD — the missing half.** The previous release shipped the
  listing UI component + controllers but no layout files, so the
  `panth_robots_seo/policy/index` and `.../edit` pages rendered empty.
  This release adds:
  - `view/adminhtml/layout/panth_robots_seo_policy_index.xml` — wires
    the listing component into the grid page.
  - `view/adminhtml/ui_component/panth_robots_seo_policy_form.xml` —
    edit form with User-agent, Directive, Path, Store View, Priority
    and Active fields (plus validation).
  - `view/adminhtml/layout/panth_robots_seo_policy_edit.xml` +
    `panth_robots_seo_policy_new.xml` — render the form on edit / new.
  - `Ui\Component\Form\DataProvider\PolicyFormDataProvider` — supplies
    existing row data or sensible new-row defaults.
  - `Ui\Component\Listing\Column\PolicyActions` — per-row Edit / Delete
    links on the grid.
  - `Block\Adminhtml\Policy\Edit\{Back,Save,SaveAndContinue,Delete}Button`
    — standard form toolbar.
  - `Controller\Adminhtml\Policy\{MassDelete,MassStatus}` — mass Enable /
    Disable / Delete actions on the grid.
  - `Save` controller now tolerates both top-level params and
    `data[<field>]` submit payloads so the ui_component form works
    regardless of the form's `dataScope` setting.
- **HTML `<meta name="robots">` finally respects the module.** Magento
  core pulls the tag value from `design/search_engine_robots/default_robots`
  — a free-text store-design field with no awareness of this module's
  per-path / per-bot logic, so rendered HTML always emitted
  `INDEX,FOLLOW` regardless of whether `/customer/*`, a catalogsearch
  page or a layered-nav URL was being served. The new
  `Plugin\Page\RobotsMetaPlugin` is an `afterGetRobots` on
  `Magento\Framework\View\Page\Config` that consults the same precedence
  order as `XRobotsTagPlugin` (noindex-assets → catalogsearch-noindex →
  configured noindex paths → MetaResolver default) and appends
  `max-image-preview` / `max-snippet`, so the HTML tag now matches the
  HTTP header on every frontend page.
- **Wired the dead debug flag.** `panth_robots_seo/general/debug` existed
  in system.xml but no code read it. The plugin now logs every
  resolved directive (URI, HTTP status, final value) to
  `var/log/panth_robots_seo.log` through a dedicated
  `Panth\RobotsSeo\Logger\Logger` virtualType when the flag is on.

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
