<?php

namespace GestContratos\Services;

use GestContratos\Core\Database;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ArpValuesSpreadsheetService
{
    public function updateFromSpreadsheet(string $path): array
    {
        @set_time_limit(0);
        ini_set('memory_limit', '512M');

        $reader = IOFactory::createReaderForFile($path);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        $spreadsheet = $reader->load($path);
        $result = [
            'arquivo' => basename($path),
            'abas_lidas' => 0,
            'linhas_lidas' => 0,
            'atualizadas' => 0,
            'ignoradas' => 0,
            'sem_correspondencia' => 0,
            'erros' => [],
        ];

        try {
            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $sheetResult = $this->processSheet($sheet);
                if ($sheetResult['linhas_lidas'] === 0 && $sheetResult['atualizadas'] === 0) {
                    continue;
                }

                $result['abas_lidas']++;
                foreach (['linhas_lidas', 'atualizadas', 'ignoradas', 'sem_correspondencia'] as $key) {
                    $result[$key] += $sheetResult[$key];
                }
                $result['erros'] = array_merge($result['erros'], $sheetResult['erros']);
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        return $result;
    }

    private function processSheet(Worksheet $sheet): array
    {
        $result = [
            'linhas_lidas' => 0,
            'atualizadas' => 0,
            'ignoradas' => 0,
            'sem_correspondencia' => 0,
            'erros' => [],
        ];

        $headers = $this->headers($sheet, 1);
        $columns = [
            'chave' => $this->findColumn($headers, ['chave', 'concatenado', 'identificador']),
            'numero' => $this->findColumn($headers, ['numero', 'n', 'no', 'numero_ata', 'ata']),
            'ano' => $this->findColumn($headers, ['ano', 'exercicio']),
            'valor_inicial' => $this->findColumn($headers, ['valor_inicial', 'valor_global_inicial', 'inicial', 'valor_total_da_ata', 'valor_total']),
            'valor_atualizado' => $this->findColumn($headers, ['valor_atualizado', 'valor_global_atualizado', 'atualizado', 'valor_atual', 'valor_por_fornecedor']),
        ];

        if (! $columns['chave'] && (! $columns['numero'] || ! $columns['ano'])) {
            return $result;
        }
        if (! $columns['valor_inicial'] && ! $columns['valor_atualizado']) {
            return $result;
        }

        $highestRow = $sheet->getHighestDataRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $chave = $columns['chave'] ? trim((string) $this->cell($sheet, $columns['chave'], $row)) : '';
            $numero = $columns['numero'] ? trim((string) $this->cell($sheet, $columns['numero'], $row)) : '';
            $ano = $columns['ano'] ? (int) $this->cell($sheet, $columns['ano'], $row) : 0;

            $rawInicial = $columns['valor_inicial'] ? $this->cell($sheet, $columns['valor_inicial'], $row) : null;
            $rawAtualizado = $columns['valor_atualizado'] ? $this->cell($sheet, $columns['valor_atualizado'], $row) : null;
            $hasInicial = $rawInicial !== null && $rawInicial !== '';
            $hasAtualizado = $rawAtualizado !== null && $rawAtualizado !== '';

            if ($chave === '' && ($numero === '' || $ano <= 0)) {
                $result['ignoradas']++;
                continue;
            }
            if (! $hasInicial && ! $hasAtualizado) {
                $result['ignoradas']++;
                continue;
            }

            $result['linhas_lidas']++;
            try {
                $updated = $this->updateArpValues($chave, $numero, $ano, [
                    'valor_global_inicial' => $hasInicial ? $this->money($rawInicial) : null,
                    'valor_global_atualizado' => $hasAtualizado ? $this->money($rawAtualizado) : null,
                ]);
                if ($updated) {
                    $result['atualizadas']++;
                } else {
                    $result['sem_correspondencia']++;
                }
            } catch (\Throwable $exception) {
                $result['erros'][] = $sheet->getTitle() . " linha {$row}: " . $exception->getMessage();
            }
        }

        return $result;
    }

    private function updateArpValues(string $chave, string $numero, int $ano, array $values): bool
    {
        $pdo = Database::pdo();
        $contract = $this->findContractArp($chave, $numero, $ano);
        if (! $contract) {
            return false;
        }

        $sets = ['updated_at = NOW()'];
        $params = ['id' => (int) $contract['id']];
        if ($values['valor_global_inicial'] !== null) {
            $sets[] = 'valor_global_inicial = :valor_global_inicial';
            $params['valor_global_inicial'] = $values['valor_global_inicial'];
        }
        if ($values['valor_global_atualizado'] !== null) {
            $sets[] = 'valor_global_atualizado = :valor_global_atualizado';
            $params['valor_global_atualizado'] = $values['valor_global_atualizado'];
        }

        $stmt = $pdo->prepare('UPDATE contratos SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($params);

        return true;
    }

    private function findContractArp(string $chave, string $numero, int $ano): ?array
    {
        $pdo = Database::pdo();

        if ($chave !== '') {
            $stmt = $pdo->prepare("SELECT * FROM contratos WHERE tipo = 'ARP' AND deleted_at IS NULL AND chave = ? LIMIT 1");
            $stmt->execute([$chave]);
            if ($row = $stmt->fetch()) {
                return $row;
            }
        }

        $numDigits = preg_replace('/\D+/', '', explode('/', $numero)[0] ?? '');
        if ($numDigits === '' || $ano <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT * FROM contratos
            WHERE tipo = 'ARP'
              AND ano = ?
              AND deleted_at IS NULL
              AND LPAD(REGEXP_REPLACE(numero, '[^0-9]', ''), 3, '0') = LPAD(?, 3, '0')
            LIMIT 1
        ");
        $stmt->execute([$ano, $numDigits]);
        return $stmt->fetch() ?: null;
    }

    private function headers(Worksheet $sheet, int $row): array
    {
        $headers = [];
        $maxColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        for ($col = 1; $col <= $maxColumn; $col++) {
            $headers[$col] = $this->normalizeHeader((string) $this->cell($sheet, $col, $row));
        }
        return $headers;
    }

    private function findColumn(array $headers, array $aliases): ?int
    {
        $normalized = array_map(fn (string $alias) => $this->normalizeHeader($alias), $aliases);
        foreach ($headers as $column => $header) {
            if (in_array($header, $normalized, true)) {
                return (int) $column;
            }
        }
        return null;
    }

    private function cell(Worksheet $sheet, int $column, int $row): mixed
    {
        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row);
        $value = $cell->isFormula() ? $cell->getOldCalculatedValue() : $cell->getValue();
        return is_string($value) ? trim($value) : $value;
    }

    private function normalizeHeader(string $header): string
    {
        $header = iconv('UTF-8', 'ASCII//TRANSLIT', $header);
        $header = str_replace(["'", '`', '´', '^', '~', '"'], '', (string) $header);
        $header = strtolower((string) $header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        return trim((string) $header, '_');
    }

    private function money(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(["\xc2\xa0", 'R$', ' '], '', $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return (float) $value;
    }
}
