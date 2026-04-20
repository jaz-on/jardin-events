# Fichiers pour le thème Jardin (FSE)

Ces fichiers remplacent ou complètent ceux du dépôt **[jaz-on/jardin](https://github.com/jaz-on/jardin)**. Ils ne sont **pas** chargés par le plugin : copiez-les dans le thème (mêmes chemins relatifs) puis validez dans l’éditeur de site.

## Chemins cibles

| Fichier ici | Copier vers (thème) |
|-------------|---------------------|
| `archive-event.html` | `templates/archive-event.html` |
| `events-upcoming.php` | `patterns/events-upcoming.php` |

Si votre thème n’inclut pas automatiquement tous les fichiers de `patterns/`, ajoutez dans `functions.php` du thème :

```php
require_once get_template_directory() . '/patterns/events-upcoming.php';
```

(adaptez si vous chargez déjà les patterns autrement).

## Conventions du plugin

Sur chaque bloc **Query Loop** (`core/query`) qui liste uniquement le CPT `event`, ajoutez une classe CSS supplémentaire :

- `jardin-events-query--upcoming` — prochains / en cours (tri `event_date` ASC, filtre meta géré par le plugin)
- `jardin-events-query--past` — événements passés (tri `event_date` DESC, filtre meta géré par le plugin)

Sans ces classes, le plugin **ne modifie pas** la requête (comportement WordPress par défaut).

Les blocs **Post Meta** utilisent les clés enregistrées par le plugin : `event_date`, `event_location`, `event_link` (valeur brute ; le thème peut formater via CSS ou filtres).

## Template parts

`archive-event.html` suppose des **template parts** `header` et `footer`. Ajustez les slugs ou retirez les blocs `template-part` si votre structure diffère.
