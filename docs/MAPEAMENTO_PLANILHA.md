# Mapeamento da planilha

A planilha analisada foi `Contratos 2024.xlsm`. Ela possui 12 abas e 28.534 celulas com formulas.

## Abas identificadas

| Aba | Linhas | Colunas | Formulas | Uso no sistema |
| --- | ---: | ---: | ---: | --- |
| Contratos Vigentes | 892 | 53 | 19.755 | Base principal de contratos e ARPs cadastrados como contratos. |
| ARP execucao | 2.136 | 6 | 4.270 | Execucao financeira anual de ARPs. |
| ATA empresa valores | 155 | 14 | 598 | Cadastro de ARPs/atas e valores por fornecedor. |
| dinamica execucao ARP | 173 | 11 | 0 | Relatorio/pivot; substituido por consultas e graficos. |
| M.11 Contratos execucao | 361 | 25 | 1.785 | Execucao financeira e aditivos de contratos. |
| acompanhamento | 259 | 21 | 0 | Informacoes auxiliares de acompanhamento. |
| Gestao&Fiscalizacao | 278 | 7 | 275 | Servidores, unidades, cargas e e-mails. |
| Gestao e fiscalizacao atual | 266 | 12 | 1.850 | Carga atual por papel, convertida em consultas SQL. |
| Tabela dinamica | 175 | 41 | 1 | Relatorios consolidados, convertidos em dashboards/relatorios. |
| Validacao de Dados | 16 | 15 | 0 | Listas auxiliares para formas, bases legais e outros dominios. |
| SETOREQ | 11 | 2 | 0 | Cadastro de setores demandantes. |
| validacao dados | 25 | 5 | 0 | Naturezas, despesas e tipos auxiliares. |

## Campos principais

`Contratos Vigentes` alimenta `contratos` com:

- tipo, numero, ano e chave automatica;
- fornecedor, CNPJ/CPF e objeto;
- natureza, forma, tipo de contrato e base legal;
- licitacao/dispensa/inexigibilidade e processo/protocolo;
- setor demandante;
- inicio, termino, dias de contrato, dias restantes, situacao e faixa de prazo;
- valores inicial, atualizado, executado e acumulado;
- aditivos, prorrogacao, prazo legal, trimestre de vencimento e reajuste;
- gestor, gestores substitutos, fiscais e e-mails da equipe;
- texto automatico de notificacao.

`ATA empresa valores` alimenta `arps` com numero da ata, ano, chave, fornecedor, objeto, vigencia, setor, processo, valor por fornecedor e valor total.

`ARP execucao` e `M.11 Contratos execucao` alimentam `execucoes_financeiras` com chave, exercicio, valor inicial, valor atualizado, valor executado no exercicio, acumulado e saldo.

`Gestao&Fiscalizacao` alimenta `servidores` e ajuda a montar e-mails e cargas.

`SETOREQ`, `Validacao de Dados` e `validacao dados` alimentam cadastros auxiliares.

## Observacoes

- A planilha usa formulas, tabelas estruturadas, formulas de matriz, PROCV/VLOOKUP, COUNTIFS, CONCAT, TODAY e diferencas entre datas.
- O sistema substitui as formulas por services PHP e consultas SQL, mantendo os resultados calculados como campos persistidos quando isso facilita filtros e relatorios.
- Macros VBA nao sao executadas pela importacao; apenas dados, formulas e valores calculados existentes sao lidos.
