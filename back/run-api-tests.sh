#!/bin/bash
# Script to run API tests for Clinica GO

set -e

echo "🧪 Running API Tests for Clinica GO"
echo "===================================="

# Change to back directory
cd "$(dirname "$0")"

# Run tests with uv
case "${1:-all}" in
  all)
    echo "Running ALL tests..."
    uv run pytest tests/api/ -v --tb=short
    ;;
  auth)
    echo "Running PHASE 1: Authentication tests..."
    uv run pytest tests/api/test_01_auth.py -v
    ;;
  waha)
    echo "Running PHASE 2: WAHA tests..."
    uv run pytest tests/api/test_02_waha.py -v
    ;;
  playbooks)
    echo "Running PHASE 3: Playbooks tests..."
    uv run pytest tests/api/test_03_playbooks.py -v
    ;;
  messages)
    echo "Running PHASE 4: Messages tests..."
    uv run pytest tests/api/test_04_messages.py -v
    ;;
  conversations)
    echo "Running PHASE 5: Conversations tests..."
    uv run pytest tests/api/test_05_conversations.py -v
    ;;
  gemini)
    echo "Running PHASE 6: Gemini tests..."
    uv run pytest tests/api/test_06_gemini.py -v
    ;;
  escalation)
    echo "Running PHASE 7: Escalation tests..."
    uv run pytest tests/api/test_07_escalation.py -v
    ;;
  tags)
    echo "Running PHASE 8: Tags tests..."
    uv run pytest tests/api/test_08_tags.py -v
    ;;
  metrics)
    echo "Running PHASE 9: Metrics tests..."
    uv run pytest tests/api/test_09_metrics.py -v
    ;;
  queues)
    echo "Running PHASE 10: Queues tests..."
    uv run pytest tests/api/test_10_queues.py -v
    ;;
  collect)
    echo "Collecting tests..."
    uv run pytest tests/api/ --collect-only -q
    ;;
  *)
    echo "Usage: $0 [all|auth|waha|playbooks|messages|conversations|gemini|escalation|tags|metrics|queues|collect]"
    exit 1
    ;;
esac
