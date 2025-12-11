<div align="center">

# 🤖 FAQ Chatbot WhatsApp - Backend

**Sistema backend para chatbot WhatsApp com FastAPI, PostgreSQL, Redis e serviços auxiliares**

[![Python](https://img.shields.io/badge/Python-3.11+-blue.svg)](https://www.python.org/)
[![FastAPI](https://img.shields.io/badge/FastAPI-0.100+-green.svg)](https://fastapi.tiangolo.com/)
[![Docker](https://img.shields.io/badge/Docker-20.10+-blue.svg)](https://www.docker.com/)
[![uv](https://img.shields.io/badge/uv-package%20manager-orange.svg)](https://docs.astral.sh/uv/)

</div>

---

## 📋 Índice

- [Pré-requisitos](#-pré-requisitos)
- [Instalação do uv](#-instalação-do-uv)
- [Clonando o Projeto](#-clonando-o-projeto)
- [Configuração Inicial](#-configuração-inicial)
- [Docker - Guia Completo](#-docker---guia-completo)
- [Auto Commit](#-auto-commit)
- [Rotina Diária](#-rotina-diária)
- [Desenvolvimento Local](#-desenvolvimento-local)
- [Troubleshooting](#-troubleshooting)

---

## 🎯 Pré-requisitos

<table>
<tr>
<td width="50%">

### 🖥️ Ferramentas Essenciais

- ✅ [Docker Desktop](https://www.docker.com/products/docker-desktop/) ou Docker Engine
- ✅ [Git](https://git-scm.com/downloads)
- ✅ [Python 3.11+](https://www.python.org/downloads/)
- ✅ [uv](https://docs.astral.sh/uv/) (instalação abaixo)

</td>
<td width="50%">

### 💻 Terminal Recomendado

- **Windows**: PowerShell ou Git Bash
- **Linux/Mac**: Terminal padrão (bash/zsh)

> 💡 **Dica**: Escolha um terminal e use-o consistentemente

</td>
</tr>
</table>

---

## 📦 Instalação do uv

> **uv** é o gerenciador de pacotes Python ultrarrápido usado neste projeto. Ele substitui pip/poetry/conda.

<details open>
<summary><b>🪟 Windows (PowerShell)</b></summary>

Execute no **PowerShell** como usuário normal (não precisa de admin):

```powershell
powershell -c "irm https://astral.sh/uv/install.ps1 | iex"
```

Verifique a instalação:

```powershell
uv --version
```

</details>

<details>
<summary><b>🐧 Linux / WSL / 🍎 macOS</b></summary>

Execute no **bash/zsh**:

```bash
curl -LsSf https://astral.sh/uv/install.sh | sh
```

Verifique a instalação:

```bash
uv --version
```

</details>

<div align="center">

✅ **Instalação concluída!** Prossiga para clonar o projeto.

</div>

---

## 📥 Clonando o Projeto

<details open>
<summary><b>🎥 Tutorial Visual Disponível</b></summary>

📹 [Assista ao tutorial completo no Notion](https://www.notion.so/Git-Clone-2984d53b3ec280b78d9ef14bf76cc3a3?source=copy_link)

</details>

### Passo a Passo

<table>
<tr>
<td width="50%">

#### Para Git Bash

```bash
# 1. Verificar se o diretório está vazio
ls -la

# 2. (Se necessário) Limpar diretório ⚠️
rm -rf * .* 2>/dev/null || true

# 3. Clonar o repositório
git clone https://github.com/FAQ-com-chatbot-WhatsApp/faqchatbot.git .

# 4. Ajustar para Windows (NTFS)
git config core.protectNTFS false

# 5. Atualizar branches
git checkout main
git pull origin main

# (Opcional) Branch grupotic
git checkout grupotic
git pull origin grupotic

# 6. Verificar resultado
git branch -a
```

</td>
<td width="50%">

#### Para PowerShell

```powershell
# 1. Verificar se o diretório está vazio
Get-ChildItem -Force

# 2. (Se necessário) Limpar diretório ⚠️
Remove-Item * -Recurse -Force

# 3. Clonar o repositório
git clone https://github.com/FAQ-com-chatbot-WhatsApp/faqchatbot.git .

# 4. Ajustar para Windows (NTFS)
git config core.protectNTFS false

# 5. Atualizar branches
git checkout main
git pull origin main

# (Opcional) Branch grupotic
git checkout grupotic
git pull origin grupotic

# 6. Verificar resultado
git branch -a
```

</td>
</tr>
</table>

---

## ⚙️ Configuração Inicial

### 1️⃣ Preparar Auto Commit

<table>
<tr>
<td width="50%">

**Git Bash**

```bash
cd /d/_projects/clinica_go
chmod +x auto_commit.sh
```

</td>
<td width="50%">

**PowerShell**

```powershell
# Liberar execução de scripts (primeira vez)
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass -Force

cd D:\_projects\clinica_go
# O arquivo auto_commit.ps1 já está pronto
```

</td>
</tr>
</table>

### 2️⃣ Configurar Ambiente Python

<table>
<tr>
<td width="50%">

**Git Bash**

```bash
cd back

# Criar ambiente e instalar dependências
uv sync

# Ativar ambiente
source .venv/Scripts/activate
```

</td>
<td width="50%">

**PowerShell**

```powershell
cd back

# Criar ambiente e instalar dependências
uv sync

# Ativar ambiente
.\.venv\Scripts\Activate.ps1
```

</td>
</tr>
</table>

> 💡 **Dica**: `uv sync` lê automaticamente o `pyproject.toml`, cria o ambiente virtual e instala todas as dependências necessárias em um único comando.

---

## 🐳 Docker - Guia Completo

### 📂 Estrutura

```
back/
├── docker/
│   ├── docker-compose.yml  ← Configuração dos serviços
│   ├── Dockerfile          ← Imagem da aplicação
│   └── entrypoint.sh       ← Script de inicialização
└── ...
```

> ⚠️ **Importante**: Todos os comandos docker devem ser executados a partir da pasta `back/`

### 🚀 Comandos Principais

<details open>
<summary><b>Iniciar Serviços</b></summary>

```bash
# Subir todos os serviços em background
docker-compose -f docker/docker-compose.yml up -d

# Verificar status
docker ps

# Ver logs em tempo real
docker-compose -f docker/docker-compose.yml logs -f
```

</details>

<details>
<summary><b>Parar Serviços</b></summary>

```bash
# Opção A: Parar e remover containers (recomendado)
docker-compose -f docker/docker-compose.yml down

# Opção B: Apenas parar (mantém containers)
docker-compose -f docker/docker-compose.yml stop
```

</details>

<details>
<summary><b>Retomar Serviços</b></summary>

```bash
# Se usou 'stop' → use 'start' (mais rápido)
docker-compose -f docker/docker-compose.yml start

# Se usou 'down' → use 'up -d' (recria containers)
docker-compose -f docker/docker-compose.yml up -d
```

</details>

<details>
<summary><b>Reconstruir Imagens</b></summary>

```bash
# Reconstruir do zero (sem cache)
docker-compose -f docker/docker-compose.yml build --no-cache

# Subir com as novas imagens
docker-compose -f docker/docker-compose.yml up -d
```

</details>

### 🌐 URLs dos Serviços

| 🔗 Serviço  | URL                        | Descrição                         |
| ----------- | -------------------------- | --------------------------------- |
| 🚀 API      | http://localhost:3333      | API FastAPI principal             |
| 📚 API Docs | http://localhost:3333/docs | Documentação interativa (Swagger) |
| 🗄️ Adminer  | http://localhost:8080      | Interface web do PostgreSQL       |
| 💬 WhatsApp | http://localhost:3000      | Servidor WAHA                     |

### 📊 Tabela de Comandos Docker

| Comando             | Função                       | Quando usar                 |
| ------------------- | ---------------------------- | --------------------------- |
| `up -d`             | Cria e inicia containers     | Primeira vez ou após `down` |
| `start`             | Inicia containers parados    | Após usar `stop`            |
| `stop`              | Para containers (não remove) | Pausar sem remover dados    |
| `down`              | Para e remove containers     | Encerrar o dia              |
| `down -v`           | Remove containers + volumes  | Limpar banco de dados       |
| `logs -f`           | Ver logs em tempo real       | Debugging                   |
| `restart <serviço>` | Reinicia um serviço          | Recarregar código           |
| `ps`                | Lista containers ativos      | Verificar status            |

### 🔧 Comandos Úteis

```bash
# Ver logs de um serviço específico
docker-compose -f docker/docker-compose.yml logs -f api_app

# Reiniciar apenas um serviço
docker-compose -f docker/docker-compose.yml restart api_app

# Executar comando dentro do container
docker exec -it api_app bash

# Limpar tudo (containers, volumes, imagens não usadas)
docker system prune -a --volumes
```

---

## 🤖 Auto Commit

> Sistema automatizado de commits seguindo [Conventional Commits](https://www.conventionalcommits.org/)

### Como Funciona

1. 🔍 Analisa mudanças no repositório
2. 🏷️ Classifica automaticamente o tipo de commit
3. 📦 Agrupa commits similares (docs, style, tests)
4. ✍️ Cria commits individuais para features/fixes
5. 🚀 Push único de todos os commits

### Tipos de Commit Automáticos

| Tipo       | Exemplo                            | Quando Aplica                  |
| ---------- | ---------------------------------- | ------------------------------ |
| `feat`     | `feat: adiciona user_service.py`   | Novos arquivos/funcionalidades |
| `fix`      | `fix: corrige lógica em auth.py`   | Correções de bugs              |
| `docs`     | `docs: atualiza documentação`      | Arquivos .md, README           |
| `style`    | `style: ajusta formatação`         | Mudanças de formatação         |
| `refactor` | `refactor: refatora user_model.py` | Refatorações                   |
| `chore`    | `chore: atualiza configuração`     | Config, deps, testes           |

### Uso Seguro

<table>
<tr>
<td width="50%">

#### Git Bash

```bash
# 1. Revisar mudanças
git status
git diff

# 2. Executar o script
cd /d/_projects/clinica_go
./auto_commit.sh

# Durante execução:
# ⏱️ Aguarda 5s → Ctrl+C para abortar
# ⏱️ Aguarda 3s antes do push → Ctrl+C para evitar
```

</td>
<td width="50%">

#### PowerShell

```powershell
# 1. Liberar execução (primeira vez)
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass -Force

# 2. Revisar mudanças
git status
git diff

# 3. Executar o script
cd D:\_projects\clinica_go
.\auto_commit.ps1

# Durante execução:
# ⏱️ Aguarda 5s → Ctrl+C para abortar
# ⏱️ Aguarda 3s antes do push → Ctrl+C para evitar
```

</td>
</tr>
</table>

### ✅ Checklist Antes de Executar

- [ ] Revisei todas as mudanças com `git diff`
- [ ] Não há arquivos sensíveis (.env, tokens, senhas)
- [ ] Estou na branch correta (`git branch`)
- [ ] .gitignore está configurado corretamente
- [ ] Li o plano de commits que o script apresentará

### ⚠️ Cuidados Importantes

> 🚨 **NUNCA commite arquivos sensíveis**: `.env`, tokens, senhas, chaves de API

- Use `git status` antes de executar
- Revise o plano de commits durante os 5s de espera
- Use `Ctrl+C` se algo estiver errado
- Mantenha o .gitignore atualizado

### 🔄 Reverter Commits (Se Necessário)

```bash
# Desfazer último commit (mantém mudanças)
git reset --soft HEAD~1

# Desfazer últimos 3 commits
git reset --soft HEAD~3

# Forçar push (⚠️ use com cuidado em branches compartilhadas)
git push origin nome-da-branch --force
```

---

## 📅 Rotina Diária

### 🌅 Começando o Dia

```bash
# 1. Atualizar repositório
git checkout main
git pull origin main
git checkout sua-branch
git merge main  # ou git rebase main

# 2. Subir Docker
cd back
docker-compose -f docker/docker-compose.yml up -d
sleep 5
docker ps

# 3. Ativar ambiente Python (se for trabalhar localmente)
source .venv/Scripts/activate  # Git Bash
# .\.venv\Scripts\Activate.ps1  # PowerShell

# 4. Ver logs
docker-compose -f docker/docker-compose.yml logs -f api_app
```

### 💼 Durante o Dia

```bash
# Ver status dos containers
docker ps

# Logs de um serviço
docker-compose -f docker/docker-compose.yml logs -f api_app

# Reiniciar serviço após mudanças
docker-compose -f docker/docker-compose.yml restart api_app

# Verificar mudanças Git
git status
git diff
```

### 🌙 Encerrando o Dia

```bash
# 1. Commitar mudanças
cd /d/_projects/clinica_go
./auto_commit.sh  # ou .\auto_commit.ps1

# 2. Parar Docker
cd back
docker-compose -f docker/docker-compose.yml down

# 3. Desativar ambiente Python
deactivate
```

### 🔄 Workflow Git Diário

```bash
# Salvar trabalho temporário
git stash

# Atualizar main
git checkout main
git pull origin main

# Voltar para sua branch
git checkout minha-feature

# Opção A: Merge (simples)
git merge main

# OU Opção B: Rebase (histórico linear)
git rebase main

# Restaurar trabalho
git stash pop
```

---

## 💻 Desenvolvimento Local

### Executar Aplicação sem Docker

```bash
# 1. Ativar ambiente
cd back
source .venv/Scripts/activate  # Git Bash
# .\.venv\Scripts\Activate.ps1  # PowerShell

# 2. Executar migrações
alembic upgrade head

# 3. Rodar aplicação
uvicorn robbot.main:app --reload --host 0.0.0.0 --port 3333
```

### Gerenciar Dependências com uv

```bash
# Adicionar nova dependência
uv add nome-do-pacote

# Adicionar dependência de desenvolvimento
uv add --dev nome-do-pacote

# Remover dependência
uv remove nome-do-pacote

# Atualizar dependências
uv sync --upgrade

# Listar pacotes instalados
uv pip list
```

### Executar Testes

```bash
# Todos os testes
pytest

# Testes específicos
pytest tests/unit/
pytest tests/integration/

# Com cobertura
pytest --cov=robbot
```

---

## 🔧 Troubleshooting

<details>
<summary><b>❌ Erro: "no configuration file provided"</b></summary>

**Problema**: Docker Compose não encontra o arquivo de configuração.

**Solução**:

```bash
# ❌ Errado
docker-compose down

# ✅ Correto (especificar o caminho)
docker-compose -f docker/docker-compose.yml down
```

</details>

<details>
<summary><b>❌ Erro: "port is already allocated"</b></summary>

**Problema**: Porta já está em uso por outro processo.

**Solução**:

```bash
# Ver processos usando a porta
netstat -ano | findstr :3333  # Windows
lsof -i :3333                 # Linux/Mac

# Parar todos os containers
docker-compose -f docker/docker-compose.yml down
```

</details>

<details>
<summary><b>❌ Container não inicia / fica reiniciando</b></summary>

**Solução**:

```bash
# Ver logs detalhados
docker-compose -f docker/docker-compose.yml logs api_app

# Verificar saúde do container
docker inspect api_app

# Reconstruir sem cache
docker-compose -f docker/docker-compose.yml build --no-cache api_app
docker-compose -f docker/docker-compose.yml up -d
```

</details>

<details>
<summary><b>❌ Erro: "permission denied" ao executar auto_commit.sh</b></summary>

**Solução**:

```bash
chmod +x auto_commit.sh
```

</details>

<details>
<summary><b>❌ PowerShell: "cannot be loaded because running scripts is disabled"</b></summary>

**Solução**:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass -Force
```

</details>

<details>
<summary><b>❌ uv: "command not found"</b></summary>

**Solução**: Reinstale o uv seguindo as [instruções de instalação](#-instalação-do-uv)

Depois, reinicie o terminal.

</details>

---

## 📚 Recursos Adicionais

- 📖 [Documentação FastAPI](https://fastapi.tiangolo.com/)
- 🐳 [Docker Compose Docs](https://docs.docker.com/compose/)
- 📦 [uv Documentation](https://docs.astral.sh/uv/)
- 🔀 [Conventional Commits](https://www.conventionalcommits.org/)
- 🎨 [Git Best Practices](https://git-scm.com/book/en/v2)

---

## 🆘 Suporte

<div align="center">

**Precisa de ajuda?**

1. 📋 Verifique os logs: `docker-compose -f docker/docker-compose.yml logs`
2. 📖 Revise este README
3. 💬 Contate o time de desenvolvimento

---

<sub>Feito com ❤️ pela equipe FAQ Chatbot WhatsApp</sub>

</div>
