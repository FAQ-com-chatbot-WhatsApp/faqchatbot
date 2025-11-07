# WhatsBot - Sistema Completo

## O que é este projeto?

Sistema de chatbot conversacional integrado com WhatsApp Business API, desenvolvido sobre estrutura Joomla com API REST customizada. Permite criar fluxos conversacionais, gerenciar contatos, mensagens e conversas. O sistema roda dentro de containers Docker para facilitar a instalação e uso.

## Documentação Completa

- **[Setup do Banco de Dados](docs/DB_SETUP.md)** - Configuração, migrations, models e repositories
- **[API Reference](docs/API.md)** - Documentação dos endpoints REST
- **[Fluxos Conversacionais](docs/FLOWS.md)** - Como criar e gerenciar flows

## Tecnologias Principais

- PHP 8.1+
- MariaDB 10.8
- Docker & Docker Compose
- Joomla 4.x (base)
- API REST customizada (sem framework externo)

## Preparação do ambiente - Windows

### Passo a Passo

### 1. Habilitar WSL 2

Abra o PowerShell como Administrador:

Habilitar WSL e Máquina Virtual:

```bash
dism.exe /online /enable-feature /featurename:Microsoft-Windows-Subsystem-Linux /all /norestart
```

```bash
dism.exe /online /enable-feature /featurename:VirtualMachinePlatform /all /norestart
```

Reiniciar o computador:

```bash
Restart-Computer
```

Após reiniciar:

```bash
wsl --set-default-version 2
```

### 2. Instalar Distribuição Linux

```bash
wsl --install -d Ubuntu
```

**Importante**: Configure usuário e senha quando solicitado

### 3. Instalar Docker Desktop

- Windows: Baixe em https://desktop.docker.com/win/main/amd64/Docker%20Desktop%20Installer.exe
- Mac: Baixe em https://desktop.docker.com/mac/main/amd64/Docker.dmg
- Linux: Siga as instruções em https://docs.docker.com/engine/install/

### Como verificar se está instalado:

```bash
docker --version
```

## Como usar este sistema - PRIMEIRA VEZ

### Passo 1: Navegar até o projeto

Abra o terminal e navegue até a pasta do projeto:

```bash
cd faqchatbot
```

### Passo 2: Dar permissão para executar scripts (Linux/Mac)

Se você está no Linux ou Mac, precisa executar este comando primeiro:

```bash
chmod +x start-faq.sh
chmod +x reset-faq.sh
```

No Windows: Não precisa fazer isso, pode pular este passo.

### Passo 3: Iniciar o sistema pela primeira vez

```bash
# Linux/Mac
./start-faq.sh

# Windows (Git Bash)
./start-faq.sh

# OU execute via bash
bash start-faq.sh

# OU execute manualmente
docker-compose up -d
```

### Passo 4: Configurar o Banco de Dados

Na primeira vez, você precisa aplicar as migrations:

```bash
# Criar database
docker exec whatsbot-db mysql -uroot -prootpassword123 -e "CREATE DATABASE IF NOT EXISTS botdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON botdb.* TO 'bot_user'@'%';"

# Aplicar schema e migrations
docker exec webcore php scripts/apply_migrations.php
```

**Veja documentação completa**: [docs/DB_SETUP.md](docs/DB_SETUP.md)

### Passo 5: Testar se funcionou

```bash
# Executar smoke tests
docker exec webcore php scripts/smoke_tests.php
```

Ou abra seu navegador:

- Site Joomla: http://localhost:8080
- API Health: http://localhost:8080/api/health/ping
- phpMyAdmin: http://localhost:8081

## Como acessar o sistema

| O que é                               | Endereço no navegador               | Usuário | Senha      |
| ------------------------------------- | ----------------------------------- | ------- | ---------- |
| **Site do FAQ** (para visitantes)     | http://localhost:8080               | -       | -          |
| **Área administrativa** (para editar) | http://localhost:8080/administrator | admin   | admin123   |
| **Gerenciar banco de dados**          | http://localhost:8081               | faquser | faqpass123 |

## Como usar no dia a dia

### Para LIGAR o sistema

```bash
cd faqchatbot
```

```bash
# Primeira vez ou após 'down'
docker-compose up -d
```

```bash
# Após 'stop'
docker-compose start
```

### Para PARAR temporariamente

```bash
docker-compose stop
```

### Para DESLIGAR completamente

```bash
docker-compose down
```

### Para REINICIAR o sistema

```bash
docker-compose restart
```

## Como editar as perguntas e respostas

1. Acesse: http://localhost:8080/administrator
2. Digite usuário: **admin** e senha: **admin123**
3. No menu, clique em: **Components → NoBoss FAQ**
4. Aqui você pode:
   - Criar categorias (exemplo: "Vendas", "Suporte")
   - Adicionar perguntas e respostas
   - Organizar por grupos

