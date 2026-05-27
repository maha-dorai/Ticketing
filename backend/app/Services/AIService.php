<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $model  = 'claude-sonnet-4-20250514';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', '');
    }

    /**
     * Analyse un ticket et retourne : categorie_ia, priorite_ia, solution_ia
     */
    public function analyzeTicket(string $titre, string $description = ''): array
    {
        // Résultat par défaut si l'IA est indisponible
        $default = [
            'categorie_ia' => 'NON_CLASSE',
            'priorite_ia'  => null,
            'solution_ia'  => null,
        ];

        if (empty($this->apiKey)) {
            Log::warning('AIService: ANTHROPIC_API_KEY manquant dans .env');
            return $default;
        }

        $prompt = $this->buildPrompt($titre, $description);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'x-api-key'         => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])
                ->post($this->apiUrl, [
                    'model'      => $this->model,
                    'max_tokens' => 500,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('AIService: Erreur API', [
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                    'headers' => $response->headers(),
                ]);
                return $default;
            }

            $text = $response->json('content.0.text', '');
            Log::info('AIService: Réponse reçue', ['text' => $text]);
            return $this->parseResponse($text, $default);

        } catch (\Exception $e) {
            Log::error('AIService: Exception', ['message' => $e->getMessage()]);
            return $default;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function buildPrompt(string $titre, string $description): string
    {
        $desc = trim($description) ?: 'Aucune description fournie.';

        return <<<PROMPT
Tu es un assistant expert en support technique pour une équipe de développement logiciel.

Analyse ce ticket de bug et réponds UNIQUEMENT en JSON valide, sans aucun texte avant ou après.

Titre du ticket : {$titre}
Description : {$desc}

Retourne exactement ce format JSON :
{
  "categorie_ia": "<une seule valeur parmi : BUG, PERFORMANCE, SECURITE, UI_UX, BASE_DE_DONNEES, API, CONFIGURATION, AUTRE>",
  "priorite_ia": "<une seule valeur parmi : BASSE, MOYENNE, HAUTE, CRITIQUE>",
  "solution_ia": "<suggestion de solution courte en français, 2-3 phrases maximum>"
}

Règles de priorité :
- CRITIQUE : site down, perte de données, faille sécurité grave
- HAUTE : fonctionnalité bloquée, erreur fréquente
- MOYENNE : bug mineur, gêne l'utilisateur mais contournable
- BASSE : cosmétique, amélioration, question
PROMPT;
    }

    private function parseResponse(string $text, array $default): array
    {
        // Nettoyer les backticks markdown si présents
        $text = preg_replace('/```json|```/', '', $text);
        $text = trim($text);

        $data = json_decode($text, true);

        if (!is_array($data)) {
            Log::warning('AIService: Réponse JSON invalide', ['text' => $text]);
            return $default;
        }

        $categories = ['BUG', 'PERFORMANCE', 'SECURITE', 'UI_UX', 'BASE_DE_DONNEES', 'API', 'CONFIGURATION', 'AUTRE'];
        $priorites  = ['BASSE', 'MOYENNE', 'HAUTE', 'CRITIQUE'];

        return [
            'categorie_ia' => in_array($data['categorie_ia'] ?? '', $categories)
                ? $data['categorie_ia']
                : 'AUTRE',

            'priorite_ia'  => in_array($data['priorite_ia'] ?? '', $priorites)
                ? $data['priorite_ia']
                : null,

            'solution_ia'  => isset($data['solution_ia']) && is_string($data['solution_ia'])
                ? substr(trim($data['solution_ia']), 0, 1000)
                : null,
        ];
    }
}
