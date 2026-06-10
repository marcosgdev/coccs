<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;
use GestContratos\Core\Request;
use GestContratos\Models\Contract;

final class DeadlinesController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();
        $filters = $request->query;
        $contracts = (new Contract())->search($filters);
        $groups = [
            'Vencidos' => fn ($c) => ($c['dias_restantes'] ?? 1) < 0,
            'Ate 30 dias' => fn ($c) => ($c['dias_restantes'] ?? 9999) >= 0 && ($c['dias_restantes'] ?? 9999) <= 30,
            'Ate 60 dias' => fn ($c) => ($c['dias_restantes'] ?? 9999) > 30 && ($c['dias_restantes'] ?? 9999) <= 60,
            'Ate 90 dias' => fn ($c) => ($c['dias_restantes'] ?? 9999) > 60 && ($c['dias_restantes'] ?? 9999) <= 90,
            'Ate 120 dias' => fn ($c) => ($c['dias_restantes'] ?? 9999) > 90 && ($c['dias_restantes'] ?? 9999) <= 120,
            'Ate 150 dias' => fn ($c) => ($c['dias_restantes'] ?? 9999) > 120 && ($c['dias_restantes'] ?? 9999) <= 150,
            'Acima de 150 dias' => fn ($c) => ($c['dias_restantes'] ?? -1) > 150,
            'Indeterminados' => fn ($c) => empty($c['data_termino']),
            'Prorrogacao fora do prazo' => fn ($c) => ($c['prorrogacao_no_prazo'] ?? '') === 'Fora do prazo',
        ];
        $summary = [];
        foreach ($groups as $label => $fn) {
            $summary[$label] = count(array_filter($contracts, $fn));
        }

        $this->view('deadlines/index', [
            'title' => 'Prorrogacoes e Prazos',
            'contracts' => $contracts,
            'summary' => $summary,
            'filters' => $filters,
        ]);
    }
}
