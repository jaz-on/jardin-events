# Changelog

Toutes les notes importantes de ce projet sont listées ici. La version distribuée du plugin reste indiquée dans l’en-tête de `jardin-events.php` (voir historique Git pour le détail des tags).

## [Unreleased]

### Changed

- REST : la création d’un événement requiert `meta.event_date` non vide ; sinon erreur `jardin_events_missing_start`.
- REST : suppression des champs top-level redondants `event_city`, `event_country`, `event_map_url` (valeurs via `meta.*` ; `event_location` calculé inchangé).
- Tests PHPUnit : rôles couverts via la taxonomie `event_role` (plus de post_meta `event_role`).
- PHPCS : variables locales renommées dans certains `blocks/*/render.php` ; docblocks complétés (`Jardin_Events_Admin`, callbacks block bindings).
- `uninstall.php` : plus de nettoyage de transients inutilisés (aucun code ne les créait).

### Fixed

- Commentaire / flux REST : la validation à la création ne dépend plus d’une métabox classique absente du plugin.

## [0.1.0]

### Added

- Taxonomies `category` et `post_tag` sur le CPT `event` (retirées ensuite au profit de `event_role` et des filtres d’archive).
- `register_activation_hook` / `register_deactivation_hook` avec `flush_rewrite_rules`.
- Requêtes « à venir » / « passés » alignées sur la spec (prise en charge de la date de fin pour les événements multi-jours).
- Méthodes statiques `Jardin_Events_Core::build_upcoming_meta_query()` et `build_past_meta_query()`.
- Filtre `query_loop_block_query_vars` pour les blocs Query marqués `jardin-events-query--upcoming` ou `jardin-events-query--past`.
- Accesseur singleton `jardin_events_core()` ; helpers `jardin_events_get_*`.
- Métadonnées d’en-tête : `Requires at least`, `Requires PHP`.
- Documentation FSE : [`docs/theme-handoff/`](docs/theme-handoff/README.md).
- Script Composer `phpcs` (dépendances de développement).
- Fichier `inc/event-meta-helpers.php` : validation des dates, fusion des meta REST.
- Sanitize callbacks sur les meta ; filtres `jardin_events_register_post_type_args`, `jardin_events_upcoming_query_args`, `jardin_events_past_query_args`, `jardin_events_query_loop_query_vars`.
- Validation REST (`rest_pre_insert_event`, `rest_pre_update_event`).
- `load_plugin_textdomain`, répertoire `languages/`, JSON-LD optionnel (`jardin_events_enable_jsonld`), `uninstall.php`.
- Squelette PHPUnit (`phpunit.xml.dist`, `tests/`).

### Changed

- Styles : suppression de `opacity` sur `.jardin-events-item-meta`.
- Lien « Événements » dans la liste des extensions.
- Enregistrement classique : en cas de dates invalides, autres champs pouvant être enregistrés selon le cas ; notices admin.
- `jardin_events_is_active()` : vérifie constante et classe du plugin.
- `format_event_date` : dates via fuseau du site.

### Removed

- Fichiers dupliqués à la racine du plugin : `patterns/events-upcoming.php`, `templates/archive-event.html` (remplacés par le handoff thème).

### Fixed

- Libellé de la métabox (guillemets « En savoir plus »).
- Sauvegarde : champs vides suppriment la meta ; validation fin ≥ début ; révisions ignorées.
