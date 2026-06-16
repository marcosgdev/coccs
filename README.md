# GestContratos

Sistema web para gestao de contratos, ARPs, execucao financeira, prazos, fiscalizacao, notificacoes, relatorios e importacao de planilhas.

## Iniciar localmente no Windows

Execute:

```bat
iniciar-local.bat
```

Para parar:

```bat
parar-local.bat
```

Mais detalhes em `README_LOCAL.md`.

## Acessos de demonstracao

- Administrador: `admin@gestcontratos.local` / `Admin@123`
- Usuario comum: `usuario@gestcontratos.local` / `Usuario@123`

## Manuais internos

Depois de logar, acesse o menu **Manuais**:

- Manual de Uso;
- Manual de Manutencao;
- Manual de Implantacao, apenas para Administrador.

## Comandos uteis

```bash
composer install
composer serve
composer check
composer validate --strict
php scripts/import_spreadsheet.php "Contratos 2024.xlsm"
```
