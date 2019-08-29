# Changelog
All notable changes to **DecaLog** is documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **DecaLog** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Changed
- Better "Page not found" detection and report.
- 'Component' become 'source' in WordPress events viewer.
### Fixed
- WordPress formatter may (wrongly) emit warnings when source ip is unknown.

## [1.1.0] - 2019-08-28
### Added
- A test message can now be sent to a specific logger, from the loggers list.
### Changed
- Status (in loggers list) have now their own column.
- Polishing loggers list.
- The `README.md` displays badges.
### Removed
- DecaLog admin pages no longer render emoji.
- The section "install from GitHub" has been removed from `README.md`.
### Fixed
- Url of the EFF website was wrong in `readme.txt`.

## [1.0.1] - 2019-08-26
### Changed
- Language file (`.pot`) is updated.
- Main file has now a consistent GPL version license.
- In admin dashboard, `&` character is now outputted in UTF, not with its HTML entity equivalent.
### Fixed
- Erroneous date in `CHANGELOG.md` is changed.
- Wrongly rendered unordered lists in `readme.txt` are fixed.

## [1.0.0] - 2019-08-26
### Initial release
