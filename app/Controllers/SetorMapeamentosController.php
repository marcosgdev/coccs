<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;
use GestContratos\Core\Csrf;
use GestContratos\Core\Database;
use GestContratos\Core\Request;

final class SetorMapeamentosController extends Controller
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS setor_mapeamentos (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                nome_origem  VARCHAR(255) NOT NULL,
                nome_destino VARCHAR(255) NOT NULL,
                ativo        TINYINT(1)  NOT NULL DEFAULT 1,
                observacao   VARCHAR(500) NULL,
                created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_origem (nome_origem)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function index(): void
    {
        $this->requirePermission(['administrador']);
        $rows = $this->pdo->query(
            "SELECT * FROM setor_mapeamentos ORDER BY ativo DESC, nome_origem ASC"
        )->fetchAll();

        // Setores distintos da base para sugerir no formulário
        $setoresBase = $this->pdo->query(
            "SELECT DISTINCT setor_nome FROM contratos WHERE setor_nome IS NOT NULL AND setor_nome != '' ORDER BY setor_nome"
        )->fetchAll(\PDO::FETCH_COLUMN);

        $this->view('setor_mapeamentos/index', [
            'title'       => 'Mapeamento de Unidades Gestoras',
            'rows'        => $rows,
            'setoresBase' => $setoresBase,
        ]);
    }

    public function store(Request $request): void
    {
        $this->requirePermission(['administrador']);
        if (!Csrf::verify((string) $request->input('_csrf', ''))) {
            flash('danger', 'Sessão expirada.');
            redirect('/mapeamento-setores');
        }

        $origem  = trim($request->body['nome_origem']  ?? '');
        $destino = trim($request->body['nome_destino'] ?? '');
        $obs     = trim($request->body['observacao']   ?? '');

        if (!$origem || !$destino) {
            flash('danger', 'Preencha os dois campos obrigatórios.');
            redirect('/mapeamento-setores');
        }

        $this->pdo->prepare(
            "INSERT INTO setor_mapeamentos (nome_origem, nome_destino, observacao)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE nome_destino=VALUES(nome_destino), observacao=VALUES(observacao), ativo=1, updated_at=NOW()"
        )->execute([$origem, $destino, $obs ?: null]);

        flash('success', "Regra criada: \"$origem\" → \"$destino\".");
        redirect('/mapeamento-setores');
    }

    public function toggle(Request $request, string $id): void
    {
        $this->requirePermission(['administrador']);
        if (!Csrf::verify((string) $request->input('_csrf', ''))) {
            flash('danger', 'Sessão expirada.');
            redirect('/mapeamento-setores');
        }

        $this->pdo->prepare(
            "UPDATE setor_mapeamentos SET ativo = IF(ativo=1,0,1) WHERE id = ?"
        )->execute([(int) $id]);

        flash('success', 'Status da regra atualizado.');
        redirect('/mapeamento-setores');
    }

    public function delete(Request $request, string $id): void
    {
        $this->requirePermission(['administrador']);
        if (!Csrf::verify((string) $request->input('_csrf', ''))) {
            flash('danger', 'Sessão expirada.');
            redirect('/mapeamento-setores');
        }

        $this->pdo->prepare("DELETE FROM setor_mapeamentos WHERE id = ?")->execute([(int) $id]);

        flash('success', 'Regra removida.');
        redirect('/mapeamento-setores');
    }
}
