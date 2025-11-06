# Setup do Ambiente de Desenvolvimento

## Pré-requisitos

- Docker + Docker Compose
- PHP 8.1 (opcional para comandos fora dos containers)
- Composer (local ou via container)
- Git Bash / WSL2 no Windows

## Passos

1. Clone o repositório
2. Execute `scripts/setup-dev.sh`
3. Suba os serviços: `docker-compose up -d`
4. Acesse http://localhost:8080

## Estrutura Criada

```
app/               # Código da nova camada de aplicação
config/bootstrap.php# Bootstrap/env
docs/               # Documentação
scripts/setup-dev.sh# Script de inicialização
database/schema.sql # Modelo conversacional (a criar)
```

## Variáveis de Ambiente (ver `.env.example`)

Principais: APP*ENV, DB*_ , JWT*SECRET, RATE_LIMIT*_.

## Teste rápido do autoload

```bash
php -r "require 'vendor/autoload.php'; var_dump((new \App\Api\Controllers\HealthController())->ping());"
```

## Próximos Passos

- Implementar endpoints REST (controllers + router)
- Completar `database/schema.sql`
- Adicionar `docs/api-spec.yml`
- Criar collection Postman
