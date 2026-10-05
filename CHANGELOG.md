# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.3.6] - 2026-10-06

### Fixed
- The Sitemap lines that robots.txt lists when the Sitemap URL(s) field is empty now use the Output Path and Sitemap Filename of each active Panth XML Sitemap profile of the store view, so every store view points at its own sitemap file (for example https://luma.test/sitemap_luma.xml). Profiles without a Sitemap Filename keep using the Sitemap Index Filename setting.
- When Panth XML Sitemap is disabled for the store view (XML Sitemap > General > Enabled = No), its profile files are no longer listed; Magento's own site map files are listed instead when they exist.
