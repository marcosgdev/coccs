<?php

namespace GestContratos\Services;

/**
 * Gera arquivos .docx válidos (OOXML) sem dependências externas.
 * Usa ZipArchive (embutido no PHP) para montar o pacote.
 */
final class DocxService
{
    private array $paragraphs = [];

    // ── API pública ───────────────────────────────────────────────────────

    public function titulo(string $text): self
    {
        $this->paragraphs[] = ['type' => 'heading', 'text' => $text, 'level' => 1];
        return $this;
    }

    public function subtitulo(string $text): self
    {
        $this->paragraphs[] = ['type' => 'heading', 'text' => $text, 'level' => 2];
        return $this;
    }

    public function paragrafo(string $text): self
    {
        $this->paragraphs[] = ['type' => 'para', 'text' => $text];
        return $this;
    }

    /**
     * @param string[] $headers Cabeçalhos das colunas
     * @param array[]  $rows    Linhas de dados; cada linha é array de strings na mesma ordem dos headers
     * @param string   $caption Título opcional acima da tabela
     * @param string   $headerBg Cor hex do cabeçalho (sem #), default azul TJPA
     */
    public function tabela(array $headers, array $rows, string $caption = '', string $headerBg = '1E3A5F'): self
    {
        $this->paragraphs[] = [
            'type'     => 'table',
            'caption'  => $caption,
            'headers'  => $headers,
            'rows'     => $rows,
            'headerBg' => strtoupper($headerBg),
        ];
        return $this;
    }

    public function secao(string $text): self
    {
        $this->paragraphs[] = ['type' => 'section', 'text' => $text];
        return $this;
    }

    public function espacamento(): self
    {
        $this->paragraphs[] = ['type' => 'space'];
        return $this;
    }

    /**
     * Envia o arquivo .docx diretamente para o navegador.
     */
    public function download(string $filename): never
    {
        if (!str_ends_with($filename, '.docx')) $filename .= '.docx';
        $tmp = tempnam(sys_get_temp_dir(), 'docx_');
        $this->save($tmp);

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: max-age=0');
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    // ── Geração interna ───────────────────────────────────────────────────

    private function save(string $path): void
    {
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml',      $this->contentTypes());
        $zip->addFromString('_rels/.rels',              $this->rootRels());
        $zip->addFromString('word/_rels/document.xml.rels', $this->docRels());
        $zip->addFromString('word/styles.xml',          $this->styles());
        $zip->addFromString('word/settings.xml',        $this->settings());
        $zip->addFromString('word/document.xml',        $this->document());

        $zip->close();
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Override PartName="/word/document.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/word/settings.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
    Target="word/document.xml"/>
</Relationships>';
    }

    private function docRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"
    Target="styles.xml"/>
  <Relationship Id="rId2"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings"
    Target="settings.xml"/>
</Relationships>';
    }

    private function settings(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:defaultTabStop w:val="708"/>
  <w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat>
</w:settings>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:style w:type="paragraph" w:styleId="Normal" w:default="1">
    <w:name w:val="Normal"/>
    <w:rPr><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/>
    <w:basedOn w:val="Normal"/>
    <w:pPr><w:spacing w:before="240" w:after="120"/></w:pPr>
    <w:rPr><w:b/><w:color w:val="1E3A5F"/><w:sz w:val="32"/><w:szCs w:val="32"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/>
    <w:basedOn w:val="Normal"/>
    <w:pPr><w:spacing w:before="160" w:after="80"/></w:pPr>
    <w:rPr><w:b/><w:color w:val="2563EB"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>
  </w:style>
</w:styles>';
    }

    private function document(): string
    {
        $ns  = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
             . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';
        $body = '';

        foreach ($this->paragraphs as $p) {
            $body .= match($p['type']) {
                'heading' => $this->xmlHeading($p['text'], $p['level']),
                'section' => $this->xmlSection($p['text']),
                'para'    => $this->xmlPara($p['text']),
                'space'   => '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr></w:p>',
                'table'   => ($p['caption'] ? $this->xmlSection($p['caption']) : '')
                           . $this->xmlTable($p['headers'], $p['rows'], $p['headerBg']),
                default   => '',
            };
        }

        // Orientação paisagem para tabelas largas
        $sectPr = '<w:sectPr>
          <w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>
          <w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="0" w:footer="0"/>
        </w:sectPr>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . "<w:document {$ns}><w:body>{$body}{$sectPr}</w:body></w:document>";
    }

