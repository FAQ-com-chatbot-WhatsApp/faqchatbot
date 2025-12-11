# FAQ Chatbot WhatsApp - Backend

Backend do chatbot WhatsApp com FastAPI, PostgreSQL, Redis e serviços auxiliares em Docker.

## 📋 Pré-requisitos

- Docker e Docker Compose
- Git Bash ou terminal compatível com Bash (Windows/Linux/Mac)
- Python 3.11+
- [uv](https://docs.astral.sh/uv/) instalado (gerenciador de pacotes usado no projeto)

## 📥 Passo a Passo Completo para Clonar o Repositório

🎥 Apoio visual: https://www.notion.so/Git-Clone-2984d53b3ec280b78d9ef14bf76cc3a3?source=copy_link

1. Verificar se o diretório está vazio

```bash
ls -la
```

⚠️ Se houver arquivos, limpe antes de continuar (cuidado: apaga tudo):

```bash
rm -rf * .* 2>/dev/null || true
```

2. Clonar o repositório na pasta atual

```bash
git clone https://github.com/FAQ-com-chatbot-WhatsApp/faqchatbot.git .
```

3. Ajustar configuração para Windows (NTFS)

```bash
git config core.protectNTFS false
```

4. Garantir branch main atualizada

```bash
git checkout main
git pull origin main
```

5. (Opcional) Trocar para a branch grupotic

```bash
git checkout grupotic
git pull origin grupotic
```

6. Conferir resultado

```bash
git branch -a
```

7. Tornar o auto_commit executável (primeira vez apenas)

```bash
cd /d/_projects/clinica_go
chmod +x auto_commit.sh
```

8. Ativar ambiente virtual e instalar dependências

```bash
cd back
uv venv .venv
source .venv/Scripts/activate   # Windows (Git Bash)
# source .venv/bin/activate     # Linux/Mac
uv sync
```

## 🐳 Guia de Uso do Docker

Estrutura (executar os comandos a partir de `back/`):

```
back/
├── docker/
│   ├── docker-compose.yml
│   ├── Dockerfile
│   └── entrypoint.sh
└── ...
```

Comandos principais:

```bash
# Subir todos os serviços
docker-compose -f docker/docker-compose.yml up -d

# Ver status
docker ps

# Logs (todos ou serviço específico)
docker-compose -f docker/docker-compose.yml logs -f
docker-compose -f docker/docker-compose.yml logs -f api_app

# Parar serviços
docker-compose -f docker/docker-compose.yml down

# Parar e remover volumes (⚠️ apaga dados do banco)
docker-compose -f docker/docker-compose.yml down -v

# Reconstruir imagens
docker-compose -f docker/docker-compose.yml build --no-cache
docker-compose -f docker/docker-compose.yml up -d
```

URLs úteis:
| Serviço | URL |
|---------|-----|
| API | http://localhost:3333 |
| API Docs | http://localhost:3333/docs |
| Adminer | http://localhost:8080 |
| WhatsApp | http://localhost:3000 |

Problema comum: "no configuration file provided"

```bash
# Correto: sempre informe o caminho do compose
docker-compose -f docker/docker-compose.yml down
```

## 🤖 Guia de Uso do Auto Commit

O script `auto_commit.sh` (na raiz `clinica_go/`) automatiza commits seguindo Conventional Commits.

⚠️ **Primeira vez**: Tornar executável (já feito no passo 7 do clone)

Fluxo seguro:

```bash
# 1) Revisar mudanças
git status
git diff

# 2) Executar o script (raiz do projeto)
cd /d/_projects/clinica_go
./auto_commit.sh

# Durante a execução
# - Mostra plano de commits e aguarda 5s (Ctrl+C aborta)
# - Faz commits e antes do push espera 3s (Ctrl+C evita push)
```

Tipos aplicados automaticamente (exemplos):
| Tipo | Exemplo |
|------|---------|
| feat | `feat: adiciona user_service.py` |
| fix | `fix: corrige lógica em auth.py` |
| docs | `docs: atualiza documentação` |
| style | `style: ajusta formatação` |
| refactor | `refactor: refatora user_model.py` |
| chore | `chore: atualiza configuração` |

Checklist antes de rodar:

- Revise com `git diff`
- Não inclua arquivos sensíveis (.env, tokens)
- Confirme a branch atual
- Tenha .gitignore atualizado

## 🗒️ Rotina Diária de Git (exemplo)

```bash
# Salvar trabalho temporário (se necessário)
git stash

git checkout main
git pull
git checkout minha-feature

# Opção A: Merge simples
git merge main

# OU Opção B: Rebase para histórico linear
git rebase main

# Restaurar trabalho stasheado (se usou)
git stash pop
```

## 🚀 Rotina Diária de Docker

### Start (iniciar o dia)

A partir de `back/`:

```bash
# Subir todos os serviços em background
docker-compose -f docker/docker-compose.yml up -d

# Esperar saúde dos containers (alguns segundos)
sleep 5

# Checar status
docker ps

# Ver logs do app
docker-compose -f docker/docker-compose.yml logs -f api_app
```

### Stop (encerrar o dia)

```bash
# Opção A: Parar e remover containers (recomendado)
docker-compose -f docker/docker-compose.yml down

# Opção B: Apenas parar (mantém containers, volta com start)
docker-compose -f docker/docker-compose.yml stop

# (Opcional) Se quiser remover volumes também (⚠️ apaga dados do banco)
# docker-compose -f docker/docker-compose.yml down -v
```

### Retomar após Stop

```bash
# Se usou 'stop', retoma com 'start' (mais rápido que up)
docker-compose -f docker/docker-compose.yml start

# Se usou 'down', retoma com 'up -d' (como na primeira vez)
docker-compose -f docker/docker-compose.yml up -d
```

### Verificações rápidas durante o dia

```bash
# Ver status dos containers
docker ps

# Ver logs de um serviço
docker-compose -f docker/docker-compose.yml logs -f api_app

# Reiniciar um serviço específico
docker-compose -f docker/docker-compose.yml restart api_app
```

## Resumo de Comandos Docker

| Comando   | Função                            | Quando usar                  |
| --------- | --------------------------------- | ---------------------------- |
| `up -d`   | Cria e inicia containers          | Primeira vez ou após `down`  |
| `start`   | Inicia containers parados         | Após usar `stop`             |
| `stop`    | Para containers (não remove)      | Pausar sem remover dados     |
| `down`    | Para e remove containers          | Encerrar o dia (recomendado) |
| `down -v` | Para, remove containers e volumes | Limpar tudo incluindo banco  |
| `logs -f` | Ver logs em tempo real            | Debugging e monitoramento    |
| `restart` | Reinicia um container             | Recarregar código/config     |

## 🔧 Desenvolvimento Local com uv

```bash
# 1) Criar/usar ambiente virtual gerenciado pelo uv
uv venv .venv
source .venv/Scripts/activate   # Windows (Git Bash)
# source .venv/bin/activate     # Linux/Mac

# 2) Instalar dependências declaradas no pyproject
uv sync

# 3) Executar migrações
alembic upgrade head

# 4) Rodar a aplicação
uvicorn robbot.main:app --reload --host 0.0.0.0 --port 3333
```

## 🆘 Suporte

Em caso de dúvidas ou problemas:

1. Verifique os logs: `docker-compose -f docker/docker-compose.yml logs`
2. Revise este README
3. Contate o time de desenvolvimento
