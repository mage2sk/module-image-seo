# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.13] - 2026-10-04

### Fixed
- Hyva product page: the server-rendered main gallery image now carries the generated alt and title text instead of a placeholder label such as "Image"; merchant-written labels are still kept.
- The Alt Text Template and Title Text Template settings are now validated on save. Unknown tokens, unknown filters, incomplete tokens and templates longer than 255 characters are rejected with a message that names the problem.
- The help text of "Enable Template-Based Image Alt/Title" shows the token examples again.
