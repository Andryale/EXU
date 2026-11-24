# EXU · Système de vote en ligne
## Dossier de cadrage technique, démarche et feuille de route

**Version :** 0.4 • **Date :** 24 novembre 2026 • **Remplace :** version 0.3 du 24 novembre 2026  
**Porteur :** association Andryale • **Domaine :** exu.fr • **Dépôt :** github.com/Andryale/EXU (publication à venir)  
**Statut :** cadrage en cours (phase P0). Aucun logiciel, audit, hébergement ou protocole n’est réputé validé par ce dossier. Les décisions marquées « arrêtées » ont été prises par le porteur ; les autres restent des propositions.

## 0. Ce qui change

### Depuis la version 0.3

- Principe de sobriété arrêté : EXU est conçu pour fonctionner sur un hébergement mutualisé courant, afin que chacun puisse l’installer, le reprendre ou le dériver sans infrastructure lourde (section 1).
- L’hébergement OVH mutualisé est présenté comme l’environnement de référence, et non plus comme un ensemble de limites (section 4).

### Depuis la version 0.2

- Résultats du diagnostic de l’hébergement (livraison 0.4.1, rapport du 24 novembre 2026, 16 h 22 UTC) : section 4.
- Nouvelles exigences tirées du diagnostic : mode SQL imposé à chaque connexion, limite de 30 connexions MySQL simultanées, vérification complète des urnes en ligne de commande, sessions hors de `/tmp`, taille du groupe cryptographique à trancher en P1 (sections 4, 5, 12 et 15).
- Page de garde en 0.4.1 : redirection `/index.html` corrigée, licence alignée sur AGPL-3.0-or-later.

### Depuis la version 0.1

- Nom définitif, porteur, isolement du projet, public visé et hébergement de test arrêtés (section 1).
- **Tous les scrutins sont secrets.** Le mode nominatif est retiré du périmètre ; la feuille de route est réordonnée en conséquence (sections 8 et 11).
- Procurations précisées : procuration standard et procuration de retard (section 9).
- Double authentification obligatoire pour administrer un vote (section 3).
- Données d’identité : nom et prénom ou pseudonyme, et code unique de votant en clair ; coordonnées chiffrées en base (section 14).
- Hébergement de test inventorié à partir du phpinfo du 24 novembre 2026 ; conséquence majeure pour le choix cryptographique (section 4).
- Versions numérotées 0.x.1 jusqu’à la version 1 (section 13).

## 1. Objet, périmètre et décisions arrêtées

### Objet

Concevoir un système de vote en ligne libre et auto-hébergeable : comptes permanents, espaces autonomes appelés « chambres », consultations, approbation de documents, élections et constitution de groupes mandatés. L’interface reste simple pour les décisions courantes et permet une configuration plus poussée lorsque les règles l’exigent.

La cible exclut toute prétention à couvrir les élections politiques ou les scrutins dont la loi impose un dispositif agréé. La validité d’une décision dépend aussi du règlement de l’organisation, des convocations et des conditions de participation ; le logiciel ne la garantit pas à lui seul.

### Décisions arrêtées le 24 novembre 2026

| Sujet | Décision |
|---|---|
| Nom | **EXU** (pour *external unit* : chacun garde la main sur son vote), sur le domaine court **exu.fr**. Sous-titre : « Système de vote en ligne » |
| Ton | Noms et formulations sérieux. Pas de vocabulaire « associatif », ni de « petit vote » ou équivalent |
| Porteur | L’association **Andryale** porte le projet. Ses mentions légales couvrent déjà exu.fr [S4] |
| Articulation | Projet **isolé sur exu.fr**, sans lien technique avec PLACE ni avec un outil de sondage |
| Public | Service **ouvert sur inscription** |
| Pilotes | Andryale, puis la fédération E-TEAM |
| Premiers usages | Approbation de compte rendu, élection, consultation |
| Confidentialité | **Secret pour tous les scrutins.** C’est la seule règle non négociable ; la plupart des autres règles sont facultatives et réglables par chambre ou par scrutin |
| Volume visé | Plusieurs milliers de votants |
| Sobriété | **Le code doit fonctionner sur un hébergement mutualisé courant** (PHP, MySQL, cron), sans service permanent ni infrastructure dédiée, pour que toute organisation puisse l’installer ou le dériver. Une offre plus puissante reste possible, jamais nécessaire |
| Environnement de référence | OVH mutualisé Performance avec CDN Basic : c’est sur lui que le code est développé, testé et mesuré |
| Langage et base | PHP 8.5, compatible 8.6 ; MySQL 8.4. Framework PHP reconnu autorisé |
| Courriels | Envoi par Proton (SMTP avec jeton), domaine exu.fr |
| Licence | AGPL-3.0-or-later |
| Code source | Organisation GitHub d’Andryale, dépôt **EXU** |
| Double authentification | Obligatoire pour toute personne qui organise ou administre un vote |
| Détenteurs de clés | Nombre non limité ; seuil de déchiffrement réglable par scrutin |
| Procurations | Procuration standard et procuration de retard (section 9) |

