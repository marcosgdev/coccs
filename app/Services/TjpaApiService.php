<?php

namespace GestContratos\Services;

final class TjpaApiService
{
    private const BASE_URL = 'https://tjpa.thema.inf.br/transparencia/dados-abertos';
    // Anos com contratos Ativo confirmados na API (2016-2026).
    // A API exige descricaoSituacaoContrato=Ativo para retornar resultados sem timeout.
    private const ALL_YEARS = [2016, 2017, 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025, 2026];
    private const EMPENHO_CONCURRENCY = 6;

    public function searchContratos(array $params): array
    {
        $params = $this->clean($params);

        if (!empty($params['exercicio'])) {
            return $this->post('/contrato/', $params);
        }

        // Consulta por ano com filtro Ativo: única forma confiável — chamadas sem
        // descricaoSituacaoContrato travam (timeout) para vários anos.
        $ativos = array_merge(
            ['descricaoSituacaoContrato' => 'Ativo'],
            $params
        );
        $byYear = $this->parallelPost('/contrato/', $ativos, 'exercicio', self::ALL_YEARS);
        $all    = array_merge(...array_values($byYear));

        $unique = [];
        foreach ($all as $c) {
            $key = trim((string)($c['numero'] ?? $c['numeroExibicao'] ?? ''))
                 . '/' . (int)($c['exercicio'] ?? 0);
            if ($key !== '/0') {
                $unique[$key] = $c;
            }
        }

        return array_values($unique);
    }

    public function searchAditivos(array $params): array
    {
        return $this->post('/ajusteContrato/', $this->clean($params));
    }

    public function getEmpenhosBatch(array $empenhoIds): array
    {
        $parsed = [];
        foreach ($empenhoIds as $rawId) {
            if (preg_match('/^(\d+)-(\d+)\/(\d+)$/', $rawId, $m)) {
                $parsed[$rawId] = ['adm' => $m[1], 'exercicio' => $m[2], 'numero' => $m[3]];
            }
        }

        $result = [];
        foreach (array_chunk(array_keys($parsed), self::EMPENHO_CONCURRENCY, true) as $chunk) {
            $mh = curl_multi_init();
            $handles = [];
            foreach ($chunk as $rawId) {
                $p = $parsed[$rawId];
                $ch = $this->buildCurl('/empenho/', [
                    'exercicio'    => $p['exercicio'],
                    'numeroEmpenho' => $p['numero'],
                ]);
                curl_multi_add_handle($mh, $ch);
                $handles[] = ['ch' => $ch, 'raw' => $rawId, 'adm' => $p['adm']];
            }
            $this->execMulti($mh);
            foreach ($handles as $h) {
                $body = curl_multi_getcontent($h['ch']);
                curl_multi_remove_handle($mh, $h['ch']);
                curl_close($h['ch']);
                $data = ($body && ($decoded = json_decode($body, true)) && is_array($decoded)) ? $decoded : [];
                $filtered = array_values(array_filter($data, fn ($e) => (string) ($e['administracao'] ?? '') === $h['adm']));
                $result[$h['raw']] = $filtered[0] ?? null;
            }
            curl_multi_close($mh);
        }

        return $result;
    }

    private function post(string $endpoint, array $params): array
    {
        $ch = $this->buildCurl($endpoint, $params);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err || !$body) return [];
        if ($http !== 200) return [];
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function parallelPost(string $endpoint, array $baseParams, string $yearKey, array $years): array
    {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($years as $year) {
            $ch = $this->buildCurl($endpoint, $baseParams + [$yearKey => (string) $year]);
            curl_multi_add_handle($mh, $ch);
            $handles[(string) $year] = $ch;
        }
        $this->execMulti($mh);
        $results = [];
        $errors  = [];
        foreach ($handles as $year => $ch) {
            $body = curl_multi_getcontent($ch);
            $err  = curl_error($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            if ($err) {
                $errors[] = "cURL {$year}: {$err}";
                continue;
            }
            if ($http && $http !== 200) {
                $errors[] = "HTTP {$http} para exercicio={$year}";
                continue;
            }
            if ($body) {
                $decoded = json_decode($body, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $results[] = $decoded;
                }
            }
        }
        curl_multi_close($mh);
        if ($errors && !$results) {
            throw new \RuntimeException('Falha na API TJPA: ' . implode('; ', $errors));
        }
        return $results ?: [[]];
    }

    private function buildCurl(string $endpoint, array $params): \CurlHandle
    {
        $url  = self::BASE_URL . $endpoint . '?' . http_build_query($params);
        $cert = $this->caCertPath();
        $ch   = curl_init($url);
        $opts = [
            CURLOPT_POST            => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HTTPHEADER      => ['Accept: application/json'],
            // O servidor TJPA inclui o cert raiz na cadeia, o que o OpenSSL rejeita.
            // Dado que a API é pública (dados abertos), desabilitar peer verify é aceitável.
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => 2,
            // Ignora proxy do sistema Windows para conexões diretas à API pública
            CURLOPT_PROXY           => '',
        ];
        curl_setopt_array($ch, $opts);
        return $ch;
    }

    private function execMulti(\CurlMultiHandle $mh): void
    {
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);
    }

