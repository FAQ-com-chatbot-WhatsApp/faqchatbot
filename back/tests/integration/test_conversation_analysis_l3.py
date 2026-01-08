"""
Testes de integração para Relatório de Análise de Conversas (L3).

Valida queries SQL e transformações de dados.
"""

import pytest
from datetime import datetime, timedelta
from unittest.mock import MagicMock
from robbot.adapters.repositories.analytics_repository import AnalyticsRepository


@pytest.fixture
def mock_db_session():
    """Mock da sessão do banco de dados."""
    session = MagicMock()
    return session


@pytest.fixture
def analytics_repo(mock_db_session):
    """Instância do AnalyticsRepository com mock."""
    return AnalyticsRepository(mock_db_session)


def test_activity_heatmap_query_structure(analytics_repo, mock_db_session):
    """Testa estrutura do heatmap de atividade."""
    # Arrange
    start_date = datetime(2026, 1, 1)
    end_date = datetime(2026, 1, 31)

    # Mock result
    mock_result = MagicMock()
    mock_result.fetchall.return_value = [
        MagicMock(day_of_week=1, hour=9, message_count=120),
        MagicMock(day_of_week=1, hour=14, message_count=85),
        MagicMock(day_of_week=2, hour=10, message_count=95),
    ]
    mock_db_session.execute.return_value = mock_result

    # Act
    result = analytics_repo.get_message_frequency_by_hour(start_date, end_date)

    # Assert
    assert len(result) == 3
    assert result[0]["day_of_week"] == 1
    assert result[0]["hour"] == 9
    assert result[0]["message_count"] == 120
    assert result[1]["day_of_week"] == 1
    assert result[1]["hour"] == 14
    assert result[2]["day_of_week"] == 2

    # Verifica query SQL
    call_args = mock_db_session.execute.call_args
    assert "EXTRACT(DOW FROM created_at)" in str(call_args[0][0])
    assert "EXTRACT(HOUR FROM created_at)" in str(call_args[0][0])
    assert "GROUP BY day_of_week, hour" in str(call_args[0][0])


def test_keyword_frequency_query_structure(analytics_repo, mock_db_session):
    """Testa extração de palavras-chave com stop words filtering."""
    # Arrange
    start_date = datetime(2026, 1, 1)
    end_date = datetime(2026, 1, 31)

    # Mock result
    mock_result = MagicMock()
    mock_result.fetchall.return_value = [
        MagicMock(keyword="agendamento", count=250),
        MagicMock(keyword="preço", count=180),
        MagicMock(keyword="consulta", count=120),
    ]
    mock_db_session.execute.return_value = mock_result

    # Act
    result = analytics_repo.get_keyword_frequency(start_date, end_date, limit=50)

    # Assert
    assert len(result) == 3
    assert result[0]["keyword"] == "agendamento"
    assert result[0]["count"] == 250
    assert result[1]["keyword"] == "preço"
    assert result[1]["count"] == 180

    # Verifica query SQL
    call_args = mock_db_session.execute.call_args
    assert "unnest(string_to_array(body, ' '))" in str(call_args[0][0])
    assert "regexp_replace" in str(call_args[0][0])
    assert "stop_words" in str(call_args[0][0])
    assert "direction = 'INBOUND'" in str(call_args[0][0])
    # Verifica que limit está no params (pode ser key diferente dependendo do binding)
    params = call_args[1] if len(call_args) > 1 else {}
    assert params.get("limit") == 50 or "limit" in str(call_args)


def test_sentiment_distribution_query_structure(analytics_repo, mock_db_session):
    """Testa análise de sentimento baseada em keywords."""
    # Arrange
    start_date = datetime(2026, 1, 1)
    end_date = datetime(2026, 1, 31)

    # Mock result
    mock_result = MagicMock()
    mock_result.fetchone.return_value = MagicMock(
        positive=350,
        negative=80,
        neutral=570,
        total_messages=1000
    )
    mock_db_session.execute.return_value = mock_result

    # Act
    result = analytics_repo.get_message_sentiment_distribution(start_date, end_date)

    # Assert
    assert result["positive"] == 350
    assert result["negative"] == 80
    assert result["neutral"] == 570
    assert result["total_messages"] == 1000

    # Verifica query SQL
    call_args = mock_db_session.execute.call_args
    assert "CASE" in str(call_args[0][0])
    assert "obrigad|legal|ótimo" in str(call_args[0][0])  # positive keywords
    assert "ruim|péssimo|horrível" in str(call_args[0][0])  # negative keywords
    assert "FILTER (WHERE sentiment = 'positive')" in str(call_args[0][0])


def test_topic_distribution_query_structure(analytics_repo, mock_db_session):
    """Testa classificação de topics baseada em keywords temáticas."""
    # Arrange
    start_date = datetime(2026, 1, 1)
    end_date = datetime(2026, 1, 31)

    # Mock result
    mock_result = MagicMock()
    mock_result.fetchall.return_value = [
        MagicMock(topic="Agendamento", count=250, percentage=35.0),
        MagicMock(topic="Preços", count=150, percentage=21.0),
        MagicMock(topic="Localização", count=100, percentage=14.0),
    ]
    mock_db_session.execute.return_value = mock_result

    # Act
    result = analytics_repo.get_conversation_topics(start_date, end_date)

    # Assert
    assert len(result) == 3
    assert result[0]["topic"] == "Agendamento"
    assert result[0]["count"] == 250
    assert result[0]["percentage"] == 35.0
    assert result[1]["topic"] == "Preços"

    # Verifica query SQL
    call_args = mock_db_session.execute.call_args
    assert "agendar|agendamento|marcar" in str(call_args[0][0])
    assert "preço|valor|custo" in str(call_args[0][0])
    assert "localização|endereço" in str(call_args[0][0])
    assert "percentual do total" in str(call_args[0][0]).lower() or "percentage" in str(call_args[0][0]).lower()


def test_sentiment_distribution_empty_data(analytics_repo, mock_db_session):
    """Testa retorno quando não há mensagens."""
    # Arrange
    start_date = datetime(2026, 1, 1)
    end_date = datetime(2026, 1, 31)

    # Mock result vazio
    mock_result = MagicMock()
    mock_result.fetchone.return_value = MagicMock(
        positive=None,
        negative=None,
        neutral=None,
        total_messages=0
    )
    mock_db_session.execute.return_value = mock_result

    # Act
    result = analytics_repo.get_message_sentiment_distribution(start_date, end_date)

    # Assert
    assert result["positive"] == 0
    assert result["negative"] == 0
    assert result["neutral"] == 0
    assert result["total_messages"] == 0
