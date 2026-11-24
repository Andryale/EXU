# Diagnostic de l'hébergement

Script PHP autonome qui vérifie qu'un hébergement convient à EXU : PHP et extensions, cryptographie (libsodium, Argon2id, GMP), MySQL (mode SQL, verrous entre connexions, contraintes, horloge), en-têtes HTTP à travers le CDN, pare-feu applicatif, réseau sortant (SMTP), outils système.

## Usage

1. En haut de `index.php`, remplacer `DIAG_CLE` par une clé de 24 caractères au moins, ajuster `DIAG_EXPIRATION` et `DIAG_DOMAINE`, et renseigner `DIAG_BDD` pour les tests MySQL (base de test dédiée de préférence).
2. Copier ce dossier sous `www/diagnostic-exu/` sur le serveur.
3. Ouvrir `https://votre-domaine/diagnostic-exu/`, saisir la clé, lancer le diagnostic.
4. **Supprimer le dossier du serveur** une fois le rapport obtenu.

Le script refuse de fonctionner tant que la clé n'est pas définie et après la date d'expiration. Il ne modifie rien sur le serveur, sauf une table de test temporaire au nom aléatoire, supprimée à la fin. Le rapport ne contient ni la clé ni le mot de passe de la base.

**Ne jamais publier ce fichier avec une clé ou des identifiants renseignés.**
