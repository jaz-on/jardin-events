# jardin-events

WordPress plugin: **`event`** CPT for sites using **[jardin-theme](https://github.com/jaz-on/jardin-theme)** — dates, location, links, recap post, slides/video URLs, **`event_role`** taxonomy, REST fields, Query Loop integration, and Gutenberg blocks for archives and singles.

## Requirements

- WordPress **6.4+** (uses `query_loop_block_query_vars`)
- PHP **7.4+**

## Install

1. Place in `wp-content/plugins/jardin-events` and activate.
2. If the events archive 404s, save **Settings → Permalinks** once.

## What it does

- **Archive** at `/evenements/` by default (slug filterable). Query arg `?event_role=speaker` filters the main query; archive sorted by `event_date`.
- **Meta (examples):** `event_date` (required), `event_date_end`, `event_city`, `event_country`, `event_map_url`, `event_link`, `event_ticket_url`, `event_article` (linked post ID), `event_slides_url`, `event_video_url`. Old keys `event_end_date` / `event_linked_post` migrate automatically.
- **Roles taxonomy:** `event_role` — `speaker`, `organizer`, `sponsor`, `attendee` (filterable lists).
- **Blocks:** `jardin-events/event-filter` (chips + counts), `event-inline-date`, `event-inline-location`, `event-archive-meta`, `event-external-link`, `event-single-meta`, `event-status-bar`.
- **Query Loop CSS classes** on the block (post type must be events only): `jardin-events-query--upcoming` (ascending), `jardin-events-query--past` (descending) so lists use **`event_date`**, not post date.
- **REST:** computed `event_roles`, `event_start`, `event_end`, `event_location`; meta exposed as saved. Filter `jardin_events_event_article_post_types` widens linked post types (default `post`).
- **JSON-LD Event:** off by default; enable via filter `jardin_events_enable_jsonld`.

**Hooks / PHP API:** many filters (`jardin_events_slug`, `jardin_events_roles`, `jardin_events_query_loop_query_vars`, …) and helpers `jardin_events_get_upcoming()`, `jardin_events_get_past()`, `jardin_events_get_filters()`, etc. — see plugin source.

**Theme handoff:** copy-ready patterns and notes in [`docs/theme-handoff/`](docs/theme-handoff/README.md).

## Jardin stack

| Repository | Role |
|------------|------|
| [jardin-theme](https://github.com/jaz-on/jardin-theme) | FSE theme |
| **jardin-events** (this repo) | Events CPT + blocks |
| [jardin-scrobbles](https://github.com/jaz-on/jardin-scrobbles) | Last.fm / listens |
| [jardin-toasts](https://github.com/jaz-on/jardin-toasts) | Untappd check-ins |
| [jardin-bookmarks](https://github.com/jaz-on/jardin-bookmarks) | Feedbin → favorites / blogroll |

## Development

```bash
composer install
composer run phpcs
export WP_TESTS_DIR=/path/to/wordpress/tests/phpunit
composer run test
```

## Release Checklist (branch `dev`)

Current state: this plugin does not load `vendor/autoload.php` at runtime. The `pre-push` hook therefore exits quickly and only activates full Composer runtime checks if runtime Composer loading is introduced later.

Before each push used by Git Updater on `dev.jasonrouet.com`, run:

```bash
composer run release:dev
```

Then verify and publish:

1. `rg "myclabs/deep-copy|phpunit|phpstan" vendor/composer/autoload_files.php` returns no match (if the file exists).
2. Commit updated runtime Composer files (`vendor/composer/*` + tracked runtime `vendor/` changes).
3. Push branch `dev`, then update plugin with Git Updater on staging.

Optional but recommended (one-time per clone): install the local `pre-push` hook that runs these checks automatically and blocks invalid pushes.

```bash
composer run hooks:install
```

**Manual smoke:** activation, permalinks, create event + REST, end date validation, empty meta removal, dual Query Loops (upcoming/past), multi-day event still “upcoming” until `event_date_end` passes.

## License

GPL-2.0-or-later
