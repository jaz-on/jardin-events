# Jardin Events

Plugin WordPress pour le CPT **événement** (`event`) sur les sites utilisant le thème [jaz-on/jardin](https://github.com/jaz-on/jardin) : métadonnées (dates, lieu, lien), REST API, requêtes PHP et prise en charge des **Query Loops** FSE.

## Prérequis

- **WordPress 6.4+** (filtre `query_loop_block_query_vars` sur les blocs Query).
- **PHP 7.4+**

## Installation

1. Placer le dossier du plugin dans `wp-content/plugins/jardin-event/` (ou le nom de dossier de votre choix).
2. Activer l’extension dans **Réglages → Extensions**.
3. En cas de 404 sur `/events/`, enregistrez à nouveau les permaliens (**Réglages → Permaliens** → Enregistrer).

## Métadonnées

| Clé               | Rôle                                      |
|-------------------|-------------------------------------------|
| `event_date`      | Date de début (`Y-m-d`)                   |
| `event_end_date`  | Date de fin (optionnel)                   |
| `event_location`  | Lieu (texte)                              |
| `event_link`      | URL « En savoir plus »                    |

Le CPT supporte aussi les taxonomies **catégories** et **étiquettes** (articles).

## Thème FSE : Query Loop

Pour qu’une liste d’événements soit filtrée et triée sur **`event_date`** (et non sur la date de publication), ajoutez sur le bloc **Query Loop** une classe CSS supplémentaire :

- `jardin-events-query--upcoming` — à venir / en cours, ordre croissant.
- `jardin-events-query--past` — passés, ordre décroissant.

Le type de publication de la requête doit être **uniquement** `event`. Sans ces classes, le plugin ne modifie pas la requête.

Modèles et pattern prêts à copier : répertoire [`docs/theme-handoff/`](docs/theme-handoff/README.md).

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

## Tests manuels (checklist)

- [ ] Activation du plugin sans erreur.
- [ ] Permaliens : archive accessible (`/events/` ou slug configuré).
- [ ] Création d’un événement avec dates, lieu, lien ; affichage des meta dans l’éditeur de blocs.
- [ ] Date de fin strictement avant la date de début : refus d’enregistrement et message d’admin.
- [ ] Vidage d’un champ meta : meta supprimée en base.
- [ ] Template archive avec deux Query Loops (`--upcoming` / `--past`) : listes cohérentes.
- [ ] Événement multi-jours : encore listé tant que `event_end_date` ≥ aujourd’hui.
- [ ] Catégorie / étiquette assignée à un événement.

## Licence

GPL v2 or later
