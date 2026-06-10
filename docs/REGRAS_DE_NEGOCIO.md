# Regras de negocio convertidas

As principais formulas da planilha foram convertidas em `app/Services/ContractRulesService.php` e em consultas dos controllers de dashboard, prazos, gestao/fiscalizacao e relatorios.

## Chave automatica

Planilha: concatenava tipo, numero com tres digitos e ano.

Sistema:

- `CONTRATO018/2021`
- `ARP060/2024`

Implementacao: `ContractRulesService::generateKey()`.

## Dias de contrato

Planilha: `DAYS(termino, inicio)`.

Sistema: `dias_contrato = data_termino - data_inicio`.

## Dias restantes

Planilha: se termino vazio, retorna marcador; se vencido, `EXPIRADO`; caso contrario, `termino - TODAY()`.

Sistema:

- sem termino: `Indeterminado`;
- termino passado: numero negativo e situacao `Expirado`;
- termino futuro: quantidade de dias restantes.

## Situacao

- termino vazio: `Indeterminado`;
- termino maior ou igual a hoje: `Vigente`;
- termino menor que hoje: `Expirado`.

## Faixa de prazo

Convertida para badges:

- `Expirado`;
- `Inferior a 30 dias`;
- `Inferior a 60 dias`;
- `Inferior a 90 dias`;
- `Inferior a 120 dias`;
- `Inferior a 150 dias`;
- `150 dias`;
- `Superior a 150 dias`;
- `Indeterminado`.

## Prazo legal, excepcional ou emergencial

Planilha: comparava `DIAS_CONTRATO` com 1.800 e 2.130 dias.

Sistema: usa parametros alteraveis:

- `limite_prazo_legal_dias`;
- `limite_prazo_excepcional_dias`.

## Trimestre de vencimento

Planilha: `YEAR(termino) & " - " & trimestre`.

Sistema: gera texto como `2026 - 2o Trimestre`.

## Prazo de prorrogacao

Planilha: `termino - 60`.

Sistema: usa `dias_antecedencia_prorrogacao`, com padrao `60`.

## Prorrogacao apresentada no prazo

Sistema compara:

- data de recebimento;
- prazo limite calculado.

Resultados:

- `Dentro do prazo`;
- `Fora do prazo`;
- `Sem informacao`.

## Reajuste

Planilha: se o orcamento estimado tem mais de 365 dias, recomenda iniciar reajuste.

Sistema: usa `dias_reajuste_orcamento`, com padrao `365`.

## Carga de fiscalizacao

Planilha: usava `COUNTIFS` por servidor e papel.

Sistema: usa consultas SQL somando aparicoes em:

- gestor;
- fiscal demandante;
- fiscal tecnico;
- gestor substituto;
- fiscal substituto;
- fiscal administrativo.

## E-mails da equipe

Planilha: usava buscas em `Gestao&Fiscalizacao`.

Sistema: importa e persiste `emails_equipe` quando disponivel. A evolucao recomendada e montar os e-mails dinamicamente por `contrato_responsaveis` e `servidores`.

## Notificacao automatica

Planilha: formula variava texto conforme base legal, especialmente Lei 8.666 e Lei 14.133.

Sistema: `ContractRulesService::notificationText()` monta um modelo parametrizado com:

- base legal;
- chave do contrato/ARP;
- setor;
- termino;
- faixa de prazo;
- gestor;
- marcador de contrato estrategico.
