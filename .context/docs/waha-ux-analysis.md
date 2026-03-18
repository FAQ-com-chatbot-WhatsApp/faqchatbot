# 📱 Análise UX/UI - Configuração WhatsApp

## 🔍 Situação Atual

### ✅ Backend - FUNCIONAL

O backend está **completo e bem implementado**:

```
Endpoints Disponíveis:
├── GET    /api/v1/waha/sessions                    → Lista todas sessões
├── POST   /api/v1/waha/sessions                    → Cria nova sessão
├── GET    /api/v1/waha/sessions/{name}/status      → Status atual + QR code
├── POST   /api/v1/waha/sessions/{name}/start       → Inicia sessão (gera QR)
├── POST   /api/v1/waha/sessions/{name}/stop        → Para sessão
├── POST   /api/v1/waha/sessions/{name}/restart     → Reinicia
├── GET    /api/v1/waha/sessions/{name}/qr          → QR code isolado
└── POST   /api/v1/waha/sessions/{name}/logout      → Desvincula device
```

**Schema de Resposta:**

```typescript
interface SessionStatus {
	name: string; // Ex: "default"
	status: string; // STOPPED | STARTING | SCAN_QR_CODE | WORKING | FAILED
	qr: string | null; // Base64 QR code (quando status = SCAN_QR_CODE)
	qr_code: string | null; // Alias de compatibilidade
	me: { id: string } | null; // Ex: "5511999999999@c.us" quando conectado
}
```

---

### ❌ Problema Principal

**A sessão "default" NÃO EXISTE no WAHA!**

```
Frontend → GET /api/v1/waha/sessions/default/status
Backend  → GET /api/sessions/default (WAHA)
WAHA     → 404 Not Found ❌
```

**Por quê?**

- Nenhum código cria a sessão automaticamente no startup
- Frontend tenta buscar status de sessão inexistente
- Usuário não consegue criar sessão pela UI (endpoint POST não é chamado)

---

## 🎯 Fluxo Ideal do Usuário

### 1. Primeira conexão (sessão não existe)

```
┌─────────────────────────────────────────┐
│ 🔴 WhatsApp Desconectado                │
├─────────────────────────────────────────┤
│                                         │
│  Você ainda não configurou o WhatsApp. │
│                                         │
│  [🟢 Conectar WhatsApp]                 │
│                                         │
└─────────────────────────────────────────┘
```

**Ação:** Clique cria a sessão + inicia automaticamente

---

### 2. Escaneamento QR Code (status = SCAN_QR_CODE)

```
┌─────────────────────────────────────────┐
│ 🟡 Aguardando Conexão                   │
├─────────────────────────────────────────┤
│                                         │
│        ████████████████████             │
│        ████  ██  ████  ████             │
│        ████████████████████   (QR)      │
│        ████  ██████  ██████             │
│        ████████████████████             │
│                                         │
│  Instruções:                            │
│  1. Abra WhatsApp no celular            │
│  2. Menu > Aparelhos conectados         │
│  3. Conectar aparelho                   │
│  4. Escaneie o código acima             │
│                                         │
│  [🔄 Atualizar QR]  [❌ Cancelar]       │
│                                         │
└─────────────────────────────────────────┘
```

**Auto-refresh:** QR expira em ~90s, precisa atualizar

---

### 3. Conectado (status = WORKING)

```
┌─────────────────────────────────────────┐
│ 🟢 WhatsApp Conectado                   │
├─────────────────────────────────────────┤
│                                         │
│  📱 +55 11 99999-9999                   │
│  ✓ Dispositivo vinculado com sucesso    │
│                                         │
│  Status: Ativo e recebendo mensagens    │
│                                         │
│  [🔄 Reiniciar]  [⏸ Pausar]  [🗑 Desvincular] │
│                                         │
└─────────────────────────────────────────┘
```

**Informações úteis:**

- Número conectado (formatado)
- Tempo conectado
- Última mensagem recebida

---

## 🚀 Melhorias Propostas

### 🎨 Frontend

#### 1. **Auto-criação de Sessão**

```typescript
// Modificar useSession hook
const fetchSessionStatus = async () => {
	try {
		const status = await getSessionStatus(sessionName);
		setCurrentSession(status);
	} catch (err) {
		if (err?.status === 404) {
			// Sessão não existe - criar automaticamente
			await createAndStartSession();
		}
	}
};

const createAndStartSession = async () => {
	try {
		// 1. Criar sessão
		await createSession({
			name: sessionName,
			webhook_url: null, // Backend usa default
			config: null,
		});

		// 2. Iniciar imediatamente
		await startSession(sessionName);

		// 3. Buscar status com QR
		await fetchSessionStatus();
	} catch (err) {
		setError(err.message);
	}
};
```

---

#### 2. **QR Code Auto-refresh**

```typescript
useEffect(() => {
	if (currentSession?.status !== "SCAN_QR_CODE") return;

	// Atualizar QR a cada 15 segundos (expira em ~90s)
	const interval = setInterval(async () => {
		try {
			const status = await getSessionStatus(sessionName);
			setCurrentSession(status);
		} catch (err) {
			console.error("QR refresh failed:", err);
		}
	}, 15000);

	return () => clearInterval(interval);
}, [currentSession?.status]);
```

