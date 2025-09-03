# Óticas Lumina (CodeIgniter 4)

Sistema para gestão de ordens, clientes e indicadores.

---

## 1) Visão geral

- **Dashboard** com 5 KPIs:
  1. Ordens no período (`data_compra` dentro do range)
  2. Faturamento do mês (`SUM(valor_venda)`)
  3. Valor pago do mês (`SUM(valor_pago)`)
  4. Imposto (7% sobre faturamento do mês)
  5. Lucro = **valor_pago – imposto – custo_mensal**
- **Custo de operação** mensal (últimos 6 meses): soma de
  `valor_armacao_1`, `valor_armacao_2`, `valor_lente_1`, `valor_lente_2`, **`consulta` (DECIMAL 10,2)**.
- **Ordens** com busca em tempo real por `nome_cliente`, `ordem_servico` e **`vendedor`**.
- **Clientes** CRUD.
- UI com **Bootstrap 5**.

---

## 2) Setores do sistema

### 2.1 Dashboard
- Controller: `app/Controllers/Dashboard.php`
- KPIs e custos calculados por mês atual.
- Lista “Últimas ordens”.

### 2.2 Ordens
- Controller: `app/Controllers/Ordem.php`
- Filtros:
  - `field` ∈ {`nome_cliente`, `ordem_servico`, `vendedor`}
  - `q` texto livre
  - `vendedor` opcional como filtro dedicado (além de `field+q`)
- Views:
  - `app/Views/ordens/index.php` (tabela + filtros)
  - `app/Views/ordens/_rows.php` (linhas; inclui coluna **Vendedor** entre “Cliente” e “Valor venda”)

### 2.3 Clientes
- Controller: `app/Controllers/Clientes.php` (CRUD básico)
- Views em `app/Views/clientes/`

### 2.4 Layout/Navegação
- Layout base: `app/Views/layouts/main.php`
- Sidebar e offcanvas; conteúdo em `<main>`.

---

## 3) Requisitos

- PHP 8.1+
- Extensões: `intl`, `mbstring`, `json`, `mysqli` ou `pdo_mysql`
- Composer
- MySQL/MariaDB

---

## 4) Instalação

```bash
git clone https://github.com/SEUUSUARIO/SEUREPO.git
cd SEUREPO
composer install
cp .env.example .env
```

Edite `.env`:

```ini
app.baseURL = 'http://localhost:8080/'

database.default.hostname = 127.0.0.1
database.default.database = lumina
database.default.username = root
database.default.password = ''
database.default.DBDriver = MySQLi
database.default.charset  = utf8mb4
```

Permissões (Linux/Mac):
```bash
chmod -R 0777 writable
```

---

## 5) Banco de dados

### 5.1 Colunas esperadas em `ordens`
- Identificação e datas:
  - `id` (PK), `data_compra` (DATETIME), `data_entrega_oculos` (DATE), `deleted_at` (nullable)
- Cliente e OS:
  - `ordem_servico` (VARCHAR), `nome_cliente` (VARCHAR), **`vendedor` (VARCHAR)**
- Valores:
  - `valor_venda` DECIMAL(10,2), `valor_pago` DECIMAL(10,2)
  - `valor_armacao_1` DECIMAL(10,2), `valor_armacao_2` DECIMAL(10,2)
  - `valor_lente_1` DECIMAL(10,2), `valor_lente_2` DECIMAL(10,2)
  - **`consulta` DECIMAL(10,2)**, `pagamento_laboratorio` DECIMAL(10,2)
- Flags:
  - `nota_gerada` TINYINT(1)

### 5.2 Ajustes rápidos
Adicionar **vendedor** (se faltar):
```sql
ALTER TABLE ordens ADD COLUMN vendedor VARCHAR(100) NULL AFTER nome_cliente;
CREATE INDEX idx_ordens_vendedor ON ordens (vendedor);
```

Garantir **consulta** decimal:
```sql
ALTER TABLE ordens MODIFY COLUMN consulta DECIMAL(10,2) NULL;
```

---

## 6) Executar

```bash
php spark serve
# http://localhost:8080
```

---

## 7) Uso dos filtros (Ordens)

- Por campo + texto:
  ```
  /ordens?field=vendedor&q=Maria
  /ordens?field=nome_cliente&q=Silva
  ```
- Filtro dedicado de vendedor:
  ```
  /ordens?vendedor=Joao
  ```

Na view `ordens/index.php`, o JS envia `q`, `field` e `vendedor` (se existir o input).
No controller, ambos funcionam: `like($field, $q)` e `like('vendedor', $vendedor)`.

---

## 8) Versionamento (Git/GitHub)

`.gitignore` sugerido:
```
/vendor/
/writable/*
!/writable/index.html
/public/uploads/*
!/public/uploads/.gitkeep
.env
/.env.*
/*.local.php
/.idea/
/.vscode/
/node_modules/
```

Fluxo:
```bash
git init
git add -A
git commit -m "chore: initial import"

git branch -M main
git remote add origin https://github.com/SEUUSUARIO/SEUREPO.git
git push -u origin main
```

Erros comuns de push:
- Se o remoto já tem commits:
  ```bash
  git pull origin main --allow-unrelated-histories
  git push -u origin main
  ```
- Para sobrescrever:
  ```bash
  git push -u origin main --force
  ```

---

## 9) Deploy

- `DocumentRoot` apontando para `public/`.
- `.env`:
  ```ini
  app.env = production
  app.debug = false
  ```
- Permissões em `writable/`.
- `baseURL` com o domínio final.

---
