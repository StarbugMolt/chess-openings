# Navigation and learning experience

This is the interaction contract for guided lessons, variation practice and review. M5b is implemented and locally verified; [practice and review](PROGRESS.md) records its current behavior and verification. The [M4 checkpoint](M4_IMPLEMENTATION.md) and [earlier review record](REVIEW_FIXES.md) retain their historical results. Release requirements below remain separate from implementation evidence.

## Routes and navigation

| Route | Access | Purpose |
| --- | --- | --- |
| `/` | Public | Explain the two modes and link directly to beginner lessons and the opening reference. |
| `/learn` | Public | List deliberately authored courses, their scope and available lines. |
| `/learn/:id` | Signed in | Select a finite line and colour, then practise it with automatic fixed opponent replies. |
| `/train/:id` | Signed in | Mix explicitly selected taught lines, accept any supported response and practise against replies from that scope. |
| `/progress` | Signed in | Show private review suggestions, current line/colour coverage, recall evidence and earlier-version practice. |
| `/openings` | Public | Search named source records, inspect recorded moves and find a related authored course when available. |
| `/login`, `/register` | Public, redirect signed-in users | Sign in or create an account. Preserve a validated local destination for after sign-in. |
| `/forgot-password`, `/resend-verification` | Public | Request an email using the same public response for eligible and unknown addresses. |
| `/verify-email`, `/reset-password` | Public | Confirm a single-use link. Keep the token out of query strings and clear it from browser history. |
| `/account` | Signed in | Account state, learning/review links, password-confirmed data download and cancellable permanent deletion. Sign-out returns home. |
| `/admin` | Admins only (non-admins → `/learn`) | Usage statistics from stored data and a searchable user list with password-confirmed account deletion. See [ADMIN_REVIEW.md](ADMIN_REVIEW.md). |
| `/privacy` | Public | Describe actual preview data use and distinguish pending public-launch information. |
| `/dev/board` | Development builds only | Exercise board/rules/audio; never serve an unrestricted board in production. |
| Unknown route | Public | Explain that the page does not exist and offer learning/home links. |

Keep Learn, Openings and Account/Sign in available in desktop and mobile navigation, with a visible current-page state. Keep the Privacy, source/licence and third-party notice links in the footer. Mobile navigation must wrap or fit without horizontal scrolling and retain usable touch targets. Each page needs a descriptive document title and one clear main heading.

On route changes, restore browser back/forward scroll, honour local hash anchors and otherwise start at the top. Move focus to the new main region without scrolling it away. Search paging retains focus on a persistent control or announces the updated results. Loading and unavailable sign-in state must be distinguishable from being signed out.

The owner chose ordinary account registration: enter email and password once, confirm the email link, then sign in with that password. Verification posts only the token and asks for no password. Each registration link retains its own password binding until a link is consumed; resending uses the most recent pending registration. Tell recipients to ignore registrations they did not request. See [ACCOUNTS.md](ACCOUNTS.md) for the accepted unsolicited-link residual and legacy-link handling.

## Admin area

Show the header **Admin** link only when `/api/me` reports `admin: true`; nobody can become an admin from the web. The page is a dashboard: a section rail (Overview, Growth, Courses, Users) that tracks the scroll position and becomes a horizontal strip under 900 px, a header with the calculation time and **Refresh**, and **Usage** panels. An ink band leads with practice totals and a 30-day column chart whose latest (today, partial) column is in the highlighter colour. Below it: account, sign-in recency and unassisted-completion tiles, weekly sign-ups versus verifications (a two-step ink ramp with a legend and a disclosure table), the White/Black split, a course leaderboard and the ranked top lines. Every value is printed as text or reachable without hovering: each chart is a list with one tab stop, arrow/Home/End keys move between columns, and hover or focus reveals that column's tooltip. Reloading keeps the previous figures dimmed rather than blanking them. A footnote states that figures come only from stored data and that practice mode and page views are not recorded. **Users** keeps search and page in the URL, uses a table on desktop and cards below 760 px, shows relative times with the exact time in the tooltip, and marks admins, the current account and unverified accounts. Delete is disabled for yourself and other admins, with an explanation. Otherwise it expands into the same permanence warning, own-password field and confirmation checkbox as Account. Success removes the row and announces it. 401 offers sign-in again (a CLI grant or revoke signs that account out everywhere), 404 counts as already deleted, 409 refreshes and explains, and 429 shows the wait.

## Account data controls

Account provides **Download your data** and **Delete your account** in separate wrapping cards. Each action requires the current password; passwords are cleared on submission/navigation and never stored in URLs or browser storage. Download gives a private JSON file with account details and every saved practice, including earlier versions. Explain that the file contains personal information and that passwords/sign-in secrets are excluded.

