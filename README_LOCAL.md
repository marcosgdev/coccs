# GestContratos - uso local

## Iniciar depois de reiniciar o computador

1. Abra a pasta do projeto.
2. Dê dois cliques em `iniciar-local.bat`.
3. Aguarde a janela indicar que o servidor PHP iniciou.
4. O navegador deve abrir em `http://localhost:8080/`.

A janela do `.bat` deve ficar aberta enquanto o sistema estiver em uso, pois ela mostra os logs do servidor local.

## Parar o servidor local

Use uma das opcoes:

- pressione `Ctrl+C` na janela do servidor;
- execute `parar-local.bat`.

## Dependencias

O ambiente local usa:

- PHP no `PATH`;
- dependencias do Composer em `vendor`;
- MySQL/MariaDB configurado no `.env`;
- porta local `8080`.

O script tenta iniciar o servico `MySQL80` quando ele existe. Se o banco tiver outro nome de servico, inicie-o manualmente antes de abrir o sistema.

## Acessos de demonstracao

- Administrador: `admin@gestcontratos.local` / `Admin@123`
- Usuario comum: `usuario@gestcontratos.local` / `Usuario@123`

## Problemas comuns

### A porta 8080 ja esta em uso

Execute `parar-local.bat` e tente iniciar novamente. Se outro programa estiver usando a porta, encerre esse programa ou ajuste o comando de inicializacao.

### Erro de banco de dados

Verifique:

- se o MySQL/MariaDB esta rodando;
- se o `.env` aponta para banco, usuario e senha corretos;
- se o schema foi criado com `database/schema.sql`.

### Pagina abre com URL estranha

Use sempre:

```text
http://localhost:8080/
```

Evite colar uma URL completa dentro da barra do app quando ele ja estiver em `localhost`.

### Tela branca ou erro interno

Veja o arquivo:

```text
storage/logs/app.log
```

Depois rode:

```text
php scripts/check_syntax.php
```
