<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * OpenRouter API fallback for Tannus chat.
 *
 * Recommended free models (configurable via OPENROUTER_MODEL env var):
 *   - meta-llama/llama-3.1-8b-instruct:free  (default)
 *   - microsoft/phi-3-mini-128k-instruct:free
 *   - google/gemma-3-4b-it:free
 *
 * To change the model, set OPENROUTER_MODEL in Replit Secrets or .env.local.
 */
class OpenRouterApiService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const DEFAULT_MODEL = 'meta-llama/llama-3.1-8b-instruct:free';
    private const SYSTEM_PROMPT = <<<'PROMPT'
Você é o assistente virtual da Tannus IA, uma plataforma de inteligência artificial integrada ao Chamilo LMS para educação corporativa e institucional.

Seu papel é responder dúvidas sobre:
- A Tannus IA e suas capacidades (aprendizado adaptativo, geração de conteúdo, análise preditiva, assistente virtual 24/7)
- O Mega Sistema educacional baseado em Chamilo LMS
- Plano de negócios: como a Tannus IA transforma a experiência de aprendizagem
- Como começar: acesso à plataforma, criação de cursos, acompanhamento de resultados
- Integrações, sessões de aprendizagem, certificados e métricas

Seja conciso, direto e profissional. Responda sempre em português do Brasil. Se não souber algo específico sobre a empresa ou produto, diga que pode conectar o usuário com a equipe Tannus.
PROMPT;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {}

    public function chat(string $message): string
    {
        $apiKey = $_SERVER['OPENROUTER_API_KEY'] ?? getenv('OPENROUTER_API_KEY') ?? '';

        if (empty($apiKey)) {
            throw new \RuntimeException('OPENROUTER_API_KEY not configured');
        }

        $model = $_SERVER['OPENROUTER_MODEL'] ?? getenv('OPENROUTER_MODEL') ?: self::DEFAULT_MODEL;

        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'HTTP-Referer' => 'https://tannus.ia',
                'X-Title' => 'Tannus IA Chat',
            ],
            'json' => [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => $message],
                ],
                'max_tokens' => 512,
                'temperature' => 0.7,
            ],
            'timeout' => 30,
        ]);

        $data = $response->toArray();

        $reply = $data['choices'][0]['message']['content'] ?? null;

        if ($reply === null) {
            $this->logger->error('OpenRouterApiService: unexpected response structure', ['data' => $data]);
            throw new \RuntimeException('Unexpected response from OpenRouter API');
        }

        return trim($reply);
    }
}
