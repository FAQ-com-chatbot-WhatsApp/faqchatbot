"""Rotas de autenticação para a API pública.

Implementa rate limiting para prevenir ataques de força bruta.
"""

import contextlib
import os
from datetime import UTC, datetime

from fastapi import APIRouter, Depends, Form, HTTPException, Request, Response, status
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.security import OAuth2PasswordRequestForm
from sqlalchemy.orm import Session

from robbot.api.v1.dependencies import get_current_user, get_db
from robbot.config.settings import get_settings
from robbot.core import security
from robbot.core.custom_exceptions import AuthException
from robbot.core.rate_limiting import (
    RATE_LIMIT_LOGIN,
    RATE_LIMIT_PASSWORD_RECOVERY,
    RATE_LIMIT_PASSWORD_RESET,
    RATE_LIMIT_REFRESH,
    RATE_LIMIT_REGISTER,
)
from robbot.infra.persistence.models.user_model import UserModel
from robbot.infra.persistence.repositories.auth_session_repository import AuthSessionRepository
from robbot.infra.persistence.repositories.credential_repository import CredentialRepository
from robbot.schemas.auth import (
    AuthSessionResponse,
    ChangePasswordRequest,
    EmailResendRequest,
    LoginResponse,
    MfaLoginRequest,
    ResetPasswordRequest,
    SessionListResponse,
    SessionOut,
    SignupRequest,
)
from robbot.schemas.mfa import (
    MfaDisableRequest,
    MfaDisableResponse,
    MfaSetupResponse,
    MfaVerifyRequest,
    MfaVerifyResponse,
)
from robbot.schemas.user import UserOut
from robbot.services.auth.auth_services import AuthService
from robbot.services.auth.email_verification_service import EmailVerificationService
from robbot.services.auth.mfa_service import MfaService

router = APIRouter()
settings = get_settings()


@router.post("/signup", response_model=UserOut, status_code=status.HTTP_201_CREATED)
@RATE_LIMIT_REGISTER  # 3 per hour per IP
async def signup(_request: Request, payload: SignupRequest, db: Session = Depends(get_db)):
    """Registra um novo usuário.

    Controller apenas mapeia request -> service -> response.

    Rate limiting: 3 requisições por hora por endereço IP.
    """
    service = AuthService(db)
    try:
        user = service.signup(payload)
    except Exception as exc:  # noqa: BLE001 (blind exception)
        raise HTTPException(status_code=400, detail=str(exc)) from exc
    return user


@router.post("/test/create-verified-user", response_model=UserOut, status_code=status.HTTP_201_CREATED)
async def test_create_verified_user(payload: SignupRequest, db: Session = Depends(get_db)):
    """WARNING: TEST ONLY - Creates user with verified email.

    Este endpoint NÃO DEVE SER EXPOSTO EM PRODUÇÃO.

    Válido apenas quando ENV != 'production'.
    Usado para testes de API que precisam fazer login sem verificação de email.

    Args:
        payload: Dados de signup (email, password, full_name, role)
        db: Database session

    Returns:
        UserOut com usuário criado e email_verified=True

    Raises:
        HTTPException 403: Se chamado em ambiente de produção
        HTTPException 400: Se usuário já existe
    """
    # Validar: apenas em ambiente de teste
    env = os.getenv("ENV", "development")
    if env == "production":
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Test endpoint not available in production",
        )

    service = AuthService(db)
    try:
        user = service.signup(payload)
    except Exception as exc:  # noqa: BLE001 (blind exception)
        raise HTTPException(status_code=400, detail=str(exc)) from exc

    # Mark email as verified

    cred_repo = CredentialRepository(db)
    cred = cred_repo.get_by_user_id(user.id)
    if cred:
        cred_repo.update(int(cred.id), {"email_verified": True})  # type: ignore
        db.commit()

    return user


