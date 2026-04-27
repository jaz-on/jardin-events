# Changelog

Toutes les notes importantes de ce projet sont listées ici. La version distribuée du plugin reste indiquée dans l’en-tête de `jardin-events.php` (voir historique Git pour le détail des tags).

## [Unreleased]

### Removed

- Taxonomies WordPress `category` et `post_tag` sur le CPT `event` (classification : meta `event_role` et filtres d’archive `?event_role=`).

### Added

- Fichier `inc/event-meta-helpers.php` : validation des dates, fusion des meta REST, formatage des dates d’affichage avec fuseau du site.
- Sanitize callbacks sur les meta enregistrées ; filtres `jardin_events_register_post_type_args`, `jardin_events_upcoming_query_args`, `jardin_events_past_query_args`, `jardin_events_query_loop_query_vars`.
- Validation REST (`rest_pre_insert_event`, `rest_pre_update_event`) alignée sur la métabox.
- `load_plugin_textdomain`, répertoire `languages/`, JSON-LD optionnel (`jardin_events_enable_jsonld`, désactivé par défaut), `uninstall.php` (nettoyage des transients de notice).
- Squelette PHPUnit (`phpunit.xml.dist`, `tests/`).

### Changed

- Lien « Événements » dans la liste des extensions (à la place du libellé trompeur « Settings »).
- Enregistrement classique : en cas de dates invalides, le lieu et le lien sont tout de même enregistrés ; notice admin mise à jour.
- `jardin_events_is_active()` : vérifie aussi la constante et la classe du plugin.
- `format_event_date` : dates affichées via `DateTimeImmutable` / `wp_timezone()`.

## [0.1.0]

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
