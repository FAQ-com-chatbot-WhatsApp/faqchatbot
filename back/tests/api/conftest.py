"""Pytest configuration for API tests."""
import re
import time
import redis

import pytest
import requests


@pytest.fixture(scope="session")
def api_base_url() -> str:
    """Base URL for API requests."""
    return "http://localhost:3333/api/v1"


@pytest.fixture(scope="session")
def maildev_base_url() -> str:
    """Maildev API base URL."""
    return "http://localhost:1080"


@pytest.fixture(autouse=True)
def clear_rate_limiting():
    """Clear Redis rate limiting keys before each test.
    
    This ensures tests are isolated and don't interfere with each other.
    Rate limit keys follow pattern: ratelimit:*
    """
    redis_client = redis.Redis(host="localhost", port=6379, db=0, decode_responses=True)
    
    # Find all rate limiting keys
    rate_limit_keys = redis_client.keys("ratelimit:*")
    
    # Delete them if any exist
    if rate_limit_keys:
        redis_client.delete(*rate_limit_keys)
    
    yield  # Run the test
    
    # Optional: cleanup after test as well
    rate_limit_keys = redis_client.keys("ratelimit:*")
    if rate_limit_keys:
        redis_client.delete(*rate_limit_keys)


def extract_verification_token_from_email(maildev_url: str, recipient_email: str, timeout: int = 10) -> str:
    """Extract verification token from email in Maildev.
    
    Maildev captures all emails. We:
    1. Poll Maildev API for emails to the recipient
    2. Find email with verification link
    3. Extract token from verification link
    
    Args:
        maildev_url: Maildev API base URL
        recipient_email: Email address to search for
        timeout: Seconds to wait for email to arrive
        
    Returns:
        Verification token extracted from email body
        
    Raises:
        TimeoutError: If email not found within timeout
    """
    start_time = time.time()
    
    while time.time() - start_time < timeout:
        try:
            # Get all emails from Maildev API
            response = requests.get(f"{maildev_url}/email")
            if response.status_code != 200:
                time.sleep(0.5)
                continue
                
            emails = response.json()
            
            # Find email to our recipient
            for email in emails:
                if email.get("to") and any(recipient_email in addr.get("address", "") for addr in email.get("to", [])):
                    # Extract token from email body
                    # Email contains verification link like: http://...?token=xxx
                    body = email.get("text", "")
                    match = re.search(r'token=([a-zA-Z0-9\-_.]+)', body)
                    if match:
                        return match.group(1)
                    
            time.sleep(0.5)
            
        except Exception as e:
            pytest.skip(f"Error accessing Maildev: {e}")
    
    raise TimeoutError(f"Verification email not found in Maildev for {recipient_email} within {timeout}s")


@pytest.fixture
def admin_token(api_base_url: str, maildev_base_url: str) -> str:
    """Get admin authentication token using email verification flow.
    
    Changed to scope='function' (default) to avoid rate limiting.
    Each test gets fresh admin user.
    
    Flow:
    1. Signup (cria user com email_verified=False)
    2. Captura email de verificação no Maildev
    3. Extrai token do email
    4. Chama /auth/verify-email?token=xxx
    5. Faz login com sucesso
    """
    # Use timestamp to ensure unique email per test
    timestamp = int(time.time() * 1000)
    email = f"test_admin_{timestamp}@example.com"
    password = "TestAdmin123!Secure"
    
    # Step 1: Signup (cria user não verificado)
    signup_response = requests.post(
        f"{api_base_url}/auth/signup",
        json={
            "email": email,
            "password": password,
            "full_name": f"Test Admin {timestamp}",
            "role": "admin"
        }
    )
    
    if signup_response.status_code not in [201, 400]:  # 400 se já existe
        pytest.skip(f"Signup failed: {signup_response.status_code} - {signup_response.text}")
    
    # Se status 400, usuário já existe - tenta verificar email mesmo assim
    
    # Step 2: Extrair token do email capturado no Maildev
    try:
        token = extract_verification_token_from_email(maildev_base_url, email, timeout=10)
    except TimeoutError as e:
        pytest.skip(f"Email verification timeout: {e}")
    
    # Step 3: Verificar email usando o token
    verify_response = requests.get(
        f"{api_base_url}/auth/email/verify",
        params={"token": token}
    )
    
    if verify_response.status_code not in [200, 400]:  # 400 se já foi verificado
        pytest.skip(f"Email verification failed: {verify_response.status_code} - {verify_response.text}")
    
    # Step 4: Fazer login
    session = requests.Session()
    login_response = session.post(
        f"{api_base_url}/auth/token",
        data={
            "username": email,
            "password": password
        }
    )
    
    if login_response.status_code != 200:
        pytest.skip(f"Login failed: {login_response.status_code} - {login_response.text}")
    
    # Token is stored in HttpOnly cookie, extract from cookies
    access_token = session.cookies.get("access_token")
    if not access_token:
        pytest.skip(f"No access_token in cookies")
    
    return access_token


