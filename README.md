# Jardin Events

Plugin WordPress pour le CPT **événement** (`event`) sur les sites utilisant le thème [jaz-on/jardin](https://github.com/jaz-on/jardin) : métadonnées (dates, lieu, lien, rôles, article récap, URLs slides/vidéo), REST API, requêtes PHP et prise en charge des **Query Loops** FSE.

## Prérequis

- **WordPress 6.4+** (filtre `query_loop_block_query_vars` sur les blocs Query).
- **PHP 7.4+**

## Installation

1. Placer le dossier du plugin dans `wp-content/plugins/jardin-events/` (recommandé pour correspondre au dépôt Git et à Git Updater).
2. Activer l’extension dans **Réglages → Extensions**.
3. En cas de 404 sur `/evenements/`, enregistrez à nouveau les permaliens (**Réglages → Permaliens** → Enregistrer).

## Métadonnées

| Clé               | Rôle                                      |
|-------------------|-------------------------------------------|
| `event_date`      | Date de début (`Y-m-d`), obligatoire à l’enregistrement |
| `event_date_end`  | Date de fin (optionnelle)                 |
| `event_location`  | Lieu (texte)                              |
| `event_link`      | URL de la page de l’événement             |
| `event_ticket_url`| URL de billetterie (optionnelle)          |
| `event_role`      | Rôles multiples (`speaker`, `organizer`, `sponsor`, `attendee`) — plusieurs lignes de meta |
| `event_article`   | ID d’un contenu lié (récap) ; par défaut type `post` |
| `event_slides_url`| URL des présentations (optionnel)        |
| `event_video_url` | URL vidéo (optionnel)                    |

Lors d’une mise à jour depuis une ancienne version du plugin qui utilisait encore `event_end_date` ou `event_linked_post`, ces clés sont renommées automatiquement en base (`event_date_end`, `event_article`).

Archive publique **`/evenements/`** (slug réécriture filtrable) : ajouter **`?event_role=speaker`** pour filtrer la requête principale. Tri archive par **`event_date`** (décroissant).

Bloc **Event role filters** (`jardin-events/event-filter`) : puces (classes `.feed-filters`, `.ff-btn`) avec comptes, rendu serveur (FSE / éditeur).

Autres blocs dynamiques : **`event-inline-date`**, **`event-inline-location`** (liste « à venir »), **`event-archive-meta`**, **`event-external-link`**, **`event-single-meta`**, **`event-status-bar`** — utilisés dans les gabarits du thème Jardin.

REST : champ calculé **`event_roles`** (tableau de slugs) ; métas **`event_article`**, **`event_ticket_url`**, **`event_slides_url`**, **`event_video_url`** exposées selon l’enregistrement.

Le type autorisé pour `event_article` est filtrable via `jardin_events_event_article_post_types` (défaut: `post`).

## Thème FSE : Query Loop

Pour qu’une liste d’événements soit filtrée et triée sur **`event_date`** (et non sur la date de publication), ajoutez sur le bloc **Query Loop** une classe CSS supplémentaire :

- `jardin-events-query--upcoming` — à venir / en cours, ordre croissant.
- `jardin-events-query--past` — passés, ordre décroissant.

Le type de publication de la requête doit être **uniquement** celui du CPT événement (par défaut `event`). Sans ces classes, le plugin ne modifie pas la requête.

Modèles et pattern prêts à copier : répertoire [`docs/theme-handoff/`](docs/theme-handoff/README.md).

## Hooks (filtres)

| Filtre | Rôle |
|--------|------|
| `jardin_events_post_type` | Slug du CPT enregistré (défaut : `event`) |
| `jardin_events_slug` | Préfixe d’URL / réécriture (défaut : `evenements`) |
| `jardin_events_meta_keys` | Liste canonique des clés meta (`jardin_events_get_meta_key_list()`) |
| `jardin_events_roles` | Liste fermée des slugs de rôles |
| `jardin_events_role_labels` | Libellés par slug |
| `jardin_events_filters` | Tableau `roles`, `labels`, `counts`, `total` (`jardin_events_get_filters()`) |
| `jardin_events_register_post_type_args` | Arguments passés à `register_post_type` |
| `jardin_events_upcoming_query_args` | Arguments `WP_Query` événements à venir (`$limit` en 2e argument) |
| `jardin_events_past_query_args` | Idem pour les événements passés |
| `jardin_events_query_loop_query_vars` | Variables de requête du bloc Query Loop après filtrage (`$query`, `$block`, `$is_upcoming`) |
| `jardin_events_enable_jsonld` | Retourner `true` pour activer le JSON-LD « Event » sur les pages événement |
| `jardin_events_jsonld_data` | Données structurées avant encodage JSON |

## API PHP

```php
jardin_events_is_active();
jardin_events_get_post_type();
jardin_events_get_rewrite_slug();
jardin_events_get_upcoming( 3 );
jardin_events_get_past( 10 );
jardin_events_format_date( $id );
jardin_events_get_filters(); // roles, labels, counts, total
jardin_events_has_role( $id, 'speaker' );
jardin_events_is_upcoming( $id );
jardin_events_days_until( $id );
```

## Qualité du code

Avec [Composer](https://getcomposer.org/) :

```bash
composer install
composer run phpcs
```

## Tests PHPUnit

Un squelette de tests est fourni (`phpunit.xml.dist`, `tests/`). Il faut une copie des tests WordPress et la variable d’environnement `WP_TESTS_DIR` pointant vers le répertoire `tests/phpunit` de cette copie (voir la [documentation des tests automatisés WordPress](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)). Ensuite :

```bash
export WP_TESTS_DIR=/chemin/vers/wordpress/tests/phpunit
composer run test
```

## Tests manuels (checklist)

- [ ] Activation du plugin sans erreur.
- [ ] Permaliens : archive accessible (`/evenements/` ou slug configuré).
- [ ] Création d’un événement avec date de début obligatoire, lieu, lien ; métabox et REST.
- [ ] Date de fin avant la date de début : dates non enregistrées ; lieu / lien / autres champs oui ; notice admin ; REST refusée.
- [ ] Vidage d’un champ meta : meta supprimée en base.
- [ ] Template archive avec deux Query Loops (`--upcoming` / `--past`) : listes cohérentes.
- [ ] Événement multi-jours : encore listé tant que `event_date_end` ≥ aujourd’hui.

## Licence

GPL v2 or later
