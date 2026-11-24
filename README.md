# EXU · Système de vote en ligne

EXU est un système de vote en ligne libre et vérifiable, destiné aux organisations qui décident ensemble : consultations, approbation de documents, élections et désignation de groupes mandatés. **Tous les scrutins sont secrets.**

Projet porté par l'association [Andryale](https://andryale.org/). Site : [exu.fr](https://exu.fr/).

## État du projet

**Phase de cadrage (P0).** Le dépôt contient la page de garde publiée sur exu.fr, l'outil de diagnostic de l'hébergement et le dossier de cadrage. Le logiciel de vote lui-même n'est pas encore écrit. Aucun scrutin réel ne sera ouvert avant une revue de sécurité et un scrutin blanc complet.

Version actuelle : **0.4.1** (préversion). Les versions sont numérotées 0.x.1 jusqu'à la version 1.

## Principes

- **Secret du vote** : seule règle non négociable. Le dépouillement exige des détenteurs de clés distincts de l'administration du site.
- **Code ouvert** : licence AGPL-3.0 ou ultérieure.
- **Sobriété** : le code doit fonctionner sur un hébergement mutualisé courant (PHP 8.5, compatible 8.6 ; MySQL 8.4 ; cron), sans service permanent ni infrastructure dédiée, pour que chacun puisse l'installer ou le dériver.
- **Administration protégée** : double authentification obligatoire pour organiser ou administrer un vote.

## Contenu du dépôt

| Dossier | Contenu |
|---|---|
| `www/` | Page de garde statique de exu.fr : page d'accueil, page 404, icônes, `.htaccess`, `robots.txt`, `sitemap.xml`, `security.txt` |
| `outils/diagnostic/` | Script PHP qui vérifie qu'un hébergement convient à EXU (voir son `README.md`) |
| `docs/` | Dossier de cadrage technique et feuille de route |

## Installer la page de garde

1. Copier le contenu de `www/` à la racine web du domaine, fichiers cachés compris (`.htaccess`, `.well-known/`).
2. Adapter le domaine `exu.fr` dans `.htaccess`, `robots.txt`, `sitemap.xml`, `site.webmanifest`, `.well-known/security.txt` et les balises `canonical` et `og:` de `index.html`.
3. Derrière un CDN, purger son cache après chaque mise à jour.

La page ne charge aucune ressource externe, n'exécute aucun script et ne dépose aucun cookie.

## Sécurité

Pour signaler une faille, voir [SECURITY.md](SECURITY.md).

## Licence

Code : [GNU Affero General Public License](https://www.gnu.org/licenses/agpl-3.0.html), version 3 ou toute version ultérieure (`AGPL-3.0-or-later`).
