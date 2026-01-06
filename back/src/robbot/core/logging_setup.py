"""
Centralized logging configuration for Clinica Go backend.

Structured logging aligned with WAHA model:
Format: [HH:MM:SS.mmm] LEVEL (ModuleName/ProcessID): Message

Features:
- No emojis (professional logs)
- Structured format with timestamps (milliseconds)
- Process ID for multi-worker debugging
- Log rotation by size and count
- Environment-based log levels (dev: DEBUG, prod: INFO)
- Console + file output
"""

import logging
import re
import os
import sys
from logging.handlers import RotatingFileHandler
from pathlib import Path
from typing import Optional

from robbot.config.settings import settings


class StructuredFormatter(logging.Formatter):
    """
    Custom formatter following WAHA-style structured logging.

    Format: service | [HH:MM:SS.mmm] LEVEL (ModuleName/ProcessID): Message
    Example: api | [12:13:58.177] INFO (Bootstrap/48): Application started
    """

    def __init__(self) -> None:
        # Python's logging can append milliseconds via %(msecs)03d
        fmt = "%(service_name)s | [%(asctime)s.%(msecs)03d] %(levelname)s (%(module_name)s/%(process)d): %(message)s"
        super().__init__(fmt=fmt, datefmt="%H:%M:%S")

    def format(self, record: logging.LogRecord) -> str:
        # Enrich record with shortened module name and service name
        record.module_name = record.name.replace("robbot.", "")
        record.service_name = os.getenv("SERVICE_NAME", "app")
        return super().format(record)


class ColoredStructuredFormatter(StructuredFormatter):
    """Colorized console formatter (optional), using ANSI escape codes.

    - Service name colored by service
    - Level colored by severity
    """

    COLORS = {
        "reset": "\x1b[0m",
        # levels
        "DEBUG": "\x1b[34m",     # blue
        "INFO": "\x1b[32m",      # green
        "WARNING": "\x1b[33m",   # yellow
        "ERROR": "\x1b[31m",     # red
        "CRITICAL": "\x1b[91m",  # bright red
        # services
        "api": "\x1b[36m",        # cyan
        "worker": "\x1b[35m",     # magenta
        "autoscaler": "\x1b[33m", # yellow
        "default": "\x1b[37m",    # white
    }

    def format(self, record: logging.LogRecord) -> str:
        # basic fields
        service = os.getenv("SERVICE_NAME", "app")
        module_name = record.name.replace("robbot.", "")
        ts = f"{self.formatTime(record, '%H:%M:%S')}.{int(record.msecs):03d}"
        level = record.levelname
        message = record.getMessage()
        pid = record.process

        # colors
        svc_color = self.COLORS.get(service, self.COLORS["default"])
        lvl_color = self.COLORS.get(level, self.COLORS["default"])
        reset = self.COLORS["reset"]

        return (
            f"{svc_color}{service}{reset} | "
            f"[{ts}] {lvl_color}{level}{reset} ({module_name}/{pid}): {message}"
        )


class MessagePrefixStripFilter(logging.Filter):
    """Remove leading bracket-tags like [INFO], [SUCCESS], [WARNING], [ERROR] from messages.

    This keeps code-level messages clean while preserving WAHA-style level display.
    """

    _pattern = re.compile(r"^\[(INFO|SUCCESS|WARNING|ERROR)\]\s*")

    def filter(self, record: logging.LogRecord) -> bool:  # noqa: D401
        if isinstance(record.msg, str):
            record.msg = self._pattern.sub("", record.msg)
        return True


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
    # Console colorization controlled by env LOG_COLOR=true
    use_color = os.getenv("LOG_COLOR", "false").lower() == "true"
    base_formatter = StructuredFormatter()
    console_formatter = ColoredStructuredFormatter() if use_color else base_formatter
    msg_filter = MessagePrefixStripFilter()
    
    # Console handler (stdout for container logs)
    if console_output:
        console = logging.StreamHandler(sys.stdout)
        console.setLevel(level)
        console.setFormatter(console_formatter)
        console.addFilter(msg_filter)
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
    file_handler.setFormatter(base_formatter)
    file_handler.addFilter(msg_filter)
    root.addHandler(file_handler)
    
    # Log initial configuration
    # Harmonize uvicorn loggers to use root handlers/format
    for name in ("uvicorn", "uvicorn.error", "uvicorn.access"):
        uv_logger = logging.getLogger(name)
        uv_logger.handlers = []  # delegate to root
        uv_logger.propagate = True

    logger = logging.getLogger(__name__)
    logger.info(
        f"Logging configured: level={logging.getLevelName(level)}, "
        f"file={log_file}, max_bytes={max_bytes}, backup_count={backup_count}"
    )
