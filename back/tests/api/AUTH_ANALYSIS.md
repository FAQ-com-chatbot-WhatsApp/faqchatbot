# 🔍 Análise da Lógica de Autenticação

## Problemas Encontrados

### 1. ❌ Email Verification Blocking Login

**Arquivo:** `src/robbot/services/auth_services.py:118-120`

```python
if not self.email_verification_svc.is_email_verified(user.id):
    raise AuthException("Email not verified. Please check your email for verification link.")
```

**Problema:**
- Quando um usuário faz **signup**, a credencial é criada com `email_verified=False` (padrão)
- Tentativa de **login** FALHA com erro "Email not verified"
- É **IMPOSSÍVEL logar** sem verificar o email primeiro

**Fluxo Atual:**
```
signup() → email_verified=False ❌
    ↓
authenticate_user() → Bloqueia se email_verified=False ❌
    ↓
Login impossível até verificação de email
```

**Como Funciona a Verificação:**
1. Usuário recebe email com link + token de verificação
2. Clica no link → POST `/auth/verify-email?token=xxx`
3. Endpoint chama `EmailVerificationService.verify_email(token)`
4. Marca `email_verified=True`
5. **Agora** pode fazer login

**Código de Referência:**
- [test_email_verification.py:125-180](../../tests/unit/services/test_email_verification.py)
- [auth_services.py:118-120](src/robbot/services/auth_services.py)

---

### 2. ❌ No Email Verification Endpoint for Tests

**Problema:**
- Testes de API não conseguem acessar diretamente o banco de dados
- Não há um **endpoint público** para verificar email em testes
- Endpoint de verificação espera um **token enviado via email**
- Em testes, não temos acesso a emails reais

**Como Unit Tests Resolvem:**
```python
# tests/unit/services/test_auth_service.py:63-67
cred_repo = CredentialRepository(db_session)
cred = cred_repo.get_by_user_id(user.id)
cred.email_verified = True  # ← Marca diretamente
db_session.commit()
```

**Solução Necessária na API:**

Criar um endpoint de **teste apenas** que permite criar usuário já verificado:

```python
@router.post("/test/create-verified-user", status_code=201)
def test_create_verified_user(payload: SignupRequest, db: Session = Depends(get_db)):
    """APENAS PARA TESTES: Cria usuário com email já verificado.
    
    NÃO DEVE SER EXPOSTO EM PRODUÇÃO!
    
    Ambiente: apenas em TESTING=true ou ENV!=production
    """
    service = AuthService(db)
    user = service.signup(payload)
    
    # Mark as verified
    cred_repo = CredentialRepository(db)
    cred = cred_repo.get_by_user_id(user.id)
    cred.email_verified = True
    db.commit()
    
    return user
```

---

### 3. ⚠️ Current Test Fixture Attempts

**Arquivo:** `tests/api/conftest.py`

**Tentativa Atual:**
```python
response = requests.post(
    f"{api_base_url}/auth/token",
    data={
        "username": "admin@clinicago.com.br",
        "password": "Admin2025"
    }
)
```

**Problemas:**
1. ❌ Usuário `admin@clinicago.com.br` provavelmente não existe
2. ❌ Mesmo que exista, `email_verified=False` bloqueia login
3. ❌ Senha pode estar errada (policy: mín 8 chars, `Admin2025` = OK mas pode não ser a correta)

**Fluxo Desejado:**
```
1. POST /test/create-verified-user
   └─ Cria usuário com email_verified=True ✅
   
2. POST /auth/token (login)
   └─ Agora funciona ✅
   
3. Obter access_token
   └─ Use em outras requisições ✅
```

---

## 📋 Checklist de Correção

### Backend (API)

- [ ] **Criar endpoint de teste:** `/test/create-verified-user`
  - Apenas disponível se `ENV != production`
  - Cria usuário com `email_verified=True`
  - Retorna dados do usuário criado
  - Arquivo: `src/robbot/adapters/controllers/auth_controller.py`

