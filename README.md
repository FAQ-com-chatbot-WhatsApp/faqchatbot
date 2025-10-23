# FAQ Chatbot - Sistema Completo

## O que é este projeto?

Sistema de perguntas e respostas (FAQ) desenvolvido em Joomla que funciona como um site onde visitantes podem consultar perguntas frequentes organizadas por categorias. O sistema roda dentro de containers Docker para facilitar a instalação e uso.

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

**IMPORTANTE**: Se você está no Linux ou Mac, precisa executar este comando primeiro:

```bash
chmod +x start-faq.sh
chmod +x reset-faq.sh
```

**No Windows**: Não precisa fazer isso, pode pular este passo.

### Passo 3: Iniciar o sistema pela primeira vez

```bash
# Linux/Mac - Execute o script
./start-faq.sh

# Windows - Execute assim
bash start-faq.sh

# OU execute manualmente (qualquer sistema)
docker-compose up -d
```

### Passo 4: Aguarde a instalação

Na primeira vez, o sistema vai:

- Baixar os programas necessários (2-5 minutos dependendo da internet)
- Criar o banco de dados
- Configurar o site
- **AGUARDE ATÉ 5 MINUTOS** antes de testar

### Passo 5: Testar se funcionou

Abra seu navegador e acesse: http://localhost:8080

Se aparecer o site do FAQ, funcionou!

## Como acessar o sistema

| O que é                               | Endereço no navegador               | Usuário | Senha      |
| ------------------------------------- | ----------------------------------- | ------- | ---------- |
| **Site do FAQ** (para visitantes)     | http://localhost:8080               | -       | -          |
| **Área administrativa** (para editar) | http://localhost:8080/administrator | admin   | admin123   |
| **Gerenciar banco de dados**          | http://localhost:8081               | faquser | faqpass123 |

## Como usar no dia a dia

### Para LIGAR o sistema

```bash
# Navegue até a pasta do projeto
cd faqchatbot
```

```bash
# Primeira vez ou após 'down'
docker-compose up -d
```

```bash
# Após 'stop' (containers já existem)
docker-compose start
```

### Para PARAR temporariamente

```bash
# Para parar sem remover (mais rápido para religar)
docker-compose stop
```

### Para DESLIGAR completamente

```bash
# Para desligar e remover containers (fim do trabalho)
docker-compose down
```

### Para REINICIAR o sistema

```bash
# Se estiver com problemas
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
# Ver status dos containers
docker-compose ps

# Ver logs em tempo real
docker-compose logs -f

# Ver logs só do site
docker-compose logs -f web
```

### Fazer backup dos dados

```bash
# Criar backup do banco de dados
docker exec faqchatbot-db mysqldump -u faquser -pfaqpass123 faqchatbot > backup_$(date +%Y%m%d).sql
```

### Restaurar backup

```bash
# Restaurar um backup (substitua backup.sql pelo seu arquivo)
docker exec -i faqchatbot-db mysql -u faquser -pfaqpass123 faqchatbot < backup.sql
```

### Recomeçar do zero (CUIDADO: Apaga tudo!)

```bash
# Use o script que pede confirmação
./reset-faq.sh

# OU faça manualmente
docker-compose down -v
docker-compose up -d
```

## Resolução de problemas comuns

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
# No Windows
netstat -ano | findstr :8080

# No Linux/Mac
lsof -i :8080

# Mate o processo ou use outras portas editando docker-compose.yml
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
├── docker/                    # Configurações do Docker
├── _dumps/                   # Backups do banco de dados
├── components/              # Componentes do Joomla (código do FAQ)
├── modules/                # Módulos do Joomla (exibição do FAQ)
├── administrator/          # Área administrativa do Joomla
├── docker-compose.yml     # Configuração dos containers
├── start-faq.sh          # Script para iniciar (Linux/Mac)
├── reset-faq.sh         # Script para resetar tudo
└── README.md           # Este arquivo de instruções
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

### ATENÇÃO: Senhas padrão (MUDE EM PRODUÇÃO!)

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

---

**Sistema desenvolvido para facilitar o atendimento ao cliente através de FAQs organizados**
