"""
Centralized logging configuration for Clinica Go backend.

Implements structured logging following WAHA model:
Format: [YYYY-MM-DD HH:MM:SS.mmm] LEVEL (ModuleName/ProcessID): Message

Features:
- No emojis (professional logs)
- Structured format with timestamps
- Process ID for multi-worker debugging
- Log rotation by size and count
- Environment-based log levels (dev: DEBUG, prod: INFO)
- Console + file output
"""

import logging
import os
import sys
from logging.handlers import RotatingFileHandler
from pathlib import Path
from typing import Optional

from robbot.config.settings import settings


class StructuredFormatter(logging.Formatter):
    """
    Custom formatter following WAHA-style structured logging.
    
    Format: [YYYY-MM-DD HH:MM:SS.mmm] LEVEL (ModuleName/ProcessID): Message
    Example: [2026-01-05 12:13:58.177] INFO (Bootstrap/48): Application started
    """
    
    def format(self, record: logging.LogRecord) -> str:
        # Get module name (strip 'robbot.' prefix for brevity)
        module_name = record.name.replace("robbot.", "")
        
        # Format: [timestamp] LEVEL (ModuleName/PID): message
        log_fmt = (
            "[%(asctime)s] %(levelname)s "
            f"({module_name}/%(process)d): "
            "%(message)s"
        )
        
        # Use ISO format with milliseconds
        formatter = logging.Formatter(
            fmt=log_fmt,
            datefmt="%Y-%m-%d %H:%M:%S"
        )
        formatter.default_msec_format = "%s.%03d"
        
        return formatter.format(record)


def get_log_level() -> int:
    """
    Get log level based on environment.
    
    Returns:
        logging level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
    """
    env = os.getenv("ENVIRONMENT", "development").lower()
    log_level_str = os.getenv("LOG_LEVEL", "").upper()
    
    # Environment-based defaults
    if not log_level_str:
        if env in ("production", "prod"):
            log_level_str = "INFO"
        elif env in ("staging", "test"):
            log_level_str = "INFO"
        else:  # development
            log_level_str = "DEBUG"
    
    return getattr(logging, log_level_str, logging.INFO)


def configure_logging(
    log_file: Optional[str] = None,
    max_bytes: int = 10 * 1024 * 1024,  # 10MB
    backup_count: int = 5,
    console_output: bool = True,
) -> None:
    """
    Configure structured logging for the application.
    
    Args:
        log_file: Path to log file (default: logs/robbot.log)
        max_bytes: Max size per log file before rotation
        backup_count: Number of backup files to keep
        console_output: Enable console output (True for dev, False for prod)
    
    Example:
        configure_logging()  # Uses defaults
        configure_logging(log_file="custom.log", backup_count=10)
    """
    level = get_log_level()
    root = logging.getLogger()
    
    # Avoid duplicate handlers on reload
    if root.handlers:
        return
    
    root.setLevel(level)
    formatter = StructuredFormatter()
    
    # Console handler (stdout for container logs)
    if console_output:
        console = logging.StreamHandler(sys.stdout)
        console.setLevel(level)
        console.setFormatter(formatter)
        root.addHandler(console)
    
    # File handler with rotation
    if log_file is None:
        log_dir = Path("logs")
        log_dir.mkdir(exist_ok=True)
        log_file = str(log_dir / "robbot.log")
    
    file_handler = RotatingFileHandler(
        log_file,
        maxBytes=max_bytes,
        backupCount=backup_count,
        encoding="utf-8",
    )
    file_handler.setLevel(level)
    file_handler.setFormatter(formatter)
    root.addHandler(file_handler)
    
    # Log initial configuration
    logger = logging.getLogger(__name__)
    logger.info(
        f"Logging configured: level={logging.getLevelName(level)}, "
        f"file={log_file}, max_bytes={max_bytes}, backup_count={backup_count}"
    )
