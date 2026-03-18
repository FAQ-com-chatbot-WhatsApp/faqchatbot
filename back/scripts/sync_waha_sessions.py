#!/usr/bin/env python3
"""
Sync WAHA sessions to database.

This script fetches all sessions from WAHA API and syncs them to the database.
Does NOT hardcode any user-specific data - uses WAHA's dynamic response.

Usage:
    python scripts/sync_waha_sessions.py
"""

import asyncio
import sys
from pathlib import Path

# Add src to path for imports
sys.path.insert(0, str(Path(__file__).parent.parent / "src"))

from robbot.config.settings import get_settings
from robbot.infra.db.base import SessionLocal
from robbot.infra.integrations.waha.waha_client import get_waha_client
from robbot.infra.persistence.repositories.session_repository import SessionRepository

settings = get_settings()


async def sync_sessions():
    """
    Sync all WAHA sessions to database.

    - Fetches sessions from WAHA API
    - For each session, creates DB record if it doesn't exist
    - Updates existing records with current status
    """
    waha_client = get_waha_client()

    print("[SYNC] Fetching sessions from WAHA...")
    try:
        waha_sessions = await waha_client.list_sessions()
        print(f"[SYNC] Found {len(waha_sessions)} session(s) in WAHA")
    except Exception as e:  # noqa: BLE001 - Top-level script error handling
        print(f"[ERROR] Failed to fetch WAHA sessions: {e}")
        return

    # Get database session
    with SessionLocal() as db:
        session_repo = SessionRepository(db)

        for waha_session in waha_sessions:
            session_name = waha_session.get("name")

            # Skip sessions without name
            if not session_name or not isinstance(session_name, str):
                print("[WARNING] Session without valid name, skipping...")
                continue

            session_status = waha_session.get("status", "UNKNOWN")

            # Extract connected phone from 'me' field (if available)
            me_data = waha_session.get("me")
            connected_phone = None
            if me_data and isinstance(me_data, dict):
                connected_phone = me_data.get("id")  # e.g., "555195941505@c.us"

            # Determine webhook URL
            webhook_url = settings.WAHA_WEBHOOK_URL

            print(f"\n[SESSION] {session_name}")
            print(f"   Status: {session_status}")
            print(f"   Connected: {connected_phone or 'Not connected'}")

            # Check if session exists in DB
            existing = session_repo.get_by_name(session_name)

            if existing:
                # Update existing session
                print(f"   [INFO] Already exists in DB (ID: {existing.id})")

                # Update status and connected_phone if changed
                if existing.status != session_status or existing.connected_phone != connected_phone:
                    session_repo.update_status(
                        session_id=existing.id,
                        status=session_status,
                        connected_phone=connected_phone,
                    )
                    print(f"   [UPDATE] Status changed to {session_status}")
            else:
                # Create new session record
                print("   [CREATE] Creating new DB record...")
                new_session = session_repo.create(
                    name=session_name,
                    webhook_url=webhook_url,
                )
                print(f"   [CREATE] Created with ID: {new_session.id}")

                # Update status and connected_phone if different from default
                if session_status != "STOPPED" or connected_phone:
                    session_repo.update_status(
                        session_id=new_session.id,
                        status=session_status,
                        connected_phone=connected_phone,
                    )
                    print(f"   [UPDATE] Status changed to {session_status}")

        db.commit()

    print("\n[SYNC] Sync completed successfully")


if __name__ == "__main__":
    asyncio.run(sync_sessions())