---

#### 3. **UI Limpa e Intuitiva**

```tsx
// Estados visuais claros
const StatusCard = ({ session }) => {
	if (!session) {
		return (
			<Card>
				<CardHeader>
					<CardTitle>WhatsApp Desconectado</CardTitle>
				</CardHeader>
				<CardContent>
					<p>Configure sua conexão com o WhatsApp.</p>
					<Button onClick={handleConnect}>
						<Power className="mr-2" />
						Conectar WhatsApp
					</Button>
				</CardContent>
			</Card>
		);
	}

	if (session.status === "SCAN_QR_CODE") {
		return <QRCodeCard qr={session.qr_code} onRefresh={refreshQR} />;
	}

	if (session.status === "WORKING") {
		return (
			<ConnectedCard
				phone={session.me?.id}
				onRestart={handleRestart}
				onLogout={handleLogout}
			/>
		);
	}

	return <LoadingCard status={session.status} />;
};
```

---

### ⚙️ Backend

#### 4. **Sync Automático no Startup**

Adicionar ao `entrypoint.sh` (linha ~207):

```bash
# Sincroniza sessões WAHA com banco (apenas API)
if [ "${SERVICE_NAME}" = "go" ] || [ "${SERVICE_NAME}" = "api" ]; then
  if [ -f "./scripts/sync_waha_sessions.py" ]; then
    log_info "Sincronizando sessões WAHA..."
    if python scripts/sync_waha_sessions.py; then
      log_info "✓ Sessões WAHA sincronizadas"
    else
      log_warn "⚠ Falha ao sincronizar (não crítico)"
    fi
  fi
fi
```

**Benefício:** Sessões existentes no WAHA aparecem automaticamente no PostgreSQL

---

#### 5. **Endpoint de Status Agregado** (NOVO)

```python
@router.get("/sessions/overview")
async def get_sessions_overview(
    service: WAHAService = Depends(_get_waha_service),
):
    """Retorna overview de todas as sessões do usuário.

    Returns:
        {
            "has_active_session": bool,
            "active_session": SessionStatus | null,
            "total_sessions": int,
            "needs_setup": bool
        }
    """
    sessions = await service.list_sessions()
    active = next((s for s in sessions if s['status'] == 'WORKING'), None)

    return {
        "has_active_session": active is not None,
        "active_session": active,
        "total_sessions": len(sessions),
        "needs_setup": len(sessions) == 0
    }
```

**Uso no frontend:**

```typescript
const { data: overview } = useQuery('/api/v1/waha/sessions/overview')

if (overview.needs_setup) {
  return <SetupWizard />
}
```

---

## 📋 Checklist de Implementação

### Frontend (`frontend/src/`)

- [ ] **settings/page.tsx**
  - [ ] Adicionar lógica de auto-criação de sessão no 404
  - [ ] Implementar QR auto-refresh (15s)
  - [ ] Simplificar UI (4 estados: Desconectado, Escaneando, Conectado, Loading)
  - [ ] Formatar número de telefone (me.id)
  - [ ] Adicionar temporizador de expiração do QR (90s countdown)

- [ ] **hooks/useSession.ts**
  - [ ] Integrar createSession no fluxo de erro 404
  - [ ] Auto-refresh otimista apenas quando status = SCAN_QR_CODE
  - [ ] Adicionar método `logout()` para desvincular device

- [ ] **services/wahaService.ts**
  - [ ] Já tem todos os métodos! ✅

---

### Backend (`back/src/robbot/`)

- [ ] **scripts/entrypoint.sh**
  - [ ] Adicionar chamada a `sync_waha_sessions.py` após migrações

- [ ] **adapters/controllers/waha_controller.py**
  - [ ] (Opcional) Adicionar endpoint `/sessions/overview`

- [ ] **scripts/sync_waha_sessions.py**
  - [ ] Já corrigido! ✅

---

## 🎯 Resultado Final

### Antes (Problemático)

```
❌ Sessão não existe → 404 → Tela vazia
❌ QR expira → Usuário não sabe
❌ Botões habilitados em estados incorretos
❌ Muita informação técnica (JSON, IDs internos)
```

### Depois (Clean & Útil)

```
✅ Primeira vez → Auto-cria sessão → Mostra QR
✅ QR expira → Atualiza automaticamente
✅ Estados claros: Desconectado | Escaneando | Conectado
✅ Info útil: Número conectado, status, ações relevantes
```

---

## 📚 Referências

- [WAHA Docs - Sessions](https://waha.devlike.pro/docs/how-to/sessions/)
- [WAHA Docs - QR Code](https://waha.devlike.pro/docs/how-to/connect-device/)
- [WAHA GitHub](https://github.com/devlikeapro/waha)

---

## 🔄 Próximos Passos

1. ✅ Corrigir `sync_waha_sessions.py` (FEITO)
2. 🟡 Adicionar sync ao `entrypoint.sh`
3. 🟡 Implementar auto-criação no frontend
4. 🟡 Melhorar UI da tela de configurações
5. 🟡 Adicionar QR auto-refresh
6. 🟡 Testes E2E (Playwright) para fluxo completo