## Comandos úteis para desenvolvedores

### Ver se está funcionando

```bash
docker-compose ps
docker-compose logs -f
docker-compose logs -f web
```

### Fazer backup dos dados

```bash
docker exec faqchatbot-db mysqldump -u faquser -pfaqpass123 faqchatbot > backup_$(date +%Y%m%d).sql
```

### Restaurar backup

```bash
docker exec -i faqchatbot-db mysql -u faquser -pfaqpass123 faqchatbot < backup.sql
```

### Recomeçar do zero

```bash
./reset-faq.sh
```

```bash
docker-compose down -v
docker-compose up -d
```

## Resolução de problemas comuns

### Problema: "command not found" ao executar start-faq.sh

**Sintaxe INCORRETA:**

```bash
start-faq.sh
```

**Sintaxe CORRETA:**

```bash
./start-faq.sh
```

**OU use:**

```bash
bash start-faq.sh
```

### Problema: "Comando não encontrado" no Linux/Mac

**Solução**: Você esqueceu de dar permissão. Execute:

```bash
chmod +x start-faq.sh
```

### Problema: "Docker não está rodando"

**Solução**:

1. Abra o Docker Desktop
2. Aguarde ele inicializar completamente
3. Tente novamente

### Problema: "Porta já está em uso"

**Solução**: Outro programa está usando a porta 8080 ou 8081

```bash
# Windows
netstat -ano | findstr :8080

# Linux/Mac
lsof -i :8080
```

### Problema: Site não carrega após 5 minutos

**Solução**:

1. Verifique os logs: `docker-compose logs -f web`
2. Tente acessar diretamente: http://localhost:8080/index.php?lang=pt
3. Reinicie: `docker-compose restart`

### Problema: Esqueci a senha do admin

**Solução**: As senhas padrão são:

- Admin Joomla: admin / admin123
- Banco de dados: faquser / faqpass123

## Estrutura do projeto (para desenvolvedores)

```
faqchatbot/
├── docker/
├── _dumps/
├── components/
├── modules/
├── administrator/
├── docker-compose.yml
├── start-faq.sh
├── reset-faq.sh
└── README.md
```

## Diferença entre os comandos

| Comando                  | O que faz                       | Containers    | Volumes | Quando usar        | Tempo |
| ------------------------ | ------------------------------- | ------------- | ------- | ------------------ | ----- |
| `docker-compose up -d`   | Inicia containers               | Cria/Inicia   | Mantém  | Uso diário         | 10s   |
| `docker-compose stop`    | Para containers                 | Para (mantém) | Mantém  | Pausa temporária   | 5s    |
| `docker-compose start`   | Inicia containers parados       | Inicia        | Mantém  | Após stop          | 5s    |
| `docker-compose restart` | Reinicia containers             | Reinicia      | Mantém  | Resolver problemas | 10s   |
| `docker-compose down`    | Para e remove containers        | Remove        | Mantém  | Final do dia       | 10s   |
| `docker-compose down -v` | Para, remove containers e dados | Remove        | Remove  | Reset completo     | 15s   |

## Configurações de segurança

### ATENÇÃO: Senhas padrão (MUDE EM PRODUÇÃO)

- Joomla: admin / admin123
- Banco: faquser / faqpass123

### Para usar em servidor real:

1. Mude todas as senhas
2. Configure SSL (HTTPS)
3. Restrinja acesso ao phpMyAdmin
4. Configure backup automático

## Suporte

Se tiver problemas:

1. Leia a seção "Resolução de problemas comuns"
2. Verifique os logs: `docker-compose logs -f`
3. Abra uma issue no GitHub: https://github.com/FAQ-com-chatbot-WhatsApp/faqchatbot/issues

## Status do Projeto - Cards Implementados

### ✅ CARD 1: Esquema do Banco de Dados (100%)

- **Tabelas**: `contacts`, `flows`, `conversations`, `messages`, `conversation_transitions`
- **Features**: Soft delete, timestamps automáticos, índices otimizados, foreign keys
- **Migrations**: Sistema automatizado via `scripts/apply_migrations.php`
- **Seeders**: Dados de teste disponíveis via `migrations/seed_sample_data.sql`

### ✅ CARD 2: CRUD Mensagens e Contatos (100%)

**Mensagens:**

- `POST /messages` - Criar mensagem (valida conversation, incrementa message_count)
- `GET /messages` - Listar com filtros (conversation_id, type, status, direction, paginação)
- `GET /messages/{id}` - Detalhe
- `PUT /messages/{id}` - Atualizar
- `DELETE /messages/{id}` - Soft delete

**Contatos:**

- `POST /contacts` - Criar contato (valida telefone único)
- `GET /contacts` - Listar com paginação
- `GET /contacts/{id}` - Detalhe
- `GET /contacts/phone/{phone}` - Buscar por telefone
- `PUT /contacts/{id}` - Atualizar
- `DELETE /contacts/{id}` - Soft delete (bloqueia se há conversas ativas)

