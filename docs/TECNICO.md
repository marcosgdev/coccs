# Documentacao tecnica

## Estrutura

```text
app/
  Controllers/
  Core/
  Models/
  Services/
  Views/
config/
database/
public/
storage/
docs/
scripts/
```

## Camadas

- `Core`: roteamento, request, view, base de controller/model, banco, auth e CSRF.
- `Controllers`: recebem request, validam acesso, chamam models/services e renderizam views.
- `Models`: acesso SQL via PDO e prepared statements.
- `Services`: regras de negocio, importacao Excel, auditoria e upload.
- `Views`: templates PHP com Bootstrap 5, DataTables e Chart.js.

## Banco

Schema principal: `database/schema.sql`.

Tabelas centrais:

- `usuarios`, `perfis`;
- `fornecedores`, `setores`, `servidores`;
- `contratos`, `arps`, `contrato_responsaveis`;
- `execucoes_financeiras`, `aditivos`, `prorrogacoes`;
- `notificacoes`, `anexos`;
- `parametros_sistema`;
- `logs_auditoria`, `logs_importacao`.

## Seguranca

- Login por senha com `password_hash`/`password_verify`.
- Sessao com cookie `httponly`, `SameSite=Lax` e `secure` quando HTTPS esta ativo.
- CSRF em formularios.
- PDO com prepared statements.
- Saida HTML escapada com `e()`.
- Upload com extensoes permitidas e nome seguro.
- Controle de perfis via `requirePermission()`.

## Identidade visual e acessibilidade

O sistema usa paleta institucional fixa:

- azul escuro: `#002952`;
- branco: `#FFFFFF`;
- dourado: `#D4AF37`.

Nao ha alternancia de tema por usuario. A acessibilidade e tratada por HTML semantico, foco visivel, contraste adequado e componentes Bootstrap usados com rotulos/ARIA quando necessario.

CSS principal: `public/assets/css/app.css`.

JavaScript principal: `public/assets/js/app.js`.

## Relatorios

Relatorios principais estao em `ReportsController`. Eles podem ser ampliados adicionando um novo caso em `build()` e uma opcao na view `reports/index.php`.

## Novas regras

Para novas regras derivadas da planilha:

1. Adicione metodo em `ContractRulesService`.
2. Chame esse metodo em `normalize()`.
3. Persista campo calculado se ele for usado em filtros/relatorios.
4. Documente em `docs/REGRAS_DE_NEGOCIO.md`.

## Auditoria

Use `AuditService::log()` em operacoes que alteram dados. A auditoria nao bloqueia a operacao principal se houver falha no log.
