# Óticas Lumina — Gestão Operacional (Ordens, Estoque e Caixa)

Aplicação web construída em **CodeIgniter 4** para apoiar a operação de uma ótica no dia a dia: **vendas/ordens**, **itens**, **pagamentos**, **estoque**, **clientes**, **usuários** e **relatórios** — com controle de acesso por perfil.

> **Nota (confidencialidade):** este repositório público descreve funcionalidades e visão geral. Detalhes internos (rotas, estrutura de banco, regras sensíveis, chaves, endpoints e padrões de integração) ficam fora do README por segurança e por boas práticas.

---

## O que este sistema resolve (na prática)

Em operação real, o caos costuma nascer em três lugares:

1. vendas sem previsibilidade,
2. estoque sem controle,
3. caixa que não fecha com o que foi vendido.

Este sistema foi desenhado para centralizar isso em um fluxo simples:

- registrar a venda (ordem),
- compor itens (produtos/serviços),
- controlar pagamentos (parciais ou múltiplos),
- acompanhar saldo,
- apoiar o financeiro com relatórios e indicadores.

---

## Principais módulos e funcionalidades

### Dashboard (visão executiva)

- Indicadores consolidados por período (ex.: dia anterior / mês corrente)
- Acompanhamento de **faturamento**, **recebimentos**, **pendências** e **resumo operacional**
- Listagem rápida das últimas movimentações/ordens para conferência

### Ordens (vendas / OS)

- Criação e edição de ordens com vínculo de cliente
- Suporte a fluxo de venda com **itens** (ex.: armação, lente, serviços)
- Cálculo de totais e saldo com base em itens e pagamentos
- Campos operacionais típicos de ótica (ex.: status, observações, controle de entrega/retirada)
- Compatibilidade com cenários “legados” onde o vendedor pode ser textual, além do usuário do sistema

### Itens da Ordem

- Itens do tipo **produto** (integrados ao estoque)
- Itens do tipo **serviço** (descrição e valores)
- Totalização por item e total geral da ordem
- Estrutura preparada para permitir auditoria/controle de alterações (quando habilitado)

### Pagamentos (caixa)

- Múltiplos pagamentos por ordem (parciais/parcelados)
- Conciliação simples: vendido × recebido × saldo
- Regras de acesso para ações sensíveis (ex.: remoção/ajustes)

### Clientes

- Cadastro e gerenciamento de clientes
- Busca/filtro para facilitar atendimento e recorrência
- Normalização de campos comuns (documentos, telefone, etc.), mantendo o dado “limpo” para relatórios

### Estoque

- Cadastro de itens com identificação e informações essenciais
- Controle de disponibilidade e referência de preços
- Integração com a rotina de vendas (seleção de produtos ao montar a ordem)

### Relatórios

- Relatórios operacionais e financeiros para conferência
- Visão consolidada de vendas e recebimentos por período
- Exportação/impressão (quando habilitado conforme layout do sistema)

---

## Perfis de acesso (controle por função)

O sistema trabalha com papéis para proteger ações sensíveis:

- **Vendedor**: foco em operação (criar/editar ordens, lançar itens e pagamentos, consultar dados necessários).
- **Gerente**: visão ampliada (estoque, relatórios e recursos adicionais de conferência).
- **Admin**: gestão completa (usuários, ajustes avançados e operações críticas).

> Observação: o nível exato de permissões pode variar conforme o ambiente, porque algumas restrições são definidas por configuração e política interna.

---

## Tecnologia (em alto nível)

- **Backend:** PHP + CodeIgniter 4
- **Banco de dados:** MySQL/MariaDB (camada de models)
- **Frontend:** Bootstrap (layout), componentes JS conforme necessidade
- **Autenticação:** sessão para área web; suporte opcional a **JWT** para integrações/API (se habilitado)

---

## Como rodar localmente (visão rápida)

> Este guia é propositalmente “alto nível” para não expor detalhes sensíveis.  
> Se você faz parte do time e precisa do setup completo, use a documentação interna / checklist do projeto.

1. Clone o repositório e instale dependências via **Composer**
2. Configure o ambiente (arquivo `.env`) com os parâmetros do seu servidor
3. Aponte seu servidor web para o projeto (Apache/Nginx)
4. Garanta permissões de escrita na pasta de runtime do framework
5. Execute em modo desenvolvimento e valide o login

---

## Boas práticas e segurança

- **Nunca** versione credenciais, chaves, tokens ou dumps reais no repositório
- Use variáveis de ambiente (`.env`) e segredos gerenciados no servidor
- Mantenha logs e auditoria habilitados no ambiente de produção
- Evite expor relatórios detalhados publicamente (especialmente financeiros)

---

## Roadmap (ideias de evolução)

- Migrações e seeders para padronizar a instalação
- Melhorias em auditoria de alterações e histórico de operações
- Padronização de relatórios e exportações
- Endpoints de integração (API) formalizados com controle de escopo

---

## Licença

[MIT License](https://github.com/augustofanck/estoque-ci4?tab=MIT-1-ov-file#)