# InkSync - Requirements Traceability Matrix

Current-state matrix: one row per requirement, showing where it lives today (not a
full commit-history dump). A short **History** note is added only for requirements
whose behavior changed after the original implementation. See
[requirements.md](./requirements.md) for the full requirement text, user stories, and
acceptance criteria.

Commit hashes are short-SHA, from `inksync_v3`'s `main` history.

| Req ID | Requirement (short) | Source / Rationale | Priority | Feature / Module | Commit(s) | Test Case ID | Test Result | Verification Status |
|---|---|---|---|---|---|---|---|---|
| FR-1 | Generate panel from prompt + reference image | Core product function | Must | `backend/prompt.php`, `backend/poll_prediction.php` | `f56ab7c`, `93fa37a`, `931ffdf` | `PromptTest::testStyleTogglesChangeThePromptAndSlidersPassThrough` | Pass | Automated (CI) |
| FR-2 | Register + log in with username/password | Core account function | Must | `pages/register.php`, `backend/check_login.php` | `ef77e44`, `857a2bb` | - | Not automated | Manually verified (original dev) |
| FR-3 | Reject weak/common passwords at registration | Security - credential stuffing / brute-force mitigation | Should | `backend/check_login.php::validate_password` | `bc0f6b1` | `ValidatePasswordTest::testAcceptsAStrongPasswordButRejectsWeakOrCommonOnes` | Pass | Automated (CI) |
| FR-4 | Lock out account after repeated failed logins | Security - brute-force mitigation | Should | `backend/check_login.php::login_lockout_minutes_left` | `8970d45` | - | Not automated | Manually verified (original dev) |
| FR-5 | Daily generation cap (40/day standard, unlimited admin) | Cost control - Replicate API is metered | Must | `index.php` (cap check), `backend/poll_prediction.php` (counter) | `3c70ba7` | - | Not automated | Manually verified (original dev) |
| FR-6 | Admin view of per-user generation counts | Operator visibility / abuse detection | Should | `pages/admin_panel.php` | `3c70ba7`, `a9758d7` | - | Not automated | Manually verified (original dev) |
| FR-7 | Save / rename / delete / reopen projects | Core product function - persistence | Must | `backend/save_panels.php`, `pages/projects.php`, `backend/create_project.php`, `backend/rename_project.php`, `backend/delete_project.php` | `6c366b1`, `94cd807`, `18c5334`, `f8db105`, `245012d`, `d336518` | - | Not automated | Manually verified (original dev) |
| FR-8 | Export storyboard grid as one PNG | Core product function - sharing | Should | `frontend` (html2canvas), storyboard export UI | `1878599`, `2b35a82`, `0323cc1` | - | Not automated | Manually verified (original dev) - client-side, hard to unit test |
| FR-9 | Validate uploaded reference image (real MIME, size cap, decompression-bomb guard) | Security - spoofed uploads, memory exhaustion | Must | `backend/api.php::validate_image` | `f56ab7c` | `ValidateImageTest::testAcceptsARealSmallJpeg`, `::testRejectsASpoofedFileType`, `::testRejectsOversizedDimensions` | Pass (3/3) | Automated (CI) |
| FR-10 | Safe panel-image deletion (no path traversal) | Security - path traversal | Must | `backend/storage.php::delete_panel_image` | `8970d45` | `StorageTest::testDeletesAFileInsideStorageRootButRefusesPathTraversal` | Pass | Automated (CI) |
| FR-11 | Capture consent before processing personal/usage data | Legal - GDPR | Must | `pages/consent.php`, `pages/privacy.php`, `backend/consent.php` | `8970d45`, `4feaef5` | - | Not automated | Manually verified (original dev) |
| NFR-1 | Preserve reference image composition in output | Core product quality - the "storyboard" value prop depends on it | Must | `backend/prompt.php` (ControlNet params) | `931ffdf`, `93fa37a` | `PromptTest::testStyleTogglesChangeThePromptAndSlidersPassThrough` (partial - covers param wiring, not visual output) | Pass | Automated (CI), partial - no visual-similarity assertion |
| NFR-2 | Interactive-use generation latency | Usability - user waits synchronously for one panel | Should | Replicate API call config, `prompt.php` | `93fa37a` | - | Not automated | Unverified - flagged gap, no latency benchmark exists |
| NFR-3 | Responsive UI, basic accessibility on interactive elements | Usability | Could | `frontend` CSS/JS | `0463ae3`, `be60ddd` | - | Not automated | Manually verified (original dev) |
| NFR-4 | Automated regression tests + lint gate every push | Maintainability - CI/CD retrofit for this application | Should | `.github/workflows/ci.yml`, `phpunit.xml`, `phpcs.xml` | `4feaef5` | (pipeline itself) | Green - PHPCS + PHPUnit both pass | Automated (CI), confirmed green on GitHub Actions |
| NFR-5 | No secrets/config committed to VCS | Security hygiene | Must | `.gitignore` | `8970d45`, `4feaef5` | - | Not automated | Manually reviewed - `.gitignore` covers `config.php`, `.env*`, `secrets.php`; full history not audited for secrets committed before these entries existed |

## History (requirements whose behavior changed)

| Req ID | Change | Commit | Why |
|---|---|---|---|
| FR-9 | `validate_image` hardened (real-MIME check via `finfo`, decompression-bomb guard added) | `f56ab7c` (superseding earlier, weaker upload handling in `8970d45`) | Original check trusted client-supplied MIME type - spoofable |

## Coverage summary

- 16 requirements tracked (11 FR, 5 NFR).
- 6 have automated PHPUnit coverage (FR-1, FR-3, FR-9, FR-10, NFR-1 partial, NFR-4 via
  the pipeline itself).
- 10 are manually-verified-only or flagged as unverified.

---
Related: [requirements.md](./requirements.md)