### Principes conservés de la version 0.1

- PHP, HTML et CSS pour l’application ; JavaScript pour l’ergonomie et le chiffrement avant transmission. Toute requête reste contrôlée côté serveur.
- Comptes personnels durables, appartenance à plusieurs chambres, votes accessibles depuis le tableau de bord.
- Courriels non nécessaires à chaque vote ; notifications facultatives ; récupération du compte traitée à part.
- Séparation entre bulletin, méthode de dépouillement, émargement et urne.
- Modèles réutilisables, lancement rapide, gestion des jurys au sens de groupes mandatés.

## 2. Expérience utilisateur

### Tableau de bord

Afficher les votes ouverts, les échéances, les procurations à accepter ou à exercer, les suppléances de retard actives, les chambres rejointes et les résultats accessibles. Les tâches de scrutateur ou de détenteur de clé sont présentées à part. Les appartenances d’un compte ne sont jamais exposées aux autres chambres.

### Parcours de vote

Lire la question et les pièces figées ; consulter les règles utiles ; remplir le bulletin ; relire son choix ; confirmer ; obtenir un accusé de réception. Distinguer clairement un bulletin reçu, un bulletin remplacé et un résultat dépouillé.

L’accusé de réception ne doit pas constituer une preuve transférable du choix. Sa forme dépend du protocole retenu. Ne pas promettre une résistance absolue à la contrainte exercée au domicile ni à un terminal compromis.

### Création rapide

Choisir une chambre et un modèle, saisir la question et les options, fixer l’échéance, vérifier le récapitulatif et ouvrir. Membres, droits et détenteurs de clés sont préparés en amont. L’objectif de quelques minutes porte sur la préparation ; il ne garantit pas un dépouillement immédiat, puisque celui-ci exige l’intervention des détenteurs de clés.

### Présentation

Interface sobre, responsive, contrastée, utilisable au clavier et avec un lecteur d’écran ; aucun état transmis par la seule couleur. Libellés simples, erreurs explicites, confirmation des opérations irréversibles, paramètres avancés repliés. Prévenir de l’expiration de session sans conserver silencieusement un choix en clair sur le serveur.

La page de garde publiée (version 0.2.1) fixe la charte : logo EXU, bleu #4E7BB8 et marine #1B3557, aucune ressource externe, aucun cookie.

## 3. Organisation fonctionnelle et rôles

| Rôle | Responsabilités proposées | Limites | Double authentification |
|---|---|---|---|
| Administrateur technique | Exploitation, déploiement, sauvegardes | Ne détient pas les clés des urnes | Obligatoire |
| Administrateur de chambre | Admissions, membres, modèles, droits | Aucun accès automatique aux autres chambres | Obligatoire |
| Organisateur | Préparation et suivi d’un scrutin | Ne modifie pas les règles après ouverture | Obligatoire |
| Participant | Vote et consultation des informations autorisées | Un droit de vote par inscription, sauf pondération explicite | Facultative (proposée) |
| Mandataire | Exercice d’une procuration acceptée et active | Pas d’accès au compte du mandant | Facultative (proposée) |
| Suppléant de retard | Vote pour le mandant à partir du seuil prévu, s’il n’a pas voté | Ne voit pas le choix du mandant ; peut être remplacé par le vote personnel | Facultative (proposée) |
| Scrutateur | Surveillance, contrôle, constat des incidents | Pas d’accès aux choix | Obligatoire (proposée) |
| Détenteur de clé | Participation au déchiffrement autorisé | Rôle cryptographique distinct de l’administration | Obligatoire (proposée) |
| Auditeur | Vérification des preuves et procédures | Accès limité au nécessaire | Obligatoire (proposée) |

L’inscription sur exu.fr, l’admission dans une chambre et l’inscription sur une liste électorale sont trois opérations distinctes. Le service étant ouvert sur inscription, un compte ne prouve pas l’unicité d’une personne physique : chaque chambre organise l’admission et traite les doublons selon l’enjeu de ses scrutins.

## 4. Architecture technique

### Application principale

Monolithe modulaire PHP séparant identité, chambres, scrutins, procurations, désignations, résultats, audit et notifications. Un framework PHP reconnu est autorisé ; le choix sera documenté après vérification de sa compatibilité avec PHP 8.5 et 8.6 et de sa politique de maintenance. À défaut, réutiliser des composants maintenus pour les sessions, le routage, les migrations et les protections web.

