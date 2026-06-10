# Importacao

O modulo de importacao usa PhpSpreadsheet para ler `.xlsm` e `.xlsx`. Ele nao executa macros VBA.

## Fluxo

1. Acesse `Importacao`.
2. Envie `Contratos 2024.xlsm`.
3. Clique em `Pre-visualizar`.
4. Revise abas, linhas, colunas, cabecalhos e contagem de formulas.
5. Escolha:
   - `Simular`: executa a leitura sem gravar dados principais.
   - `Importar`: grava dados no banco.
6. Escolha o modo de duplicidade:
   - `Ignorar duplicados`;
   - `Sobrescrever duplicados`.

## Abas tratadas

- `Contratos Vigentes`: contratos e ARPs tratados no cadastro principal.
- `ATA empresa valores`: ARPs/atas.
- `ARP execucao`: execucao financeira de ARPs.
- `M.11 Contratos execucao`: execucao financeira de contratos.
- `Gestao&Fiscalizacao`: servidores, unidades e e-mails.
- `SETOREQ`: setores.
- `Validacao de Dados` e `validacao dados`: cadastros auxiliares.

## Logs

Cada linha processada gera log em `logs_importacao` com:

- arquivo;
- aba;
- linha;
- status;
- mensagem;
- dados mapeados.

## Limitacoes conhecidas

- Macros VBA nao sao executadas.
- Formulas sao lidas por valor calculado quando possivel. Se a planilha estiver sem valores calculados salvos, o importador usa o valor bruto ou registra erro.
- Formulas de matriz podem depender do ultimo calculo salvo pelo Excel.
- Alguns cabecalhos variam entre versoes da planilha; se uma coluna mudar muito, inclua um novo alias em `ExcelImportService`.
- O importador atual guarda gestor/fiscal como texto quando a planilha nao traz identificador de servidor unico.

## Evolucao recomendada

- Criar uma tela de mapeamento manual de colunas por versao da planilha.
- Resolver fornecedores, setores e servidores por chaves normalizadas antes de gravar contratos.
- Validar conflitos de duplicidade por chave, numero/ano/tipo e CNPJ.
- Exportar um relatorio de erros em CSV.
