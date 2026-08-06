# InkSync - Requirements Documentation (Retrospective)

InkSync was built during coursework without a formal requirements spec. This document
recovers the requirements from the shipped code, tests, and commit history - a
**requirements recovery** exercise: a real legacy-system practice for onboarding onto
undocumented codebases, not an improvised workaround.

Method: triangulate each requirement from code behavior + tests + commit history + the
author's own memory of intent. Requirements are stated as technology-agnostic outcomes;
where a specific technology or algorithm was the implementation choice, it's noted
separately as a **design decision**, not baked into the requirement text. (Test applied:
would this sentence still hold if the underlying tech were swapped out? If not, it's a
mechanism, not a requirement.)

Full matrix mapping each requirement to its implementing commit(s), test case, and
verification status: [traceability-matrix.md](./traceability-matrix.md).

---

## User stories → requirements

### Story 1
As a user, I want to upload a reference image and describe a scene, so that I get a
storyboard panel in a consistent sketch style without drawing it myself.

- **FR-1** - The system shall generate a storyboard panel from a user-supplied text
  prompt and reference image.
- **NFR-1** - The generated panel shall preserve the spatial composition (layout, pose,
  framing) of the reference image.
  *Design decision: ControlNet (edge-canny + lineart) conditioning + a custom LoRA
  style fine-tune. Not part of the requirement - the requirement is the outcome.*
- **NFR-2** - Generation shall complete within a time bound acceptable for interactive,
  one-panel-at-a-time use (not a batch/offline job).
  *Design decision: SDXL step count / sampler tuning, not the requirement itself.*

**Acceptance criteria:** given a valid reference image and non-empty prompt, submitting
the form returns a rendered panel matching the reference image's composition, without
the user needing to configure any model parameters.

### Story 2
As a returning user, I want an account with my own history, so that my work is private
and persists across sessions.

- **FR-2** - The system shall allow a user to register an account and log in with a
  username and password.
- **FR-3** - The system shall reject weak or commonly-used passwords at registration.
- **FR-4** - The system shall lock out an account for a cooldown period after repeated
  failed login attempts.

**Acceptance criteria:** registration fails with a clear message for a password found
in the common-password list; N consecutive failed logins block further attempts until
the cooldown elapses.

### Story 3
As the operator, I want to cap usage per user and see who's generating what, so that
API costs stay predictable and abuse is visible.

- **FR-5** - The system shall enforce a daily generation cap per standard user
  (40/day), with admin accounts exempt.
- **FR-6** - The system shall provide an admin view of each user's generation count
  for the current day.

**Acceptance criteria:** a standard user's 41st generation request the same day is
rejected; the admin panel shows today's count per user, unlimited shown for admins.

### Story 4
As a user, I want to save, revisit, and organize my storyboards, so that I don't lose
work between sessions.

- **FR-7** - The system shall let a user save, rename, delete, and reopen a project
  containing multiple panels.
- **FR-10** - Deleting a panel's stored image shall not be able to escape the storage
  root directory (no path traversal via a crafted filename).

**Acceptance criteria:** a saved project's panels are exactly as left after logout/
login; a delete request referencing `../../` or an absolute path is rejected rather
than deleting outside the storage directory.

### Story 5
As a user, I want to export my finished storyboard as an image, so I can share it
outside the app.

- **FR-8** - The system shall export a full storyboard (all panels in a project) as a
  single downloadable PNG.

**Acceptance criteria:** the exported PNG contains every panel in the project, in
order, legible at normal zoom.

### Story 6
As a user, I want the app to reject bad uploads before they reach the generation
pipeline, so that errors are caught early and cheaply rather than wasting an API call.

- **FR-9** - The system shall validate an uploaded reference image's real file type
  (not the client-supplied MIME string), enforce a maximum file size, and reject
  images whose declared dimensions could cause a decompression-bomb memory spike -
  before the file is sent to the generation API.

**Acceptance criteria:** a renamed non-image file, an oversized file, and an image
with declared dimensions above the safe threshold are all rejected with a clear error,
and none reach the Replicate API call.

### Story 7
As a user, I want to be asked for consent before my usage data is processed, so the
app is transparent about what it collects (GDPR).

- **FR-11** - The system shall capture explicit user consent before processing
  personal or usage data, and disclose what is collected in a privacy notice.

**Acceptance criteria:** a user cannot reach the generation flow without first
accepting the consent screen; the privacy page enumerates what's collected (login
attempts, daily generation counts).

---

## Non-functional requirements (cross-cutting)

| ID | Requirement | Design decision (not part of the requirement) |
|----|---|---|
| NFR-1 | Preserve reference image composition in output | ControlNet (edge-canny + lineart) |
| NFR-2 | Interactive-use generation latency | SDXL step count / sampler choice |
| NFR-3 | UI shall be usable across common viewport sizes and follow basic accessibility practice for interactive elements (labels, focus targets) | CSS responsive breakpoints; semantic `<span>` roles |
| NFR-4 | The codebase shall run automated regression tests and static lint checks on every push, gating merges | PHPUnit + PHPCS (PSR-12) via GitHub Actions |
| NFR-5 | Secrets and environment-specific config shall never be committed to version control | `.gitignore` entries for `config.php`, `.env*`, `secrets.php` |

---

## Known gaps (honest limitations of this retrofit)

- FR-2, FR-6, FR-7, FR-8, NFR-2, NFR-3 have **no automated test** - verified manually
  during original development, not covered by the current PHPUnit suite. Flagged in
  the matrix rather than glossed over.
- Recovered from code + commit messages, not a stakeholder interview - priorities
  (Must/Should/Could) are the author's reconstruction of original intent, not a
  client's, since this was a solo student project.