@router.post("/token", response_model=dict)
@RATE_LIMIT_LOGIN  # 5 per 15min per IP
async def login_for_access_token(
    request: Request,
    response: Response,
    form_data: OAuth2PasswordRequestForm = Depends(),
    db: Session = Depends(get_db),
):
    """Autentica usuário e retorna tokens em cookies HttpOnly.

    Retorna dados públicos do usuário no corpo da resposta.
    Armazena access_token e refresh_token em cookies HttpOnly.

    Rate limiting: 5 requisições por 15 minutos por endereço IP.

    Se MFA estiver habilitado, retorna token temporário e mfa_required=True.
    """
    user_agent = request.headers.get("user-agent")
    client_ip = request.client.host if request.client else "unknown"

    service = AuthService(db)
    # Accept rememberMe from frontend (form or JSON)
    remember_me = False
    # Check request body for JSON (for custom clients)
    with contextlib.suppress(Exception):
        body = await request.json()
        if "rememberMe" in body:
            remember_me = bool(body["rememberMe"])
    try:
        token_result = service.authenticate_user(
            form_data.username,
            form_data.password,
            user_agent=user_agent,
            ip_address=client_ip,
            remember_me=remember_me,
        )
    except AuthException as exc:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=str(exc),
        ) from exc
    if token_result is None:
        # Checa se o usuário existe para mensagem mais clara
        user_exists = False
        with contextlib.suppress(Exception):
            user_exists = AuthService(db).repo.get_by_email(form_data.username) is not None
        if not user_exists:
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="Usuário não encontrado. Cadastre-se para acessar.",
            )
        else:
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="Credenciais inválidas. Verifique seu e-mail e senha.",
            )

    if token_result.mfa_required:
        return {
            "temporary_token": token_result.access_token,
            "mfa_required": True,
            "message": "MFA verification required. Use POST /auth/mfa/login with your TOTP code.",
        }

    # Set refresh token cookie duration based on rememberMe
    refresh_max_age = (60 * 24 * 30 * 60) if remember_me else (settings.REFRESH_TOKEN_EXPIRE_MINUTES * 60)
    samesite_value = "lax"  # type: ignore
    if settings.COOKIE_SAMESITE in ("lax", "strict", "none"):
        samesite_value = settings.COOKIE_SAMESITE  # type: ignore

    response.set_cookie(
        key="refresh_token",
        value=token_result.refresh_token,
        httponly=settings.COOKIE_HTTPONLY,
        secure=settings.COOKIE_SECURE,
        samesite=samesite_value,
        max_age=refresh_max_age,
        path="/api/v1/auth/refresh",  # Only sent to refresh endpoint
        domain=settings.COOKIE_DOMAIN,
    )

    # Set access token in HttpOnly cookie
    response.set_cookie(
        key="access_token",
        value=token_result.access_token,
        httponly=settings.COOKIE_HTTPONLY,
        secure=settings.COOKIE_SECURE,
        samesite=samesite_value,
        max_age=settings.ACCESS_TOKEN_EXPIRE_MINUTES * 60,
        path="/api/v1",  # Sent to all API endpoints
        domain=settings.COOKIE_DOMAIN,
    )

    # Return public user data (no tokens in response body)
    return {
        "user": UserOut.model_validate(token_result.user).model_dump(),
        "token_type": "bearer",
        "expires_in": settings.ACCESS_TOKEN_EXPIRE_MINUTES * 60,
        "mfa_required": False,
    }


@router.post("/refresh", response_model=dict)
@RATE_LIMIT_REFRESH  # 10 per 1min per user
async def refresh_token(request: Request, response: Response, db: Session = Depends(get_db)):
    """Renova access token usando refresh token do cookie HttpOnly.

    Rate limiting: 10 requisições por minuto por usuário.
    """
    # Read refresh token from cookie
    refresh_token_value = request.cookies.get("refresh_token")
    if not refresh_token_value:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Refresh token missing",
        )

    # Extract device metadata
    user_agent = request.headers.get("user-agent")
    client_ip = request.client.host if request.client else "unknown"

    service = AuthService(db)
    try:
        token_result = service.refresh(
            refresh_token_value,
            user_agent=user_agent,
            ip_address=client_ip,
        )
    except Exception as exc:  # noqa: BLE001 (blind exception)
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=str(exc),
        ) from exc

    samesite_value = "lax"  # type: ignore
    if settings.COOKIE_SAMESITE in ("lax", "strict", "none"):
        samesite_value = settings.COOKIE_SAMESITE  # type: ignore

    # Update access token in cookie
    response.set_cookie(
        key="access_token",
        value=token_result.access_token,
        httponly=settings.COOKIE_HTTPONLY,
        secure=settings.COOKIE_SECURE,
        samesite=samesite_value,
        max_age=settings.ACCESS_TOKEN_EXPIRE_MINUTES * 60,
        path="/api/v1",
        domain=settings.COOKIE_DOMAIN,
    )

    return {
        "message": "Token refreshed successfully",
        "expires_in": settings.ACCESS_TOKEN_EXPIRE_MINUTES * 60,
    }


