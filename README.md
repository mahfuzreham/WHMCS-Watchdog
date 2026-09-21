# WHMCS Watchdog

Secure file-integrity monitoring addon for WHMCS.

## Compatibility

- WHMCS 8.9
- PHP 8.1
- PHP 8.2
- PHP 8.3
- PHP 8.4
- PHP 8.5

WHMCS 8.9 officially lists PHP 8.1 as its recommended PHP version. WHMCS 8.9 is EOL, so this addon does not replace the need to keep WHMCS itself updated.

## Security fixes

- Removed the broken action database index.
- Added safe schema creation and activation/deactivation handling.
- Moved integrity checks from AdminAreaHeadOutput to AfterCronJob.
- Added cURL connection and operation timeouts.
- Kept TLS certificate and host verification enabled.
- Added strict checksum-manifest validation.
- Added safe recursive PHP file scanning and symlink skipping.
- Removed raw SQL interpolation from the dashboard inspection query.
- Added admin CSRF-token validation for POST operations.
- Restricted settings to an allow-list.
- Added strict validation for frequency and notification email addresses.
- Added safe JSON handling.
- Removed echo/print_r/die debugging from the integrity scanner.
- Added missing-file, modified-file and unknown-file handling.
- Added basic whitelist lookup support.
- Hardened Smarty output against HTML injection.

## Checksum manifests

A checksum manifest must come from a trusted, clean WHMCS release/package.

Do not generate a manifest from a potentially compromised production installation and treat it as trusted.

The scanner intentionally refuses to perform an integrity scan when a version manifest is missing or invalid.

Manifests are expected at:

checksum/<exact-whmcs-version>.json

Example:

checksum/8.9.0.json

The manifest should contain relative WHMCS paths mapped to their trusted hashes.

## Cron

Watchdog runs from WHMCS cron and respects the configured check interval. It no longer performs a filesystem scan on every admin page request.

## Important

The module can be compatible with PHP 8.1–8.5 while WHMCS itself may have a narrower officially supported PHP matrix. Always follow the WHMCS version's own system requirements when choosing the PHP runtime.
