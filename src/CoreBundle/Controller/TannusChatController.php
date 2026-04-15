<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Service\OpenRouterApiService;
use Chamilo\CoreBundle\Service\PerplexityApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class TannusChatController extends BaseController
{
    private const MAX_MESSAGES = 40;
    private const MAX_CONTENT_LENGTH = 2000;

    #[Route('/api/tannus-chat', name: 'tannus_chat', methods: ['POST'])]
    public function chat(
        Request $request,
        PerplexityApiService $perplexity,
        OpenRouterApiService $openRouter,
        LoggerInterface $logger,
    ): JsonResponse {
        $body = json_decode($request->getContent(), true);

        $messages = $body['messages'] ?? null;

        if (!is_array($messages) || count($messages) === 0) {
            return $this->json(['error' => 'messages array is required', 'reply' => ''], 400);
        }

        if (count($messages) > self::MAX_MESSAGES) {
            return $this->json(['error' => 'too many messages in history', 'reply' => ''], 400);
        }

        $validated = [];
        foreach ($messages as $msg) {
            if (!is_array($msg)) {
                return $this->json(['error' => 'each message must be an object', 'reply' => ''], 400);
            }

            $role = $msg['role'] ?? '';
            $content = $msg['content'] ?? '';

            if (!in_array($role, ['user', 'assistant'], true)) {
                return $this->json(['error' => 'message role must be user or assistant', 'reply' => ''], 400);
            }

            if (!is_string($content) || trim($content) === '') {
                return $this->json(['error' => 'message content must be a non-empty string', 'reply' => ''], 400);
            }

            if (mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
                return $this->json(['error' => 'message content too long', 'reply' => ''], 400);
            }

            $validated[] = ['role' => $role, 'content' => trim($content)];
        }

        $lastMessage = $validated[count($validated) - 1];
        if ($lastMessage['role'] !== 'user') {
            return $this->json(['error' => 'last message must be from user', 'reply' => ''], 400);
        }

        try {
            $reply = $perplexity->chat($validated);
            $logger->info('TannusChat: Perplexity responded', ['chars' => mb_strlen($reply)]);

            return $this->json(['reply' => $reply]);
        } catch (\Throwable $e) {
            $logger->warning('TannusChat: Perplexity failed, trying OpenRouter fallback', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $reply = $openRouter->chat($validated);
            $logger->info('TannusChat: OpenRouter responded', ['chars' => mb_strlen($reply)]);

            return $this->json(['reply' => $reply]);
        } catch (\Throwable $e) {
            $logger->error('TannusChat: both APIs failed', ['error' => $e->getMessage()]);

            return $this->json([
                'error' => 'service_unavailable',
                'reply' => 'Desculpe, o assistente está temporariamente indisponível. Por favor, tente novamente em alguns instantes ou entre em contato com a equipe Tannus.',
            ], 503);
        }
    }
}