Seul `www/` est accessible en HTTP. Configuration, clés de chiffrement des données, journaux et sauvegardes restent dans un dossier privé au-dessus de `www/`. Les tâches différées passent par cron, sans que la clôture ou l’activation d’une procuration en dépende : les échéances sont aussi vérifiées à chaque opération.

### Environnement de référence (diagnostic 0.4.1 du 24 novembre 2026)

Le rapport complet est à conserver dans le dépôt. Bilan : 98 contrôles réussis, 10 points d’attention, aucun échec. L’environnement réunit tout ce dont EXU a besoin ; les points ci-dessous fixent des réglages de l’application, pas des obstacles.

| Élément | Constat | Conséquence |
|---|---|---|
| PHP | 8.5.13 en FPM, OPcache actif (JIT désactivé), 512 Mo, 165 s par requête | Suffisant pour le site ; les traitements longs passent en ligne de commande |
| PHP en ligne de commande | 7.3 à 8.6 disponibles ; temps CPU et mémoire non limités | Tâches cron et vérifications d’urne en 8.5 ; tests de compatibilité en 8.6 par SSH |
| `.ovhconfig` | `environment=production`, `http.firewall=security`, `container.image=stable64` | Inchangé |
| Pare-feu applicatif | Bulletins simulés jusqu’à 1 Mio, JSON, textes avec mots SQL : tous transmis intacts. Code `<script>` transmis aussi | Pas de contrainte sur le format des bulletins ; encodage systématique de toutes les sorties |
| MySQL | Percona Server 8.4.11, InnoDB, utf8mb4 / utf8mb4_0900_ai_ci, latence 0,5 ms, 1 000 insertions en 195 ms | Conforme |
| Mode SQL | `NO_ENGINE_SUBSTITUTION` seul (mode strict inactif) | Mode imposé à chaque connexion (section 5) |
| Connexions MySQL | 151 pour le serveur, 30 pour l’utilisateur | Transactions courtes, pas de connexion persistante : bonnes pratiques valables sur tout hébergement |
| Verrous | Unicité (1062) et `CHECK` (3819) appliqués ; verrou de ligne effectif entre deux connexions (`NOWAIT`, 3572) ; `SKIP LOCKED` fonctionnel | Émargement atomique et files de tâches réalisables en base |
| Attente de verrou | 50 s par défaut | Réduite par session ou `NOWAIT` sur l’émargement, avec message explicite |
| Fuseau MySQL | `SYSTEM` (CET) ; `SET time_zone = '+00:00'` vérifié | UTC imposé à chaque connexion |
| Horloges | Écart nul entre PHP, MySQL, le frontal OVH et GitHub | L’heure du serveur peut faire autorité pour la clôture |
| GMP | 6.1.2 ; 3 149 exponentiations par seconde (module 2048 bits, exposant 256 bits), 1 426 (3072 bits), 418 (2048 bits, exposant plein) | ElGamal en PHP réaliste (voir plus bas) |
| libsodium | 1.0.17, aucune fonction ristretto255 | Piste écartée ; libsodium sert au chiffrement des données, aux signatures et à l’index aveugle |
| Argon2id | 173 ms par calcul avec les réglages par défaut | Retenu ; réglage final en P2 |
| OpenSSL | 1.1.1n | Ne porte pas la cryptographie du vote |
| HTTPS et adresse IP | HTTPS visible par PHP ; `REMOTE_ADDR` contient l’adresse réelle du visiteur malgré le CDN | Limitation des essais fondée sur `REMOTE_ADDR`, jamais sur `X-Forwarded-For` |
| CDN | En-têtes de sécurité transmis ; `no-store` respecté sur les pages dynamiques ; accueil gardé 10 minutes | Purge du CDN à chaque mise à jour |
| Courriels | SMTP Proton joignable sur 587 et 465 | Conforme |
| Sessions | Fichiers dans `/tmp` ; cookies sans `Secure`, `HttpOnly` ni `SameSite` par défaut | Sessions dans un dossier privé ou en base ; cookies réglés par l’application |
| Réglages PHP | Journal d’erreurs inactif, `expose_php` actif, `zend.assertions=1`, `.user.ini` pris en compte | `.user.ini` en P2 : journal hors de `www/`, cookies, `zend.assertions=0`. `expose_php` reste actif (réglage serveur) ; l’en-tête est retiré par le `.htaccess` |
| Outils | git 2.20, `exec` et `proc_open` disponibles ; Composer, OCaml et belenios-tool absents | `composer.phar` installable dans le dossier privé ; binaire de vérification externe à tester en P1 |
| Processeur | AMD EPYC 9554, 128 cœurs visibles, partagés | Information |

### Urne cryptographique