**Código Necessário:**
```python
@router.post("/test/create-verified-user", status_code=201)
@pytest.mark.skipif(os.getenv("ENV") == "production", reason="Only for testing")
def test_create_verified_user(
    payload: SignupRequest, 
    db: Session = Depends(get_db)
):
    """APENAS PARA TESTES: Cria usuário com email já verificado."""
    service = AuthService(db)
    user = service.signup(payload)
    
    from robbot.adapters.repositories.credential_repository import CredentialRepository
    cred_repo = CredentialRepository(db)
    cred = cred_repo.get_by_user_id(user.id)
    cred.email_verified = True
    db.commit()
    
    return UserOut.model_validate(user)
```

### Tests (conftest.py)

- [x] **Atualizar admin_token fixture** para usar `/test/create-verified-user`
- [x] **Atualizar secretary_token fixture** para usar `/test/create-verified-user`
- [ ] **Testar após implementação do endpoint**

**Código Necessário no conftest.py:**
```python
@pytest.fixture(scope="session")
def admin_token(api_base_url: str) -> str:
    """Get admin token usando endpoint de teste."""
    email = "test_admin@example.com"
    password = "TestAdmin123!Secure"
    
    # Criar usuário já verificado
    response = requests.post(
        f"{api_base_url}/test/create-verified-user",
        json={
            "email": email,
            "username": "admin_test",
            "password": password,
            "full_name": "Test Admin",
            "role": "admin"
        }
    )
    
    if response.status_code != 201:
        pytest.skip(f"Failed to create verified user: {response.text}")
    
    # Fazer login
    login_response = requests.post(
        f"{api_base_url}/auth/token",
        data={"username": email, "password": password}
    )
    
    if login_response.status_code != 200:
        pytest.skip(f"Login failed: {login_response.text}")
    
    return login_response.json()["access_token"]
```

---

## 🎯 Root Cause Analysis

**Por que os testes falham com 401?**

1. ✅ Fixture tenta fazer login
2. ✅ Credenciais são enviadas corretamente (form-encoded)
3. ❌ Usuário não existe OU email_verified=False
4. ❌ Endpoint retorna 401 Unauthorized
5. ❌ Fixture falha
6. ❌ Todos os testes que dependem de `admin_token` falham

**Fluxo de Erro:**

```
admin_token fixture
├─ POST /auth/token (login attempt)
├─ usuario n existe ou email_verified=False
├─ API retorna 401 Unauthorized
├─ fixture.skip() → Test SKIPPED/FAILED
└─ Todas as funções que usam admin_token falham em setup
```

---

## 📚 Referências de Código

| Arquivo | Linhas | Descrição |
|---------|--------|-----------|
| `auth_services.py` | 87-187 | `authenticate_user()` - Lógica de login |
| `auth_services.py` | 118-120 | **Bloqueio de email não verificado** |
| `auth_services.py` | 60-85 | `signup()` - Cria user com email_verified=False |
| `test_auth_service.py` | 63-67 | **Solução: marcar email_verified=True** |
| `email_verification_service.py` | ? | Lógica de verificação de email |
| `conftest.py` | 15-27 | Fixtures que precisam de correção |

---

## ✅ Próximos Passos

1. **Backend:**
   - [ ] Implementar `/test/create-verified-user` endpoint
   - [ ] Proteger com `ENV` check (somente testes)
   - [ ] Testar endpoint manualmente

2. **Testes:**
   - [ ] Atualizar `conftest.py` com novo endpoint
   - [ ] Executar `test_01_auth.py` novamente
   - [ ] Verificar se `admin_token` e `secretary_token` agora funcionam
   - [ ] Executar `test_02_waha.py` etc

3. **Documentação:**
   - [ ] Atualizar ARCHITECTURE.md com novo endpoint
   - [ ] Adicionar comentário no código: "Test-only endpoint"

---

## 🔐 Segurança

⚠️ **IMPORTANTE:** O endpoint `/test/create-verified-user` DEVE SER:
- ✅ Protegido com verificação de ambiente (`ENV != production`)
- ✅ Não exposto em produção
- ✅ Documentado como "Test only"
- ✅ Possível usar decorator `@skipif_production` ou similar
