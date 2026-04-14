<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Service\DocxConverterService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TannusIaController extends BaseController
{
    #[Route('/TannusIA', name: 'tannus_ia', methods: ['GET'])]
    public function view(DocxConverterService $converter): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');

        $docxPaths = [
            $projectDir . '/var/uploads/documents/TANNUS_-_PLANO_DE_NEGÓCIOS_1776200764234.docx',
            $projectDir . '/public/uploads/documents/TANNUS_-_PLANO_DE_NEGÓCIOS_1776200764234.docx',
            $projectDir . '/attached_assets/TANNUS_-_PLANO_DE_NEGÓCIOS_1776200764234.docx',
        ];

        $docxPath = null;
        foreach ($docxPaths as $path) {
            if (file_exists($path)) {
                $docxPath = $path;
                break;
            }
        }

        if (!$docxPath) {
            return new Response(
                $this->renderView('tannus_ia/error.html.twig', [
                    'message' => 'Documento não encontrado. O arquivo .docx de origem não está disponível no momento.',
                ]),
                503
            );
        }

        $result = $converter->convert($docxPath);

        if (isset($result['error'])) {
            return new Response(
                $this->renderView('tannus_ia/error.html.twig', [
                    'message' => 'Não foi possível converter o documento. Tente novamente mais tarde.',
                ]),
                503
            );
        }

        return $this->render('tannus_ia/view.html.twig', [
            'title' => $result['title'] ?: '',
            'subtitle' => $result['subtitle'] ?: '',
            'html_content' => $result['html'],
            'toc' => $result['toc'],
        ]);
    }
}
