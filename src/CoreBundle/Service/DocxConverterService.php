<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service;

class DocxConverterService
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function convert(string $docxPath): array
    {
        if (!file_exists($docxPath)) {
            return ['error' => 'File not found: ' . $docxPath];
        }

        $scriptPath = $this->projectDir . '/scripts/mammoth_convert.js';
        if (!file_exists($scriptPath)) {
            return ['error' => 'Conversion script not found'];
        }

        $tempFile = sys_get_temp_dir() . '/mammoth_' . md5($docxPath) . '_' . filemtime($docxPath) . '.html';

        if (!file_exists($tempFile)) {
            $safeDocx = sys_get_temp_dir() . '/mammoth_input_' . md5($docxPath) . '.docx';
            if (!file_exists($safeDocx) || filemtime($safeDocx) < filemtime($docxPath)) {
                copy($docxPath, $safeDocx);
            }

            $cmd = sprintf(
                'node %s %s %s 2>/dev/null',
                escapeshellarg($scriptPath),
                escapeshellarg($safeDocx),
                escapeshellarg($tempFile)
            );

            shell_exec($cmd);

            if (!file_exists($tempFile) || filesize($tempFile) === 0) {
                $this->convertWithPython($safeDocx, $tempFile);
            }
        }

        if (!file_exists($tempFile) || filesize($tempFile) === 0) {
            return ['error' => 'Conversion failed'];
        }

        $rawHtml = file_get_contents($tempFile);

        $title = '';
        if (preg_match('/<h1[^>]*class="doc-cover-title"[^>]*>(.*?)<\/h1>/is', $rawHtml, $m)) {
            $title = strip_tags($m[1]);
        }

        $subtitle = '';
        if (preg_match('/<p[^>]*class="doc-cover-subtitle"[^>]*>(.*?)<\/p>/is', $rawHtml, $m)) {
            $subtitle = strip_tags($m[1]);
        }

        $toc = [];
        $processedHtml = $this->processHtml($rawHtml, $toc);

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'html' => $processedHtml,
            'toc' => $toc,
        ];
    }

    private function processHtml(string $html, array &$toc): string
    {
        $html = preg_replace('/<h1[^>]*class="doc-cover-title"[^>]*>.*?<\/h1>/is', '', $html);
        $html = preg_replace('/<p[^>]*class="doc-cover-subtitle"[^>]*>.*?<\/p>/is', '', $html);

        $usedIds = [];
        $html = preg_replace_callback(
            '/<(h[2-4])>(.*?)<\/\1>/is',
            function ($matches) use (&$toc, &$usedIds) {
                $tag = strtolower($matches[1]);
                $text = strip_tags($matches[2]);
                $id = $this->slugify($text);

                if (isset($usedIds[$id])) {
                    $usedIds[$id]++;
                    $id .= '-' . $usedIds[$id];
                } else {
                    $usedIds[$id] = 1;
                }

                $level = (int) substr($tag, 1);
                $toc[] = [
                    'id' => $id,
                    'text' => $text,
                    'level' => $level,
                ];

                return sprintf('<%s id="%s">%s</%s>', $tag, $id, $matches[2], $tag);
            },
            $html
        );

        $html = preg_replace(
            '/<table>/',
            '<div class="table-responsive"><table>',
            $html
        );
        $html = preg_replace(
            '/<\/table>/',
            '</table></div>',
            $html
        );

        $html = preg_replace(
            '/<img ([^>]*)>/i',
            '<img $1 loading="lazy">',
            $html
        );

        return $html;
    }

    private function convertWithPython(string $docxPath, string $outputPath): void
    {
        $pythonBin = null;
        foreach (['python3', 'python'] as $bin) {
            $which = trim(shell_exec('which ' . escapeshellarg($bin) . ' 2>/dev/null') ?: '');
            if ($which !== '') {
                $pythonBin = $which;
                break;
            }
        }

        if (!$pythonBin) {
            return;
        }

        $pythonScript = sprintf(
            '%s -c "
import sys
try:
    import mammoth
    with open(sys.argv[1], \'rb\') as f:
        result = mammoth.convert_to_html(f)
        with open(sys.argv[2], \'w\', encoding=\'utf-8\') as out:
            out.write(result.value)
except ImportError:
    sys.exit(1)
except Exception as e:
    sys.exit(2)
" %s %s 2>/dev/null',
            escapeshellarg($pythonBin),
            escapeshellarg($docxPath),
            escapeshellarg($outputPath)
        );

        shell_exec($pythonScript);
    }

    private function slugify(string $text): string
    {
        $text = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; Lower()',
            $text
        ) ?: mb_strtolower($text, 'UTF-8');

        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        return $text ?: 'section';
    }
}
