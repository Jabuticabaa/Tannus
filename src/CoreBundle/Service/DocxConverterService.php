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
        $images = [];
        $imgIndex = 0;
        $html = preg_replace_callback(
            '/src="data:image\/[^;]+;base64,[^"]+"/i',
            function ($m) use (&$images, &$imgIndex) {
                $placeholder = 'src="__IMG_PLACEHOLDER_' . $imgIndex . '__"';
                $images[$imgIndex] = $m[0];
                $imgIndex++;
                return $placeholder;
            },
            $html
        );

        $html = preg_replace('/<h1[^>]*class="doc-cover-title"[^>]*>.*?<\/h1>/is', '', $html);
        $html = preg_replace('/<p[^>]*class="doc-cover-subtitle"[^>]*>.*?<\/p>/is', '', $html);

        $html = $this->stripDocumentToc($html);

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

                $phaseMatch = [];
                $isPhase = (bool) preg_match('/^0?([1-5])\s*[–\-]/u', $text, $phaseMatch);
                $phaseNum = $isPhase ? (int) $phaseMatch[1] : 0;

                if ($isPhase) {
                    return sprintf(
                        '<div class="pitch-phase-card" data-phase="%d"><%s id="%s" class="pitch-phase-heading"><span class="pitch-phase-num">%02d</span>%s</%s></div>',
                        $phaseNum,
                        $tag,
                        $id,
                        $phaseNum,
                        $matches[2],
                        $tag
                    );
                }

                return sprintf('<%s id="%s">%s</%s>', $tag, $id, $matches[2], $tag);
            },
            $html
        );

        $html = $this->transformMarketTable($html);

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

        foreach ($images as $idx => $src) {
            $html = str_replace('src="__IMG_PLACEHOLDER_' . $idx . '__"', $src, $html);
        }

        $html = preg_replace(
            '/<img ([^>]*)>/i',
            '<img $1 loading="lazy">',
            $html
        );

        return $html;
    }

    /**
     * Strips the DOCX table-of-contents block from the body HTML.
     * Removes: the "Sumário" paragraph and all TOC link paragraphs (<p><a href="#_Toc...">).
     */
    private function stripDocumentToc(string $html): string
    {
        $html = preg_replace('/<p>\s*Sum[aá]rio\s*<\/p>/iu', '', $html);
        $html = preg_replace('/<p>\s*<a\s+href="#_Toc[^"]*"[^>]*>.*?<\/a>\s*<\/p>/is', '', $html);

        return $html;
    }

    /**
     * Transforms the first data table (market indicators) into a metric-card grid.
     * The market table has columns: Indicadores | Brasil | EUA | China | Global.
     * Each data row becomes a visual card highlighting the Brasil value.
     */
    private function transformMarketTable(string $html): string
    {
        $tableCount = 0;
        $html = preg_replace_callback(
            '/<table>(.*?)<\/table>/is',
            function ($matches) use (&$tableCount) {
                $tableCount++;
                $tableInner = $matches[1];

                if ($tableCount !== 1) {
                    return $matches[0];
                }

                $rows = [];
                preg_match_all('/<tr>(.*?)<\/tr>/is', $tableInner, $rowMatches);

                foreach ($rowMatches[1] as $rowHtml) {
                    $cells = [];
                    preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $rowHtml, $cellMatches);
                    foreach ($cellMatches[1] as $cell) {
                        $cells[] = trim(strip_tags($cell));
                    }
                    if ($cells) {
                        $rows[] = $cells;
                    }
                }

                if (count($rows) < 2) {
                    return $matches[0];
                }

                $headers = $rows[0];
                $dataRows = array_slice($rows, 1);

                $cards = '';
                foreach ($dataRows as $row) {
                    $indicator = $row[0] ?? '';
                    $brasil = $row[1] ?? '';
                    $eua = $row[2] ?? '';
                    $china = $row[3] ?? '';
                    $global = $row[4] ?? '';

                    $cards .= sprintf(
                        '<div class="pitch-metric-card">'
                        . '<div class="pitch-metric-card__indicator">%s</div>'
                        . '<div class="pitch-metric-card__values">'
                        . '<div class="pitch-metric-card__primary"><span class="pitch-metric-card__val">%s</span><span class="pitch-metric-card__region">Brasil</span></div>'
                        . '<div class="pitch-metric-card__secondary">'
                        . '<span><strong>EUA</strong> %s</span>'
                        . '<span><strong>China</strong> %s</span>'
                        . '<span><strong>Global</strong> %s</span>'
                        . '</div>'
                        . '</div>'
                        . '</div>',
                        htmlspecialchars($indicator, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($brasil, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($eua, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($china, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($global, ENT_QUOTES, 'UTF-8')
                    );
                }

                return '<div class="pitch-metric-grid">' . $cards . '</div>';
            },
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