Isoler le composant chargé des autorisations de vote, de la vérification des bulletins chiffrés, des preuves et du déchiffrement, derrière une interface documentée. Cette séparation logicielle n’équivaut pas à une indépendance de sécurité.

Le principe de sobriété exclut un service cryptographique tournant en permanence à côté de PHP. Deux voies sont à évaluer en P1 :

1. **Implémentation en PHP (GMP) et JavaScript d’un protocole publié et analysé** : chiffrement ElGamal homomorphe sur un groupe multiplicatif de grande taille, preuves à divulgation nulle de connaissance, déchiffrement partagé. La spécification est reprise telle quelle (groupe, encodages, preuves), avec des jeux de test croisés avec l’implémentation de référence.
2. **Appel d’un outil externe de vérification**, par exemple l’outil en ligne de commande de Belenios, si un binaire compatible fonctionne sur le conteneur. Cette voie peut servir au moins à la vérification indépendante des urnes.

Belenios reste un candidat de référence, pas une dépendance choisie. Sa FAQ décrit plusieurs méthodes de vote et des autorités de déchiffrement, et précise que les procurations ne sont pas une fonction native [S1].

Interdits : développer un protocole cryptographique original, ou modifier un protocole publié sans nouvelle analyse de sécurité.

### Ordres de grandeur pour la voie PHP/GMP (à confirmer par le prototype)

Les mesures du diagnostic portent sur des exponentiations isolées. Le coût réel dépend du protocole : nombre d’options, forme des preuves, nombre de détenteurs de clés.

- **Vérification à la réception** : en supposant quelques dizaines d’exponentiations par bulletin, chaque dépôt coûte de l’ordre de 10 ms en 2048 bits et de 20 ms en 3072 bits. C’est acceptable dans une requête web.
- **Vérification complète d’une urne** : traitement de fond en ligne de commande (cron ou SSH), par lots reprenables, avec un rapport signé, comme toute tâche longue.
- **Dépouillement homomorphe** : multiplication des chiffrés (peu coûteuse), puis un déchiffrement partiel par détenteur de clé et par option. Négligeable devant la vérification.
- **Chiffrement dans le navigateur** : il repose sur les grands entiers de JavaScript, plus lents que GMP. Le temps sur un téléphone d’entrée de gamme doit être mesuré en P1 avant de choisir la taille du groupe.
- **Taille du groupe** : le secret d’un vote doit tenir des années après le scrutin. Un module de 3072 bits est donc proposé, avec un sous-groupe d’ordre premier de 256 bits, plutôt que 2048 bits. Choix définitif en P1, après la mesure côté navigateur et la revue spécialisée.

### Environnements

Développement, préproduction et production séparés, avec des données fictives hors production. Le CDN ne doit jamais mettre en cache une page dynamique de l’application : `Cache-Control: no-store` sur toutes les réponses personnelles.

## 5. Modèle de données conceptuel

| Ensemble | Entités principales |
|---|---|
| Identité | Compte, moyens d’authentification, second facteur, session, récupération |
| Organisation | Chambre, appartenance, rôle, invitation, règle d’admission |
| Configuration | Modèle versionné, scrutin, question, option, document figé, jeu de règles |
| Électorat | Liste électorale figée, inscription, éligibilité, poids éventuel |
| Participation | Émargement, autorisation de vote, remplacement |
| Urne | Bulletin chiffré, preuve, référence au scrutin et au protocole |
| Procuration | Procuration standard, suppléance de retard, acceptation, échéance, état, exercice |
| Désignation | Vivier, consentement, contrainte, tirage, mandat, suppléance |
| Résultat | Dépouillement, version du calcul, preuves, procès-verbal |
| Exploitation | Événement administratif, incident, notification, file de tâches |

Ce tableau n’est pas un schéma SQL. Les identifiants qui relient l’identité à l’urne sont définis par le protocole retenu, pas inventés à part. Séparer deux tables ne garantit pas l’anonymat : métadonnées, horodatages, journaux et sauvegardes peuvent permettre des rapprochements.

Pour les opérations concurrentes : transactions, contraintes d’unicité, verrous de ligne (`FOR UPDATE`, `NOWAIT`) et traitement idempotent. L’acceptation d’un bulletin et l’émargement restent cohérents même après une panne ou une requête répétée.

### Réglages imposés à chaque connexion MySQL

- `SET time_zone = '+00:00'`
- `SET SESSION sql_mode = 'STRICT_ALL_TABLES,ONLY_FULL_GROUP_BY,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'`
- `SET SESSION innodb_lock_wait_timeout = 5` (valeur à ajuster en P2)
- Jeu de caractères `utf8mb4`, interclassement `utf8mb4_0900_ai_ci`, préparations natives (pas d’émulation).

L’application vérifie ces réglages au démarrage et refuse de fonctionner s’ils ne sont pas appliqués.