Deletion first reveals its own form, moves focus to the password field and states permanent loss of account/practice. Require an explicit acknowledgement before enabling **Permanently delete my account**; **Keep my account** cancels, clears the form and returns focus. Success shows **Account deleted.**, focuses that heading and clears client account state. It must not look like an ordinary sign-out. Wrong passwords and rate limits retain useful feedback; an expired session offers sign-in without a cached-account redirect loop. Controls have 44 px touch targets and fit mobile without horizontal scrolling. Backup/log/temporary-security-record boundaries are linked from the form and described honestly in Privacy; owner-approved retention and daily maintenance are documented in [PRIVACY_RETENTION.md](PRIVACY_RETENTION.md). See [ACCOUNT_DATA.md](ACCOUNT_DATA.md).

## Source catalogue and courses

An opening family groups related named variations; a variation is a named reference position with one or more source move sequences. A line is a finite ordered path selected for teaching. A lesson teaches one or more authored lines; a course groups that teaching for the user. Catalogue counts must say whether they count raw source records or grouped variation names.

Search matches literal text anywhere in the name, ignoring case. SQL wildcard characters are ordinary input. ECO A–E prefixes are optional reference filters with an explanation; users should not need to know ECO to begin. Store the committed search, ECO and page in the URL. Normalise invalid page values to page 1, uppercase ECO and never report a malformed local URL as a backend outage. Retry repeats the committed query rather than an unsubmitted edit.

Selecting a catalogue result reveals its recorded move sequences and ECO codes. Group exact duplicate names without claiming that their move orders are interchangeable lessons. Related family-course links offer an introduction; they must not claim that the selected reference variation is taught just because the family title matches. An exact “Practise this” link would require an explicit authored-path mapping. When no related course is available, say that guided teaching is not available yet and offer the course list. Expanding a result mounts a read-only board for its first recorded sequence, opened at the final position. Start/previous/next/final buttons, clickable numbered moves and arrow/Home/End keys step through it, with a polite position status. Each recorded sequence is a toggle button that switches the board. Only expanded entries mount a board. A sequence that cannot be legally replayed shows a plain notice rather than a wrong position. Family shortcuts cover every course family. A complete family taxonomy, a dedicated detail route and move search remain later increments.

## Guided trainer

On desktop, place a large board beside the lesson controls, feedback and move list. On narrow screens, place the board first and controls below without covering squares. Display the selected line and playing colour; offer flip, restart, undo, hints and a persistent sound toggle. A "Why these moves" card stays visible while playing. It shows the latest move pair, your move and the automatic reply, with numbered notation and the original explanation, and emphasises the most recent move. It sits beside the board on desktop and directly under the board on narrow screens. On completion it becomes "How the line ends" and keeps the final notes. Move announcements for screen readers include the same explanation. The variation trainer deliberately keeps explanations behind Hint/"Why that move?" because they can reveal hidden variation names. Keep move input available through drag, tap and a labelled keyboard coordinate field. Promotion requires an explicit piece choice.

Only an accepted taught move advances the fixed opponent response. Feedback is neutral:

- Illegal move: explain that the move is not legal and preserve the current position.
- Legal move in another supported lesson line: “Valid alternative, but not the line we’re practising.” Offer retry or reveal.
- Other legal move: “Outside this lesson.” Offer retry or reveal the taught move without an engine judgement.
- End of line: explain what was practised and offer another line or restart. Save only the authenticated user's validated finite path and original lesson revision.

Pin catalogue and lesson revisions when a session begins. A catalogue refresh must not change that active lesson. Undo/restart restore the same lesson scope. Finished practice includes a compact recall summary; the completion screen names the actual taught decisions recalled on first try without a hint, with that line's decision count as its denominator. A takeback conservatively prevents independent-recall credit for the attempt. A completion count does not imply mastery of an opening.

## Variation trainer

Courses with multiple taught lines link to **Practise variations**. Start with lines completed for the same course revision and playing colour. When none have been completed, leave selection empty and explain that the learner can first use guided learning or explicitly select available lines. Offer All learned, Main line only and Select all available. Changing colour recalculates learned defaults. A single selected line is described as a fixed sequence; it does not promise different opponent replies.

Both sides can follow any continuation from the selected compatible authored paths. Reject a legal move outside that scope neutrally and preserve the board; no opponent reply follows it. Shared positions preserve the full chosen path context and do not unlock a suffix from a different move order. Continue beyond a shorter selected prefix while another compatible selected path continues. End only at a taught finite endpoint and save only the exact line actually practised.

