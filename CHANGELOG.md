# Changelog

Toutes les notes importantes de ce projet sont listées ici. La version distribuée du plugin reste indiquée dans l’en-tête de `jardin-events.php` (voir historique Git pour le détail des tags).

## [Unreleased]

### Added

- Taxonomies `category` et `post_tag` sur le CPT `event`.
- `register_activation_hook` / `register_deactivation_hook` avec `flush_rewrite_rules`.
- Requêtes « à venir » / « passés » alignées sur la spec (prise en charge de `event_end_date` pour les événements multi-jours).
- Méthodes statiques `Jardin_Events_Core::build_upcoming_meta_query()` et `build_past_meta_query()`.
- Filtre `query_loop_block_query_vars` pour les blocs Query marqués `jardin-events-query--upcoming` ou `jardin-events-query--past`.
- Accesseur singleton `jardin_events_core()` ; les helpers `jardin_events_get_*` ne recréent plus une instance à chaque appel.
- Métadonnées d’entête : `Requires at least`, `Requires PHP`.
- Documentation FSE : [`docs/theme-handoff/`](docs/theme-handoff/README.md) (archive + pattern pour le thème).
- Script Composer `phpcs` (dépendances de développement).

### Changed

- Styles : suppression de `opacity` sur `.jardin-events-item-meta` (meilleur contraste par défaut).

### Removed

- Fichiers dupliqués à la racine du plugin : `patterns/events-upcoming.php`, `templates/archive-event.html` (remplacés par le handoff thème).

### Fixed

- Libellé de la métabox (guillemets « En savoir plus »).
- Sauvegarde : champs vides suppriment la meta ; validation date fin ≥ date début avec notice admin ; ignore les révisions.
- Garde `wp_is_post_revision` sur la sauvegarde.
