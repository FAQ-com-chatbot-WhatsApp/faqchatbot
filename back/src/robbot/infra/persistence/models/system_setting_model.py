"""Model ORM para configurações globais do sistema."""

from sqlalchemy import Column, String, Text

from robbot.infra.db.base import Base


class SystemSettingModel(Base):
    """Configurações dinâmicas do sistema armazenadas no banco de dados.

    Permite que chaves de API, modelos de IA e outras configurações globais
    sejam alteradas via interface administrativa sem necessidade de reiniciar
    o backend ou atualizar variáveis de ambiente (.env).
    """

    __tablename__ = "system_settings"

    key = Column(String(100), primary_key=True, index=True)
    value = Column(Text, nullable=True)
    description = Column(String(255), nullable=True)

    def __repr__(self) -> str:
        """Representação para depuração."""
        return f"<SystemSetting(key='{self.key}', value='{self.value}')>"
