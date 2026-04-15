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
    #[Route('/api/tannus-chat', name: 'tannus_chat', methods: ['POST'])]
    public function chat(
        Request $request,
        PerplexityApiService $perplexity,
        OpenRouterApiService $openRouter,
        LoggerInterface $logger,
    ): JsonResponse {
        $body = json_decode($request->getContent(), true);
        $message = trim((string) ($body['message'] ?? ''));

        if ($message === '') {
            return $this->json(['error' => 'message is required', 'reply' => ''], 400);
        }

        if (mb_strlen($message) > 2000) {
            return $this->json(['error' => 'message too long', 'reply' => ''], 400);
        }

        try {
            $reply = $perplexity->chat($message);
            $logger->info('TannusChat: Perplexity responded', ['chars' => mb_strlen($reply)]);

            return $this->json(['reply' => $reply]);
        } catch (\Throwable $e) {
            $logger->warning('TannusChat: Perplexity failed, trying OpenRouter fallback', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $reply = $openRouter->chat($message);
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
