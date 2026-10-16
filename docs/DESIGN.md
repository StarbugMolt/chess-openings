# Visual design

The interface borrows from a printed opening book studied with a highlighter: navy ink on cool paper, diagram boards with hatched dark squares, and one marker colour that always means "now". This document records the tokens and rules so later screens stay consistent. Interaction behaviour remains governed by [UX.md](UX.md).

## Tokens

All tokens live in `frontend/src/style.css` as CSS custom properties.

| Token | Value | Use |
| --- | --- | --- |
| `--paper` | `#f1f3f6` | Page background |
| `--sheet` | `#ffffff` | Panels, forms, result detail, light board squares (`#f9fafc`) |
| `--ink` | `#182544` | Text, headings, primary buttons, board frame and hatching, focus ring |
| `--pencil` | `#535f78` | Secondary text (at least 4.5:1 on paper and sheet) |
| `--rule` / `--rule-strong` | `#d3d9e3` / `#a3adc0` | Dividers / control borders |
| `--marker` / `--marker-soft` | `#ffde4d` / `#fff0a6` | Highlighter: last move, current move, hints, active navigation, Hint button |
| `--danger` | `#a12b2b` | Account deletion only |

The marker colour is reserved for "what is happening now". It is never used for text and never means right or wrong. Feedback about moves stays neutral: an ink rule beside plain text (`.notice`), consistent with the neutral-feedback rule in [UX.md](UX.md).

## Type

Archivo is self-hosted from `frontend/src/assets/fonts` (latin and latin-ext variable WOFF2, OFL-1.1, appended to the generated dependency notices). No font or other asset is loaded from a third party, so the existing `font-src 'self'` policy applies.

- Headings use the width axis: `font-stretch: 125%` (`--wide`) at weight 800–850 for page titles, `112%` (`--semi-wide`) for card and panel titles.
- Body text is Archivo at normal width, 16–17 px with 1.55–1.6 line height and lines held near 70 characters.
- Chess notation uses tabular figures at weight 650 rather than a monospace face.
- Labels are sentence case. There are no all-caps eyebrows; a label appears only when it carries information (for example "You play Black" or "Variation training").

## Board

`ChessBoard.vue` draws a book diagram: plain light squares, dark squares hatched in ink at 45°, and a 3 px ink frame. Highlights are translucent marker washes, so the hatching stays visible underneath. Move destinations are ink dots with a white halo, which keeps them legible on hatched squares. Hints add a pulsing ink ring (a dashed square on the destination), and the pulse stops under `prefers-reduced-motion`.

## Layout

- Pages are left aligned inside a 1240 px frame with 40 px gutters (24 px under 900 px, 18 px on phones).
- Trainers size the board from the viewport height (`--board`) so the board and lesson panel fit on a laptop screen without scrolling. Under 900 px the board comes first, with the turn and Hint in a toolbar directly above it.
- The home page uses a two-line banner headline, then the copy beside a board that plays the Italian Game once next to a scoresheet. Reduced motion shows the final position without playing through it.
- Progress bars are ink on a rule-grey track. Where items are countable (your moves in a line, current lines in a course), `--segments` splits the bar into one block per item.

## Controls

- Buttons are at least 44 px high. Primary actions are ink (`.primary-link`), secondary actions are outlined (`.secondary-button` or the default `button`), the Hint is a marker button, and destructive actions use `--danger`.
- The visible keyboard focus is a 3 px ink outline with a 3 px offset.
- Segmented choices (the Learn side filter) keep native radio inputs stretched transparently over each segment, so assistive technology and pointer input both reach the real control.

## Charts (admin dashboard)

The admin dashboard is the only screen with charts. Its one loud element is an ink band holding the practice totals and a 30-day column chart; the latest column (today, so far) is the marker colour, which keeps the "marker means now" rule. Everything else sits on white panels on paper.

- Columns are HTML list items, at most 24 px wide with a 4 px rounded top, on solid 1 px gridlines at round values. A zero is a 2 px baseline stub.
- Two-series charts use an ink ramp, `#7f98d0` then `--ink`, checked as an ordinal palette with the dataviz validator. They always have a legend, and the figures are also available as a table.
- Values and labels use text colours, never series colours. Part-to-whole bars (verified accounts, White/Black) separate segments with a 2 px gap. The White/Black legend reuses `.side-token`.
- The tooltip is the list item's own text, clipped until hover or keyboard focus, so screen readers and the table never depend on it.
