# Jardin Events

Plugin WordPress pour le CPT **événement** (`event`) sur les sites utilisant le thème [jaz-on/jardin](https://github.com/jaz-on/jardin) : métadonnées (dates, lieu, lien), REST API, requêtes PHP et prise en charge des **Query Loops** FSE.

## Prérequis

- **WordPress 6.4+** (filtre `query_loop_block_query_vars` sur les blocs Query).
- **PHP 7.4+**

## Installation

1. Placer le dossier du plugin dans `wp-content/plugins/jardin-events/` (recommandé pour correspondre au dépôt Git et à Git Updater).
2. Activer l’extension dans **Réglages → Extensions**.
3. En cas de 404 sur `/events/`, enregistrez à nouveau les permaliens (**Réglages → Permaliens** → Enregistrer).

## Métadonnées

| Clé               | Rôle                                      |
|-------------------|-------------------------------------------|
| `event_date`      | Date de début (`Y-m-d`)                   |
| `event_end_date`  | Date de fin (optionnel)                   |
| `event_location`  | Lieu (texte)                              |
| `event_link`      | URL « En savoir plus »                    |
| `event_role`      | Rôles multiples (`speaker`, `organizer`, `sponsor`, `attendee`) — cases à cocher dans l’éditeur classique |

Archive publique **`/events/`** : ajouter **`?event_role=speaker`** (ou un autre rôle) pour filtrer la requête principale.

Bloc **Event role filters** (`jardin-events/event-filter`) : puces avec comptes, rendu serveur (FSE / éditeur).

## Thème FSE : Query Loop

Pour qu’une liste d’événements soit filtrée et triée sur **`event_date`** (et non sur la date de publication), ajoutez sur le bloc **Query Loop** une classe CSS supplémentaire :

- `jardin-events-query--upcoming` — à venir / en cours, ordre croissant.
- `jardin-events-query--past` — passés, ordre décroissant.

Le type de publication de la requête doit être **uniquement** `event`. Sans ces classes, le plugin ne modifie pas la requête.

Modèles et pattern prêts à copier : répertoire [`docs/theme-handoff/`](docs/theme-handoff/README.md).

## Hooks (filtres)

| Filtre | Rôle |
|--------|------|
| `jardin_events_register_post_type_args` | Arguments passés à `register_post_type( 'event', … )` |
| `jardin_events_upcoming_query_args` | Tableau d’arguments `WP_Query` pour les événements à venir (`$limit` en 2e argument) |
| `jardin_events_past_query_args` | Idem pour les événements passés (`$limit` en 2e argument) |
| `jardin_events_query_loop_query_vars` | Variables de requête du bloc Query Loop après filtrage (`$query`, `$block`, `$is_upcoming`) |
| `jardin_events_enable_jsonld` | Retourner `true` pour activer le script JSON-LD « Event » sur les pages événement (désactivé par défaut ; utile si aucun plugin SEO ne fournit déjà le schéma) |
| `jardin_events_jsonld_data` | Filtrer le tableau de données structurées (`$data`, `$post_id`) avant encodage JSON |

## API PHP

```php
jardin_events_is_active();           // bool
jardin_events_get_upcoming( 3 );     // WP_Query
jardin_events_get_past( 10 );       // WP_Query
jardin_events_format_date( $id );   // chaîne localisée
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
- [ ] Permaliens : archive accessible (`/events/` ou slug configuré).
- [ ] Création d’un événement avec dates, lieu, lien ; affichage des meta dans l’éditeur de blocs.
- [ ] Date de fin strictement avant la date de début : les dates ne sont pas enregistrées (classique) ; lieu et lien enregistrés ; message d’admin ; via l’API REST la requête est refusée.
- [ ] Vidage d’un champ meta : meta supprimée en base.
- [ ] Template archive avec deux Query Loops (`--upcoming` / `--past`) : listes cohérentes.
- [ ] Événement multi-jours : encore listé tant que `event_end_date` ≥ aujourd’hui.

## Licence

GPL v2 or later
