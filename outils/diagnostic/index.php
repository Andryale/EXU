<?php
declare(strict_types=1);

/*
 * EXU · diagnostic de l'hébergement · version 0.4.1
 * Projet porté par Andryale · https://exu.fr/
 *
 * Ce script vérifie que l'hébergement convient au futur système de vote :
 * PHP, extensions, cryptographie, MySQL (verrous, contraintes, horloge),
 * en-têtes HTTP et CDN, pare-feu applicatif, réseau sortant, outils système.
 *
 * Usage
 *   1. Renseigner DIAG_CLE (24 caractères au moins) et, si besoin, DIAG_BDD.
 *   2. Déposer le dossier diagnostic-exu/ dans www/ puis ouvrir https://exu.fr/diagnostic-exu/
 *   3. Saisir la clé, lancer, copier ou télécharger le rapport.
 *   4. SUPPRIMER le dossier du serveur une fois le rapport obtenu.
 *
 * Le script ne modifie rien sur le serveur, sauf une table de test temporaire
 * dans la base indiquée, créée sous un nom aléatoire puis supprimée.
 * Il n'affiche ni la clé ni le mot de passe de la base.
 */

// ===================================================================
// CONFIGURATION
// ===================================================================

/** Clé d'accès : 24 caractères au moins. Tant qu'elle n'est pas changée, le script refuse de fonctionner. */
const DIAG_CLE = 'CHANGEZ-MOI';

/** Date (UTC) après laquelle le script refuse de fonctionner. */
const DIAG_EXPIRATION = '2026-12-08 23:59:59';

/** Domaine contrôlé. Ne jamais le déduire de l'en-tête Host, que le client peut falsifier. */
const DIAG_DOMAINE = 'exu.fr';

/** Base de test. Laisser 'hote' vide pour sauter les tests MySQL. Utiliser de préférence une base dédiée. */
const DIAG_BDD = [
    'hote'         => '',      // ex. : xxxxxx.mysql.db
    'port'         => 3306,
    'base'         => '',
    'utilisateur'  => '',
    'mot_de_passe' => '',
];

const DIAG_VERSION = '0.4.1';

// ===================================================================
// AMORÇAGE
// ===================================================================

ini_set('display_errors', '0');
error_reporting(E_ALL);
$tempsIni = (string) ini_get('max_execution_time');
@set_time_limit(150);
$fuseauIni = (string) ini_get('date.timezone');
date_default_timezone_set('UTC');

header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

function refuser(int $code, string $message): never
{
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message, "\n";
    exit;
}

if (DIAG_CLE === 'CHANGEZ-MOI' || strlen(DIAG_CLE) < 24) {
    refuser(503, "Diagnostic désactivé : définissez DIAG_CLE (24 caractères au moins) en haut de index.php.");
}
$expiration = strtotime(DIAG_EXPIRATION . ' UTC');
if ($expiration === false || time() > $expiration) {
    refuser(410, "Diagnostic expiré : supprimez le dossier diagnostic-exu/ du serveur.");
}

function cleFournie(): string
{
    $cle = $_POST['cle'] ?? ($_SERVER['HTTP_X_DIAG_CLE'] ?? '');
    return is_string($cle) ? $cle : '';
}

// -------------------------------------------------------------------
// Point d'écho pour le test du pare-feu applicatif (appelé par le script lui-même)
// -------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (($_GET['echo'] ?? '') === '1')) {
    if (!hash_equals(DIAG_CLE, cleFournie())) {
        sleep(1);
        refuser(403, 'Clé incorrecte.');
    }
    $type = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    $charge = str_starts_with($type, 'application/json')
        ? (string) file_get_contents('php://input')
        : (string) ($_POST['charge'] ?? '');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ECHO-OK:', strlen($charge), ':', hash('sha256', $charge);
    exit;
}

// -------------------------------------------------------------------
// Formulaire d'accès
// -------------------------------------------------------------------
$autorise = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && hash_equals(DIAG_CLE, cleFournie());
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !$autorise) {
    sleep(1);
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!$autorise) {
    $erreur = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    $bddConfiguree = DIAG_BDD['hote'] !== '';
    header('Content-Type: text/html; charset=utf-8');
    ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Diagnostic de l’hébergement · EXU</title>
<link rel="stylesheet" href="diagnostic.css?v=<?= e(DIAG_VERSION) ?>">
</head>
<body>
<main class="carte etroite">
  <p class="marque"><img src="/logo-exu.png" alt="" width="324" height="218"> <span>EXU · diagnostic</span></p>
  <h1>Diagnostic de l’hébergement</h1>
  <p>Cette page vérifie que le serveur convient au futur système de vote. Elle est temporaire : supprimez-la après usage. Accès autorisé jusqu’au <?= e(gmdate('d/m/Y H:i', $expiration)) ?> UTC.</p>
  <?php if ($erreur): ?><p class="alerte" role="alert">Clé incorrecte.</p><?php endif; ?>
  <form method="post" action="">
    <label for="cle">Clé d’accès</label>
    <input id="cle" name="cle" type="password" autocomplete="off" required>
    <fieldset>
      <legend>Tests à exécuter</legend>
      <label><input type="checkbox" name="reseau" value="1" checked> Contrôle du site, pare-feu et réseau sortant (requêtes HTTP réelles)</label>
      <label><input type="checkbox" name="bdd" value="1" <?= $bddConfiguree ? 'checked' : 'disabled' ?>> Base de données <?= $bddConfiguree ? '' : '(non configurée dans le fichier)' ?></label>
      <label><input type="checkbox" name="systeme" value="1" checked> Commandes système en lecture seule (nproc, uname, outils présents)</label>
    </fieldset>
    <fieldset>
      <legend>Format du rapport</legend>
      <label><input type="radio" name="format" value="html" checked> Page web</label>
      <label><input type="radio" name="format" value="txt"> Fichier texte à télécharger</label>
    </fieldset>
    <button type="submit">Lancer le diagnostic</button>
  </form>
  <p class="petit">Durée indicative : 10 à 40 secondes.</p>
</main>
</body>
</html>
<?php
    exit;
}

// ===================================================================
// OUTILS DE RAPPORT
// ===================================================================

final class Rapport
{
    /** @var array<string, list<array{statut:string, libelle:string, valeur:string, note:string}>> */
    public array $sections = [];
    private string $courante = '';

    public function section(string $titre): void
    {
        $this->courante = $titre;
        $this->sections[$titre] = [];
    }

    public function ajouter(string $statut, string $libelle, string $valeur = '', string $note = ''): void
    {
        $this->sections[$this->courante][] = compact('statut', 'libelle', 'valeur', 'note');
    }

    public function ok(string $l, string $v = '', string $n = ''): void { $this->ajouter('ok', $l, $v, $n); }
    public function attention(string $l, string $v = '', string $n = ''): void { $this->ajouter('attention', $l, $v, $n); }
    public function echec(string $l, string $v = '', string $n = ''): void { $this->ajouter('echec', $l, $v, $n); }
    public function info(string $l, string $v = '', string $n = ''): void { $this->ajouter('info', $l, $v, $n); }

    /** Exécute un test ; toute exception devient une ligne « échec » sans interrompre le diagnostic. */
    public function essai(string $libelle, callable $test): void
    {
        try {
            $test($this);
        } catch (Throwable $t) {
            $this->echec($libelle, get_class($t), self::nettoyer($t->getMessage()));
        }
    }

