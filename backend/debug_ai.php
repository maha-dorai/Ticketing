<?php
/**
 * Debug complet de l'AIService — étape par étape
 * Usage : php debug_ai.php
 */

require __DIR__ . '/vendor/autoload.php';
$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "========================================" . PHP_EOL;
echo "  DEBUG AIService — étape par étape" . PHP_EOL;
echo "========================================" . PHP_EOL . PHP_EOL;

// ── ÉTAPE 1 : La clé API est-elle lue depuis .env ? ──────────────────────────
echo "[ ÉTAPE 1 ] Lecture de la clé API..." . PHP_EOL;

$apiKey = config('services.anthropic.api_key', '');

if (empty($apiKey)) {
    echo "  ❌ ÉCHEC — clé vide." . PHP_EOL;
    echo "     Causes possibles :" . PHP_EOL;
    echo "       1. ANTHROPIC_API_KEY absent du fichier .env" . PHP_EOL;
    echo "       2. 'anthropic' absent de config/services.php" . PHP_EOL;
    echo "       3. Cache de config pas vidé → lancez : php artisan config:clear" . PHP_EOL . PHP_EOL;
    echo "     Vérification directe depuis .env :" . PHP_EOL;
    $envLine = shell_exec("grep ANTHROPIC_API_KEY " . __DIR__ . "/.env 2>/dev/null");
    echo "       " . ($envLine ? trim($envLine) : "→ ligne ANTHROPIC_API_KEY introuvable dans .env") . PHP_EOL;
    exit(1);
} else {
    $masked = substr($apiKey, 0, 10) . '...' . substr($apiKey, -4);
    echo "  ✅ Clé trouvée : " . $masked . PHP_EOL . PHP_EOL;
}

// ── ÉTAPE 2 : L'appel HTTP vers l'API Anthropic fonctionne-t-il ? ─────────────
echo "[ ÉTAPE 2 ] Appel direct à l'API Anthropic..." . PHP_EOL;

$response = Illuminate\Support\Facades\Http::timeout(15)
    ->withHeaders([
        'x-api-key'         => $apiKey,
        'anthropic-version' => '2023-06-01',
        'content-type'      => 'application/json',
    ])
    ->post('https://api.anthropic.com/v1/messages', [
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 500,
        'messages'   => [[
            'role'    => 'user',
            'content' => 'Réponds uniquement avec ce JSON exact, sans rien d\'autre : {"categorie_ia":"BUG","priorite_ia":"HAUTE","solution_ia":"Test OK"}',
        ]],
    ]);

echo "  Status HTTP : " . $response->status() . PHP_EOL;

if ($response->failed()) {
    echo "  ❌ ÉCHEC — l'API a rejeté la requête." . PHP_EOL;
    echo "  Réponse complète :" . PHP_EOL;
    echo "  " . $response->body() . PHP_EOL . PHP_EOL;

    $status = $response->status();
    if ($status === 401) echo "  → Clé API invalide ou expirée." . PHP_EOL;
    if ($status === 403) echo "  → Accès refusé (quota dépassé ?)." . PHP_EOL;
    if ($status === 429) echo "  → Rate limit atteint, réessayez dans quelques secondes." . PHP_EOL;
    if ($status === 0)   echo "  → Pas de connexion réseau vers api.anthropic.com." . PHP_EOL;
    exit(1);
}

echo "  ✅ Appel réussi." . PHP_EOL . PHP_EOL;

// ── ÉTAPE 3 : La réponse JSON est-elle bien parsée ? ──────────────────────────
echo "[ ÉTAPE 3 ] Parsing de la réponse..." . PHP_EOL;

$rawText = $response->json('content.0.text', '');
echo "  Texte brut reçu : " . $rawText . PHP_EOL;

$text = preg_replace('/```json|```/', '', $rawText);
$text = trim($text);
$data = json_decode($text, true);

if (!is_array($data)) {
    echo "  ❌ ÉCHEC — le JSON est invalide." . PHP_EOL;
    echo "  json_last_error : " . json_last_error_msg() . PHP_EOL;
    exit(1);
}

echo "  ✅ JSON parsé : " . json_encode($data) . PHP_EOL . PHP_EOL;

// ── ÉTAPE 4 : L'AIService complet retourne-t-il les bonnes valeurs ? ──────────
echo "[ ÉTAPE 4 ] Test de l'AIService complet..." . PHP_EOL;

$aiService = new App\Services\AIService();
$result    = $aiService->analyzeTicket(
    'Le site est complètement inaccessible',
    'Depuis ce matin, aucun utilisateur ne peut se connecter. La page d\'accueil retourne une erreur 500.'
);

echo "  categorie_ia : " . var_export($result['categorie_ia'], true) . PHP_EOL;
echo "  priorite_ia  : " . var_export($result['priorite_ia'],  true) . PHP_EOL;
echo "  solution_ia  : " . var_export($result['solution_ia'],  true) . PHP_EOL . PHP_EOL;

$aiOk = $result['categorie_ia'] !== 'NON_CLASSE'
     && $result['priorite_ia']  !== null
     && $result['solution_ia']  !== null;

if (!$aiOk) {
    echo "  ❌ L'AIService retourne encore les valeurs par défaut." . PHP_EOL;
    echo "  Consultez les logs Laravel : storage/logs/laravel.log" . PHP_EOL;
    exit(1);
}

echo "  ✅ AIService fonctionne correctement." . PHP_EOL . PHP_EOL;

// ── ÉTAPE 5 : Le $fillable sauvegarde-t-il bien en base ? ─────────────────────
echo "[ ÉTAPE 5 ] Test sauvegarde en base (fillable)..." . PHP_EOL;

$admin = App\Models\User::where('role', 'admin')->where('statut', 'actif')->first();
if (!$admin) {
    echo "  ⚠️  Pas d'admin actif trouvé, étape ignorée." . PHP_EOL;
} else {
    $project = App\Models\Project::first();
    if (!$project) {
        echo "  ⚠️  Pas de projet trouvé, étape ignorée." . PHP_EOL;
    } else {
        $ticket = App\Models\Ticket::create([
            'titre'      => 'Debug test ticket',
            'etat'       => 'OUVERT',
            'priorite'   => 'BASSE',
            'project_id' => $project->id,
            'testeur_id' => $admin->id,
        ]);

        $ticket->update([
            'categorie_ia' => $result['categorie_ia'],
            'priorite_ia'  => $result['priorite_ia'],
            'solution_ia'  => $result['solution_ia'],
        ]);

        $fresh = App\Models\Ticket::find($ticket->id);

        $saved = $fresh->categorie_ia !== null
              && $fresh->priorite_ia  !== null
              && $fresh->solution_ia  !== null;

        if ($saved) {
            echo "  ✅ Sauvegarde OK — champs bien présents en base." . PHP_EOL;
        } else {
            echo "  ❌ ÉCHEC — champs toujours NULL après update()." . PHP_EOL;
            echo "  → categorie_ia, priorite_ia, solution_ia manquent dans \$fillable de Ticket.php" . PHP_EOL;
        }

        $ticket->delete();
    }
}

echo PHP_EOL . "========================================" . PHP_EOL;
echo "  DIAGNOSTIC TERMINÉ" . PHP_EOL;
echo "========================================" . PHP_EOL;
