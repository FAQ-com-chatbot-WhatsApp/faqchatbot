import requests
import re
import sys

def run_tests():
    url = "http://localhost:3333/api/v1"
    s = requests.Session()
    
    # login
    print("Logging in...")
    r1 = s.post(f"{url}/auth/token", data={
        "username": "test_admin2@clinica.com",
        "password": "Admin@2026!Secure"
    })
    
    if r1.status_code != 200:
        print(f"Login failed: {r1.status_code} {r1.text}")
        return

    # Extract access_token from Set-Cookie manually since requests might miss it due to Path
    set_cookie = r1.headers.get("Set-Cookie", "")
    token_match = re.search(r"access_token=([^;]+)", set_cookie)
    
    if not token_match:
        print("Access token cookie not found in response headers")
        # Try to use current session cookies anyway
        cookies = s.cookies
    else:
        cookies = {"access_token": token_match.group(1)}
    
    # Test Leads
    print("Testing Leads...")
    r_leads = s.get(f"{url}/leads/", cookies=cookies)
    print(f"Leads: {r_leads.status_code}")
    if r_leads.status_code == 200:
        print(f"Total leads: {r_leads.json().get('total')}")
    
    # Test Conversations
    print("Testing Conversations...")
    r_convs = s.get(f"{url}/conversations/", cookies=cookies)
    print(f"Conversations: {r_convs.status_code}")
    if r_convs.status_code == 200:
        print(f"Total conversations: {r_convs.json().get('total')}")

if __name__ == "__main__":
    run_tests()