    public static function nettoyer(string $message): string
    {
        // Ne jamais laisser passer le mot de passe de la base ou la clé dans un message d'erreur.
        foreach ([DIAG_BDD['mot_de_passe'], DIAG_CLE] as $secret) {
            if ($secret !== '') {
                $message = str_replace($secret, '[masqué]', $message);
            }
        }
        return mb_substr($message, 0, 400);
    }

    /** @return array<string,int> */
    public function totaux(): array
    {
        $t = ['ok' => 0, 'attention' => 0, 'echec' => 0, 'info' => 0];
        foreach ($this->sections as $lignes) {
            foreach ($lignes as $l) {
                $t[$l['statut']]++;
            }
        }
        return $t;
    }
}

function oui(bool $b): string { return $b ? 'oui' : 'non'; }
function ms(float $secondes): string { return number_format($secondes * 1000, 1, ',', ' ') . ' ms'; }
function nombre(float|int $n, int $dec = 0): string { return number_format((float) $n, $dec, ',', ' '); }
function chrono(): float { return hrtime(true) / 1e9; }

function iniBool(string $cle): bool
{
    $v = strtolower(trim((string) ini_get($cle)));
    return in_array($v, ['1', 'on', 'true', 'yes'], true);
}

function fonctionDisponible(string $nom): bool
{
    if (!function_exists($nom)) {
        return false;
    }
    $desactivees = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    return !in_array($nom, $desactivees, true);
}

/**
 * Requête HTTP sortante via cURL, sans suivre les redirections par défaut.
 * @param array<int,string> $entetes
 * @return array{statut:int, entetes:array<string,list<string>>, corps:string, duree:float, erreur:string, version:string, url:string}
 */
function requete(string $url, string $methode = 'GET', ?string $corps = null, array $entetes = [], bool $suivre = false, int $delai = 15): array
{
    $recus = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST   => $methode,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_FOLLOWLOCATION  => $suivre,
        CURLOPT_MAXREDIRS       => 5,
        CURLOPT_CONNECTTIMEOUT  => 8,
        CURLOPT_TIMEOUT         => $delai,
        CURLOPT_HTTPHEADER      => array_merge(['User-Agent: EXU-diagnostic/' . DIAG_VERSION], $entetes),
        CURLOPT_HEADERFUNCTION  => static function ($ch, string $ligne) use (&$recus): int {
            $p = strpos($ligne, ':');
            if ($p !== false) {
                $recus[strtolower(trim(substr($ligne, 0, $p)))][] = trim(substr($ligne, $p + 1));
            } elseif (str_starts_with($ligne, 'HTTP/')) {
                $recus = []; // nouvelle réponse (redirection suivie) : on ne garde que la dernière
            }
            return strlen($ligne);
        },
    ]);
    if (defined('CURLOPT_PROTOCOLS_STR')) {
        curl_setopt($ch, CURLOPT_PROTOCOLS_STR, 'http,https');
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS_STR, 'http,https');
    }
    if ($corps !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $corps);
    }
    $t = chrono();
    $reponse = curl_exec($ch);
    $duree = chrono() - $t;
    $versions = [CURL_HTTP_VERSION_1_0 => '1.0', CURL_HTTP_VERSION_1_1 => '1.1', CURL_HTTP_VERSION_2_0 => '2'];
    if (defined('CURL_HTTP_VERSION_3')) {
        $versions[CURL_HTTP_VERSION_3] = '3';
    }
    $info = curl_getinfo($ch);
    $resultat = [
        'statut'  => (int) ($info['http_code'] ?? 0),
        'entetes' => $recus,
        'corps'   => is_string($reponse) ? $reponse : '',
        'duree'   => $duree,
        'erreur'  => curl_error($ch),
        'version' => $versions[$info['http_version'] ?? 0] ?? '?',
        'url'     => (string) ($info['url'] ?? $url),
    ];
    return $resultat;
}

function entete(array $r, string $nom): ?string
{
    return isset($r['entetes'][$nom]) ? implode(' | ', $r['entetes'][$nom]) : null;
}

// ===================================================================
// TESTS
// ===================================================================

$debut = chrono();
$R = new Rapport();
$avecReseau = ($_POST['reseau'] ?? '') === '1';
$avecBdd = ($_POST['bdd'] ?? '') === '1' && DIAG_BDD['hote'] !== '';
$avecSysteme = ($_POST['systeme'] ?? '') === '1';
$horloges = []; // décalages mesurés (secondes) par source

// -------------------------------------------------------------------
// 1. PHP et serveur
// -------------------------------------------------------------------
$R->section('PHP et serveur');

$R->essai('Version de PHP', function (Rapport $r): void {
    $v = PHP_VERSION;
    if (version_compare($v, '8.5.0', '>=')) {
        $r->ok('Version de PHP', $v, 'Cible du projet : 8.5, compatible 8.6.');
    } else {
        $r->attention('Version de PHP', $v, 'Le projet vise PHP 8.5 au minimum.');
    }
});
$R->info('Interface serveur (SAPI)', PHP_SAPI);
$R->info('Système', php_uname('s') . ' ' . php_uname('r') . ' ' . php_uname('m'));
$R->info('Nom de la machine', (string) gethostname(), 'Sur un mutualisé, la machine peut changer après une migration OVH.');
$R->info('Entiers', (PHP_INT_SIZE * 8) . ' bits');
$R->info('Fuseau PHP configuré', $fuseauIni !== '' ? $fuseauIni : '(aucun)', 'L’application devra travailler en UTC, comme le prévoit le cadrage.');
$R->info('Mémoire, durée maximale', ini_get('memory_limit') . ', ' . $tempsIni . ' s');
$R->info('Envoi de données (post, fichier, variables)', ini_get('post_max_size') . ', ' . ini_get('upload_max_filesize') . ', ' . ini_get('max_input_vars'));

$R->essai('OPcache', function (Rapport $r): void {
    $actif = iniBool('opcache.enable');
    $jit = (string) ini_get('opcache.jit');
    $actif ? $r->ok('OPcache', 'actif', 'JIT : ' . ($jit !== '' ? $jit : 'non défini')) : $r->attention('OPcache', 'inactif', 'Performances réduites.');
});

iniBool('display_errors')
    ? $R->echec('Affichage des erreurs', 'activé', 'Doit être désactivé en production.')
    : $R->ok('Affichage des erreurs', 'désactivé');
iniBool('log_errors')
    ? $R->ok('Journal des erreurs PHP', 'activé', 'Fichier : ' . ((string) ini_get('error_log') ?: 'défaut du serveur'))
    : $R->attention('Journal des erreurs PHP', 'désactivé', 'L’application devra tenir son propre journal, hors de www/, sans donnée secrète.');
iniBool('expose_php')
    ? $R->attention('expose_php', 'activé', 'Révèle la version de PHP ; l’en-tête X-Powered-By est retiré par le .htaccess.')
    : $R->ok('expose_php', 'désactivé');

$assert = (string) ini_get('zend.assertions');
$assert === '-1'
    ? $R->ok('zend.assertions', '-1', 'Assertions compilées hors du code (production).')
    : $R->attention('zend.assertions', $assert, 'À mettre à -1 en production si un fichier .user.ini est pris en compte ; sinon 0 depuis le code.');

$userIni = (string) ini_get('user_ini.filename');
$R->info('Fichier de réglages par dossier', $userIni !== '' ? $userIni : '(désactivé)', $userIni !== '' ? 'Permet d’ajuster certains réglages PHP sans accès au php.ini.' : '');

