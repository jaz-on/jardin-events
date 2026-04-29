# Changelog

Toutes les notes importantes de ce projet sont listées ici. La version distribuée du plugin reste indiquée dans l’en-tête de `jardin-events.php` (voir historique Git pour le détail des tags).

## [Unreleased]

### Changed

- Nom produit / dépôt canonique **jardin-events** (libellés d’en-tête, README, docs handoff, PHPCS, commentaires) ; identifiants PHP (`Jardin_Events_*`, `jardin_events_*`, `JARDIN_EVENTS_*`) inchangés (API publique).

### Added

- Migration base (`jardin_events_db_version`, option `2`) : renommage des lignes de meta `event_end_date` → `event_date_end`, `event_linked_post` → `event_article`.
- Métabox : URLs `event_slides_url`, `event_video_url` ; recherche AJAX d’articles pour `event_article` (`assets/js/admin-event-article.js`).
- Métabox : distinction `Page de l’événement` (`event_link`) et `Billetterie` (`event_ticket_url`), avec rendu front séparé.
- Filtres `jardin_events_post_type`, `jardin_events_slug` (défaut `evenements`), `jardin_events_meta_keys`, `jardin_events_filters` ; helpers associés (`jardin_events_get_post_type()`, `jardin_events_get_rewrite_slug()`, `jardin_events_get_filters()`, `jardin_events_get_event_article_id()`, `jardin_events_get_event_date_end()`).
- Validation « date de début obligatoire » (métabox classique + REST).
- Analyse PHPCS sur `blocks/`.

### Changed

- Réécriture d’URL du CPT : slug par défaut **`evenements`** (chemins `/evenements/`, `/evenements/{slug-de-l-evenement}/`).
- Renommage canonique des meta fin de date et article récap pour le thème Jardin.

### Removed

- Taxonomies WordPress `category` et `post_tag` sur le CPT `event` (rôles via meta `event_role` et archive filtrée avec `?event_role=`).

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