## 6. Cycle de vie d’un scrutin

États proposés : brouillon, prêt, ouvert, clos, dépouillement en cours, résultat vérifié, publié, archivé. Annulation, contestation et incident bloquant sont traités à part.

Avant ouverture, figer la question, les documents, les options, les règles de calcul, les échéances, la liste électorale, les procurations autorisées, le seuil d’activation des suppléances de retard et les détenteurs de clés. Une correction substantielle impose une nouvelle version du scrutin et une information des participants.

Le serveur fait autorité pour l’heure. Tous les instants sont enregistrés en UTC et affichés dans le fuseau pertinent. Une requête reçue après la clôture est refusée, même si le formulaire était ouvert avant.

La publication est distincte du dépouillement. Le lien entre une personne et son choix reste protégé après la clôture, y compris dans les archives.

### Approbation d’un document

Conserver le fichier exact, sa version et son empreinte avec le scrutin. L’empreinte permet de détecter une différence ; elle ne prouve pas seule l’auteur ni la date. Le procès-verbal cite cette version, les effectifs, les règles, les résultats et les incidents. Toute modification crée une nouvelle version, qui ne bénéficie pas de l’approbation précédente.

## 7. Méthodes de vote

Chaque méthode définit un format de bulletin, une validation, une règle de calcul et une version d’algorithme. Les variantes portent des noms distincts. La méthode est figée avant l’ouverture.

| Livraison proposée | Méthodes |
|---|---|
| Premier socle | Pour/contre/abstention, choix unique, choix multiple limité, approbation |
| Première extension prioritaire | Jugement majoritaire |
| Extensions | Notes, Borda, Condorcet avec variante explicite, vote alternatif |
| Élections à plusieurs sièges | STV et autres règles précisément spécifiées |
| Fonctions ultérieures | Budgets, pondérations, collèges, plusieurs tours |

Le secret étant obligatoire, une méthode n’est proposée que si le protocole permet de la dépouiller sans révéler les bulletins individuels, ou si la publication de bulletins anonymisés a été analysée (risque de reconnaissance d’un bulletin par une combinaison rare). Les méthodes à classement complet sont les plus exposées à ce risque.

Maintenir une matrice méthode × protocole × procuration et bloquer les combinaisons non validées.

## 8. Confidentialité et modèle de menace

### Garantie unique : le secret

| Mode | Statut |
|---|---|
| Secret, détenteurs de clés multiples et seuil réglable | **Mode unique d’EXU** |
| Secret à détenteur unique | Possible si le scrutin le prévoit (seuil de 1), à signaler clairement aux votants |
| Nominatif | **Retiré du périmètre** |
| Clé disponible sur le serveur | **Interdit** |

Les détenteurs de clés ne sont pas limités en nombre ; le seuil de déchiffrement est réglable. Un seuil de 1 sur 1 concentre la confiance sur une personne ; le seuil limite le pouvoir individuel mais ne protège pas d’une collusion suffisante. Une horloge logicielle ne rend pas une clé inutilisable avant la clôture.

Le chiffrement dans le navigateur protège le choix avant transmission, sous les hypothèses du protocole. Le serveur vérifie les autorisations et les preuves de validité sans lire le choix. Une validation en JavaScript n’a aucune valeur d’autorisation.

### Menaces et réponses attendues

| Menace | Réponse de conception |
|---|---|
| Vol de compte | Argon2id, limitation des essais, révocation des sessions, récupération contrôlée, second facteur obligatoire pour l’administration |
| Accès à une autre chambre | Contrôle de périmètre sur chaque opération, tests d’isolation |
| Injection ou contenu actif | Requêtes préparées, encodage des sorties, politique de sécurité du contenu stricte |
| Requête forgée ou rejouée | Protection CSRF, autorisations serveur, traitement idempotent |
| Double vote ou dépôt simultané | Contraintes atomiques, arbitrage déterministe, tests de concurrence |
| Altération administrative | Permissions minimales, preuves et traces vérifiables indépendamment |
| Serveur compromis | Clés hors serveur, protocole vérifiable, contrôle du code distribué, audit |
| Page personnelle mise en cache par le CDN | `no-store` sur toute réponse dynamique, test au diagnostic |
| Adresse IP du votant masquée par le CDN | Limitation des essais fondée sur un en-tête fiable, à établir au diagnostic |
| Perte de clé | Seuil adapté, procédure testée avant ouverture |
| Panne ou saturation | Capacité mesurée, surveillance, procédure d’incident |
| Faux positif du pare-feu OVH | Format des bulletins testé ; message d’erreur explicite si un dépôt est bloqué |

Un journal chaîné conservé sur le seul serveur peut être réécrit par un administrateur puissant : prévoir des témoins ou ancrages externes. Ne jamais journaliser mots de passe, jetons, clés ou choix.