iniBool('allow_url_include') ? $R->echec('allow_url_include', 'activé') : $R->ok('allow_url_include', 'désactivé');
$R->info('allow_url_fopen', oui(iniBool('allow_url_fopen')));
$R->info('open_basedir', (string) ini_get('open_basedir') ?: '(aucun)');
$R->info('Fonctions désactivées', (string) ini_get('disable_functions') ?: '(aucune)');
strtoupper((string) ini_get('default_charset')) === 'UTF-8'
    ? $R->ok('Jeu de caractères par défaut', 'UTF-8')
    : $R->attention('Jeu de caractères par défaut', (string) ini_get('default_charset'));

$R->essai('Sessions', function (Rapport $r): void {
    $reglages = [
        'session.use_strict_mode'  => iniBool('session.use_strict_mode'),
        'session.use_only_cookies' => iniBool('session.use_only_cookies'),
        'session.cookie_secure'    => iniBool('session.cookie_secure'),
        'session.cookie_httponly'  => iniBool('session.cookie_httponly'),
    ];
    foreach ($reglages as $cle => $valeur) {
        $valeur ? $r->ok($cle, 'activé') : $r->attention($cle, 'désactivé', 'À forcer par l’application (session_set_cookie_params ou session_start).');
    }
    $samesite = (string) ini_get('session.cookie_samesite');
    $samesite !== ''
        ? $r->ok('session.cookie_samesite', $samesite)
        : $r->attention('session.cookie_samesite', '(vide)', 'À fixer à Strict ou Lax par l’application.');
    $chemin = (string) ini_get('session.save_path');
    $r->info('Stockage des sessions', ini_get('session.save_handler') . ($chemin !== '' ? ' (' . $chemin . ')' : ''));
});

$R->essai('Dossiers', function (Rapport $r): void {
    $racine = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $r->info('Racine web', $racine !== '' ? $racine : '(inconnue)');
    $parent = $racine !== '' ? dirname($racine) : '';
    if ($parent !== '' && is_dir($parent)) {
        is_writable($parent)
            ? $r->ok('Dossier au-dessus de www/ inscriptible', $parent, 'Configuration, journaux et fichiers privés pourront y vivre, hors de portée du Web.')
            : $r->attention('Dossier au-dessus de www/ inscriptible', $parent, 'Non inscriptible par PHP : prévoir un dossier privé protégé.');
    }
    $tmp = sys_get_temp_dir();
    is_writable($tmp) ? $r->ok('Dossier temporaire', $tmp) : $r->attention('Dossier temporaire', $tmp, 'Non inscriptible.');
    $libre = $racine !== '' ? @disk_free_space($racine) : false;
    $r->info('Espace disque libre signalé', $libre !== false ? nombre($libre / 1024 ** 3, 1) . ' Gio' : '(non communiqué)', 'Sur un mutualisé, le quota réel est celui de l’offre.');
});

$R->essai('PHP en ligne de commande', function (Rapport $r): void {
    $binaires = glob('/usr/local/php*/bin/php') ?: [];
    $versions = array_map(static fn (string $b): string => preg_replace('#^/usr/local/php([^/]+)/.*#', '$1', $b) ?? $b, $binaires);
    $r->info('Versions de PHP en ligne de commande', $versions ? implode(', ', $versions) : '(non trouvées)', 'Les tâches cron OVH choisissent leur version de PHP séparément du site.');
});

// -------------------------------------------------------------------
// 2. Extensions
// -------------------------------------------------------------------
$R->section('Extensions PHP');

$requises = [
    'pdo_mysql' => 'Accès MySQL', 'mysqlnd' => 'Pilote MySQL natif', 'gmp' => 'Grands entiers (chiffrement ElGamal)',
    'bcmath' => 'Calcul décimal exact (dépouillement)', 'sodium' => 'Chiffrement et signatures', 'openssl' => 'TLS et chiffrement',
    'hash' => 'Empreintes', 'random' => 'Aléa cryptographique', 'mbstring' => 'Texte multioctet', 'intl' => 'Tri et formats français',
    'json' => 'JSON', 'ctype' => 'Validation', 'filter' => 'Validation', 'session' => 'Sessions', 'curl' => 'Requêtes sortantes (SMTP, vérifications)',
    'dom' => 'Exports XML', 'zlib' => 'Compression',
];
foreach ($requises as $ext => $role) {
    extension_loaded($ext)
        ? $R->ok($ext, (string) (phpversion($ext) ?: 'chargée'), $role)
        : $R->echec($ext, 'absente', $role);
}
$facultatives = [
    'gd' => 'Images (codes QR de la double authentification)', 'imagick' => 'Images', 'zip' => 'Archives d’export',
    'apcu' => 'Cache mémoire local', 'redis' => 'Client Redis', 'memcached' => 'Client Memcached', 'igbinary' => 'Sérialisation',
];
foreach ($facultatives as $ext => $role) {
    $R->info($ext . ' (facultative)', extension_loaded($ext) ? (string) (phpversion($ext) ?: 'chargée') : 'absente', $role);
}

// -------------------------------------------------------------------
// 3. Cryptographie
// -------------------------------------------------------------------
$R->section('Cryptographie');

$R->essai('Aléa cryptographique', function (Rapport $r): void {
    $a = random_bytes(32);
    $b = random_bytes(32);
    ($a !== $b && strlen($a) === 32)
        ? $r->ok('random_bytes', '32 octets', 'Source d’aléa du système disponible.')
        : $r->echec('random_bytes', 'résultat anormal');
    random_int(1, 6);
    $r->ok('random_int', 'disponible');
});

$R->essai('libsodium', function (Rapport $r): void {
    if (!extension_loaded('sodium')) {
        $r->echec('libsodium', 'absente');
        return;
    }
    $v = SODIUM_LIBRARY_VERSION;
    version_compare($v, '1.0.18', '>=')
        ? $r->ok('Version de libsodium', $v)
        : $r->attention('Version de libsodium', $v, 'Antérieure à 1.0.18 : pas de ristretto255 ; le chiffrement homomorphe passera par GMP.');

    $ristretto = ['sodium_crypto_core_ristretto255_add', 'sodium_crypto_scalarmult_ristretto255', 'sodium_crypto_core_ristretto255_from_hash'];
    $presentes = array_filter($ristretto, 'function_exists');
    count($presentes) === count($ristretto)
        ? $r->ok('Fonctions ristretto255', 'disponibles')
        : $r->attention('Fonctions ristretto255', count($presentes) . ' sur ' . count($ristretto), 'Piste écartée sur cet hébergement.');

    // Chiffrement authentifié (données personnelles chiffrées en base : courriels, etc.)
    $cle = sodium_crypto_aead_xchacha20poly1305_ietf_keygen();
    $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
    $clair = 'personne@exemple.org';
    $chiffre = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($clair, 'exu:compte:42', $nonce, $cle);
    $dechiffre = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($chiffre, 'exu:compte:42', $nonce, $cle);
    $falsifie = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($chiffre, 'exu:compte:43', $nonce, $cle);
    ($dechiffre === $clair && $falsifie === false)
        ? $r->ok('Chiffrement authentifié XChaCha20-Poly1305', 'aller-retour correct, falsification détectée')
        : $r->echec('Chiffrement authentifié XChaCha20-Poly1305', 'résultat anormal');
    $r->info('AES-256-GCM matériel (sodium)', oui(sodium_crypto_aead_aes256gcm_is_available()));

    // Signatures Ed25519 (procès-verbaux, journaux, exports signés)
    $paire = sodium_crypto_sign_keypair();
    $message = 'Procès-verbal de test';
    $signature = sodium_crypto_sign_detached($message, sodium_crypto_sign_secretkey($paire));
    $public = sodium_crypto_sign_publickey($paire);
    (sodium_crypto_sign_verify_detached($signature, $message, $public) && !sodium_crypto_sign_verify_detached($signature, $message . '.', $public))
        ? $r->ok('Signature Ed25519', 'signature et vérification correctes')
        : $r->echec('Signature Ed25519', 'résultat anormal');

    // Index aveugle : rechercher un courriel chiffré sans le déchiffrer
    $cleIndex = random_bytes(SODIUM_CRYPTO_GENERICHASH_KEYBYTES);
    $i1 = sodium_crypto_generichash(mb_strtolower('Personne@Exemple.org'), $cleIndex, 32);
    $i2 = sodium_crypto_generichash(mb_strtolower('personne@exemple.org'), $cleIndex, 32);
    $i1 === $i2
        ? $r->ok('BLAKE2b avec clé (index aveugle)', 'disponible', 'Permet de retrouver un compte par courriel chiffré.')
        : $r->echec('BLAKE2b avec clé (index aveugle)', 'résultat anormal');
});

