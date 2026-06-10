<?php

namespace GestContratos\Services;

use GestContratos\Core\Auth;
use GestContratos\Models\Arp;
use GestContratos\Models\Contract;
use GestContratos\Models\FinancialExecution;
use GestContratos\Models\GenericModel;
use GestContratos\Models\ImportLog;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ExcelImportService
{
    private ?int $batchId = null;
    private string $mode = 'simulacao';
    private string $fileName = 'Contratos 2024.xlsm';

    public function preview(string $path): array
    {
        $spreadsheet = $this->loadSpreadsheet($path, true);
        $formulaCounts = $this->formulaCountsFromArchive($path);
        $sheets = [];
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheets[] = [
                'name' => $sheet->getTitle(),
                'rows' => $sheet->getHighestDataRow(),
                'columns' => Coordinate::columnIndexFromString($sheet->getHighestDataColumn()),
                'headers' => $this->headers($sheet),
                'sample' => $this->sampleRows($sheet, 5),
                'formulas' => $formulaCounts[$sheet->getTitle()] ?? 0,
            ];
        }
        $spreadsheet->disconnectWorksheets();
        return $sheets;
    }

    public function import(string $path, bool $simulate = false, string $duplicateMode = 'ignore', ?int $batchId = null): array
    {
        @set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->batchId = $batchId;
        $this->mode = $simulate ? 'simulacao' : 'importacao';
        $this->fileName = basename($path);

        $spreadsheet = $this->loadSpreadsheet($path, true);
        $result = [
            'batch_id' => $batchId,
            'simulate' => $simulate,
            'mode' => $this->mode,
            'contracts' => 0,
            'arps' => 0,
            'financial' => 0,
            'servers' => 0,
            'sectors' => 0,
            'auxiliary' => 0,
            'errors' => [],
        ];

        foreach ([
            'contracts' => fn () => $this->importContracts($spreadsheet->getSheetByName('Contratos Vigentes'), $simulate, $duplicateMode),
            'arps' => fn () => $this->importArps($spreadsheet->getSheetByName('ATA empresa valores'), $simulate, $duplicateMode),
            'financial' => fn () => $this->importFinancial($spreadsheet, $simulate),
            'servers' => fn () => $this->importServers($spreadsheet->getSheetByName('Gestão&Fiscalização'), $simulate),
            'sectors' => fn () => $this->importSectors($spreadsheet->getSheetByName('SETOREQ'), $simulate),
            'auxiliary' => fn () => $this->importAuxiliary($spreadsheet, $simulate),
        ] as $key => $operation) {
            try {
                $result[$key] = $operation();
            } catch (\Throwable $exception) {
                $result['errors'][] = "{$key}: " . $exception->getMessage();
                $this->log('Sistema', null, 'erro', "{$key}: " . $exception->getMessage(), [
                    'operation' => $key,
                    'exception' => $exception::class,
                ]);
            }
        }

        $spreadsheet->disconnectWorksheets();
        return $result;
    }

    private function importContracts(?Worksheet $sheet, bool $simulate, string $duplicateMode): int
    {
        if (! $sheet) {
            return 0;
        }
        $rows = $this->assocRows($sheet, 1);
        $model = new Contract();
        $rules = new ContractRulesService();
        $count = 0;

        foreach ($rows as $rowNumber => $row) {
            $tipo = strtoupper((string) $this->value($row, ['arp_ou_contrato']));
            $numero = $this->value($row, ['n', 'no', 'numero']);
            $ano = $this->value($row, ['ano']);
            if (! $tipo || ! $numero || ! $ano) {
                continue;
            }

            $data = [
                'tipo' => $tipo === 'ARP' ? 'ARP' : 'CONTRATO',
                'numero' => $numero,
                'ano' => (int) $ano,
                'fornecedor_nome' => $this->value($row, ['nome_credor']),
                'cnpj_cpf' => $this->value($row, ['cnpjcpf']),
                'objeto' => $this->value($row, ['objeto']),
                'natureza_contratacao_nome' => $this->value($row, ['natureza_da_contratacao']),
                'forma_contratacao_nome' => $this->value($row, ['forma_de_contratacao']),
                'licitacao_numero' => $this->value($row, ['n_licitacaodispensainexigibilidade']),
                'data_inicio' => $this->dateValue($this->value($row, ['inicio'])),
                'data_termino' => $this->dateValue($this->value($row, ['termino'])),
                'valor_global_inicial' => $this->value($row, ['valor_global_inicial']) ?: 0,
                'tipo_contrato_nome' => $this->value($row, ['tipo_de_contrato']),
                'quantidade_aditivos' => $this->value($row, ['qtd_de_aditivos']) ?: 0,
                'setor_nome' => $this->value($row, ['setor_demandante']),
                'processo' => $this->value($row, ['protocolo']),
                'valor_executado' => $this->value($row, ['valor_executado']) ?: 0,
                'valor_global_atualizado' => $this->value($row, ['valor_global_atualizado']) ?: 0,
                'base_legal_nome' => $this->value($row, ['base_legal']),
                'data_orcamento_estimado' => $this->dateValue($this->value($row, ['data_do_orcamento_estimado'])),
                'contrato_estrategico' => $this->yesNo($this->value($row, ['contratos_estrategicos'])),
                'data_recebimento_prorrogacao' => $this->dateValue($this->value($row, ['data_recebimento_do_expediente'])),
                'observacoes' => trim((string) $this->value($row, ['observacao', 'acompanhamento'])),
                'gestor' => $this->value($row, ['gestor_do_contrato']),
                'fiscal_demandante' => $this->value($row, ['fiscal_demandante']),
                'fiscal_tecnico' => $this->value($row, ['fiscal_tecnico']),
                'gestor_substituto' => $this->value($row, ['gestor_substituto']),
                'fiscal_substituto' => $this->value($row, ['fiscal_substituto']),
                'fiscal_administrativo' => $this->value($row, ['fiscal_administrativo']),
                'emails_equipe' => $this->value($row, ['email_equipe_de_gestao_e_fiscalizacao']),
                'valor_acumulado_executado' => $this->value($row, ['valor_acumulado_executado']) ?: 0,
                'texto_notificacao' => $this->value($row, ['texto_notificacao']),
            ];
            $data = $rules->normalize($data);

            if (! $simulate) {
                $existing = $model->findByKey($data['chave']);
                if ($existing && $duplicateMode === 'overwrite') {
                    $model->update((int) $existing['id'], $data);
                } elseif (! $existing) {
                    $model->create($this->withBatch($data));
                }
            }
            $this->log('Contratos Vigentes', $rowNumber, 'ok', $simulate ? 'Simulado' : 'Importado', $data);
            $count++;
        }
        return $count;
    }

    private function importArps(?Worksheet $sheet, bool $simulate, string $duplicateMode): int
    {
        if (! $sheet) {
            return 0;
        }
        $rows = $this->assocRows($sheet, 1);
        $model = new Arp();
        $rules = new ContractRulesService();
        $count = 0;
        foreach ($rows as $rowNumber => $row) {
            $numero = $this->value($row, ['numero', 'ata']);
            $ano = $this->value($row, ['ano']);
            if (! $numero || ! $ano) {
                continue;
            }
            $vigencia = $this->value($row, ['vigencia']);
            $data = [
                'numero_ata' => $numero,
                'ano' => (int) $ano,
                'chave' => $rules->generateKey('ARP', $numero, (int) $ano),
                'fornecedor_nome' => $this->value($row, ['empresa']),
                'objeto' => $this->value($row, ['objeto']),
                'vigencia_inicial' => null,
                'vigencia_final' => $this->dateValue($vigencia),
                'valor_total' => (float) ($this->value($row, ['valor_total_da_ata']) ?: 0),
                'valor_por_fornecedor' => (float) ($this->value($row, ['valor_por_fornecedor']) ?: 0),
                'setor_nome' => $this->value($row, ['setor']),
                'observacoes' => $this->value($row, ['processo']),
            ];
            $end = $data['vigencia_final'] ? new \DateTimeImmutable($data['vigencia_final']) : null;
            $remaining = $rules->daysRemaining($end);
            $data['dias_restantes'] = is_int($remaining) ? $remaining : null;
            $data['situacao'] = $rules->situation($end);
            $data['saldo'] = $data['valor_total'];
            if (! $simulate) {
                $existing = $this->exists('arps', ['chave' => $data['chave']]);
                if (! $existing || $duplicateMode === 'overwrite') {
                    $existing
                        ? $model->update((int) $existing['id'], $data)
                        : $model->create($this->withBatch($data));
                }
            }
            $this->log('ATA empresa valores', $rowNumber, 'ok', $simulate ? 'Simulado' : 'Importado', $data);
            $count++;
        }
        return $count;
    }

    private function importFinancial(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, bool $simulate): int
    {
        $model = new FinancialExecution();
        $count = 0;
        foreach (['M.11 Contratos execução' => 1, 'ARP execução' => 1] as $sheetName => $headerRow) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                continue;
            }
            foreach ($this->assocRows($sheet, $headerRow) as $rowNumber => $row) {
                $key = $this->value($row, ['concatenado']);
                $year = $this->value($row, ['exercicio_fiananceiro', 'exercicio', 'ano']);
                if (! $key) {
                    continue;
                }
                $data = [
                    'chave' => $key,
                    'exercicio' => (int) ($year ?: date('Y')),
                    'valor_inicial' => (float) ($this->value($row, ['inicial', 'valor_inicial']) ?: 0),
                    'valor_atualizado' => (float) ($this->value($row, ['atual', 'valor_atualizado']) ?: 0),
                    'valor_executado_exercicio' => (float) ($this->value($row, ['no_exercicio_2026', 'valor_executado_ano']) ?: 0),
                    'valor_acumulado' => (float) ($this->value($row, ['acumulado_total', 'acumulado_2025']) ?: 0),
                    'observacoes' => (string) $this->value($row, ['observacao']),
                ];
                $data['saldo'] = $data['valor_atualizado'] - $data['valor_acumulado'];
                if (! $simulate) {
                    $model->create($this->withBatch($data));
                }
                $this->log($sheetName, $rowNumber, 'ok', $simulate ? 'Simulado' : 'Importado', $data);
                $count++;
            }
        }
        return $count;
    }

    private function importServers(?Worksheet $sheet, bool $simulate): int
    {
        if (! $sheet) {
            return 0;
        }
        $model = new GenericModel('servidores', ['nome', 'unidade', 'email', 'ativo', 'import_batch_id', 'created_at', 'updated_at', 'deleted_at']);
        $rows = $this->assocRows($sheet, 2);
        $count = 0;
        foreach ($rows as $rowNumber => $row) {
            $name = $this->value($row, ['servidor']);
            if (! $name) {
                continue;
            }
            $data = [
                'nome' => $name,
                'unidade' => $this->value($row, ['unidade']),
                'email' => $this->value($row, ['email']),
                'ativo' => 1,
            ];
            if (! $simulate) {
                $existing = $this->exists('servidores', ['nome' => $data['nome']]);
                if (! $existing) {
                    $model->create($this->withBatch($data));
                }
            }
            $this->log('Gestão&Fiscalização', $rowNumber, 'ok', $simulate ? 'Simulado' : 'Importado', $data);
            $count++;
        }
        return $count;
    }

    private function importSectors(?Worksheet $sheet, bool $simulate): int
    {
        if (! $sheet) {
            return 0;
        }
        $model = new GenericModel('setores', ['codigo', 'nome', 'descricao', 'ativo', 'import_batch_id', 'created_at', 'updated_at', 'deleted_at']);
        $count = 0;
        foreach ($this->assocRows($sheet, 1) as $rowNumber => $row) {
            $code = $this->value($row, ['codigo']);
            $name = $this->value($row, ['descricao']);
            if (! $code && ! $name) {
                continue;
            }
            $data = ['codigo' => $code, 'nome' => $name ?: $code, 'descricao' => $name, 'ativo' => 1];
            if (! $simulate) {
                $existing = $this->exists('setores', ['codigo' => $code ?: null, 'nome' => $name ?: $code]);
                if (! $existing) {
                    $model->create($this->withBatch($data));
                }
            }
            $this->log('SETOREQ', $rowNumber, 'ok', $simulate ? 'Simulado' : 'Importado', $data);
            $count++;
        }
        return $count;
    }

    private function importAuxiliary(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, bool $simulate): int
    {
        $maps = [
            'validação dados' => [
                ['table' => 'naturezas_contratacao', 'column' => 1],
                ['table' => 'naturezas_despesa', 'column' => 2],
                ['table' => 'tipos_contrato', 'column' => 4],
            ],
            'Validação de Dados' => [
                ['table' => 'formas_contratacao', 'column' => 3],
                ['table' => 'bases_legais', 'column' => 8],
            ],
        ];
        $count = 0;
        foreach ($maps as $sheetName => $targets) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                continue;
            }
            foreach ($targets as $target) {
                $model = new GenericModel($target['table'], ['nome', 'ativo', 'import_batch_id', 'created_at', 'updated_at', 'deleted_at']);
                $seen = [];
                for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
                    $value = trim((string) $this->cell($this->cellAt($sheet, $target['column'], $row)));
                    if ($value === '' || isset($seen[mb_strtolower($value)])) {
                        continue;
                    }
                    $seen[mb_strtolower($value)] = true;
                    if (! $simulate) {
                        $existing = $this->exists($target['table'], ['nome' => $value]);
                        if (! $existing) {
                            $model->create($this->withBatch(['nome' => $value, 'ativo' => 1]));
                        }
                    }
                    $this->log($sheetName, $row, 'ok', $simulate ? 'Simulado' : 'Importado', ['table' => $target['table'], 'nome' => $value]);
                    $count++;
                }
            }
        }
        return $count;
    }

    private function assocRows(Worksheet $sheet, int $headerRow): array
    {
        $headers = $this->headers($sheet, $headerRow);
        $rows = [];
        for ($r = $headerRow + 1; $r <= $sheet->getHighestDataRow(); $r++) {
            $data = [];
            $hasValue = false;
            foreach ($headers as $col => $header) {
                if ($header === '') {
                    continue;
                }
                $value = $this->cell($this->cellAt($sheet, $col, $r));
                if ($value !== null && $value !== '') {
                    $hasValue = true;
                }
                $data[$header] = $value;
            }
            if ($hasValue) {
                $rows[$r] = $data;
            }
        }
        return $rows;
    }

    private function headers(Worksheet $sheet, int $row = 1): array
    {
        $headers = [];
        $maxColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        for ($col = 1; $col <= $maxColumn; $col++) {
            $headers[$col] = $this->normalizeHeader((string) $this->cell($this->cellAt($sheet, $col, $row)));
        }
        return $headers;
    }

    private function sampleRows(Worksheet $sheet, int $limit): array
    {
        $sample = [];
        $maxColumn = min(8, Coordinate::columnIndexFromString($sheet->getHighestDataColumn()));
        for ($row = 1; $row <= min($limit, $sheet->getHighestDataRow()); $row++) {
            $cells = [];
            for ($col = 1; $col <= $maxColumn; $col++) {
                $cells[] = (string) $this->cell($this->cellAt($sheet, $col, $row));
            }
            $sample[] = $cells;
        }
        return $sample;
    }

    private function loadSpreadsheet(string $path, bool $dataOnly): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($path);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly($dataOnly);
        }
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }
        return $reader->load($path);
    }

    private function formulaCountsFromArchive(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relsXml === false) {
            $zip->close();
            return [];
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);
        if (! $workbook || ! $rels) {
            $zip->close();
            return [];
        }

        $relationshipTargets = [];
        foreach ($rels->Relationship as $relationship) {
            $attrs = $relationship->attributes();
            $target = (string) $attrs['Target'];
            $relationshipTargets[(string) $attrs['Id']] = str_starts_with($target, '/')
                ? ltrim($target, '/')
                : 'xl/' . ltrim($target, '/');
        }

        $counts = [];
        foreach ($workbook->sheets->sheet as $sheet) {
            $attrs = $sheet->attributes();
            $relAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $name = (string) $attrs['name'];
            $relationshipId = (string) $relAttrs['id'];
            $target = $relationshipTargets[$relationshipId] ?? null;
            if (! $target) {
                continue;
            }
            $xml = $zip->getFromName($target);
            if ($xml === false) {
                continue;
            }
            $counts[$name] = preg_match_all('/<f(?:\\s|>)/', $xml);
        }

        $zip->close();
        return $counts;
    }

    private function cellAt(Worksheet $sheet, int $column, int $row): \PhpOffice\PhpSpreadsheet\Cell\Cell
    {
        return $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row);
    }

    private function cell(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell): mixed
    {
        if ($cell->isFormula()) {
            $value = $cell->getOldCalculatedValue();
            if ($value === null || $value === '') {
                $value = null;
            }
        } else {
            $value = $cell->getValue();
        }
        if (is_numeric($value) && ExcelDate::isDateTime($cell, $value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        return is_string($value) ? trim($value) : $value;
    }

    private function value(array $row, array $aliases): mixed
    {
        foreach ($aliases as $alias) {
            $key = $this->normalizeHeader($alias);
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }
        return null;
    }

    private function normalizeHeader(string $header): string
    {
        $header = iconv('UTF-8', 'ASCII//TRANSLIT', $header);
        $header = str_replace(["'", '`', '´', '^', '~', '"'], '', (string) $header);
        $header = strtolower((string) $header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        return trim((string) $header, '_');
    }

    private function dateValue(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        try {
            return (new \DateTimeImmutable((string) $value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function yesNo(mixed $value): int
    {
        $value = mb_strtolower(trim((string) $value));
        return in_array($value, ['sim', 's', '1', 'true', 'estrategico'], true) ? 1 : 0;
    }

    private function withBatch(array $data): array
    {
        if ($this->batchId !== null) {
            $data['import_batch_id'] = $this->batchId;
        }
        return $data;
    }

    private function exists(string $table, array $filters): ?array
    {
        $clauses = ['deleted_at IS NULL'];
        $params = [];
        foreach ($filters as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $clauses[] = "{$column} = :{$column}";
            $params[$column] = $value;
        }
        if (! $params) {
            return null;
        }
        $stmt = \GestContratos\Core\Database::pdo()->prepare(
            "SELECT * FROM {$table} WHERE " . implode(' AND ', $clauses) . ' LIMIT 1'
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function log(string $sheet, ?int $row, string $status, string $message, array $data = []): void
    {
        try {
            (new ImportLog())->create([
                'usuario_id' => Auth::id(),
                'arquivo' => $this->fileName,
                'aba' => $sheet,
                'linha' => $row,
                'status' => $status,
                'import_batch_id' => $this->batchId,
                'modo' => $this->mode,
                'mensagem' => $message,
                'dados' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
        }
    }
}
