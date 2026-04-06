@echo off
echo ==========================================
echo        Iniciando Boot do Sistema Go
echo ==========================================
cd /d %~dp0

IF NOT EXIST "back\.env" (
    echo [INFO] Criando arquivo de configuracao (back\.env)...
    copy "back\.env.example" "back\.env" > nul
    echo [INFO] back\.env criado com sucesso.
) ELSE (
    echo [INFO] O arquivo de configuracao (back\.env) ja existe.
)

echo [INFO] Verificando Docker no sistema...
docker --version > nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo [ERRO] Docker nao encontrado. Por favor, instale o Docker Desktop antes.
    pause
    exit /b
)

echo [INFO] Iniciando os containers (isso pode levar alguns minutos na primeira vez)...
docker compose up -d --build

echo =================================================================
echo [SUCESSO] O sistema foi iniciado e esta rodando em segundo plano!
echo.
echo Painel de Controle (Frontend): http://localhost:3000
echo. 
echo Usuario Padrao (se banco virgem): admin@admin.com
echo Senha Padrao: admin
echo.
echo Por favor, acesse o painel e configure a IA e o WhatsApp via UI!
echo =================================================================
pause
