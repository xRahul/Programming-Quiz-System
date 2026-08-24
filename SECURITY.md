# Security Policy

## Supported versions

| Version | Branch | Supported |
| --- | --- | --- |
| 1.0.x (revitalized tree) | `master` | Yes |
| 0.x (legacy XAMPP-era code) | — | No |

Only the revitalized tree receives security fixes. The legacy pre-2026 code
(see `docs/history/v0-readme.md`) is documented for history and must not be
deployed.

## Reporting a vulnerability

Please report privately — **do not open a public issue** for anything you
believe is exploitable.

1. Go to <https://github.com/xRahul/Programming-Quiz-System/security/advisories/new>
   and open a private security advisory, or
2. contact the maintainer directly if you prefer email.

Include: affected page/endpoint, reproduction steps or a PoC, impact
assessment, and any suggested fix.

## Response expectations

- **Acknowledgement:** within 48 hours.
- **Triage & severity assignment:** within 7 days.
- **Fix target:** within 90 days of acceptance (faster for critical issues).
- You will be kept informed at each step and credited in the changelog unless
  you prefer otherwise. We ask for coordinated disclosure once a fix ships.

## Known limitations

Design decisions preserved deliberately (bug-for-bug legacy parity) — known
trade-offs, not open vulnerabilities:

- **Client-trusted grading** — `result.php` computes marks from the answer-id
  set and the `total_ques` denominator supplied by the client's form, so a
  manipulated submission can inflate a score. This is the inherent legacy
  grading model, kept on purpose; treat exported/reported scores as advisory
  in adversarial settings.
- **Login CSRF exemption** — the login endpoint accepts token-less POSTs by
  design: a pre-auth user holds no token yet. Every other state-changing POST
  verifies a CSRF token (admin.php enforces this with one unconditional gate
  at the top of the file).
- **Secure cookie behind TLS-terminating proxies** — the secure-cookie flag
  decision ignores `X-Forwarded-Proto`. When deploying behind a proxy that
  terminates TLS, either terminate HTTPS on-host or adjust the cookie flags
  for that topology; otherwise cookies may lack the `Secure` attribute as
  seen by the browser.
