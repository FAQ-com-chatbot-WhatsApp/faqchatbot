"""FastAPI application factory e tratamento global de exceções.

Inicializa rate limiter no startup e configura middleware CORS.
"""

import logging
from contextlib import asynccontextmanager

from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse

from robbot.api.v1.dependencies import initialize_rate_limiter
from robbot.api.v1.routers.api import api_router
from robbot.config.settings import get_settings
from robbot.core.logging_setup import configure_logging
from robbot.infra.db.session import get_sync_session


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
        logger.info("[INFO] Initializing rate limiter...")
        try:
            initialize_rate_limiter()
            logger.info("[SUCCESS] Rate limiter initialized successfully")
        except Exception as e:  # noqa: BLE001 (blind exception)
            logger.error("[ERROR] Failed to initialize rate limiter: %s", e)
            # Don't fail app startup, rate limiter will fail gracefully
        yield
        # (optional) shutdown hooks here

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
        Handler global que registra exceções não tratadas.
        Evita expor detalhes internos na resposta HTTP.
        """
        logger = logging.getLogger("robbot.global")
        logger.exception("[ERROR] Unhandled exception: %s", exc)

        return JSONResponse(
            status_code=500, content={"detail": "Internal server error"}
        )

    return application
app = create_app()