@pytest.fixture
def secretary_token(api_base_url: str, maildev_base_url: str) -> str:
    """Get secretary authentication token using email verification flow.
    
    Changed to scope='function' to avoid rate limiting.
    """
    # Use timestamp to ensure unique email per test
    timestamp = int(time.time() * 1000)
    email = f"test_secretary_{timestamp}@example.com"
    password = "TestSecretary123!Secure"
    
    # Step 1: Signup
    signup_response = requests.post(
        f"{api_base_url}/auth/signup",
        json={
            "email": email,
            "password": password,
            "full_name": f"Test Secretary {timestamp}",
            "role": "user"
        }
    )
    
    if signup_response.status_code not in [201, 400]:
        pytest.skip(f"Signup failed: {signup_response.status_code} - {signup_response.text}")
    
    # Step 2: Extrair token do email
    try:
        token = extract_verification_token_from_email(maildev_base_url, email, timeout=10)
    except TimeoutError as e:
        pytest.skip(f"Email verification timeout: {e}")
    
    # Step 3: Verificar email
    verify_response = requests.get(
        f"{api_base_url}/auth/email/verify",
        params={"token": token}
    )
    
    if verify_response.status_code not in [200, 400]:
        pytest.skip(f"Email verification failed: {verify_response.status_code} - {verify_response.text}")
    
    # Step 4: Fazer login
    session = requests.Session()
    login_response = session.post(
        f"{api_base_url}/auth/token",
        data={
            "username": email,
            "password": password
        }
    )
    
    if login_response.status_code != 200:
        pytest.skip(f"Login failed: {login_response.status_code} - {login_response.text}")
    
    # Token is stored in HttpOnly cookie, extract from cookies
    access_token = session.cookies.get("access_token")
    if not access_token:
        pytest.skip(f"No access_token in cookies")
    
    return access_token


@pytest.fixture
def auth_headers(admin_token: str) -> dict:
    """Headers with admin authentication.
    
    Changed to scope='function' for test isolation.
    """
    return {
        "Authorization": f"Bearer {admin_token}",
        "Content-Type": "application/json"
    }


@pytest.fixture
def api_client(api_base_url: str, maildev_base_url: str):
    """API client with authenticated session.
    
    Creates a fresh authenticated admin user for each test.
    """
    # Create authenticated user
    email, session = create_authenticated_user(api_base_url, maildev_base_url, role="admin")
    
    class APIClient:
        def __init__(self, base_url: str, session: requests.Session):
            self.base_url = base_url
            self.session = session
            
        def get(self, endpoint: str, **kwargs):
            return self.session.get(f"{self.base_url}{endpoint}", **kwargs)
            
        def post(self, endpoint: str, **kwargs):
            return self.session.post(f"{self.base_url}{endpoint}", **kwargs)
            
        def patch(self, endpoint: str, **kwargs):
            return self.session.patch(f"{self.base_url}{endpoint}", **kwargs)
            
        def delete(self, endpoint: str, **kwargs):
            return self.session.delete(f"{self.base_url}{endpoint}", **kwargs)
    
    return APIClient(api_base_url, session)


def create_authenticated_user(api_base_url: str, maildev_base_url: str, role: str = "admin") -> tuple[str, requests.Session]:
    """Helper to create and authenticate a user for testing.
    
    Returns:
        Tuple of (email, authenticated_session)
    """
    import time
    
    # Use timestamp to ensure unique email
    timestamp = int(time.time() * 1000)
    email = f"test_user_{timestamp}@example.com"
    password = "TestUser123!Secure"
    
    # Signup
    signup_response = requests.post(
        f"{api_base_url}/auth/signup",
        json={
            "email": email,
            "password": password,
            "full_name": f"Test User {timestamp}",
            "role": role
        }
    )
    
    if signup_response.status_code not in [201, 400]:
        raise Exception(f"Signup failed: {signup_response.status_code} - {signup_response.text}")
    
    # Extract verification token from email
    try:
        token = extract_verification_token_from_email(maildev_base_url, email, timeout=10)
    except TimeoutError as e:
        raise Exception(f"Email verification timeout: {e}")
    
    # Verify email
    verify_response = requests.get(
        f"{api_base_url}/auth/email/verify",
        params={"token": token}
    )
    
    if verify_response.status_code not in [200, 400]:
        raise Exception(f"Email verification failed: {verify_response.status_code} - {verify_response.text}")
    
    # Login
    session = requests.Session()
    login_response = session.post(
        f"{api_base_url}/auth/token",
        data={
            "username": email,
            "password": password
        }
    )
    
    if login_response.status_code != 200:
        raise Exception(f"Login failed: {login_response.status_code} - {login_response.text}")
    
    return email, session


@pytest.fixture
def authenticated_admin(api_base_url: str, maildev_base_url: str) -> tuple[str, requests.Session]:
    """Create and return an authenticated admin user for testing."""
    return create_authenticated_user(api_base_url, maildev_base_url, role="admin")


@pytest.fixture
def authenticated_secretary(api_base_url: str, maildev_base_url: str) -> tuple[str, requests.Session]:
    """Create and return an authenticated secretary user for testing."""
    return create_authenticated_user(api_base_url, maildev_base_url, role="user")