Les résultats eux-mêmes peuvent révéler des préférences dans un très petit groupe ou en cas d’unanimité. Prévoir un avertissement quand l’effectif est faible.

## 9. Procurations

Deux mécanismes, tous deux facultatifs et activables par chambre ou par scrutin. Le mandataire ou le suppléant agit toujours depuis son propre compte et dépose un bulletin distinct pour chaque mandat.

### Procuration standard

Pour un scrutin donné, une personne désigne un mandataire, qui accepte. Le mandataire vote à sa place.

### Procuration de retard (arrêtée)

- À son inscription, ou lorsqu’elle est inscrite par la chambre, la personne désigne un **suppléant permanent**.
- Le suppléant **doit accepter** sa désignation.
- Si la personne n’a toujours pas voté lorsque le **seuil d’activation** est atteint (par défaut aux trois quarts de la durée du scrutin, réglable, et la règle elle-même est facultative), le suppléant peut voter pour elle.
- Le suppléant **n’est pas alerté** : il lui revient d’aller vérifier sur son tableau de bord. Comme pour une procuration classique, il sait que la personne n’a pas voté ; c’est accepté et annoncé aux membres.
- **Le vote personnel prévaut** : si la personne vote avant la clôture, son bulletin remplace celui du suppléant.

### Propositions à ratifier

- Après un vote personnel, le suppléant ou le mandataire ne peut plus voter pour ce mandat.
- Pas de sous-délégation.
- Plafond de mandats par personne, toutes procurations confondues : **à décider** (proposition : réglable par chambre, 2 par défaut).
- Révocation possible jusqu’à l’exercice ; le suppléant peut se retirer à tout moment, ce qui est signalé au mandant.
- Le vote personnel doit l’emporter sur la procuration standard comme sur la procuration de retard.

### Exigences de sécurité

Spécifier les états : proposée, acceptée, refusée, révoquée, active, exercée, remplacée par le vote personnel, expirée. L’activation ne crée jamais de bulletin automatiquement.

Tester toutes les situations de concurrence : vote personnel contre vote du suppléant, révocation contre exercice, clôture contre dépôt, panne après réception. Le remplacement d’un bulletin doit être prévu par le protocole cryptographique et rester vérifiable, sans révéler lequel des bulletins a été retenu à qui n’a pas à le savoir. **Ce point bloque la livraison des procurations en scrutin secret** tant qu’il n’est pas démontré.

## 10. Jurys, groupes et mandats

Prendre en charge nomination, élection, tirage au sort, présélection suivie d’un tirage et composition mixte. Figer le vivier, l’éligibilité, le consentement, les contraintes et les suppléants avant la sélection. Prévoir refus, incompatibilités et remplacement sans tirage discrétionnaire.

Un tirage vérifiable rend contrôlables la liste initiale, l’algorithme, la source d’aléa et les éventuelles reprises, et empêche de relancer jusqu’à obtenir un résultat souhaité. Ne publier que les éléments compatibles avec la confidentialité du vivier.

## 11. Feuille de route par jalons

Le secret étant obligatoire pour tous les scrutins, **aucun scrutin réel n’a lieu avant P5 et sa revue de sécurité**. Les phases antérieures n’utilisent que des données fictives.

| Phase | Travail et livrables | Critère de sortie |
|---|---|---|
| P0 • Cadrage | Décisions de la section 1, modèle de menace, matrice des garanties, diagnostic de l’hébergement (fait le 24 novembre 2026) | Décisions ouvertes (section 15) tranchées |
| P1 • Faisabilité cryptographique | Choix entre les deux voies de la section 4 ; prototype comptes–urne en PHP/GMP et JavaScript ; mesures de performance ; test du pare-feu sur de vrais bulletins ; étude du remplacement de bulletin pour les procurations | Démonstration vérifiable, croisée avec une implémentation de référence ; limites consignées |
| P2 • Socle | Comptes, double authentification, chambres, autorisations, admission, chiffrement des coordonnées, courriels Proton, migrations, déploiement | Tests d’isolation, de récupération et de restauration réussis |
| P3 • Parcours complets à blanc | Scrutins sur données fictives, document figé, quorum, clôture, procès-verbal, avec l’urne du prototype P1 | Scrutin fictif reproductible, parcours testés par des membres d’Andryale |
| P4 • Méthodes et désignations | Jugement majoritaire, mandats, nomination, tirage, méthodes retenues | Résultats de référence et tirages vérifiés indépendamment |
| P5 • Scrutin secret de production | Clés externes, seuil, accusé de réception, dépouillement, vérificateur indépendant | Revue spécialisée ; scrutin blanc complet sans anomalie bloquante ; **premier pilote réel Andryale** |
| P6 • Procurations | Procuration standard et procuration de retard intégrées au secret | Tests adverses et de concurrence ; garanties conservées de bout en bout |
| P7 • Mise en service | Revue de sécurité, test de charge (milliers de votants), documentation, assistance, exercice d’incident | Autorisation interne de lancement ; **ouverture aux pilotes E-TEAM** |
| P8 • Évolution | Plusieurs tours, collèges, méthodes avancées, interopérabilité, portage sur une offre plus puissante | Chaque évolution repasse les contrôles adaptés à son risque |