$R->essai('Mots de passe', function (Rapport $r): void {
    if (!defined('PASSWORD_ARGON2ID')) {
        $r->attention('Argon2id', 'indisponible', 'Repli possible sur bcrypt, moins résistant aux attaques par carte graphique.');
        return;
    }
    $t = chrono();
    $h = password_hash('mot de passe de test', PASSWORD_ARGON2ID);
    $duree = chrono() - $t;
    password_verify('mot de passe de test', $h)
        ? $r->ok('Argon2id', 'disponible, ' . ms($duree) . ' par calcul (réglages par défaut)', 'À calibrer : viser environ 100 à 500 ms par connexion.')
        : $r->echec('Argon2id', 'vérification incorrecte');
});

$R->essai('Fonctions de hachage', function (Rapport $r): void {
    $algos = hash_algos();
    foreach (['sha256', 'sha512', 'sha3-256', 'sha3-512'] as $a) {
        in_array($a, $algos, true) ? $r->ok($a, 'disponible') : $r->attention($a, 'absent');
    }
    in_array('sha256', hash_hmac_algos(), true) ? $r->ok('HMAC-SHA-256', 'disponible') : $r->echec('HMAC-SHA-256', 'absent');
});

$R->essai('OpenSSL', function (Rapport $r): void {
    if (!extension_loaded('openssl')) {
        $r->echec('OpenSSL', 'absente');
        return;
    }
    $texte = OPENSSL_VERSION_TEXT;
    preg_match('/OpenSSL (\d+\.\d+\.\d+)/', $texte, $m);
    $version = $m[1] ?? '';
    ($version !== '' && version_compare($version, '3.0.0', '>='))
        ? $r->ok('Version d’OpenSSL', $texte)
        : $r->attention('Version d’OpenSSL', $texte, 'Branche plus maintenue publiquement : ne pas faire reposer la cryptographie du vote sur l’OpenSSL de PHP.');
    $courbes = function_exists('openssl_get_curve_names') ? (openssl_get_curve_names() ?: []) : [];
    $r->info('Courbes P-256 et P-384', oui(in_array('prime256v1', $courbes, true)) . ', ' . oui(in_array('secp384r1', $courbes, true)));
    $r->info('AES-256-GCM (OpenSSL)', oui(in_array('aes-256-gcm', openssl_get_cipher_methods(), true)));
});

$R->essai('GMP', function (Rapport $r): void {
    if (!extension_loaded('gmp')) {
        $r->echec('GMP', 'absente', 'Indispensable à un chiffrement ElGamal en PHP sur cet hébergement.');
        return;
    }
    $r->ok('Version de GMP', GMP_VERSION);
    // Mesure de vitesse des exponentiations modulaires, base du chiffrement ElGamal sur un groupe multiplicatif.
    // Le module est un nombre impair aléatoire de la bonne taille : le temps de calcul est le même
    // qu'avec un vrai groupe, mais ces nombres ne servent qu'à la mesure.
    foreach ([[2048, 256], [3072, 256], [2048, 2048]] as [$bitsModule, $bitsExposant]) {
        // gmp_setbit() modifie le nombre sur place et ne renvoie rien
        $p = gmp_random_bits($bitsModule);
        gmp_setbit($p, $bitsModule - 1);
        gmp_setbit($p, 0);
        $g = gmp_mod(gmp_random_bits($bitsModule), $p);
        $n = 0;
        $t = chrono();
        do {
            gmp_powm($g, gmp_random_bits($bitsExposant), $p);
            $n++;
        } while (chrono() - $t < 0.8);
        $parSeconde = $n / (chrono() - $t);
        $r->info(
            "Exponentiations {$bitsModule} bits, exposant {$bitsExposant} bits",
            nombre($parSeconde) . ' par seconde',
            '10 000 exponentiations : environ ' . nombre(10000 / $parSeconde, 1) . ' s (ordre de grandeur pour 1 000 bulletins à 10 exponentiations chacun).'
        );
    }
});