### ✅ CARD 3: CRUD de Flows (100%)

**Endpoints:**

- `GET /flows` - Listar (filtros: status, name, paginação)
- `GET /flows/{id}` - Detalhe (definition decodificada)
- `POST /flows` - Criar (valida steps, message_id references)
- `PUT /flows/{id}` - Substituir (incrementa version)
- `PATCH /flows/{id}` - Atualizar parcial (incrementa version se definition muda)
- `DELETE /flows/{id}` - Soft delete (bloqueia se conversas ativas)
- `POST /flows/{id}/activate` - Ativar flow
- `POST /flows/{id}/deactivate` - Desativar flow (status=inactive)
- `POST /flows/{id}/duplicate` - Duplicar (cópia independente, version=1, status=draft)

**Validações:**

- Estrutura `definition.steps` obrigatória
- Referências de `message_id` validadas contra tabela messages
- Versionamento automático em mudanças de definition

### ✅ CARD 4: Motor de Conversação (100%)

**Endpoints:**

- `POST /conversations` - Criar conversa (exige flow active)
- `GET /conversations/{id}` - Status + mensagens
- `GET /conversations` - Listar com filtros
- `POST /conversations/{id}/next` - Avançar step (progressão com current_step)
- `PATCH /conversations/{id}` - Atualizar status
- `GET /conversations/active/phone/{phone}` - Identificar conversa ativa por telefone

**Features:**

- **Progressão por steps**: Usa `current_step` (não message_count)
- **Timeout**: Conversas >24h marcadas como `abandoned` ao tentar /next
- **Logging**: Transições registradas em `conversation_transitions` (actions: next, complete)
- **Auto-complete**: Último step marca conversa como `completed`
- **Validação**: Apenas flows `active` podem iniciar conversas

---

## API Endpoints - Resumo

### Autenticação

- `POST /auth/login` - Login (retorna JWT token)
- `POST /auth/refresh` - Renovar token

### Health Check

- `GET /health/ping` - Verificar saúde da API

### Recursos Protegidos (requerem `Authorization: Bearer <token>`)

- **Messages**: 5 endpoints (CRUD completo)
- **Contacts**: 6 endpoints (CRUD + busca por telefone)
- **Flows**: 9 endpoints (CRUD + activate/deactivate/duplicate)
- **Conversations**: 6 endpoints (CRUD + next step + busca por telefone)

📖 **Documentação detalhada da API**: Ver `docs/API_FLOWS.md`, `docs/API_CONVERSATIONS.md`, `docs/API_MESSAGES_CONTACTS.md`

---

## Testes e Validação

### Testes Estruturais Automatizados

```bash
# Smoke tests gerais
docker exec webcore php scripts/smoke_tests.php

# Card 3 - Flows (14 testes)
docker exec webcore php scripts/card3_validation_tests.php

# Card 4 - Conversações (9 testes)
docker exec webcore php scripts/card4_validation_tests.php
```

### Status dos Testes

- ✅ **Card 1**: Estrutura DB validada (5 tabelas, índices, migrations)
- ✅ **Card 2**: 10 endpoints messages/contacts funcionais
- ✅ **Card 3**: 9 endpoints flows validados (14/14 testes passing)
- ✅ **Card 4**: Motor de conversação validado (9/9 testes passing)

### Teste Rápido da API

```bash
# Health check
curl http://localhost:8080/api/health/ping

# Login para obter token
curl -X POST http://localhost:8080/api/auth/login \
   -H "Content-Type: application/json" \
   -d '{"username":"lmswill","password":"admin123"}'

# Listar flows (substitua <TOKEN> pelo token obtido)
curl -H "Authorization: Bearer <TOKEN>" \
   "http://localhost:8080/api/flows?page=1&per_page=5"
```

---

## Estrutura do Banco de Dados

### Tabelas Principais

- **contacts**: Contatos do sistema (phone único, soft delete)
- **flows**: Fluxos conversacionais (definition JSON, versionamento, status: draft/active/archived)
- **conversations**: Conversas (vincula contact + flow, current_step, status: active/completed/abandoned)
- **messages**: Mensagens trocadas (direction: in/out, type, content, status)
- **conversation_transitions**: Log de transições (from_step → to_step, actions: next/complete)

### Features do Schema

- **Soft delete**: Coluna `deleted_at` em todas as tabelas
- **Timestamps**: `created_at` e `updated_at` automáticos
- **Índices**: Otimizados para queries de listagem e busca
- **Foreign Keys**: Integridade referencial com CASCADE onde apropriado

### Aplicar Migrations

```bash
# Aplicar todas as migrations
docker exec webcore php scripts/apply_migrations.php

# Reset completo (⚠️ apaga dados)
docker exec webcore php scripts/apply_migrations.php --reset
```

---
