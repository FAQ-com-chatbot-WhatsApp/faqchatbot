# 🔍 Análise de Código Morto e Duplicações

**Data:** 18/03/2026
**Branch:** feat-ajustes-frontend

---

## ❌ CÓDIGO MORTO IDENTIFICADO

### 1. **`get_or_create_default_session()` - NUNCA USADO**

**Arquivo:** `back/src/robbot/services/communication/waha_service.py:283`

```python
def get_or_create_default_session(self) -> WhatsAppSession:
    """Get or create default session in DB (helper for startup).

    Returns:
        Default session
    """
    session = self.session_repo.get_by_name(settings.WAHA_SESSION_NAME)
    if not session:
        session = self.session_repo.create(
            name=settings.WAHA_SESSION_NAME,
            webhook_url=settings.WAHA_WEBHOOK_URL,
        )
        logger.info("Created default session: %s", settings.WAHA_SESSION_NAME)
    return session
```

**Status:** 🔴 **NUNCA CHAMADO** - 0 referências no código
**Ação:** ❓ **MANTER mas USAR no startup** ou **REMOVER**

**Decisão:** **USAR** - Perfeito para chamar no startup do FastAPI (lifespan)

---

## 🔁 DUPLICAÇÕES IDENTIFICADAS

### 1. **Lógica de criação de sessão duplicada**

**Local 1:** `waha_service.py:52-99` (create_session)

```python
# Extrai webhook_url de forma complexa
webhook_url = data.webhook_url or None
if not webhook_url and data.config:
    try:
        webhooks = data.config.get("webhooks") or []
        if isinstance(webhooks, list) and webhooks:
            webhook_url = webhooks[0].get("url")
    except Exception:
        webhook_url = None
webhook_url = webhook_url or settings.WAHA_WEBHOOK_URL
```

**Local 2:** `waha_controller.py:125-143` (create_session - exception handler)

```python
# DUPLICAÇÃO da mesma lógica de extração de webhook
webhook_url = data.webhook_url or None
if not webhook_url and isinstance(data.config, dict):
    webhooks = data.config.get("webhooks") or []
    if isinstance(webhooks, list) and webhooks:
        first_webhook = webhooks[0] if isinstance(webhooks[0], dict) else None
        if first_webhook:
            webhook_url = first_webhook.get("url")
from robbot.config.settings import settings
webhook_url = webhook_url or settings.WAHA_WEBHOOK_URL
```

**Ação:** ✅ **REMOVER** duplicação - toda lógica deve estar em `waha_service.py`
**Solução:** Controller apenas chama `service.create_session()`, sem lógica de fallback

---

### 2. **Auto-refresh no frontend (settings) - DESABILITADO mas CÓDIGO AINDA LÁ**

**Arquivo:** `frontend/src/app/(portal)/settings/page.tsx:59-63`

```typescript
const {
	currentSession,
	// ...
} = useSession({
	sessionName: "default",
	autoRefresh: false, // ❌ DESABILITADO
	refreshInterval: 30000, // ❌ NÃO USADO (autoRefresh = false)
});
```

**Ação:** 🟡 **MANTER** - Será usado para QR auto-refresh quando status = SCAN_QR_CODE

---

## 🎯 OPORTUNIDADES DE MELHORIA

### 1. **Inicialização de sessão no startup**

**Problema:** Nenhuma sessão é criada automaticamente
**Solução:** Chamar `get_or_create_default_session()` no lifespan do FastAPI

**Código a adicionar em `main.py`:**

```python
@asynccontextmanager
async def lifespan(app: FastAPI):
    logger = logging.getLogger("robbot.startup")

    # ... DI Container init ...

    # Initialize default WAHA session
    logger.info("[INFO] Initializing default WAHA session...")
    try:
        from robbot.infra.db.base import SessionLocal
        from robbot.infra.persistence.repositories.session_repository import SessionRepository
        from robbot.services.communication.waha_service import WAHAService
        from robbot.infra.integrations.waha.waha_client import get_waha_client

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
        logger.warning("[WARN] Failed to initialize default session: %s", e)
        # Non-critical, session can be created via UI

    yield
    # ... shutdown ...
```

---

### 2. **Frontend: Auto-criação de sessão no 404**

**Arquivo:** `frontend/src/hooks/useSession.ts:fetchSessionStatus`

**Código atual:**

```typescript
const fetchSessionStatus = useCallback(async () => {
	try {
		const status = await getSessionStatus(sessionName);
		setCurrentSession(status);
	} catch (err: any) {
		if (
			err?.status === 404 ||
			(err instanceof Error && err.message.includes("404"))
		) {
			setCurrentSession(null); // ❌ Para aqui
			setError(null);
			return;
		}
		// ...
	}
}, [sessionName]);
```

**Melhoria proposta:**

```typescript
const fetchSessionStatus = useCallback(async () => {
	try {
		const status = await getSessionStatus(sessionName);
		setCurrentSession(status);
	} catch (err: any) {
		if (err?.status === 404) {
			// Sessão não existe - criar automaticamente
			try {
				setIsLoading(true);
				await createSession({
					name: sessionName,
					webhook_url: null,
					config: null,
				});
				// Retentar buscar status
				const status = await getSessionStatus(sessionName);
				setCurrentSession(status);
			} catch (createErr) {
				setError("Erro ao criar sessão WhatsApp");
			} finally {
				setIsLoading(false);
			}
			return;
		}
		// ...
	}
}, [sessionName]);
```

---

## 📊 RESUMO

| Categoria        | Quantidade | Ação                     |
| ---------------- | ---------- | ------------------------ |
| **Código Morto** | 1 método   | ✅ Usar no startup       |
| **Duplicações**  | 1 lógica   | ✅ Remover do controller |
| **Melhorias**    | 2 fluxos   | ✅ Implementar           |

---

## ✅ PLANO DE EXECUÇÃO

### Fase 1: Limpeza (5 min)

1. ✅ Manter `get_or_create_default_session()` (será usado)
2. ✅ Simplificar `waha_controller.py` (remover duplicação)

### Fase 2: Backend (10 min)

3. ✅ Adicionar inicialização de sessão no `main.py` lifespan
4. ✅ Testar startup do container `go`

### Fase 3: Frontend (15 min)

5. ✅ Implementar auto-criação de sessão no 404
6. ✅ Adicionar QR auto-refresh condicional
7. ✅ Melhorar UI (4 estados visuais)

### Fase 4: Testes (10 min)

8. ✅ Restart containers
9. ✅ Testar fluxo completo: Desconectado → Escaneando → Conectado
10. ✅ Validar que não há mais 404s

---

## 🚨 RISCOS

- ✅ **Baixo** - Mudanças isoladas sem impacto em features existentes
- ✅ **Rollback fácil** - Git revert se necessário
- ✅ **Sem breaking changes** - API backwards compatible
