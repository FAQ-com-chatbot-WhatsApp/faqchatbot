# Development Mode Guide

## Overview

Development Mode (`DEV_MODE`) is a safety feature that filters incoming WhatsApp messages by phone number. This prevents the bot from responding to all your personal contacts when testing with your own phone number.

## Problem Statement

When developers connect their personal WhatsApp account to WAHA for testing:
- ❌ Bot responds to **all** incoming messages from any contact
- ❌ Personal contacts receive automated bot responses
- ❌ No way to test safely without spamming friends/family
- ❌ Difficult to debug specific conversation flows

## Solution: Phone Number Filtering

Development Mode allows you to whitelist a single phone number for bot responses.

## Configuration

### 1. Enable in `.env`

```env
# ============================================================================
# DEVELOPMENT MODE
# ============================================================================
# DEV_MODE: Enable development mode (only respond to DEV_PHONE_NUMBER)
# DEV_PHONE_NUMBER: Phone number to respond to in dev mode (format: 5511999999999)
DEV_MODE=true
DEV_PHONE_NUMBER=5511999999999
```

### 2. Phone Number Format

- **Format**: Country code + DDD + number (no spaces, no special characters)
- **Examples**:
  - Brazil: `5511999999999` (55 = country, 11 = São Paulo, 999999999 = number)
  - US: `15551234567` (1 = country, 555 = area, 1234567 = number)

### 3. Restart Containers

```bash
docker compose up api worker --build -d
```

## How It Works

1. **Webhook receives message** from WhatsApp via WAHA
2. **Extract phone number** from `from` field (e.g., `5511999999999@c.us`)
3. **Check DEV_MODE**:
   - If `DEV_MODE=false` → Process all messages (production)
   - If `DEV_MODE=true`:
     - If `phone == DEV_PHONE_NUMBER` → Process message ✅
     - If `phone != DEV_PHONE_NUMBER` → Ignore message ❌ (log only)
4. **Log ignored messages** for visibility

## Logging

### Ignored Messages (DEV_MODE Active)

```log
[INFO] [DEV MODE] Mensagem ignorada - número não autorizado: 5511888888888 (permitido: 5511999999999)
```

### Accepted Messages (DEV_MODE Active)

```log
[INFO] [DEV MODE] Mensagem aceita de número autorizado: 5511999999999
[SUCCESS] Mensagem enfileirada para processamento: 1769912565.540702
```

### Production (DEV_MODE Disabled)

```log
[SUCCESS] Mensagem enfileirada para processamento: 1769912565.540702
```

## Testing Flow

### Scenario 1: Single Developer Testing

```env
DEV_MODE=true
DEV_PHONE_NUMBER=5511999999999  # Your test number
```

**Result**: Only messages from `5511999999999` are processed.

### Scenario 2: Team Testing (Multiple Developers)

Each developer runs their own instance:

**Developer A** (`.env`):
```env
DEV_MODE=true
DEV_PHONE_NUMBER=5511111111111
```

**Developer B** (`.env`):
```env
DEV_MODE=true
DEV_PHONE_NUMBER=5511222222222
```

### Scenario 3: Production Deployment

```env
DEV_MODE=false
# DEV_PHONE_NUMBER not needed
```

**Result**: All messages are processed (normal bot behavior).

## Best Practices

### ✅ DO
- Set `DEV_MODE=true` when testing locally
- Use your actual test phone number in `DEV_PHONE_NUMBER`
- Test with a dedicated test contact (not your own number)
- Disable DEV_MODE in production/staging environments
- Document your test phone number in team wiki

### ❌ DON'T
- Leave `DEV_MODE=true` in production
- Use production phone numbers in dev environments
- Commit real phone numbers to git (use `.env.example` instead)
- Test with customer phone numbers

## Troubleshooting

### Issue: Bot not responding to my test messages

**Check**:
1. `DEV_MODE` is enabled in `.env`
2. `DEV_PHONE_NUMBER` matches your WhatsApp number format
3. Containers were rebuilt after changing `.env`
4. Check logs for "Mensagem ignorada" or "Mensagem aceita"

**Verify**:
```bash
# Check current environment variables
docker compose exec api env | grep DEV

# Expected output:
# DEV_MODE=true
# DEV_PHONE_NUMBER=5511999999999
```

### Issue: Bot responding to all contacts

**Solution**:
1. Set `DEV_MODE=true` in `.env`
2. Add your phone number to `DEV_PHONE_NUMBER`
3. Rebuild: `docker compose up api worker --build -d`

### Issue: Can't find my phone number format

**Extract from WAHA logs**:
```bash
docker compose logs waha | grep "from"
```

Look for: `"from": "5511999999999@c.us"` → Use `5511999999999`

## Code Reference

**Implementation**: `back/src/robbot/adapters/controllers/webhook_controller.py`

```python
# DEV MODE: Filter messages by phone number
if settings.DEV_MODE and settings.DEV_PHONE_NUMBER:
    if phone != settings.DEV_PHONE_NUMBER:
        logger.info(
            "[DEV MODE] Mensagem ignorada - número não autorizado: %s",
            phone
        )
        return log  # Skip processing
```

**Configuration**: `back/src/robbot/config/settings.py`

```python
DEV_MODE: bool = Field(default=False, description="Enable dev mode")
DEV_PHONE_NUMBER: str | None = Field(default=None, description="Phone to respond to")
```

## Related Documentation

- [Logging Guidelines](logging-guidelines.md)
- [WAHA Setup](../deployment/railway.md)
- [Environment Variables](../../.env.example)
- [Architecture Overview](../../ARCHITECTURE.md#security--authentication)