La recherche sur le secret et les procurations commence dès P1.

## 12. Recette et preuves attendues

### Calcul électoral

Jeux de référence indépendants : zéro bulletin, unanimité, égalités, quorum juste atteint ou manqué, abstentions, options non évaluées, classements partiels, plusieurs sièges, arrondis. Résultats attendus écrits avant l’implémentation et comparés à un calcul indépendant.

### Sécurité et concurrence

Un compte d’une chambre ne lit ni ne modifie les données d’une autre ; un rôle retiré perd ses droits ; une récupération de compte ne crée pas d’autorisation de vote supplémentaire ; deux dépôts simultanés ne produisent jamais deux voix ; règles et fichiers figés ne sont jamais remplacés ; aucune page personnelle n’est servie par le cache du CDN.

### Cryptographie

Preuves invalides, bulletins malformés, mauvaises clés, nombre insuffisant de détenteurs, perte d’un détenteur, bulletins remplacés (procurations), rapprochement entre urne reçue et urne dépouillée. Un tiers vérifie les éléments prévus par le protocole sans accès administrateur.

### Exploitation et usage

Clavier, lecteur d’écran, mobile, erreurs de connexion, expiration de session, délai de clôture, coupure pendant un dépôt puis restauration. Charge mesurée sur l’hébergement de test pour l’effectif visé ; aucune capacité annoncée sans mesure. Le test de charge porte sur le pic de dépôts juste avant la clôture et sur l’ouverture des suppléances de retard.

Chaque livraison conserve un rapport de recette, les anomalies résiduelles et la personne chargée de leur traitement. Toute anomalie pouvant fausser le résultat, contourner l’éligibilité ou révéler un choix bloque la mise en production.

## 13. Versions, déploiement, maintenance et incidents

### Numérotation

- Versions **0.x.1** jusqu’à la publication de la version 1 : 0.1.1, 0.2.1, 0.3.1, etc. Chaque livraison a son propre numéro, jamais réutilisé.
- Proposition à ratifier : la **1.0.0** correspond à la fin de P7 (autorisation de lancement).
- Livraisons à ce jour : 0.1.1 et 0.2.1 (page de garde), 0.3.1 (diagnostic de l’hébergement), 0.4.1 (page de garde et diagnostic corrigés).
- Ce dossier suit sa propre numérotation (0.1, 0.2…).

### Pratiques

Versionner code, dépendances, migrations et algorithmes. Intégration continue avec tests, analyse statique et contrôle des dépendances. Historique des changements et consignes de mise à jour publiés. Licence AGPL-3.0-or-later, sous réserve de compatibilité des composants.

Aucun changement de protocole ou de calcul pendant un scrutin ouvert. Une correction urgente suit une procédure documentée et peut imposer une annulation. Le retour arrière tient compte des migrations et de la conservation de tous les bulletins acceptés.

Sauvegardes chiffrées, accès limités, restauration testée. Délai de reprise et perte de données admissible fixés en P0. Une restauration technique ne suffit pas si des votes confirmés ont disparu : appliquer alors la procédure de réconciliation ou d’annulation, sans déclarer le scrutin intact.

En cas d’incident : conserver les éléments utiles sans exposer de secret, qualifier l’impact, prévenir les responsables, décider selon les règles préétablies, publier un constat, corriger. Aucune prolongation discrétionnaire.

Responsables identifiés pour le produit, le développement, l’exploitation, la sécurité et le bureau de vote. Une personne peut cumuler certains rôles, mais une revue indépendante ne se remplace pas par une auto-validation. Les signalements de faille passent par `/.well-known/security.txt`.

## 14. Données personnelles et conservation

### Données du compte (arrêté)

- **En clair en base** : nom et prénom, ou pseudonyme ; code unique de votant.
- **Chiffrées en base** : adresse électronique et autres coordonnées.

### Conditions de mise en œuvre (proposées)

- Chiffrement authentifié (XChaCha20-Poly1305 de libsodium), avec une clé stockée hors de la base et hors de `www/`. Ce chiffrement protège contre une fuite de la base seule ; il ne protège pas contre une compromission du serveur, qui détient la clé.
- Recherche d’un compte par courriel (connexion, récupération) par un **index aveugle** : empreinte BLAKE2b avec clé distincte, calculée sur l’adresse normalisée.
- Le code unique de votant ne doit jamais apparaître dans l’urne ni permettre de relier un bulletin à une personne ; son rôle exact dépend du protocole retenu en P1.
- Rotation des clés de chiffrement des données prévue et testée.

