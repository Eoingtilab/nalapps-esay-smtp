# Security Policy

NalApps Easy SMTP is developed by **EOINGTI Lab / 어잉티연구소** under the NalApps WordPress Plugin Standard.

## Reporting a vulnerability

Do not post license keys, SMTP passwords, API keys, private customer data, database dumps, session values, or server credentials in a public issue.

For general project information, visit:

- Developer: https://eoingti.com/
- Repository: https://github.com/Eoingtilab/nalapps-esay-smtp

If a security report requires sensitive evidence, contact EOINGTI Lab through a private channel listed on the developer website rather than publishing the evidence in GitHub issues.

## Security baseline

- State-changing admin actions require capability and nonce checks.
- The SMTP password is stored encrypted with a key derived from this site's `wp_salt( 'auth' )` and is never included in settings exports, snapshots, system information, or logs.
- Remote license and update requests use HTTPS with certificate verification and bounded timeouts.
- Distribution ZIPs are built by GitHub Actions and include required production dependencies.
- Existing release tags/assets are treated as immutable.
