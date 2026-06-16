SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE TABLE IF NOT EXISTS import_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  arquivo VARCHAR(240) NOT NULL,
  modo ENUM('simulacao','importacao') NOT NULL,
  duplicate_mode VARCHAR(40) NOT NULL DEFAULT 'ignore',
  status VARCHAR(40) NOT NULL DEFAULT 'em_execucao',
  resultado LONGTEXT NULL,
  erros LONGTEXT NULL,
  started_by BIGINT UNSIGNED NULL,
  undone_by BIGINT UNSIGNED NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL,
  undone_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_import_batches_status (status),
  INDEX idx_import_batches_modo (modo),
  INDEX idx_import_batches_started (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perfis (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE,
  descricao TEXT NULL,
  codigo VARCHAR(60) NULL,
  documento VARCHAR(40) NULL,
  email VARCHAR(180) NULL,
  telefone VARCHAR(40) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servidores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(600) NOT NULL,
  matricula VARCHAR(60) NULL,
  cargo VARCHAR(120) NULL,
  unidade VARCHAR(120) NULL,
  email VARCHAR(180) NULL,
  telefone VARCHAR(40) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  import_batch_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_servidores_nome (nome),
  INDEX idx_servidores_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(180) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  perfil_id BIGINT UNSIGNED NOT NULL,
  servidor_id BIGINT UNSIGNED NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_usuarios_perfil FOREIGN KEY (perfil_id) REFERENCES perfis(id),
  CONSTRAINT fk_usuarios_servidor FOREIGN KEY (servidor_id) REFERENCES servidores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fornecedores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(240) NOT NULL,
  codigo VARCHAR(60) NULL,
  documento VARCHAR(40) NULL,
  email VARCHAR(180) NULL,
  telefone VARCHAR(40) NULL,
  descricao TEXT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_fornecedores_nome (nome),
  INDEX idx_fornecedores_documento (documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS setores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(180) NOT NULL,
  codigo VARCHAR(60) NULL,
  descricao TEXT NULL,
  documento VARCHAR(40) NULL,
  email VARCHAR(180) NULL,
  telefone VARCHAR(40) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  import_batch_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_setores_codigo (codigo),
  INDEX idx_setores_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS unidades LIKE setores;
CREATE TABLE IF NOT EXISTS naturezas_contratacao LIKE setores;
CREATE TABLE IF NOT EXISTS naturezas_despesa LIKE setores;
CREATE TABLE IF NOT EXISTS formas_contratacao LIKE setores;
CREATE TABLE IF NOT EXISTS tipos_contrato LIKE setores;
CREATE TABLE IF NOT EXISTS bases_legais LIKE setores;
CREATE TABLE IF NOT EXISTS modelos_notificacao LIKE setores;

CREATE TABLE IF NOT EXISTS contratos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('CONTRATO','ARP') NOT NULL DEFAULT 'CONTRATO',
  numero VARCHAR(40) NOT NULL,
  ano SMALLINT NOT NULL,
  chave VARCHAR(80) NOT NULL UNIQUE,
  fornecedor_id BIGINT UNSIGNED NULL,
  fornecedor_nome TEXT NULL,
  cnpj_cpf VARCHAR(40) NULL,
  objeto TEXT NULL,
  natureza_contratacao_id BIGINT UNSIGNED NULL,
  natureza_contratacao_nome VARCHAR(180) NULL,
  forma_contratacao_id BIGINT UNSIGNED NULL,
  forma_contratacao_nome VARCHAR(180) NULL,
  tipo_contrato_id BIGINT UNSIGNED NULL,
  tipo_contrato_nome VARCHAR(180) NULL,
  licitacao_numero VARCHAR(120) NULL,
  processo VARCHAR(120) NULL,
  setor_id BIGINT UNSIGNED NULL,
  setor_nome VARCHAR(180) NULL,
  data_inicio DATE NULL,
  data_termino DATE NULL,
  valor_global_inicial DECIMAL(16,2) NOT NULL DEFAULT 0,
  valor_global_atualizado DECIMAL(16,2) NOT NULL DEFAULT 0,
  valor_executado DECIMAL(16,2) NOT NULL DEFAULT 0,
  valor_acumulado_executado DECIMAL(16,2) NOT NULL DEFAULT 0,
  quantidade_aditivos INT NOT NULL DEFAULT 0,
  base_legal_id BIGINT UNSIGNED NULL,
  base_legal_nome VARCHAR(240) NULL,
  contrato_estrategico TINYINT(1) NOT NULL DEFAULT 0,
  import_batch_id BIGINT UNSIGNED NULL,
  gestor VARCHAR(600) NULL,
  gestor_substituto VARCHAR(600) NULL,
  fiscal_demandante VARCHAR(600) NULL,
  fiscal_tecnico VARCHAR(600) NULL,
  fiscal_substituto VARCHAR(600) NULL,
  fiscal_administrativo VARCHAR(600) NULL,
  emails_equipe TEXT NULL,
  observacoes TEXT NULL,
  situacao VARCHAR(40) NOT NULL DEFAULT 'Indeterminado',
  prazo VARCHAR(80) NULL,
  dias_contrato INT NULL,
  dias_restantes INT NULL,
  trimestre_vencimento VARCHAR(80) NULL,
  prazo_prorrogacao DATE NULL,
  data_recebimento_prorrogacao DATE NULL,
  prorrogacao_no_prazo VARCHAR(60) NULL,
  prazo_legal_classificacao VARCHAR(60) NULL,
  data_orcamento_estimado DATE NULL,
  status_reajuste VARCHAR(80) NULL,
  texto_notificacao LONGTEXT NULL,
  encerrado_em DATE NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_contratos_chave (chave),
  INDEX idx_contratos_situacao (situacao),
  INDEX idx_contratos_prazo (prazo),
  INDEX idx_contratos_termino (data_termino),
  INDEX idx_contratos_setor (setor_nome),
  INDEX idx_contratos_fornecedor (fornecedor_nome(191)),
  CONSTRAINT fk_contratos_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id),
  CONSTRAINT fk_contratos_setor FOREIGN KEY (setor_id) REFERENCES setores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS arps (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero_ata VARCHAR(40) NOT NULL,
  ano SMALLINT NOT NULL,
  chave VARCHAR(80) NOT NULL,
  fornecedor_id BIGINT UNSIGNED NULL,
  fornecedor_nome TEXT NULL,
  objeto TEXT NULL,
  vigencia_inicial DATE NULL,
  vigencia_final DATE NULL,
  valor_total DECIMAL(16,2) NOT NULL DEFAULT 0,
  valor_por_fornecedor DECIMAL(16,2) NOT NULL DEFAULT 0,
  valor_executado DECIMAL(16,2) NOT NULL DEFAULT 0,
  saldo DECIMAL(16,2) NOT NULL DEFAULT 0,
  setor_id BIGINT UNSIGNED NULL,
  setor_nome VARCHAR(180) NULL,
  observacoes TEXT NULL,
  situacao VARCHAR(40) NOT NULL DEFAULT 'Indeterminado',
  dias_restantes INT NULL,
  import_batch_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_arps_chave (chave),
  INDEX idx_arps_situacao (situacao),
  INDEX idx_arps_vigencia_final (vigencia_final)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contrato_responsaveis (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contrato_id BIGINT UNSIGNED NOT NULL,
  servidor_id BIGINT UNSIGNED NULL,
  servidor_nome VARCHAR(180) NOT NULL,
  papel ENUM('gestor','gestor_substituto','fiscal_demandante','fiscal_tecnico','fiscal_substituto','fiscal_administrativo') NOT NULL,
  data_inicio DATE NULL,
  data_fim DATE NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_resp_contrato FOREIGN KEY (contrato_id) REFERENCES contratos(id),
  CONSTRAINT fk_resp_servidor FOREIGN KEY (servidor_id) REFERENCES servidores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS execucoes_financeiras (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contrato_id BIGINT UNSIGNED NULL,
  arp_id BIGINT UNSIGNED NULL,
  chave VARCHAR(80) NOT NULL,
  exercicio SMALLINT NOT NULL,
  valor_inicial DECIMAL(16,2) NOT NULL DEFAULT 0,
  valor_atualizado DECIMAL(16,2) NOT NULL DEFAULT 0,
  valor_executado_exercicio DECIMAL(16,2) NOT NULL DEFAULT 0,
  valor_acumulado DECIMAL(16,2) NOT NULL DEFAULT 0,
  saldo DECIMAL(16,2) NOT NULL DEFAULT 0,
  import_batch_id BIGINT UNSIGNED NULL,
  observacoes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_exec_chave (chave),
  INDEX idx_exec_exercicio (exercicio),
  CONSTRAINT fk_exec_contrato FOREIGN KEY (contrato_id) REFERENCES contratos(id),
  CONSTRAINT fk_exec_arp FOREIGN KEY (arp_id) REFERENCES arps(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aditivos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contrato_id BIGINT UNSIGNED NOT NULL,
  numero_aditivo VARCHAR(80) NULL,
  tipo_aditivo VARCHAR(120) NULL,
  data_aditivo DATE NULL,
  objeto TEXT NULL,
  valor_acrescido DECIMAL(16,2) NOT NULL DEFAULT 0,
  valor_suprimido DECIMAL(16,2) NOT NULL DEFAULT 0,
  nova_data_termino DATE NULL,
  justificativa TEXT NULL,
  anexo_path VARCHAR(500) NULL,
  observacoes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_aditivos_contrato FOREIGN KEY (contrato_id) REFERENCES contratos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prorrogacoes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contrato_id BIGINT UNSIGNED NOT NULL,
  data_limite DATE NULL,
  data_recebimento DATE NULL,
  status VARCHAR(60) NULL,
  observacoes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_prorrogacoes_contrato FOREIGN KEY (contrato_id) REFERENCES contratos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificacoes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contrato_id BIGINT UNSIGNED NULL,
  arp_id BIGINT UNSIGNED NULL,
  tipo VARCHAR(80) NOT NULL,
  assunto VARCHAR(240) NULL,
  texto LONGTEXT NOT NULL,
  destinatarios TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'rascunho',
  data_envio DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_notif_contrato FOREIGN KEY (contrato_id) REFERENCES contratos(id),
  CONSTRAINT fk_notif_arp FOREIGN KEY (arp_id) REFERENCES arps(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS anexos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contrato_id BIGINT UNSIGNED NULL,
  arp_id BIGINT UNSIGNED NULL,
  aditivo_id BIGINT UNSIGNED NULL,
  nome_original VARCHAR(240) NOT NULL,
  path VARCHAR(500) NOT NULL,
  mime VARCHAR(120) NULL,
  tamanho BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parametros_sistema (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  chave VARCHAR(120) NOT NULL UNIQUE,
  valor VARCHAR(255) NOT NULL,
  descricao TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_auditoria (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NULL,
  acao VARCHAR(120) NOT NULL,
  tabela VARCHAR(120) NOT NULL,
  registro_id BIGINT UNSIGNED NULL,
  valores_anteriores LONGTEXT NULL,
  valores_novos LONGTEXT NULL,
  ip VARCHAR(60) NULL,
  user_agent VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_tabela (tabela),
  INDEX idx_audit_acao (acao),
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_importacao (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NULL,
  arquivo VARCHAR(240) NULL,
  aba VARCHAR(120) NULL,
  linha INT NULL,
  status VARCHAR(40) NOT NULL,
  import_batch_id BIGINT UNSIGNED NULL,
  modo ENUM('simulacao','importacao') NULL,
  mensagem TEXT NULL,
  dados LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_import_aba (aba),
  INDEX idx_import_status (status),
  INDEX idx_import_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO perfis (id, nome, slug, descricao) VALUES
(1, 'Administrador', 'administrador', 'Acesso total.'),
(2, 'Gestor de Contratos', 'gestor-contratos', 'Cadastro e manutencao de contratos, ARPs e notificacoes.'),
(3, 'Setor Demandante', 'setor-demandante', 'Consulta seus contratos e atualiza informacoes permitidas.'),
(4, 'Gestor/Fiscal', 'gestor-fiscal', 'Consulta contratos em que atua e registra acompanhamento.'),
(5, 'Consulta Gerencial', 'consulta-gerencial', 'Consulta paineis e relatorios.'),
(6, 'Auditoria/Controle', 'auditoria-controle', 'Consulta geral, inclusive logs, sem alterar dados.'),
(7, 'Usuario comum', 'usuario-comum', 'Acesso operacional sem area administrativa.');

INSERT IGNORE INTO usuarios (id, nome, email, password_hash, perfil_id, ativo) VALUES
(1, 'Administrador', 'admin@gestcontratos.local', '$2y$10$iQPPtvKfjL8id2ULtY4c6upT6a3ZtEZM6frVSJp7VO6HLZH1b9wMW', 1, 1),
(2, 'Usuario comum', 'usuario@gestcontratos.local', '$2y$10$gzgX/ybr2gjw8TeO1wGgoOWI8MDit3FxZ100yEOESNrC.v8vC8bBG', 7, 1);

INSERT IGNORE INTO parametros_sistema (chave, valor, descricao) VALUES
('limite_prazo_legal_dias', '1800', 'Limite em dias para classificar contrato como prazo legal.'),
('limite_prazo_excepcional_dias', '2130', 'Limite em dias para classificar contrato como excepcional.'),
('dias_antecedencia_prorrogacao', '60', 'Dias antes do termino usados para calcular prazo de prorrogacao.'),
('dias_reajuste_orcamento', '365', 'Dias para indicar necessidade de reajuste do orcamento estimado.'),
('carga_maxima_servidor', '10', 'Carga de fiscalizacao sugerida para alerta gerencial.');
