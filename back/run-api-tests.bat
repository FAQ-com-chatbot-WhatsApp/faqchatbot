@echo off
REM Script to run API tests for Clinica GO

setlocal enabledelayedexpansion

echo 🧪 Running API Tests for Clinica GO
echo ====================================

REM Get argument or default to "all"
set TEST_PHASE=%1
if "%TEST_PHASE%"=="" set TEST_PHASE=all

cd /d "%~dp0"

if "%TEST_PHASE%"=="all" (
  echo Running ALL tests...
  uv run pytest tests/api/ -v --tb=short
) else if "%TEST_PHASE%"=="auth" (
  echo Running PHASE 1: Authentication tests...
  uv run pytest tests/api/test_01_auth.py -v
) else if "%TEST_PHASE%"=="waha" (
  echo Running PHASE 2: WAHA tests...
  uv run pytest tests/api/test_02_waha.py -v
) else if "%TEST_PHASE%"=="playbooks" (
  echo Running PHASE 3: Playbooks tests...
  uv run pytest tests/api/test_03_playbooks.py -v
) else if "%TEST_PHASE%"=="messages" (
  echo Running PHASE 4: Messages tests...
  uv run pytest tests/api/test_04_messages.py -v
) else if "%TEST_PHASE%"=="conversations" (
  echo Running PHASE 5: Conversations tests...
  uv run pytest tests/api/test_05_conversations.py -v
) else if "%TEST_PHASE%"=="gemini" (
  echo Running PHASE 6: Gemini tests...
  uv run pytest tests/api/test_06_gemini.py -v
) else if "%TEST_PHASE%"=="escalation" (
  echo Running PHASE 7: Escalation tests...
  uv run pytest tests/api/test_07_escalation.py -v
) else if "%TEST_PHASE%"=="tags" (
  echo Running PHASE 8: Tags tests...
  uv run pytest tests/api/test_08_tags.py -v
) else if "%TEST_PHASE%"=="metrics" (
  echo Running PHASE 9: Metrics tests...
  uv run pytest tests/api/test_09_metrics.py -v
) else if "%TEST_PHASE%"=="queues" (
  echo Running PHASE 10: Queues tests...
  uv run pytest tests/api/test_10_queues.py -v
) else if "%TEST_PHASE%"=="collect" (
  echo Collecting tests...
  uv run pytest tests/api/ --collect-only -q
) else (
  echo Usage: %0 [all^|auth^|waha^|playbooks^|messages^|conversations^|gemini^|escalation^|tags^|metrics^|queues^|collect]
  exit /b 1
)
