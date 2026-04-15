<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PerplexityApiService
{
    private const API_URL = 'https://api.perplexity.ai/v1/sonar';
    private const DEFAULT_MODEL = 'sonar';
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

    /**
     * Send a chat request to the Perplexity API.
     *
     * @param array<int, array{role: string, content: string}> $messages
     *   Conversation history in OpenAI messages format (role: user|assistant).
     *   The system prompt is prepended automatically — do not include it here.
     * @param string $model Perplexity model identifier
     *
     * @return string The assistant reply text
     */
    public function chat(array $messages, string $model = self::DEFAULT_MODEL): string
    {
        $apiKey = $_SERVER['PERPLEXITY_API_KEY'] ?? getenv('PERPLEXITY_API_KEY') ?? '';

        if (empty($apiKey)) {
            throw new \RuntimeException('PERPLEXITY_API_KEY not configured');
        }

        $payload = array_merge(
            [['role' => 'system', 'content' => self::SYSTEM_PROMPT]],
            $messages,
        );

        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'model' => $model,
                'messages' => $payload,
                'max_tokens' => 512,
                'temperature' => 0.7,
            ],
            'timeout' => 30,
        ]);

        $data = $response->toArray();

        $reply = $data['choices'][0]['message']['content'] ?? null;

        if ($reply === null) {
            $this->logger->error('PerplexityApiService: unexpected response structure', ['data' => $data]);
            throw new \RuntimeException('Unexpected response from Perplexity API');
        }

        return trim($reply);
    }
}
