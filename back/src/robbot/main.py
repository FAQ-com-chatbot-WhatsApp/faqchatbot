"""FastAPI application factory and global exception handling.

Initializes DI container and rate limiter on startup.
Configures CORS middleware.
"""

import logging
from contextlib import asynccontextmanager

from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse

from robbot.api.v1.dependencies import initialize_rate_limiter
from robbot.api.v1.routers.api import api_router
from robbot.config.container import initialize_container, shutdown_container
from robbot.config.settings import get_settings
from robbot.core.logging_setup import configure_logging


def create_app() -> FastAPI:
    """
    Application factory that creates and configures the FastAPI app.
    Registers routers and exception handlers and configures logging.
    """
    configure_logging()
    settings = get_settings()

    @asynccontextmanager
    async def lifespan(app: FastAPI):
        """Initialize services on application startup/shutdown."""
        logger = logging.getLogger("robbot.startup")

        # Initialize DI Container
        logger.info("[INFO] Initializing DI Container...")
        try:
            await initialize_container(settings)
            logger.info("[SUCCESS] DI Container initialized successfully")
        except Exception as e:
            logger.error("[ERROR] Failed to initialize DI Container: %s", e)
            raise

        # Initialize rate limiter
        logger.info("[INFO] Initializing rate limiter...")
        try:
            initialize_rate_limiter()
            logger.info("[SUCCESS] Rate limiter initialized successfully")
        except Exception as e:
            logger.error("[ERROR] Failed to initialize rate limiter: %s", e)
            # Don't fail app startup, rate limiter will fail gracefully

        # Initialize default WAHA session in DB
        logger.info("[INFO] Initializing default WAHA session...")
        try:
            from robbot.infra.db.base import SessionLocal
            from robbot.infra.integrations.waha.waha_client import get_waha_client
            from robbot.infra.persistence.repositories.session_repository import SessionRepository
            from robbot.services.communication.waha_service import WAHAService

            with SessionLocal() as db:
                session_repo = SessionRepository(db)
                waha_service = WAHAService(session_repo, get_waha_client())
                default_session = waha_service.get_or_create_default_session()
                logger.info(
                    "[SUCCESS] Default WAHA session ready: %s (status: %s)",
                    default_session.name,
                    default_session.status,
                )
        except Exception as e:
            logger.warning("[WARN] Failed to initialize default WAHA session: %s", e)
            # Non-critical - session can be created via UI or sync script

        yield

        # Shutdown DI Container
        logger.info("[INFO] Shutting down DI Container...")
        try:
            await shutdown_container()
            logger.info("[SUCCESS] DI Container shut down successfully")
        except Exception as e:
            logger.error("[ERROR] Failed to shutdown DI Container: %s", e)

    application = FastAPI(title="Robbot API", version="0.1.0", lifespan=lifespan)

    # Configure CORS middleware for HttpOnly cookies
    # IMPORTANT: allow_credentials=True is required for cookies to work
    application.add_middleware(
        CORSMiddleware,
        allow_origins=settings.CORS_ORIGINS,
        allow_credentials=settings.CORS_CREDENTIALS,
        allow_methods=["*"],
        allow_headers=["*"],
    )

    application.include_router(api_router, prefix="/api/v1")

    @application.exception_handler(Exception)
    async def global_exception_handler(_request: Request, exc: Exception):
        """
        Global handler that logs unhandled exceptions.
        Exposes internal details in the response for debugging during development/testing.
        """
        import traceback

        logger = logging.getLogger("robbot.global")
        logger.exception("[ERROR] Unhandled exception: %s", exc)

        tb = traceback.format_exc()
        return JSONResponse(status_code=500, content={"detail": str(exc), "traceback": tb.splitlines()})

    return application


app = create_app()
