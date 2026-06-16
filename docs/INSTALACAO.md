# Instalacao

## 1. Preparar ambiente

Instale PHP 8.1 ou superior, Composer e MySQL/MariaDB. Confirme:

```bash
php -v
composer --version
```

## 2. Instalar dependencias

Na raiz do projeto:

```bash
composer install
```

## 3. Configurar ambiente

Copie `.env.example` para `.env` e ajuste:

```env
APP_URL=http://localhost:8080
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestcontratos
DB_USERNAME=root
DB_PASSWORD=
```

## 4. Criar banco

```sql
CREATE DATABASE gestcontratos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Depois aplique schema e seed essencial:

```bash
php database/seed.php
```

Opcionalmente, aplique dados de demonstracao:

```bash
mysql -u root -p gestcontratos < database/seeds/demo.sql
```

## 5. Rodar localmente

No Windows, use:

```bat
iniciar-local.bat
```

Ou, pelo Composer:

```bash
composer serve
```

Acesse `http://localhost:8080`.

## 6. Usuarios iniciais

- Administrador: `admin@gestcontratos.local` / `Admin@123`
- Usuario comum: `usuario@gestcontratos.local` / `Usuario@123`

Troque as senhas antes de uso real.

## 7. Validar instalacao

```bash
composer check
composer validate --strict
```

## 8. Producao

- Aponte o servidor web para a pasta `public`.
- Configure HTTPS.
- Use `APP_DEBUG=false`.
- Restrinja permissoes de `storage`.
- Mantenha uploads fora de execucao publica direta.
- Troque o usuario administrador inicial.
