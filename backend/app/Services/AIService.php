<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private string $model  = 'llama-3.3-70b-versatile'; // Modèle IA extrêmement performant de Meta

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key', '');
    }

    /**
     * [Sprint 4] Analyse du ticket par l'Intelligence Artificielle.
     * Cette fonction prend le titre et la description d'un bug, et envoie un prompt à l'API Llama 3 via Groq.
     * Le but est de catégoriser le bug et suggérer une solution sans intervention humaine.
     */
    public function analyzeTicket(string $titre, string $description = ''): array
    {
        // Structure de secours si l'IA plante
        $default = [
            'categorie_ia' => 'NON_CLASSE',
            'priorite_ia'  => null,
            'solution_ia'  => null,
        ];

        if (empty($this->apiKey)) {
            Log::warning('AIService: GROQ_API_KEY manquant dans .env');
            return $default;
        }

        // 1. Construit le Prompt (ingénierie du prompt)
        $prompt = $this->buildPrompt($titre, $description);

        try {
            // 2. Requête HTTP vers Groq (très rapide)
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ])
                ->post($this->apiUrl, [
                    'model'       => $this->model,
                    'max_tokens'  => 500,
                    'temperature' => 0.2, // Faible température pour éviter les hallucinations et avoir un JSON strict
                    'messages'    => [
                        [
                            'role'    => 'system',
                            'content' => 'Tu es un assistant expert en support technique. Tu réponds UNIQUEMENT en JSON valide, sans aucun texte avant ou après.',
                        ],
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            if ($response->failed()) {
                return $default;
            }

            // 3. Extraction de la réponse textuelle
            $text = $response->json('choices.0.message.content', '');
            
            // 4. Parsing et validation du JSON retourné
            return $this->parseResponse($text, $default);

        } catch (\Exception $e) {
            Log::error('AIService: Exception', ['message' => $e->getMessage()]);
            return $default;
        }
    }

    /**
     * [Sprint 4] Ingénierie du Prompt envoyée à l'IA
     */
    private function buildPrompt(string $titre, string $description): string
    {
        $desc = trim($description) ?: 'Aucune description fournie.';

        return <<<PROMPT
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

    /**
     * [Sprint 4] Nettoyage et validation de la réponse de l'IA.
     * Les IA ont souvent tendance à entourer leur JSON de ```json ... ```. Ce script nettoie tout ça.
     */
    private function parseResponse(string $text, array $default): array
    {
        $text = preg_replace('/```json|```/', '', $text); // Nettoyage du markdown
        $text = trim($text);

        $data = json_decode($text, true);

        if (!is_array($data)) {
            Log::warning('AIService: Réponse JSON invalide', ['text' => $text]);
            return $default;
        }

        // Vérification stricte contre les hallucinations (Enum validation)
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