Keep the large board, automatic replies, coordinate/promotion input, sound and undo/restart controls. A board-adjacent Hint glows one supported source piece, then its destination on selection, with the original explanation. Another supported reply remains valid. Names may stay hidden until reveal or completion; explain that requesting a hint or opening an explanation also reveals them. Finishing shows the actual line, scope and save status, with no mastery claim. Repeat this mix restores the same opponent selection sequence; Try a new mix samples again, so the same reply can still occur by chance.

Save retries reuse the attempt identity and compact assessment. A confirmed save updates learned-line defaults even if the learner already returned to setup, while stale save indicators cannot alter a newer attempt. A freshly loaded lesson snapshot receives progress from the server rather than double-counting an older response. Guided and variation completions both mean practice of the recorded immutable line. Seeds and raw move-interaction logs are not persisted.

Variation replies now favour less-practised supported lines and lines ready for review. Freeze this evidence for the selected lesson revision and colour at session start. Deduplicate next moves and duplicate full-path aliases so additional labels do not multiply a reply's probability. Weighting preserves whole-path eligibility, finite endpoints and deterministic seeded undo/restart; it never makes a move outside the chosen scope playable. If review evidence cannot be loaded, training remains available using completion counts without the due-review boost.

## Your practice and review

Account, signed-in Learn and completion screens link to **Your practice** at `/progress`. Show a short, server-ordered list of ready reviews across both colours, with course/line names, explicit playing colour and a **Review this line** action. The response contains up to 20 suggestions; expanding the list does not claim to expose all due work. New accounts get a direct guided-learning link. When no review is ready, offer ordinary practice without marking new lines overdue.

Each course starts with its recommended pieces and has a labelled White/Black selector. Coverage states an explicit current-version denominator, for example “2 of 4 current lines practised as Black”. White and Black are independent. Line details show saved practice, measured recall and suggested review dates; move notes use observed decision counters only. Additional tries include accepted replays after takebacks, so they must not all be described as moves outside the exercise. Use concrete counts and statuses rather than an undefined mastery or accuracy percentage.

Earlier completions without an assessment have recall marked **unknown**, even if their historical hint count was zero. A finished attempt is recalled without help only when it used no hint, outside-exercise retry or takeback. Decision credit is specific to the exact course, revision, line, colour and authored ply. Hints and retries on an abandoned branch affect the whole attempt but do not credit abandoned positions. Sharing a chess position with another line does not complete that line or transfer its decision history.

The server schedules the first measured unassisted review after one day; successful due reviews advance through 1, 3, 7, 14 and 30 days. Early repetition records practice without advancing the interval. Practice with help schedules another look in ten minutes. Legacy-only current practice is ready for review immediately with recall unknown. Due state and order come from the server's UTC clock; the UI formats dates without recalculating the schedule. Equal-second events use the assisted outcome if any measured practice in that bucket used help.

Review links reuse guided learning: `/learn/:id?line=...&color=b&revision=N`. Validate all parameters against the loaded current lesson. An outdated revision explains the update and returns to explicit current setup. Active old-version lessons can still finish and save against their pinned revision; earlier practice stays in collapsed **Earlier course versions** history and never inflates current coverage.

The private page distinguishes loading, retryable service failures and confirmed session expiry. After a 401 response from the practice endpoint, refresh the shared account state before offering **Sign in again**, preserving `/progress` as the destination. This avoids redirecting through a cached signed-in state. Mobile uses one-column wrapping cards, labelled 44px controls, semantic review/course regions and polite announcements, without horizontal tables. These flows passed desktop/mobile browser checks; see [current results](PROGRESS.md).

## Accessibility and preferences

Target WCAG 2.2 AA, with verification still required before claiming conformance. Use visible focus, labelled controls, a skip link and persistent polite live announcements for move feedback, errors and successful form submissions. Do not give an interactive board only an image role. Ensure 4.5:1 contrast for ordinary text, readable secondary text and controls usable without a pointer. Avoid tiny decorative captions carrying necessary information.

Honour `prefers-reduced-motion` in board animations. Sound stays optional and browser-local, starts only after a user gesture and remains silent on rejected moves. No third-party audio or tracking is needed. Explain password requirements beside the form and make link-expiry and throttling feedback actionable.

The interface supports English, French and Spanish, with a browser-local language choice and a light theme. Its colours, type, board styling and control rules are recorded in [DESIGN.md](DESIGN.md); translation maintenance is in [I18N.md](I18N.md). Dark mode requires an explicit product decision. Account export/deletion is implemented in M6a. M6b adds public publisher/contact/hosting/email details, rights and retention, with Mailpit guidance only for development and an outage retry. The notice wraps on mobile. Host maintenance activation and detailed release validation remain M6 operations; see [PRIVACY_RETENTION.md](PRIVACY_RETENTION.md).