    private function xmlHeading(string $text, int $level): string
    {
        $style = $level === 1 ? 'Heading1' : 'Heading2';
        return '<w:p><w:pPr><w:pStyle w:val="' . $style . '"/></w:pPr>'
             . '<w:r><w:t>' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    private function xmlSection(string $text): string
    {
        return '<w:p>'
             . '<w:pPr><w:shd w:val="clear" w:color="auto" w:fill="2563EB"/>'
             . '<w:spacing w:before="80" w:after="40"/></w:pPr>'
             . '<w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr>'
             . '<w:t>' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    private function xmlPara(string $text): string
    {
        return '<w:p><w:r><w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    private function xmlTable(array $headers, array $rows, string $headerBg): string
    {
        $colCount = count($headers);
        $colW     = (int) round(9000 / max($colCount, 1));

        $gridCols = '';
        for ($i = 0; $i < $colCount; $i++) {
            $gridCols .= '<w:gridCol w:w="' . $colW . '"/>';
        }

        $tbl = '<w:tbl>'
             . '<w:tblPr>'
             . '<w:tblStyle w:val="TableGrid"/>'
             . '<w:tblW w:w="0" w:type="auto"/>'
             . '<w:tblBorders>'
             . '<w:top    w:val="single" w:sz="4" w:color="CCCCCC"/>'
             . '<w:left   w:val="single" w:sz="4" w:color="CCCCCC"/>'
             . '<w:bottom w:val="single" w:sz="4" w:color="CCCCCC"/>'
             . '<w:right  w:val="single" w:sz="4" w:color="CCCCCC"/>'
             . '<w:insideH w:val="single" w:sz="4" w:color="E2E8F0"/>'
             . '<w:insideV w:val="single" w:sz="4" w:color="E2E8F0"/>'
             . '</w:tblBorders>'
             . '</w:tblPr>'
             . '<w:tblGrid>' . $gridCols . '</w:tblGrid>';

        // Cabeçalho
        $tbl .= '<w:tr>';
        foreach ($headers as $h) {
            $tbl .= '<w:tc>'
                  . '<w:tcPr><w:tcW w:w="' . $colW . '" w:type="dxa"/>'
                  . '<w:shd w:val="clear" w:color="auto" w:fill="' . $headerBg . '"/></w:tcPr>'
                  . '<w:p><w:pPr><w:spacing w:before="40" w:after="40"/></w:pPr>'
                  . '<w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="16"/></w:rPr>'
                  . '<w:t>' . $this->esc((string)$h) . '</w:t></w:r></w:p></w:tc>';
        }
        $tbl .= '</w:tr>';

        // Linhas de dados
        foreach ($rows as $idx => $row) {
            $isSectionRow = isset($row['__section']);
            $fill = $isSectionRow ? '334155' : ($idx % 2 === 0 ? 'FFFFFF' : 'F8FAFC');
            $tbl .= '<w:tr>';
            if ($isSectionRow) {
                $tbl .= '<w:tc>'
                      . '<w:tcPr><w:tcW w:w="' . ($colW * $colCount) . '" w:type="dxa"/>'
                      . '<w:gridSpan w:val="' . $colCount . '"/>'
                      . '<w:shd w:val="clear" w:color="auto" w:fill="334155"/></w:tcPr>'
                      . '<w:p><w:pPr><w:spacing w:before="40" w:after="40"/></w:pPr>'
                      . '<w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="17"/></w:rPr>'
                      . '<w:t>' . $this->esc($row['__section']) . '</w:t></w:r></w:p></w:tc>';
            } else {
                $isTotal = isset($row['__total']);
                $cells = $isTotal ? $row['__total'] : array_values($row);
                foreach ($cells as $i => $cell) {
                    $cellFill = $isTotal ? '1E3A5F' : $fill;
                    $textColor = $isTotal ? 'FFFFFF' : '1E293B';
                    $bold = $isTotal ? '<w:b/>' : '';
                    $tbl .= '<w:tc>'
                          . '<w:tcPr><w:tcW w:w="' . $colW . '" w:type="dxa"/>'
                          . '<w:shd w:val="clear" w:color="auto" w:fill="' . $cellFill . '"/></w:tcPr>'
                          . '<w:p><w:pPr><w:spacing w:before="30" w:after="30"/></w:pPr>'
                          . '<w:r><w:rPr>' . $bold . '<w:color w:val="' . $textColor . '"/><w:sz w:val="16"/></w:rPr>'
                          . '<w:t xml:space="preserve">' . $this->esc((string)$cell) . '</w:t></w:r></w:p></w:tc>';
                }
            }
            $tbl .= '</w:tr>';
        }

        $tbl .= '</w:tbl>';
        return $tbl;
    }

    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
