from sqlalchemy import select
from sqlalchemy.orm import Session

from robbot.infra.persistence.models.system_setting_model import SystemSettingModel
from robbot.infra.persistence.repositories.base_repository import BaseRepository


class SystemSettingRepository(BaseRepository[SystemSettingModel]):
    """Data access layer for system settings."""

    def __init__(self, db: Session):
        """Initialize repository."""
        super().__init__(db, SystemSettingModel)

    def get_by_key(self, key: str) -> SystemSettingModel | None:
        """Get setting by key."""
        stmt = select(SystemSettingModel).where(SystemSettingModel.key == key)
        return self.db.execute(stmt).scalar_one_or_none()

    def set_value(self, key: str, value: str, description: str = None) -> SystemSettingModel:
        """Set or update a setting value."""
        setting = self.get_by_key(key)
        if setting:
            setting.value = value
            if description:
                setting.description = description
        else:
            setting = SystemSettingModel(key=key, value=value, description=description)
            self.db.add(setting)

        self.db.commit()
        self.db.refresh(setting)
        return setting

    def get_all_settings(self) -> dict[str, str]:
        """Get all settings as a dictionary."""
        stmt = select(SystemSettingModel)
        settings = self.db.execute(stmt).scalars().all()
        return {s.key: s.value for s in settings}
