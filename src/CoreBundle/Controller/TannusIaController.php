<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TannusIaController extends BaseController
{
    #[Route('/TannusAI', name: 'tannus_ai', methods: ['GET'])]
    #[Route('/TannusIA', name: 'tannus_ia', methods: ['GET'])]
    public function view(Connection $connection, LoggerInterface $logger): Response
    {
        $stats = [
            'stat_courses' => 0,
            'stat_users' => 0,
            'stat_certificates' => 0,
            'stat_sessions' => 0,
        ];

        $queries = [
            'stat_courses' => 'SELECT COUNT(*) FROM `course`',
            'stat_users' => 'SELECT COUNT(*) FROM `user`',
            'stat_certificates' => 'SELECT COUNT(*) FROM `gradebook_certificate`',
            'stat_sessions' => 'SELECT COUNT(*) FROM `session`',
        ];

        foreach ($queries as $key => $sql) {
            try {
                $stats[$key] = (int) $connection->fetchOne($sql);
            } catch (\Throwable $e) {
                $logger->warning('TannusIA: failed to fetch {key}: {msg}', [
                    'key' => $key,
                    'msg' => $e->getMessage(),
                ]);
            }
        }

        return $this->render('@ChamiloCore/TannusIa/view.html.twig', $stats);
    }
}
