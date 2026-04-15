<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Service\DocxConverterService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TannusPitchController extends BaseController
{
    #[Route('/TannusIA', name: 'tannus_pitch_ia', methods: ['GET'])]
    #[Route('/TannusAI', name: 'tannus_pitch_ai', methods: ['GET'])]
    public function view(DocxConverterService $docxConverter, LoggerInterface $logger): Response
    {
        $docxPaths = [
            __DIR__ . '/../../../attached_assets/TANNUS_-_PLANO_DE_NEGÓCIOS_1776210166304.docx',
            __DIR__ . '/../../../attached_assets/TANNUS_-_PLANO_DE_NEGÓCIOS_1776200764234.docx',
            __DIR__ . '/../../../attached_assets/TANNUS_-_PLANO_DE_NEGÓCIOS_1776200490988.docx',
        ];

        $result = null;
        foreach ($docxPaths as $path) {
            if (file_exists($path)) {
                $logger->info('TannusPitch: converting {path}', ['path' => basename($path)]);
                $result = $docxConverter->convert($path);
                if (!isset($result['error'])) {
                    break;
                }
                $logger->warning('TannusPitch: conversion error for {path}: {err}', [
                    'path' => basename($path),
                    'err' => $result['error'],
                ]);
                $result = null;
            }
        }

        if ($result === null || isset($result['error'])) {
            $logger->error('TannusPitch: could not convert any DOCX file');
            $result = [
                'title' => 'Plano de Negócio',
                'subtitle' => 'Tannus IA + Mega Sistema de Comunicação',
                'html' => '<p>Documento indisponível no momento.</p>',
                'toc' => [],
            ];
        }

        return $this->render('@ChamiloCore/TannusPitch/view.html.twig', [
            'doc_title' => $result['title'] ?: 'Plano de Negócio — Tannus IA',
            'doc_subtitle' => $result['subtitle'] ?: 'Tannus IA + Mega Sistema de Comunicação',
            'html_content' => $result['html'],
            'toc' => $result['toc'],
        ]);
    }
}
