# Dashboard

A personal, customizable widget grid for FreeScout staff — add/remove widgets, drag to reorder, pick a size per widget (small/medium/large), macOS/iOS-widget-screen style.

Lives at its own page/nav entry (`/dashboard`, route name `dashboard.index`), deliberately separate from core FreeScout's own home page (`/`, route name `dashboard`). Core's own dashboard (mailbox cards + Calendar's "Upcoming Events" card via the `dashboard.before` filter) is untouched — the two coexist on purpose.

## Adding a new widget

1. Create `Widgets/YourWidget.php` implementing `Widgets/Widget.php`'s two static methods:
   - `isAvailable(User $user): bool` — gate on the source module actually being installed (`class_exists(...)`) and, if relevant, on `$user->isAdmin()`.
   - `render(User $user, string $size): string` — return the widget's own inner HTML only (no outer card — the view wraps it in `.dash-widget`/`.dash-widget-{size}`). Scale content to `small`/`medium`/`large`.
2. Add one entry to `Support/WidgetRegistry.php`'s `$widgets` array: `label`, `icon` (a Bootstrap glyphicon class), `sizes` (which of small/medium/large it supports), `default_size`, `class`, `admin_only`.

Nothing else needs to change — `DashboardController` and the view iterate the registry generically.

## Grid mechanics

- CSS Grid with `grid-auto-flow: dense` (`Public/css/dashboard.css`) packs differently-sized cards automatically from DOM source order alone — no custom placement/resize JS needed.
- Reordering is therefore a plain DOM-sibling-order change, done with `html5sortable.js` (core-shipped, `asset('js/html5sortable.js')`) — the same library Invoicing's Kanban board and KnowledgeBase already use elsewhere in this codebase.
- One `dashboard_widgets` row per (user, widget instance): `widget_key`, `size`, `position`. Reordering rewrites every row's `position` in one request (KnowledgeBase's whole-array pattern), rather than reconciling incremental deltas.
- Resizing (`PUT .../{id}/size`) reloads the page rather than only swapping the CSS class — a widget's rendered content (e.g. how many list rows it shows) was only ever rendered server-side at its size at page-load time, so a client-side-only class swap would leave stale content in a newly-resized card.

## Visual system

Each widget key gets a fixed accent color (`.dash-widget[data-widget-key="..."]` in `Public/css/dashboard.css`), drawn from the same 8-hex palette already used project-wide for tags/categories — a new widget that doesn't get an explicit entry there just falls back to the default `--dash-accent`. List-type widgets (Tasks, Projects, My Conversations, Notes) always render their actual list items, even at `small` — a bare count on its own was tried first and dropped as not useful.

## Scope cuts (v1, deliberate)

- One instance of a given widget per user (`unique(user_id, widget_key)`) — none of the twelve widgets need per-instance configuration, so this is a simplification, not a technical ceiling.
- The default board seeded for a brand-new user is code-defined (`WidgetRegistry::$defaultKeys`), not admin-configurable — no company-wide default-layout feature exists yet.
- A widget whose source module gets deactivated after being added just stops rendering (the row is left in place, and reappears automatically if the module comes back) — it is never silently deleted.

## Widgets and their data sources

| Widget key | Source |
|---|---|
| `upcoming_events` | `Modules\Calendar\Entities\CalendarEvent` (own calendars only) |
| `timetracking` | `Modules\Invoicing\Entities\TimeEntry` + the `invoicing.time_tracking.target_hours` Eventy filter |
| `my_conversations` | Core `App\Conversation` (no module owns this) |
| `projects` / `tasks` | `Modules\Invoicing\Entities\{Project,Task}` |
| `statistics` | `TimeEntry` + `InternalHours` + `PresenceTime` |
| `week_report_status` | `Modules\Invoicing\Entities\TimeEntryApproval` — its "submit" button posts to Invoicing's own existing `invoicing.time_entries.overview_approval.store` route, no new backend endpoint |
| `holiday_balance` | `Modules\Hr\Support\HolidayBalance::balanceForYear()` (Hr module only) |
| `open_invoices` | `TimeEntry`/`Invoice` — **admin-only** (company-wide financials) |
| `notes` | `Modules\Notes\Entities\Note::visibleTo()` (Notes module only) |
| `quick_actions` | No query — curated links into each feature's real create route |
| `bexio_sync_status` | `Modules\Bexio\Support\BexioAuth` — **admin-only** |
| `team_billable_hours` | `App\User` + `TimeEntry` — this month's billable hours per active staff member, ranked — **admin-only** (comparative per-person data) |
| `my_weekly_billable` | `TimeEntry` — the caller's own billable hours per week, last 4/8/12 weeks depending on size |

Every cross-module widget is soft-optional: `module.json`'s `"requires": []` is intentionally empty, and each such widget's `isAvailable()` checks the source module's class exists before offering itself in the "add widget" picker or rendering on an existing board.