### Principes

Inventaire des données et finalités : identité, appartenance, participation, procurations, journaux, résultats. Andryale porte le service ; les responsabilités de chaque chambre envers ses membres restent à définir et à annoncer. Une vérification d’adresse électronique ne prouve pas une identité civile.

Durées de conservation décidées par catégorie, avec la période de contestation pertinente. Suppression des comptes, maintien justifié des documents de décision, purge des sauvegardes à échéance et destruction des clés de scrutin selon le protocole. Pour le secret : minimiser les métadonnées corrélables et distinguer preuves publiques, archives restreintes et données à supprimer.

La recommandation de la CNIL sur le vote électronique, mise à jour en avril 2026, sert de référence pour apprécier les objectifs selon le risque ; elle renvoie aux recommandations de l’ANSSI [S2]. Ce dossier n’est ni un avis juridique ni une attestation de conformité.

## 15. Décisions restant à prendre

| Décision | Proposition de travail | Moment limite |
|---|---|---|
| Plafond de mandats par personne | Réglable par chambre, 2 par défaut | Avant P6 |
| Seuil d’activation par défaut de la procuration de retard | Trois quarts de la durée du scrutin | Avant P6 |
| Double authentification des détenteurs de clés, scrutateurs et auditeurs | Obligatoire | P0 |
| Double authentification des votants | Facultative, proposée à l’inscription | P0 |
| Voie cryptographique | Fondée sur le prototype P1 et une revue spécialisée | Fin de P1 |
| Framework PHP | Choix documenté selon PHP 8.5/8.6 | Début de P2 |
| Admission et doublons | Validation par chambre ; mécanisme contre les doublons | P0 |
| Taille et simultanéité | Objectifs mesurables (milliers de votants, pic à fixer) | P0 |
| Délai de reprise et perte admissible | À fixer | P0 |
| Visibilité des résultats | Aucun résultat partiel ; publication après dépouillement vérifié | Avant P3 |
| Tirage contraint | Contraintes et protocole vérifiable | Avant P4 |
| Conservation et incident | Règles par catégorie et procédure écrite | Avant P5 |
| Événement déclenchant la 1.0.0 | Fin de P7 | P0 |
| Taille du groupe cryptographique | 3072 bits, sous-groupe de 256 bits | Fin de P1 |
| Stockage des sessions | Dossier privé hors de `www/` ou table MySQL | Début de P2 |
| Outillage de déploiement | `composer.phar` et git dans le dossier privé ; aucune dépendance installée dans `www/` | Début de P2 |
| Graphie du nom dans les textes | « EXU » ; « exu » réservé au nom affiché à côté du logo s’il est conservé | Prochaine livraison de la page |
| Lien « Code source » de la page de garde | Mention « publication à venir » tant que le dépôt EXU n’est pas public | Prochaine livraison de la page |

## 16. Dossier de référence à maintenir

Le dépôt EXU contiendra : spécification fonctionnelle ; décisions d’architecture ; schéma de données et migrations ; description des protocoles et hypothèses ; matrice de compatibilité ; catalogue des méthodes ; tests et jeux de référence ; rapport de diagnostic de l’hébergement ; guide d’installation (dont le portage depuis un hébergement mutualisé) ; procédure de sauvegarde et de restauration ; procédure de scrutin ; gestion des incidents ; politique de sécurité ; journal des versions.

Priorité immédiate : retirer le diagnostic du serveur, trancher les décisions P0 restantes, puis réaliser P1 (prototype cryptographique PHP/GMP et mesure dans le navigateur) avant d’investir dans l’interface complète ou un schéma SQL définitif.

## 17. Références

Les propositions d’architecture et de séquencement sont des choix de conception, et non des exigences attribuées aux sources.

- **[S1] Belenios, FAQ officielle** : méthodes, procurations, autorités et limites de sécurité. https://www.belenios.org/faq.html
- **[S2] CNIL, recommandation sur le vote électronique, mise à jour du 24 avril 2026** : approche selon les risques et articulation avec l’ANSSI. https://www.cnil.fr/fr/recommandation-vote-electronique
- **[S3] OWASP ASVS** : référentiel de contrôles de sécurité applicative à sélectionner et tracer dans la recette ; il ne valide pas un protocole électoral. https://asvs.owasp.org/
- **[S4] Andryale, documents et mentions légales** : éditeur, hébergeurs et périmètre, dont exu.fr. https://andryale.org/mentions-legales/
