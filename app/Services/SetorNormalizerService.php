<?php

namespace GestContratos\Services;

/**
 * Centraliza a normalização de nomes de secretarias/setores.
 * Lê as regras de setor_mapeamentos (ativo=1) e aplica em PHP ou gera SQL CASE.
 * Semeia os mapeamentos padrão se a tabela estiver vazia.
 */
final class SetorNormalizerService
{
    /** Mapa UPPER(origem) => destino */
    private array $map = [];

    /** Mapeamentos padrão entregues com o sistema. */
    private static array $defaults = [
        'SEADM'                                                => 'Secretaria de Administração',
        'SECRETARIA DE ADMINISTRAÇÃO'                          => 'Secretaria de Administração',
        'SECRETARIA DE ADMINISTRACAO'                          => 'Secretaria de Administração',

        'SETIC'                                                => 'Secretaria de Tecnologia da Informação e Comunicação',
        'SECRETARIA DE TECNOLOGIA DA INFORMAÇÃO E COMUNICAÇÃO' => 'Secretaria de Tecnologia da Informação e Comunicação',
        'SECRETARIA DE TECNOLOGIA DE INFORMAÇÃO E COMUNICAÇÃO' => 'Secretaria de Tecnologia da Informação e Comunicação',
        'SECRETARIA DE TECNOLOGIA DA INFORMACAO E COMUNICACAO' => 'Secretaria de Tecnologia da Informação e Comunicação',
        'SECRETARIA DE TECNOLOGIA DE INFORMACAO E COMUNICACAO' => 'Secretaria de Tecnologia da Informação e Comunicação',

        'SEENG'                                                => 'Secretaria de Engenharia e Arquitetura',
        'SECRETARIA DE ENGENHARIA E ARQUITETURA'               => 'Secretaria de Engenharia e Arquitetura',

        'DECOM'                                                => 'Coordenadoria de Comunicação',
        'COORDENADORIA DE COMUNICAÇÃO'                         => 'Coordenadoria de Comunicação',
        'COORDENADORIA DE COMUNICACAO'                         => 'Coordenadoria de Comunicação',

        'COMIL'                                                => 'Coordenadoria Militar',
        'COORDENADORIA MILITAR'                                => 'Coordenadoria Militar',
        'COORDENADORIA MILITAR DO LAURO SODRÉ'                => 'Coordenadoria Militar',
        'COORDENADORIA MILITAR DO LAURO SODRE'                => 'Coordenadoria Militar',

        'SEFIN'                                                      => 'Secretaria de Planejamento, Coordenação e Finanças',
        'SECRETARIA DE PLANEJAMENTO, COORDENAÇÃO E FINANÇAS'         => 'Secretaria de Planejamento, Coordenação e Finanças',
        'SECRETARIA DE PLANEJAMENTO, COORDENACAO E FINANCAS'         => 'Secretaria de Planejamento, Coordenação e Finanças',
        'SECRETARIA DE PLANEJAMENTO E COORD E FINANÇAS'              => 'Secretaria de Planejamento, Coordenação e Finanças',
        'SECRETARIA DE PLANEJAMENTO E COORD E FINANCAS'              => 'Secretaria de Planejamento, Coordenação e Finanças',
        'SECRETARIA DE FINANÇAS'                                     => 'Secretaria de Planejamento, Coordenação e Finanças',
        'SECRETARIA DE FINANCAS'                                     => 'Secretaria de Planejamento, Coordenação e Finanças',

        'SEGEP'                                                => 'Secretaria de Gestão de Pessoas',
        'SECRETARIA DE GESTÃO DE PESSOAS'                      => 'Secretaria de Gestão de Pessoas',
        'SECRETARIA DE GESTAO DE PESSOAS'                      => 'Secretaria de Gestão de Pessoas',

        'EJPA'                                                 => 'Escola Judicial do Estado do Pará',
        'ESCOLA JUDICIAL DO ESTADO DO PARÁ'                    => 'Escola Judicial do Estado do Pará',
        'ESCOLA JUDICIAL DO ESTADO DO PARA'                    => 'Escola Judicial do Estado do Pará',
    ];

    public function __construct(\PDO $pdo)
    {
        // Garante que a tabela existe (idempotente)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS setor_mapeamentos (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                nome_origem  VARCHAR(255) NOT NULL,
                nome_destino VARCHAR(255) NOT NULL,
                ativo        TINYINT(1)  NOT NULL DEFAULT 1,
                observacao   VARCHAR(500) NULL,
                created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_origem (nome_origem)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Semeia padrões (INSERT IGNORE é idempotente pela UNIQUE KEY em nome_origem)
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO setor_mapeamentos (nome_origem, nome_destino) VALUES (?, ?)"
        );
        foreach (self::$defaults as $origem => $destino) {
            $stmt->execute([$origem, $destino]);
        }

        // Carrega mapeamentos ativos
        $rows = $pdo->query(
            "SELECT nome_origem, nome_destino FROM setor_mapeamentos WHERE ativo = 1"
        )->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $this->map[mb_strtoupper(trim($r['nome_origem']))] = $r['nome_destino'];
        }
    }

    /**
     * Normaliza um nome de setor em PHP.
     */
    public function normalize(?string $nome, string $fallback = 'Sem secretaria'): string
    {
        if ($nome === null || trim($nome) === '') return $fallback;
        return $this->map[mb_strtoupper(trim($nome))] ?? $nome;
    }

    /**
     * Gera expressão SQL CASE WHEN para normalizar $col dentro de uma query.
     * Usar em SELECT e GROUP BY (referenciado como alias ou posição).
     */
    public function sqlCase(string $col, string $fallback = "'Sem secretaria'"): string
    {
        if (empty($this->map)) {
            return "COALESCE(NULLIF($col, ''), $fallback)";
        }

        $sql = 'CASE';
        foreach ($this->map as $origem => $destino) {
            $o    = str_replace("'", "''", $origem);
            $d    = str_replace("'", "''", $destino);
            $sql .= " WHEN UPPER($col) = '$o' THEN '$d'";
        }
        $sql .= " ELSE COALESCE(NULLIF($col, ''), $fallback) END";
        return $sql;
    }
}
