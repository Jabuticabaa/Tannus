<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class DocumentPageController extends BaseController
{
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;
    private const ALLOWED_MIMES = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
    ];

    #[Route('/document/upload', name: 'document_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $message = '';
        $messageType = '';

        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_csrf_token', '');
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('document_upload', $submittedToken))) {
                $message = 'Token de segurança inválido. Recarregue a página e tente novamente.';
                $messageType = 'error';
            } else {
                $file = $request->files->get('document');
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    $mime = $file->getMimeType();
                    $size = $file->getSize();

                    if ($ext !== 'docx') {
                        $message = 'Apenas arquivos .docx são aceitos.';
                        $messageType = 'error';
                    } elseif (!in_array($mime, self::ALLOWED_MIMES, true)) {
                        $message = 'Tipo de arquivo inválido. Envie um documento .docx válido.';
                        $messageType = 'error';
                    } elseif ($size > self::MAX_FILE_SIZE) {
                        $message = 'Arquivo excede o tamanho máximo de 20MB.';
                        $messageType = 'error';
                    } else {
                        $projectDir = $this->getParameter('kernel.project_dir');
                        $uploadDir = $projectDir . '/var/uploads/documents';

                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = transliterator_transliterate(
                            'Any-Latin; Latin-ASCII; Lower()',
                            $filename
                        ) ?: $filename;
                        $safeFilename = preg_replace('/[^a-z0-9_-]/', '_', $safeFilename);
                        $newFilename = $safeFilename . '_' . uniqid() . '.docx';

                        $file->move($uploadDir, $newFilename);
                        $message = 'Documento enviado com sucesso: ' . $newFilename;
                        $messageType = 'success';
                    }
                } else {
                    $message = 'Erro ao enviar o arquivo. Tente novamente.';
                    $messageType = 'error';
                }
            }
        }

        return $this->render('document_page/upload.html.twig', [
            'message' => $message,
            'message_type' => $messageType,
            'csrf_token' => $csrfTokenManager->getToken('document_upload')->getValue(),
        ]);
    }
}
