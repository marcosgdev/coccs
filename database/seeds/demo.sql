INSERT INTO fornecedores (nome, documento, ativo) VALUES
('Fornecedor Exemplo Ltda', '00.000.000/0001-00', 1);

INSERT INTO setores (codigo, nome, descricao, ativo) VALUES
('SETIC', 'Secretaria de Tecnologia da Informacao', 'Setor demandante de exemplo', 1);

INSERT INTO servidores (nome, matricula, cargo, unidade, email, ativo) VALUES
('Servidor Gestor Exemplo', '12345', 'Analista Judiciario', 'SETIC', 'gestor.exemplo@tjpa.jus.br', 1);

INSERT INTO contratos (
  tipo, numero, ano, chave, fornecedor_nome, cnpj_cpf, objeto, natureza_contratacao_nome,
  forma_contratacao_nome, tipo_contrato_nome, licitacao_numero, processo, setor_nome,
  data_inicio, data_termino, valor_global_inicial, valor_global_atualizado, valor_executado,
  valor_acumulado_executado, base_legal_nome, gestor, fiscal_demandante, situacao, prazo,
  dias_contrato, dias_restantes, trimestre_vencimento, prazo_prorrogacao,
  prorrogacao_no_prazo, prazo_legal_classificacao, status_reajuste, texto_notificacao
) VALUES (
  'CONTRATO', '001', 2026, 'CONTRATO001/2026', 'Fornecedor Exemplo Ltda',
  '00.000.000/0001-00', 'Prestacao de servicos continuados de exemplo.',
  'Servico de Terceiro', 'Pregao Eletronico', 'Despesa', '001/2026', 'PA-001/2026',
  'SETIC', '2026-01-01', '2026-12-31', 100000.00, 120000.00, 20000.00,
  20000.00, 'Lei 14.133/2021', 'Servidor Gestor Exemplo', 'Servidor Gestor Exemplo',
  'Vigente', 'Superior a 150 dias', 364, 208, '2026 - 4o Trimestre', '2026-11-01',
  'Sem informacao', 'Prazo legal', 'Aguardar anualidade',
  'Notificacao de exemplo gerada pelas regras do sistema.'
);