    /**
     * Busca aditivos detalhados (com alteracoes[].dataFinal) para uma lista de contratos.
     * Retorna array keyed por "numero/exercicio" → array de aditivos com nova_data_termino.
     *
     * @param  array<array{numero:string,exercicio:string}> $contratos
     * @return array<string, list<array{numero_aditivo:string, tipo:string, data_final:string|null}>>
     */
    public function fetchAditivosDetalhados(array $contratos): array
    {
        if (!$contratos) return [];

        $results = [];
        foreach (array_chunk($contratos, 10) as $chunk) {
            $mh      = curl_multi_init();
            $handles = [];
            foreach ($chunk as $c) {
                $ch = $this->buildCurl('/ajusteContrato/', [
                    'numeroContrato'  => $c['numero'],
                    'exercicioContrato' => $c['exercicio'],
                ]);
                curl_multi_add_handle($mh, $ch);
                $handles[] = ['ch' => $ch, 'key' => $c['numero'] . '/' . $c['exercicio']];
            }
            $this->execMulti($mh);
            foreach ($handles as $h) {
                $body = curl_multi_getcontent($h['ch']);
                curl_multi_remove_handle($mh, $h['ch']);
                curl_close($h['ch']);
                $data = json_decode((string) $body, true);
                if (!is_array($data)) continue;
                $aditivos = [];
                foreach ($data as $aditivo) {
                    $numAd = (string) ($aditivo['numeroAditivo'] ?? '');
                    if ($numAd === '') continue;
                    foreach (($aditivo['alteracoes'] ?? []) as $alt) {
                        if (empty($alt['dataFinal'])) continue;
                        $tipo = $alt['descricaoTipo'] ?? '';
                        if (stripos($tipo, 'prorrog') === false) continue;
                        // dataFinal vem como "2024-12-09 00:00:00" ou "DD/MM/YYYY"
                        $raw = trim($alt['dataFinal']);
                        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
                            $dataFinal = "$m[1]-$m[2]-$m[3]";
                        } elseif (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $raw, $m)) {
                            $dataFinal = "$m[3]-$m[2]-$m[1]";
                        } else {
                            continue;
                        }
                        $aditivos[] = ['numero_aditivo' => $numAd, 'data_final' => $dataFinal];
                    }
                }
                $results[$h['key']] = $aditivos;
            }
            curl_multi_close($mh);
        }
        return $results;
    }

    /**
     * Busca detalhes de liquidação para uma lista de empenhos em paralelo.
     * Formato do número: "2-2024/8031" → exercicio=2024, numero=8031
     *
     * @param  string[] $empenhos  Números no formato original da API
     * @return array<string, array{valorLiquidacao:float, valorPago:float}>
     */
    public function fetchLiquidacoes(array $empenhos): array
    {
        $parsed = [];
        foreach ($empenhos as $emp) {
            if (preg_match('/^[^-]+-(\d{4})\/(\d+)$/', $emp, $m)) {
                $parsed[$emp] = ['exercicio' => $m[1], 'numeroEmpenho' => $m[2]];
            }
        }
        if (!$parsed) return [];

        $results = [];
        $chunks  = array_chunk(array_keys($parsed), 20, true);

        foreach ($chunks as $chunk) {
            $mh      = curl_multi_init();
            $handles = [];
            foreach ($chunk as $emp) {
                $p    = $parsed[$emp];
                $url  = self::BASE_URL . '/empenho/?' . http_build_query($p);
                $ch   = curl_init($url);
                $opts = [
                    CURLOPT_POST           => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 20,
                    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ];
                curl_setopt_array($ch, $opts);
                curl_multi_add_handle($mh, $ch);
                $handles[$emp] = $ch;
            }
            $this->execMulti($mh);
            foreach ($handles as $emp => $ch) {
                $body = curl_multi_getcontent($ch);
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
                $data = json_decode((string) $body, true);
                $row  = is_array($data) ? ($data[0] ?? null) : null;
                if ($row) {
                    $results[$emp] = [
                        'valorLiquidacao'  => (float) ($row['valorLiquidacao'] ?? 0),
                        'valorPago'        => (float) ($row['valorPagamentoAtualLiquido'] ?? 0),
                        'liquidacoes'      => $row['liquidacoesComprovantesEmpenho'] ?? [],
                    ];
                }
            }
            curl_multi_close($mh);
        }
        return $results;
    }

    private function caCertPath(): ?string
    {
        // Caminho explícito do cacert.pem instalado junto ao PHP via winget
        $candidates = [
            ini_get('curl.cainfo'),
            dirname((string) (PHP_BINARY ?: '')) . '/cacert.pem',
            __DIR__ . '/../../../../cacert.pem',
        ];
        foreach ($candidates as $p) {
            if ($p && is_file($p)) return $p;
        }
        return null;
    }

    private function clean(array $params): array
    {
        return array_filter($params, fn ($v) => $v !== '' && $v !== null);
    }
}