@router.post("/logout", status_code=status.HTTP_204_NO_CONTENT)
def logout(
    request: Request,
    response: Response,
    db: Session = Depends(get_db),
    current_user: UserModel = Depends(get_current_user),
):
    """
    Logout: revoke tokens in DB and clear HttpOnly cookies.
    """
    # Read tokens from cookies
    access_token = request.cookies.get("access_token")
    refresh_token_value = request.cookies.get("refresh_token")

    service = AuthService(db)

    # Use service to revoke tokens and session, and log audit
    with contextlib.suppress(Exception):
        # Proceed to clear cookies regardless
        service.logout(
            user_id=int(current_user.id),  # type: ignore
            access_token=access_token,
            refresh_token=refresh_token_value,
        )

    # Clear cookies
    response.delete_cookie("access_token", path="/api/v1")
    response.delete_cookie("refresh_token", path="/api/v1/auth/refresh")

    return


@router.post("/password-change", status_code=status.HTTP_200_OK)
async def password_change(
    _request: Request,
    payload: ChangePasswordRequest,
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    """
    Change password for the authenticated user.
    Verifies current password, enforces policy, revokes sessions, and audits.
    """
    service = AuthService(db)
    try:
        service.change_password(
            user_id=current_user.id,
            old_password=payload.old_password,
            new_password=payload.new_password,
        )
    except AuthException as exc:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=str(exc),
        ) from exc
    return {"detail": "Password changed successfully"}


@router.get("/me", response_model=AuthSessionResponse)
def read_me(current_user: UserModel = Depends(get_current_user), db: Session = Depends(get_db)):
    """Obtém informações da sessão de autenticação atual.

    Retorna AuthSessionResponse (dados relacionados à autenticação).
    Para dados de perfil do usuário, use GET /users/me.
    """

    credential_repo = CredentialRepository(db)
    session_repo = AuthSessionRepository(db)

    # Get credential data (email_verified, mfa_enabled)
    user_id = int(current_user.id)  # type: ignore
    credential = credential_repo.get_by_user_id(user_id)
    email_verified = bool(credential.email_verified) if credential else False  # type: ignore
    mfa_enabled = bool(credential.mfa_enabled) if credential else False  # type: ignore

    # Get most recent active session
    sessions = session_repo.get_all_by_user_id(user_id)  # type: ignore[attr-defined]
    now_utc = datetime.now(UTC)
    active_sessions = [s for s in sessions if (not bool(s.is_revoked)) and s.expires_at.replace(tzinfo=UTC) > now_utc]  # type: ignore
    session_id = int(active_sessions[0].id) if active_sessions else None  # type: ignore
    # Type cast last_login_at to avoid Column[datetime] type error
    last_login_at_value = None
    if active_sessions:
        last_login_at_value = active_sessions[0].created_at
        # Ensure it's a datetime, not Column
        if hasattr(last_login_at_value, "__class__") and "Column" in str(type(last_login_at_value)):
            last_login_at_value = None

    return AuthSessionResponse(
        user_id=user_id,
        id=user_id,
        email=str(current_user.email),  # type: ignore
        role=str(current_user.role),  # type: ignore
        is_active=bool(current_user.is_active),  # type: ignore
        email_verified=email_verified,
        mfa_enabled=mfa_enabled,
        session_id=session_id,
        last_login_at=last_login_at_value,  # type: ignore
    )


@router.post("/password-recovery", status_code=status.HTTP_202_ACCEPTED)
@RATE_LIMIT_PASSWORD_RECOVERY  # 3 per hour per email
async def password_recovery(_request: Request, email: str = Form(...), db: Session = Depends(get_db)):
    """
    Initiates password recovery flow (sends email with token).

    Rate limited: 3 requests per hour per email address.
    """
    service = AuthService(db)
    service.send_password_recovery(email)
    return {"detail": "If the email exists a recovery message was sent"}


@router.post("/password-reset", status_code=status.HTTP_200_OK)
@RATE_LIMIT_PASSWORD_RESET  # 5 per 15min per IP
async def password_reset(_request: Request, payload: ResetPasswordRequest, db: Session = Depends(get_db)):
    """
    Resets password using recovery token.

    Rate limited: 5 requests per 15 minutes per IP address.
    """
    service = AuthService(db)
    try:
        service.reset_password(payload.token, payload.new_password)
    except Exception as exc:  # noqa: BLE001 (blind exception)
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=str(exc),
        ) from exc
    return {"detail": "Password updated"}


