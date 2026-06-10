<?php

namespace GestContratos\Services;

use DateInterval;
use DateTimeImmutable;
use GestContratos\Models\Parameter;

final class ContractRulesService
{
    public function normalize(array $data): array
    {
        $data['tipo'] = strtoupper(trim((string) ($data['tipo'] ?? 'CONTRATO'))) === 'ARP' ? 'ARP' : 'CONTRATO';
        $data['numero'] = trim((string) ($data['numero'] ?? $data['numero_ata'] ?? ''));
        $data['ano'] = (int) ($data['ano'] ?? date('Y'));
        $data['chave'] = ! empty($data['chave'])
            ? (string) $data['chave']
            : $this->generateKey($data['tipo'], $data['numero'], (int) $data['ano']);

        $start = $this->date($data['data_inicio'] ?? $data['vigencia_inicial'] ?? null);
        $end = $this->date($data['data_termino'] ?? $data['vigencia_final'] ?? null);
        $received = $this->date($data['data_recebimento_prorrogacao'] ?? null);
        $budget = $this->date($data['data_orcamento_estimado'] ?? null);

        $data['dias_contrato'] = $this->daysContract($start, $end);
        $remaining = $this->daysRemaining($end);
        $data['dias_restantes'] = is_int($remaining) ? $remaining : null;
        $data['situacao'] = $this->situation($end);
        $data['prazo'] = $this->deadlineBand($remaining);
        $data['prazo_legal_classificacao'] = $this->legalTermClassification((int) ($data['dias_contrato'] ?? 0));
        $data['trimestre_vencimento'] = $this->dueQuarter($end);
        $data['prazo_prorrogacao'] = $this->extensionLimit($end);
        $data['prorrogacao_no_prazo'] = $this->extensionStatus($received, $data['prazo_prorrogacao'] ?? null);
        $data['status_reajuste'] = $this->reajusteStatus($budget);
        $data['emails_equipe'] = $data['emails_equipe'] ?? '';
        $data['texto_notificacao'] = $data['texto_notificacao'] ?: $this->notificationText($data);

        foreach (['valor_global_inicial', 'valor_global_atualizado', 'valor_executado', 'valor_acumulado_executado'] as $field) {
            $data[$field] = $this->money($data[$field] ?? 0);
        }

        $data['quantidade_aditivos'] = (int) ($data['quantidade_aditivos'] ?? 0);
        $data['contrato_estrategico'] = ! empty($data['contrato_estrategico']) ? 1 : 0;
        return $data;
    }

    public function generateKey(string $type, string|int $number, int $year): string
    {
        $number = preg_replace('/\D+/', '', (string) $number);
        $number = str_pad($number ?: '0', 3, '0', STR_PAD_LEFT);
        return strtoupper($type) . $number . '/' . $year;
    }

    public function daysContract(?DateTimeImmutable $start, ?DateTimeImmutable $end): ?int
    {
        return $start && $end ? (int) $start->diff($end)->format('%r%a') : null;
    }

    public function daysRemaining(?DateTimeImmutable $end): int|string
    {
        if (! $end) {
            return 'Indeterminado';
        }
        return (int) (new DateTimeImmutable('today'))->diff($end)->format('%r%a');
    }

    public function situation(?DateTimeImmutable $end): string
    {
        if (! $end) {
            return 'Indeterminado';
        }
        return $end >= new DateTimeImmutable('today') ? 'Vigente' : 'Expirado';
    }

    public function deadlineBand(int|string|null $remaining): string
    {
        if (! is_int($remaining)) {
            return 'Indeterminado';
        }
        return match (true) {
            $remaining < 0 => 'Expirado',
            $remaining <= 30 => 'Inferior a 30 dias',
            $remaining <= 60 => 'Inferior a 60 dias',
            $remaining <= 90 => 'Inferior a 90 dias',
            $remaining <= 120 => 'Inferior a 120 dias',
            $remaining < 150 => 'Inferior a 150 dias',
            $remaining === 150 => '150 dias',
            default => 'Superior a 150 dias',
        };
    }

    public function legalTermClassification(?int $contractDays): string
    {
        if (! $contractDays) {
            return 'Sem informacao';
        }
        $legal = (int) Parameter::value('limite_prazo_legal_dias', 1800);
        $exceptional = (int) Parameter::value('limite_prazo_excepcional_dias', 2130);
        return match (true) {
            $contractDays <= $legal => 'Prazo legal',
            $contractDays <= $exceptional => 'Excepcional',
            default => 'Emergencial',
        };
    }

    public function dueQuarter(?DateTimeImmutable $end): ?string
    {
        if (! $end) {
            return null;
        }
        $quarter = (int) ceil(((int) $end->format('n')) / 3);
        return $end->format('Y') . " - {$quarter}o Trimestre";
    }

    public function extensionLimit(?DateTimeImmutable $end): ?string
    {
        if (! $end) {
            return null;
        }
        $days = (int) Parameter::value('dias_antecedencia_prorrogacao', 60);
        return $end->sub(new DateInterval("P{$days}D"))->format('Y-m-d');
    }

    public function extensionStatus(?DateTimeImmutable $received, ?string $limit): string
    {
        if (! $received || ! $limit) {
            return 'Sem informacao';
        }
        return $received <= new DateTimeImmutable($limit) ? 'Dentro do prazo' : 'Fora do prazo';
    }

    public function reajusteStatus(?DateTimeImmutable $budgetDate): string
    {
        if (! $budgetDate) {
            return 'Sem informacao';
        }
        $days = (int) Parameter::value('dias_reajuste_orcamento', 365);
        $elapsed = (int) $budgetDate->diff(new DateTimeImmutable('today'))->format('%r%a');
        return $elapsed > $days ? 'Iniciar processo de reajuste' : 'Aguardar anualidade';
    }

    public function notificationText(array $contract): string
    {
        $law = str_contains((string) ($contract['base_legal_nome'] ?? ''), '8.666') ? 'Lei 8.666/1993' : 'Lei 14.133/2021';
        $key = $contract['chave'] ?? 'contrato/ARP';
        $end = ! empty($contract['data_termino']) ? date_br($contract['data_termino']) : 'sem data informada';
        $deadline = $contract['prazo'] ?? 'sem faixa de prazo';
        $sector = $contract['setor_nome'] ?? 'setor demandante';
        $manager = $contract['gestor'] ?? 'gestor nao informado';
        $strategic = ! empty($contract['contrato_estrategico']) ? ' O contrato esta marcado como estrategico.' : '';

        return "Nos termos da {$law}, comunicamos que {$key} vinculado ao {$sector} possui termino em {$end} ({$deadline}). Solicitamos ao gestor {$manager} avaliar a necessidade de prorrogacao, renovacao, encerramento ou nova contratacao, observando o prazo limite parametrizado no sistema.{$strategic}";
    }

    private function date(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return new DateTimeImmutable($value->format('Y-m-d'));
        }
        if (! $value) {
            return null;
        }
        try {
            return new DateTimeImmutable((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function money(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        $value = str_replace(['R$', '.', ' '], '', (string) $value);
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }
}
