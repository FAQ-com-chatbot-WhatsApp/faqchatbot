# WhatsApp Media Sending

## Scope
Image/file/location sending through WAHA with shared rate limiting and optional anti-ban flags.

## Flow
- Rate limit: `_check_rate_limit(chat_id)` uses Redis key `waha:ratelimit:<chat_id>`; compares against `WAHA_MESSAGES_PER_HOUR`; increments with 1h TTL. On Redis failure, defaults to allow.
- Anti-ban: for text/image, `apply_anti_ban` honored only if `WAHA_ANTI_BAN_ENABLED`.

## Operations
- `send_text(data)`: enforces rate limit; calls `waha_client.send_text`; returns `MessageSentResponse` with id/timestamp.
- `send_image(data)`: rate-limit + anti-ban; sends captioned image via WAHA.
- `send_file(data)`: rate-limit; sends document with filename/caption; no anti-ban flag.
- `send_location(data)`: rate-limit; sends lat/long/title.
- `send_seen(chat_id, message_id)`: marks seen; no rate limit.

## Notes / Risks
- Exceeding rate limit raises `ValueError` with max/hour detail.
- No media size/type validation here; rely on WAHA or upstream validation.
- Anti-ban only applied for text/image; consider parity for file/location if WAHA supports.
