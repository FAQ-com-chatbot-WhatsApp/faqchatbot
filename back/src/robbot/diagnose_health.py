
import asyncio
import os
import sys
import logging

# Config basic logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Ensure path is set
sys.path.append("/app/src")

from robbot.infra.integrations.waha.waha_client import WAHAClient
from robbot.config.settings import settings

async def test_connectivity():
    logger.info(f"WAHA_URL configured: {settings.WAHA_URL}")
    
    # Test 1: Direct Ping
    client = WAHAClient()
    try:
        logger.info("Attempting PING...")
        resp = await client.ping()
        logger.info(f"PING SUCCESS: {resp}")
    except Exception as e:
        logger.error(f"PING FAILED: {e}")

    # Test 2: Server Version (Alternative health check)
    try:
        logger.info("Attempting VERSION check...")
        resp = await client.get_server_version()
        logger.info(f"VERSION SUCCESS: {resp}")
    except Exception as e:
        logger.error(f"VERSION FAILED: {e}")
        
    await client.close()

if __name__ == "__main__":
    asyncio.run(test_connectivity())