// -------------------------------------------------------------------
// 4. Base de données
// -------------------------------------------------------------------
if ($avecBdd) {
    $R->section('Base de données MySQL');

    $connexion = static function (): PDO {
        $b = DIAG_BDD;
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $b['hote'], (int) $b['port'], $b['base']);
        return new PDO($dsn, $b['utilisateur'], $b['mot_de_passe'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
    };

    $pdo = null;
    $R->essai('Connexion', function (Rapport $r) use ($connexion, &$pdo): void {
        $t = chrono();
        $pdo = $connexion();
        $r->ok('Connexion', ms(chrono() - $t), 'Hôte : ' . DIAG_BDD['hote'] . ' ; base : ' . DIAG_BDD['base']);
    });

    if ($pdo instanceof PDO) {
        $R->essai('Serveur MySQL', function (Rapport $r) use ($pdo, &$horloges): void {
            $v = $pdo->query('SELECT VERSION() AS v, @@version_comment AS c')->fetch();
            str_starts_with((string) $v['v'], '8.4')
                ? $r->ok('Version', $v['v'] . ' (' . $v['c'] . ')')
                : $r->attention('Version', $v['v'] . ' (' . $v['c'] . ')', 'Le cadrage retient MySQL 8.4.');

            $var = $pdo->query("SELECT @@sql_mode AS sql_mode, @@default_storage_engine AS moteur, @@transaction_isolation AS isolation,
                @@character_set_server AS cs, @@collation_server AS coll, @@character_set_connection AS csc,
                @@global.time_zone AS tz, @@system_time_zone AS tzs, @@max_connections AS maxc, @@max_user_connections AS maxu,
                @@innodb_lock_wait_timeout AS verrou, @@wait_timeout AS attente, @@max_allowed_packet AS paquet,
                @@lower_case_table_names AS casse")->fetch();

            str_contains((string) $var['sql_mode'], 'STRICT_TRANS_TABLES')
                ? $r->ok('Mode SQL strict', 'actif', (string) $var['sql_mode'])
                : $r->attention('Mode SQL strict', 'inactif', 'L’application devra imposer STRICT_ALL_TABLES à chaque connexion. Mode actuel : ' . $var['sql_mode']);
            strtolower((string) $var['moteur']) === 'innodb'
                ? $r->ok('Moteur par défaut', (string) $var['moteur'])
                : $r->attention('Moteur par défaut', (string) $var['moteur'], 'Les tables du vote doivent être en InnoDB.');
            $r->info('Isolation des transactions', (string) $var['isolation']);
            $var['csc'] === 'utf8mb4'
                ? $r->ok('Jeu de caractères de la connexion', 'utf8mb4', 'Serveur : ' . $var['cs'] . ' / ' . $var['coll'])
                : $r->attention('Jeu de caractères de la connexion', (string) $var['csc']);
            $r->info('Fuseau du serveur MySQL', $var['tz'] . ' (système : ' . $var['tzs'] . ')');
            $r->info('Connexions maximales (serveur, par utilisateur)', $var['maxc'] . ', ' . ($var['maxu'] === 0 || $var['maxu'] === '0' ? 'sans limite propre' : $var['maxu']),
                'La limite par utilisateur borne le nombre de votes traités simultanément.');
            $r->info('Attente de verrou, inactivité', $var['verrou'] . ' s, ' . $var['attente'] . ' s');
            $paquet = (int) $var['paquet'];
            $paquet >= 16 * 1024 * 1024
                ? $r->ok('Taille maximale d’une requête', nombre($paquet / 1048576, 0) . ' Mio')
                : $r->attention('Taille maximale d’une requête', nombre($paquet / 1048576, 1) . ' Mio', 'Limite la taille d’un bulletin chiffré ou d’un export.');
            $r->info('Casse des noms de tables', (string) $var['casse']);

            // Horloge : MySQL en UTC contre PHP
            $pdo->exec("SET time_zone = '+00:00'");
            $avant = microtime(true);
            $utc = (string) $pdo->query('SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP(6)) AS t')->fetch()['t'];
            $apres = microtime(true);
            $ecart = (float) $utc - ($avant + $apres) / 2;
            $horloges['MySQL'] = $ecart;
            abs($ecart) <= 1.0
                ? $r->ok('Horloge MySQL contre PHP', sprintf('%+.3f s', $ecart), 'Le passage en UTC par connexion (SET time_zone) fonctionne.')
                : $r->attention('Horloge MySQL contre PHP', sprintf('%+.3f s', $ecart), 'Écart notable : l’heure de clôture doit venir d’une seule source.');
        });

        $R->essai('Droits', function (Rapport $r) use ($pdo): void {
            $droits = $pdo->query('SHOW GRANTS FOR CURRENT_USER()')->fetchAll(PDO::FETCH_COLUMN);
            $texte = implode(' ', array_map('strval', $droits));
            $tous = str_contains($texte, 'ALL PRIVILEGES');
            foreach (['CREATE', 'ALTER', 'INDEX', 'DROP', 'CREATE TEMPORARY TABLES', 'LOCK TABLES', 'TRIGGER', 'CREATE VIEW'] as $d) {
                ($tous || str_contains($texte, $d))
                    ? $r->ok('Droit ' . $d, 'accordé')
                    : $r->info('Droit ' . $d, 'non accordé');
            }
        });

        $R->essai('Latence', function (Rapport $r) use ($pdo): void {
            $n = 200;
            $t = chrono();
            for ($i = 0; $i < $n; $i++) {
                $pdo->query('SELECT 1')->fetchColumn();
            }
            $moyenne = (chrono() - $t) / $n;
            $r->info('Aller-retour moyen (SELECT 1)', ms($moyenne), 'Sur ' . $n . ' requêtes.');
        });

        // Table de test : nom aléatoire, supprimée à la fin quoi qu'il arrive
        $table = 'exu_diag_' . bin2hex(random_bytes(4));
        $R->essai('Table de test', function (Rapport $r) use ($pdo, $connexion, $table): void {
            try {
                $pdo->exec("CREATE TABLE `$table` (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    cle VARCHAR(64) NOT NULL,
                    etat TINYINT NOT NULL DEFAULT 0,
                    donnees JSON NULL,
                    cree_le DATETIME(6) NOT NULL,
                    UNIQUE KEY uniq_cle (cle),
                    CONSTRAINT chk_etat CHECK (etat BETWEEN 0 AND 3)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
                $r->ok('Création d’une table InnoDB utf8mb4_0900', $table);

                $ins = $pdo->prepare("INSERT INTO `$table` (cle, etat, donnees, cree_le) VALUES (?, ?, ?, UTC_TIMESTAMP(6))");
                $ins->execute(['emargement-1', 1, json_encode(['scrutin' => 1])]);

                // Unicité : un second émargement identique doit être refusé
                try {
                    $ins->execute(['emargement-1', 1, null]);
                    $r->echec('Contrainte d’unicité', 'doublon accepté');
                } catch (PDOException $ex) {
                    ($ex->errorInfo[1] ?? 0) === 1062
                        ? $r->ok('Contrainte d’unicité', 'doublon refusé (1062)', 'Base de l’émargement atomique.')
                        : $r->attention('Contrainte d’unicité', 'erreur inattendue ' . ($ex->errorInfo[1] ?? '?'));
                }

                // CHECK
                try {
                    $ins->execute(['controle', 9, null]);
                    $r->echec('Contrainte CHECK', 'valeur interdite acceptée');
                } catch (PDOException $ex) {
                    ($ex->errorInfo[1] ?? 0) === 3819
                        ? $r->ok('Contrainte CHECK', 'valeur interdite refusée (3819)')
                        : $r->attention('Contrainte CHECK', 'erreur inattendue ' . ($ex->errorInfo[1] ?? '?'));
                }

                // JSON
                $json = $pdo->query("SELECT JSON_EXTRACT(donnees, '$.scrutin') AS s FROM `$table` WHERE cle = 'emargement-1'")->fetchColumn();
                (string) $json === '1' ? $r->ok('Colonnes JSON', 'lecture correcte') : $r->attention('Colonnes JSON', 'résultat inattendu');

                // Annulation de transaction
                $pdo->beginTransaction();
                $ins->execute(['annule', 0, null]);
                $pdo->rollBack();
                $existe = (int) $pdo->query("SELECT COUNT(*) FROM `$table` WHERE cle = 'annule'")->fetchColumn();
                $existe === 0 ? $r->ok('Annulation de transaction', 'effective') : $r->echec('Annulation de transaction', 'ligne conservée');

                // Écriture en lot
                $t = chrono();
                $pdo->beginTransaction();
                for ($i = 0; $i < 1000; $i++) {
                    $ins->execute(['lot-' . $i, 0, null]);
                }
                $pdo->commit();
                $r->info('1 000 insertions dans une transaction', ms(chrono() - $t));

                // Verrous de ligne entre deux connexions
                $autre = $connexion();
                $autre->exec('SET SESSION innodb_lock_wait_timeout = 1');
                $pdo->beginTransaction();
                $pdo->query("SELECT id FROM `$table` WHERE cle = 'lot-0' FOR UPDATE")->fetchAll();
                $autre->beginTransaction();
                try {
                    $autre->query("SELECT id FROM `$table` WHERE cle = 'lot-0' FOR UPDATE NOWAIT")->fetchAll();
                    $r->echec('Verrou de ligne entre deux connexions', 'la seconde connexion a obtenu la ligne verrouillée');
                } catch (PDOException $ex) {
                    in_array($ex->errorInfo[1] ?? 0, [3572, 1205], true)
                        ? $r->ok('Verrou de ligne entre deux connexions', 'seconde connexion bloquée (' . $ex->errorInfo[1] . ')', 'Deux dépôts simultanés ne peuvent pas modifier le même émargement.')
                        : $r->attention('Verrou de ligne entre deux connexions', 'erreur inattendue ' . ($ex->errorInfo[1] ?? '?'));
                }
                $libres = $autre->query("SELECT cle FROM `$table` WHERE cle IN ('lot-0', 'lot-1', 'lot-2') FOR UPDATE SKIP LOCKED")->fetchAll(PDO::FETCH_COLUMN);
                (!in_array('lot-0', $libres, true) && count($libres) === 2)
                    ? $r->ok('SKIP LOCKED', 'lignes verrouillées ignorées', 'Utile pour les files de tâches (courriels, activations de procurations).')
                    : $r->attention('SKIP LOCKED', 'résultat inattendu : ' . implode(', ', $libres));
                $autre->rollBack();
                $pdo->rollBack();
                $autre = null;
            } finally {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
                $r->info('Table de test supprimée', $table);
            }
        });
    }
} else {
    $R->section('Base de données MySQL');
    $R->info('Tests MySQL', 'non exécutés', DIAG_BDD['hote'] === '' ? 'Renseigner DIAG_BDD en haut du fichier pour les activer.' : 'Case décochée.');
}

// -------------------------------------------------------------------
// 5. Requête en cours : HTTPS, adresse du client, CDN
// -------------------------------------------------------------------
$R->section('Requête reçue par PHP');

$https = (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off');
$R->info('$_SERVER[HTTPS]', (string) ($_SERVER['HTTPS'] ?? '(absent)'));
$R->info('Port, schéma, protocole', ($_SERVER['SERVER_PORT'] ?? '?') . ', ' . ($_SERVER['REQUEST_SCHEME'] ?? '?') . ', ' . ($_SERVER['SERVER_PROTOCOL'] ?? '?'));
$R->info('X-Forwarded-Proto', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '(absent)'));
($https || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
    ? $R->ok('HTTPS détectable par PHP', 'oui', 'Méthode : ' . ($https ? '$_SERVER[HTTPS]' : 'X-Forwarded-Proto'))
    : $R->attention('HTTPS détectable par PHP', 'non', 'L’application ne pourra pas vérifier seule qu’elle est servie en HTTPS.');
$R->info('Adresse vue par PHP (REMOTE_ADDR)', (string) ($_SERVER['REMOTE_ADDR'] ?? '?'), 'Derrière un CDN, ce peut être l’adresse du CDN et non celle du votant.');
foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_X_CLIENT_IP', 'HTTP_TRUE_CLIENT_IP', 'HTTP_X_OVH_REMOTE_ADDR'] as $h) {
    if (isset($_SERVER[$h])) {
        $R->info(str_replace('_', '-', substr($h, 5)), (string) $_SERVER[$h], 'En-tête falsifiable par le client s’il n’est pas posé par l’infrastructure.');
    }
}
$noms = [];
foreach ($_SERVER as $k => $v) {
    if (str_starts_with((string) $k, 'HTTP_')) {
        $noms[] = str_replace('_', '-', substr((string) $k, 5));
    }
}
sort($noms);
$R->info('En-têtes reçus (noms seulement)', implode(', ', $noms));
$R->info('Logiciel serveur', (string) ($_SERVER['SERVER_SOFTWARE'] ?? '(non communiqué)'));

// -------------------------------------------------------------------
// 6. Contrôle du site public
// -------------------------------------------------------------------
$urlScript = 'https://' . DIAG_DOMAINE . (string) ($_SERVER['SCRIPT_NAME'] ?? '/diagnostic-exu/index.php');

if ($avecReseau && extension_loaded('curl')) {
    $R->section('Site public : redirections et en-têtes');
    $base = 'https://' . DIAG_DOMAINE;

    $R->essai('Page d’accueil', function (Rapport $r) use ($base, &$horloges): void {
        $rep = requete($base . '/');
        if ($rep['statut'] !== 200) {
            $r->echec('Page d’accueil', 'HTTP ' . $rep['statut'], $rep['erreur']);
            return;
        }
        $r->ok('Page d’accueil', 'HTTP 200 en ' . ms($rep['duree']), 'HTTP/' . $rep['version']);
        $attendus = [
            'strict-transport-security' => 'HSTS',
            'content-security-policy'   => 'Politique de sécurité du contenu',
            'x-content-type-options'    => 'nosniff',
            'x-frame-options'           => 'Interdiction d’encadrement',
            'referrer-policy'           => 'Politique de référent',
            'permissions-policy'        => 'Permissions du navigateur',
            'cross-origin-opener-policy' => 'Isolation de la fenêtre',
        ];
        foreach ($attendus as $nom => $role) {
            $v = entete($rep, $nom);
            $v !== null ? $r->ok($nom, $v, $role) : $r->attention($nom, 'absent', $role . ' : vérifier que le .htaccess est pris en compte, y compris à travers le CDN.');
        }
        entete($rep, 'x-powered-by') === null
            ? $r->ok('x-powered-by', 'absent')
            : $r->attention('x-powered-by', (string) entete($rep, 'x-powered-by'), 'Révèle la technologie employée.');
        $r->info('server', entete($rep, 'server') ?? '(absent)');
        $r->info('cache-control', entete($rep, 'cache-control') ?? '(absent)');
        $cdn = [];
        foreach ($rep['entetes'] as $nom => $valeurs) {
            if (preg_match('/cdn|cache|^via$|^age$|^x-iplb|^x-ovh/', $nom)) {
                $cdn[] = $nom . ': ' . implode(' | ', $valeurs);
            }
        }
        $r->info('En-têtes liés au CDN ou au cache', $cdn ? implode(' ; ', $cdn) : '(aucun repéré)');
        $date = entete($rep, 'date');
        if ($date !== null && ($ts = strtotime($date)) !== false) {
            $horloges['Frontal OVH (en-tête Date)'] = $ts - time();
        }
    });

    $controles = [
        ['http://' . DIAG_DOMAINE . '/',             [301], [$base . '/'], 'HTTP renvoie vers HTTPS'],
        ['https://www.' . DIAG_DOMAINE . '/',        [301], [$base . '/'], 'www renvoie vers le domaine nu'],
        [$base . '/index.html',                      [301], ['/', $base . '/'], '/index.html renvoie vers /'],
        [$base . '/.htaccess',                       [403, 404], null,    '.htaccess inaccessible'],
        [$base . '/.ovhconfig',                      [403, 404], null,    '.ovhconfig inaccessible'],
        [$base . '/sauvegarde.sql',                  [403, 404], null,    'Fichiers de sauvegarde inaccessibles'],
        [$base . '/robots.txt',                      [200], null,         'robots.txt servi'],
        [$base . '/sitemap.xml',                     [200], null,         'sitemap.xml servi'],
        [$base . '/.well-known/security.txt',        [200], null,         'security.txt servi'],
        [$base . '/site.webmanifest',                [200], null,         'site.webmanifest servi'],
        [$base . '/favicon.ico',                     [200], null,         'favicon.ico servi'],
        [$base . '/n-existe-pas-' . bin2hex(random_bytes(3)), [404], null, 'Adresse inconnue en 404'],
    ];
    foreach ($controles as [$url, $codes, $destination, $libelle]) {
        $R->essai($libelle, function (Rapport $r) use ($url, $codes, $destination, $libelle): void {
            $rep = requete($url);
            $statut = $rep['statut'];
            $location = entete($rep, 'location');
            $detail = 'HTTP ' . $statut . ($location !== null ? ' → ' . $location : '');
            if ($statut === 0) {
                $r->attention($libelle, 'pas de réponse', $rep['erreur'] . (str_contains($url, '://www.') ? ' (le sous-domaine www existe-t-il dans la zone DNS ?)' : ''));
                return;
            }
            $bonCode = in_array($statut, $codes, true);
            $bonneCible = $destination === null || in_array($location, $destination, true);
            ($bonCode && $bonneCible)
                ? $r->ok($libelle, $detail)
                : $r->attention($libelle, $detail, 'Attendu : HTTP ' . implode(' ou ', $codes) . ($destination !== null ? ' vers ' . implode(' ou ', $destination) : ''));
        });
    }

    $R->essai('Boucle de redirection', function (Rapport $r): void {
        $rep = requete('http://' . DIAG_DOMAINE . '/', 'GET', null, [], true);
        $rep['statut'] === 200
            ? $r->ok('Pas de boucle de redirection HTTP → HTTPS', 'arrivée en HTTP 200 sur ' . $rep['url'])
            : $r->echec('Pas de boucle de redirection HTTP → HTTPS', 'HTTP ' . $rep['statut'], $rep['erreur']);
    });

    $R->essai('Page 404', function (Rapport $r): void {
        $rep = requete('https://' . DIAG_DOMAINE . '/n-existe-pas-' . bin2hex(random_bytes(3)));
        str_contains($rep['corps'], 'Page introuvable')
            ? $r->ok('Page 404 personnalisée', 'servie')
            : $r->attention('Page 404 personnalisée', 'contenu inattendu', 'Vérifier ErrorDocument dans le .htaccess.');
    });

    $R->essai('Cette page derrière le CDN', function (Rapport $r) use ($urlScript): void {
        $rep = requete($urlScript);
        $cc = (string) (entete($rep, 'cache-control') ?? '');
        str_contains($cc, 'no-store')
            ? $r->ok('Pages dynamiques non mises en cache', 'cache-control : ' . $cc, 'Indispensable pour toutes les pages de l’application de vote.')
            : $r->attention('Pages dynamiques non mises en cache', 'cache-control : ' . ($cc ?: '(absent)'), 'Une page personnelle mise en cache par le CDN pourrait être servie à une autre personne.');
        $robots = (string) (entete($rep, 'x-robots-tag') ?? '');
        str_contains($robots, 'noindex') ? $r->ok('Diagnostic non indexable', $robots) : $r->attention('Diagnostic non indexable', $robots ?: '(absent)');
    });

    // ---------------------------------------------------------------
    // 7. Pare-feu applicatif OVH
    // ---------------------------------------------------------------
    $R->section('Pare-feu applicatif (http.firewall=security)');
    $R->info('Méthode', 'Le script envoie des requêtes POST à lui-même et vérifie qu’elles arrivent intactes.');

    $choix = static fn (int $octets): string => bin2hex(random_bytes($octets));
    $bulletin = [
        'scrutin'  => 'b6f1c0de-0000-4000-8000-000000000001',
        'version'  => 1,
        'reponses' => array_map(static fn (): array => [
            'chiffres' => array_map(static fn (): array => ['alpha' => bin2hex(random_bytes(256)), 'beta' => bin2hex(random_bytes(256))], range(1, 5)),
            'preuves'  => array_map(static fn (): array => ['defi' => bin2hex(random_bytes(32)), 'reponse' => bin2hex(random_bytes(32))], range(1, 10)),
        ], range(1, 3)),
        'signature' => base64_encode(random_bytes(64)),
    ];
    $charges = [
        ['Formulaire simple', 'form', 'Bonjour, ceci est un test.'],
        ['Bulletin chiffré simulé, 8 Kio (base64)', 'form', base64_encode(random_bytes(6 * 1024))],
        ['Bulletin chiffré simulé, 64 Kio (base64)', 'form', base64_encode(random_bytes(48 * 1024))],
        ['Bulletin chiffré simulé, JSON hexadécimal', 'json', (string) json_encode($bulletin)],
        ['Charge de 1 Mio (base64)', 'form', base64_encode(random_bytes(768 * 1024))],
        ['Texte de consultation avec mots SQL', 'form', "Proposition : SELECT des projets retenus ; UNION des listes ; DROP de la motion 3 ; l'avis du bureau (1=1)."],
        ['Texte avec guillemets et chevrons', 'form', 'Amendement « 2 » : remplacer <article 4> par "article 4 bis" & supprimer l\'alinéa.'],
        ['Code actif (doit être refusé ici ou neutralisé par l’application)', 'form', '<script>alert(document.cookie)</script>'],
    ];
    foreach ($charges as [$libelle, $type, $charge]) {
        $R->essai($libelle, function (Rapport $r) use ($libelle, $type, $charge, $urlScript): void {
            if ($type === 'json') {
                $rep = requete($urlScript . '?echo=1', 'POST', $charge, ['Content-Type: application/json', 'X-Diag-Cle: ' . DIAG_CLE], false, 30);
            } else {
                $rep = requete($urlScript . '?echo=1', 'POST', http_build_query(['cle' => DIAG_CLE, 'charge' => $charge]), ['Content-Type: application/x-www-form-urlencoded'], false, 30);
            }
            $attendu = 'ECHO-OK:' . strlen($charge) . ':' . hash('sha256', $charge);
            $taille = nombre(strlen($charge) / 1024, 1) . ' Kio';
            $estCodeActif = str_contains($charge, '<script>');
            if ($rep['statut'] === 200 && trim($rep['corps']) === $attendu) {
                $estCodeActif
                    ? $r->info($libelle, 'transmis intact (' . $taille . ')', 'Le pare-feu laisse passer : l’application devra encoder toutes ses sorties.')
                    : $r->ok($libelle, 'transmis intact (' . $taille . ', ' . ms($rep['duree']) . ')');
            } elseif ($rep['statut'] === 200) {
                $r->attention($libelle, 'reçu mais modifié', 'Le contenu a été altéré en chemin.');
            } else {
                $estCodeActif
                    ? $r->ok($libelle, 'bloqué (HTTP ' . $rep['statut'] . ')')
                    : $r->attention($libelle, 'bloqué (HTTP ' . $rep['statut'] . ')', 'Faux positif du pare-feu ou limite de taille : à prendre en compte dans le format des bulletins. ' . $rep['erreur']);
            }
        });
    }

    // ---------------------------------------------------------------
    // 8. Réseau sortant et services
    // ---------------------------------------------------------------
    $R->section('Réseau sortant et services');

    $R->essai('SMTP Proton', function (Rapport $r): void {
        $ips = gethostbynamel('smtp.protonmail.ch') ?: [];
        $r->info('Résolution DNS de smtp.protonmail.ch', $ips ? implode(', ', $ips) : 'échec');
        foreach ([['tcp://smtp.protonmail.ch', 587, 'STARTTLS'], ['ssl://smtp.protonmail.ch', 465, 'TLS direct']] as [$hote, $port, $mode]) {
            $t = chrono();
            $f = @fsockopen($hote, $port, $code, $message, 6);
            if ($f === false) {
                $r->attention("Connexion sortante SMTP $port ($mode)", 'refusée ou expirée', "$message ($code). OVH peut filtrer le SMTP sortant.");
                continue;
            }
            stream_set_timeout($f, 6);
            $banniere = trim((string) fgets($f, 512));
            fwrite($f, "QUIT\r\n");
            fclose($f);
            str_starts_with($banniere, '220')
                ? $r->ok("Connexion sortante SMTP $port ($mode)", 'ouverte en ' . ms(chrono() - $t), mb_substr($banniere, 0, 80))
                : $r->attention("Connexion sortante SMTP $port ($mode)", 'réponse inattendue', mb_substr($banniere, 0, 80));
        }
        $r->info('Fonction mail()', fonctionDisponible('mail') ? 'disponible' : 'indisponible', 'Le cadrage retient l’envoi par Proton (SMTP avec jeton).');
    });

    $R->essai('HTTPS sortant', function (Rapport $r) use (&$horloges): void {
        $rep = requete('https://api.github.com/', 'GET', null, ['Accept: application/vnd.github+json'], false, 10);
        $rep['statut'] > 0
            ? $r->ok('HTTPS sortant (api.github.com)', 'HTTP ' . $rep['statut'] . ' en ' . ms($rep['duree']))
            : $r->attention('HTTPS sortant (api.github.com)', 'échec', $rep['erreur']);
        $date = entete($rep, 'date');
        if ($date !== null && ($ts = strtotime($date)) !== false) {
            $horloges['GitHub (en-tête Date)'] = $ts - time();
        }
    });

    $R->essai('Services de cache', function (Rapport $r): void {
        foreach ([['127.0.0.1', 6379, 'Redis'], ['127.0.0.1', 11211, 'Memcached']] as [$h, $p, $nom]) {
            $f = @fsockopen($h, $p, $c, $m, 1);
            $r->info("Serveur $nom local", $f !== false ? 'joignable' : 'absent', 'Sans serveur local, les sessions et verrous restent en fichiers ou en base.');
            if ($f !== false) {
                fclose($f);
            }
        }
    });
} else {
    $R->section('Site public, pare-feu et réseau');
    $R->info('Tests réseau', 'non exécutés', extension_loaded('curl') ? 'Case décochée.' : 'Extension curl absente.');
}

// -------------------------------------------------------------------
// 9. Horloges
// -------------------------------------------------------------------
$R->section('Horloges');
$R->info('Heure PHP (UTC)', gmdate('Y-m-d H:i:s') . ' UTC');
$R->info('Résolution du chronomètre', 'hrtime, nanosecondes');
if ($horloges === []) {
    $R->info('Comparaisons', 'aucune source extérieure interrogée');
}
foreach ($horloges as $source => $ecart) {
    abs($ecart) <= 2
        ? $R->ok('Écart avec ' . $source, sprintf('%+.1f s', $ecart))
        : $R->attention('Écart avec ' . $source, sprintf('%+.1f s', $ecart), 'L’heure du serveur fait autorité pour la clôture : un écart doit être compris avant tout scrutin.');
}

// -------------------------------------------------------------------
// 10. Système (lecture seule)
// -------------------------------------------------------------------
$R->section('Système et outils');
$R->essai('Processeur', function (Rapport $r): void {
    $cpu = @file_get_contents('/proc/cpuinfo');
    if (is_string($cpu)) {
        preg_match_all('/^model name\s*:\s*(.+)$/m', $cpu, $m);
        $r->info('Processeur', ($m[1][0] ?? '?') . ' (' . count($m[1]) . ' cœurs visibles)', 'Ressources partagées avec les autres sites de la machine.');
    } else {
        $r->info('Processeur', '(non lisible)');
    }
});
$execPossible = fonctionDisponible('exec');
$R->info('exec / proc_open', oui($execPossible) . ' / ' . oui(fonctionDisponible('proc_open')), 'Permettrait d’appeler un outil externe de vérification (par exemple l’outil en ligne de commande de Belenios).');
if ($avecSysteme && $execPossible) {
    $commandes = [
        'nproc'                                  => 'Cœurs alloués',
        'uname -a'                               => 'Noyau',
        'openssl version'                        => 'OpenSSL (ligne de commande)',
        'git --version'                          => 'git',
        'command -v composer'                    => 'Composer',
        'command -v ocaml opam belenios-tool'    => 'OCaml, opam, belenios-tool',
        'ulimit -t -v 2>/dev/null | tr "\n" " "' => 'Limites de processus (temps, mémoire virtuelle)',
    ];
    foreach ($commandes as $commande => $libelle) {
        $sortie = [];
        $code = 0;
        @exec($commande . ' 2>/dev/null', $sortie, $code);
        $R->info($libelle, $sortie ? mb_substr(implode(' ', $sortie), 0, 200) : '(absent ou aucune sortie)', '$ ' . $commande);
    }
} elseif ($avecSysteme) {
    $R->info('Commandes système', 'non exécutées', 'exec indisponible.');
}

$duree = chrono() - $debut;

// ===================================================================
// SORTIE
// ===================================================================

$etiquettes = ['ok' => 'OK', 'attention' => 'ATTENTION', 'echec' => 'ÉCHEC', 'info' => 'INFO'];
$totaux = $R->totaux();

$texte = "EXU · diagnostic de l'hébergement · version " . DIAG_VERSION . "\n"
    . 'Date : ' . gmdate('Y-m-d H:i:s') . " UTC · durée : " . nombre($duree, 1) . " s · domaine : " . DIAG_DOMAINE . "\n"
    . sprintf("Bilan : %d OK, %d attention, %d échec, %d info\n", $totaux['ok'], $totaux['attention'], $totaux['echec'], $totaux['info']);
foreach ($R->sections as $titre => $lignes) {
    $texte .= "\n== " . $titre . " ==\n";
    foreach ($lignes as $l) {
        $texte .= sprintf('[%s] %s : %s', $etiquettes[$l['statut']], $l['libelle'], $l['valeur'])
            . ($l['note'] !== '' ? ' (' . $l['note'] . ')' : '') . "\n";
    }
}

if (($_POST['format'] ?? 'html') === 'txt') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="exu-diagnostic-' . gmdate('Ymd-His') . '.txt"');
    echo $texte;
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Rapport de diagnostic · EXU</title>
<link rel="stylesheet" href="diagnostic.css?v=<?= e(DIAG_VERSION) ?>">
</head>
<body>
<main class="carte">
  <p class="marque"><img src="/logo-exu.png" alt="" width="324" height="218"> <span>EXU · diagnostic</span></p>
  <h1>Rapport de diagnostic</h1>
  <p><?= e(gmdate('d/m/Y H:i:s')) ?> UTC · durée <?= e(nombre($duree, 1)) ?> s · version <?= e(DIAG_VERSION) ?></p>

  <ul class="bilan" aria-label="Bilan">
    <li class="s-ok"><strong><?= $totaux['ok'] ?></strong> OK</li>
    <li class="s-attention"><strong><?= $totaux['attention'] ?></strong> attention</li>
    <li class="s-echec"><strong><?= $totaux['echec'] ?></strong> échec</li>
    <li class="s-info"><strong><?= $totaux['info'] ?></strong> info</li>
  </ul>

  <p class="alerte">Supprimez le dossier <code>diagnostic-exu/</code> du serveur après avoir récupéré ce rapport.</p>

<?php foreach ($R->sections as $titre => $lignes): ?>
  <section>
    <h2><?= e($titre) ?></h2>
    <table>
      <thead><tr><th scope="col">État</th><th scope="col">Contrôle</th><th scope="col">Résultat</th></tr></thead>
      <tbody>
<?php foreach ($lignes as $l): ?>
        <tr class="s-<?= e($l['statut']) ?>">
          <td><span class="pastille"><?= e($etiquettes[$l['statut']]) ?></span></td>
          <th scope="row"><?= e($l['libelle']) ?></th>
          <td><span class="valeur"><?= e($l['valeur']) ?></span><?php if ($l['note'] !== ''): ?><br><span class="note"><?= e($l['note']) ?></span><?php endif; ?></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </section>
<?php endforeach; ?>

  <section>
    <h2>Rapport en texte</h2>
    <p class="petit">À sélectionner et copier pour le transmettre. Il ne contient ni la clé d’accès ni le mot de passe de la base.</p>
    <label for="texte" class="masque">Rapport en texte</label>
    <textarea id="texte" readonly rows="16"><?= e($texte) ?></textarea>
  </section>
</main>
</body>
</html>