# ============================================================================
# SESSION MANAGEMENT
# ============================================================================
@router.get("/sessions", response_model=SessionListResponse)
def list_sessions(
    request: Request,
    current_user=Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """Lista todas as sessões do usuário autenticado.

    Retorna todas as sessões (ativas + revogadas) com sessão atual marcada.

    Returns:
        SessionListResponse com lista de sessões e contagem total
    """
    session_repo = AuthSessionRepository(db)
    sessions = session_repo.get_all_by_user_id(current_user.id)

    # Get current session JTI from refresh token cookie
    current_jti = None
    refresh_token_value = request.cookies.get("refresh_token")
    if refresh_token_value:
        with contextlib.suppress(Exception):
            payload = security.decode_token(refresh_token_value, verify_exp=False)
            current_jti = payload.get("jti")

    # Convert to response models
    session_outs = []
    for sess in sessions:
        session_out = SessionOut.model_validate(sess)
        session_out.is_current = sess.refresh_token_jti == current_jti
        session_outs.append(session_out)

    return SessionListResponse(sessions=session_outs, total=len(session_outs))


@router.post("/sessions/{session_id}/revoke", status_code=status.HTTP_204_NO_CONTENT)
def revoke_session(
    session_id: int,
    current_user=Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """Revoga uma sessão específica por ID.

    Permite fazer logout de um dispositivo específico.

    Args:
        session_id: ID da sessão a ser revogada

    Returns:
        204 No Content se bem-sucedido

    Raises:
        404 se sessão não encontrada ou pertence a outro usuário
    """
    session_repo = AuthSessionRepository(db)
    success = session_repo.revoke_by_id(session_id=session_id, user_id=current_user.id, reason="manual_revocation")

    if not success:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Session not found or unauthorized")

    return Response(status_code=status.HTTP_204_NO_CONTENT)


@router.post("/sessions/revoke-all", status_code=status.HTTP_200_OK)
def revoke_all_sessions(
    request: Request,
    current_user=Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """Revoga todas as sessões exceto a atual (logout de todos os outros dispositivos).

    Returns:
        JSON com número de sessões revogadas
    """
    session_repo = AuthSessionRepository(db)

    # Get current session JTI to exclude it
    current_jti = None
    refresh_token_value = request.cookies.get("refresh_token")
    if refresh_token_value:
        with contextlib.suppress(Exception):
            payload = security.decode_token(refresh_token_value, verify_exp=False)
            current_jti = payload.get("jti")

    # Get all active sessions
    all_sessions = session_repo.get_all_by_user_id(current_user.id)

    # Revoke all except current
    revoked_count = 0
    for sess in all_sessions:
        if sess.refresh_token_jti != current_jti and not bool(sess.is_revoked):  # type: ignore
            session_repo.revoke(sess, reason="revoke_all_other_sessions")
            revoked_count += 1

    return {"detail": f"Revoked {revoked_count} session(s)", "revoked_count": revoked_count}


# ============================================================================
# EMAIL VERIFICATION ENDPOINTS
# ============================================================================


@router.get("/email/verify")
async def verify_email(token: str, db: Session = Depends(get_db)):
    """Verifica email do usuário usando token de verificação do link do email.

    Args:
        token: Token de verificação do parâmetro URL do email

    Redireciona para a página de login (/signin) após sucesso.
    Em caso de erro, exibe mensagem amigável em português.
    """

    service = EmailVerificationService(db)
    try:
        user_id = service.verify_email(token)
        # Gerar access_token JWT para o usuário autenticado
        auth_service = AuthService(db)
        user = auth_service.repo.get_by_id(user_id)
        if not user:
            raise AuthException("User not found")
        access_token = security.create_token_for_subject(str(user.id), minutes=15, token_type="access")
        # Redirecionar para o frontend com o token na URL
        return RedirectResponse(url=f"http://localhost:3000/signin?verified=1&token={access_token}", status_code=302)
    except AuthException as exc:
        # Mensagem de erro amigável em português
        html = f"""
        <html><body style='font-family: Arial, sans-serif; background: #FAFAFA; color: #1F2937; text-align:center; padding:48px;'>
        <h2 style='color:#E53E3E;'>Erro ao verificar e-mail</h2>
        <p style='font-size:1.1rem; margin:24px auto; max-width:420px;'>{str(exc)}</p>
        <a href='/signin' style='display:inline-block; margin-top:32px; background:#5473E8; color:#fff; text-decoration:none; font-weight:600; padding:12px 28px; border-radius:10px;'>Ir para login</a>
        </body></html>
        """
        return HTMLResponse(content=html, status_code=400)


@router.post("/email/resend", status_code=status.HTTP_200_OK)
async def resend_verification_email(payload: EmailResendRequest, db: Session = Depends(get_db)):
    """Reenvia email de verificação para usuário.

    Rate limited para prevenir abuso (máx 1 email por 5 minutos).

    Args:
        payload: Endereço de email para reenviar verificação

    Returns:
        Mensagem de sucesso

    Raises:
        HTTPException: Se usuário não encontrado, email já verificado, ou rate limited
    """

    service = EmailVerificationService(db)
    try:
        service.resend_verification_email(payload.email)

        return {"detail": "Verification email sent. Please check your inbox.", "email": payload.email}
    except AuthException as exc:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=str(exc),
        ) from exc


# ==================== MFA ENDPOINTS ====================


@router.post("/mfa/setup", response_model=MfaSetupResponse)
def setup_mfa(
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    """Configura MFA para o usuário atual.

    Gera secret TOTP, QR code e códigos de backup.

    Returns:
        MfaSetupResponse: secret, qr_code_base64, backup_codes

    Raises:
        HTTPException: Se usuário não encontrado ou MFA já habilitado
    """

    service = MfaService(db)
    try:
        secret, qr_code_base64, backup_codes = service.setup_mfa(current_user.id)
        return MfaSetupResponse(
            secret=secret,
            qr_code_base64=qr_code_base64,
            qr_code=qr_code_base64,
            backup_codes=backup_codes,
        )
    except AuthException as exc:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=str(exc),
        ) from exc


@router.post("/mfa/verify", response_model=MfaVerifyResponse)
def verify_mfa_code(
    payload: MfaVerifyRequest,
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    """Verifica código MFA para usuário atual.

    Valida código TOTP de 6 dígitos ou código de backup.

    Args:
        payload: MfaVerifyRequest com código de 6 dígitos

    Returns:
        MfaVerifyResponse: status verified e mensagem

    Raises:
        HTTPException: Se MFA não habilitado ou código inválido
    """

    service = MfaService(db)
    try:
        # Try TOTP first
        service.verify_mfa(current_user.id, payload.code)
        return MfaVerifyResponse(
            verified=True,
            message="MFA code verified successfully",
            mfa_enabled=True,
        )
    except AuthException:
        # Try backup code
        try:
            service.verify_backup_code(current_user.id, payload.code)
            return MfaVerifyResponse(
                verified=True,
                message="Backup code verified successfully (code consumed)",
                mfa_enabled=True,
            )
        except AuthException as exc:
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail=str(exc),
            ) from exc


@router.post("/mfa/disable", response_model=MfaDisableResponse)
def disable_mfa(
    payload: MfaDisableRequest,
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    """Desabilita MFA para usuário atual (requer confirmação com código MFA).

    Verifica código primeiro, depois desabilita MFA e remove códigos de backup.

    Args:
        payload: MfaDisableRequest com código de confirmação

    Returns:
        MfaDisableResponse: mensagem de sucesso

    Raises:
        HTTPException: Se MFA não habilitado ou código inválido
    """

    service = MfaService(db)
    try:
        # Verify code before disabling
        service.verify_mfa(current_user.id, payload.code)
        service.disable_mfa(current_user.id)
        return MfaDisableResponse(message="MFA disabled successfully", mfa_enabled=False)
    except AuthException as exc:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=str(exc),
        ) from exc


@router.post("/mfa/login", response_model=LoginResponse, status_code=status.HTTP_200_OK)
def mfa_login(
    payload: MfaLoginRequest,
    request: Request,
    db: Session = Depends(get_db),
):
    """Completa login após verificação MFA.

    Fluxo:
    1. Usuário chama POST /auth/login com email/senha
    2. Se MFA habilitado, recebe token temporário com mfa_required=True
    3. Usuário chama este endpoint com token temporário + código TOTP/backup
    4. Retorna tokens finais de access e refresh

    Raises:
        HTTPException: Se token inválido, expirado, ou verificação MFA falha
    """
    service = AuthService(db)

    # Extract device info from request
    user_agent = request.headers.get("user-agent")
    ip_address = request.client.host if request.client else None

    try:
        if payload.temporary_token:
            token = service.verify_mfa_and_complete_login(
                temporary_token=payload.temporary_token,
                code=payload.code,
                user_agent=user_agent,
                ip_address=ip_address,
            )
        else:
            if not payload.email:
                raise HTTPException(
                    status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                    detail="temporary_token or email is required",
                )
            user = service.repo.get_by_email(payload.email)
            if not user:
                raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="User not found")

            temporary_token = security.create_token_for_subject(
                str(user.id),
                minutes=5,
                token_type="mfa-pending",
            )
            token = service.verify_mfa_and_complete_login(
                temporary_token=temporary_token,
                code=payload.code,
                user_agent=user_agent,
                ip_address=ip_address,
            )

        return LoginResponse(
            access_token=token.access_token,
            refresh_token=token.refresh_token,
            token_type="bearer",
            expires_in=900,
            mfa_required=False,
            temporary=False,
        )
    except AuthException as exc:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=str(exc),
        ) from exc
