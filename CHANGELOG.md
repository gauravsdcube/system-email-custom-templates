# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-07-08

### Added

- `{first_name}` variable for all templates that expose `{display_name}` (profile first name, with username fallback)
- Multi-pass variable substitution for nested placeholders inside button shortcodes
- URL fallbacks in `VariableExtractor` for invite, password recovery, email change, and login links

### Fixed

- Button shortcodes rendering as raw `{button:Label|}` in live emails when URL variables were empty
- Escaped pipe characters (`\|`) in button shortcodes from the rich-text editor not being parsed
- `{display_name}` appearing literally in registration approval emails (processing now runs at send time after `setTo()`)
- Recipient and admin variables no longer depend on `debug_backtrace()` object access (removed in PHP 8.4)
- Email change confirmation now reads `approveUrl` from HumHub compose parameters
- Header and footer button shortcodes use the same extract → render → inject pipeline as the body

### Changed

- `MailInterceptor` defers custom template processing to `beforeSend()` so recipients are resolved correctly
- Default templates updated for registration approval (login button), already-registered notice, and username change notice
- `user.change_username` default template aligned with core HumHub behaviour (informational, no confirm button)

## [1.0.0] - 2026-07-08

### Added

- Initial release
- Rich text email templates with header, body, and footer
- Button shortcode support with email-client-safe rendering
- Per-email enable toggles and live preview
- Group and permission-based access control

[1.1.0]: https://github.com/gauravsdcube/system-email-custom-templates/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/gauravsdcube/system-email-custom-templates/releases/tag/v1.0.0
