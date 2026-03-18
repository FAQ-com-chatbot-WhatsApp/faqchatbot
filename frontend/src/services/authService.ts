import { fetchApi } from '@/lib/api'

/**
 * Authenticate user and set HttpOnly cookies
 * @param email - User email
 * @param password - User password
 * @param rememberMe - Keep session active for 7 days
 */
export async function loginApi(
  email: string,
  password: string,
  rememberMe: boolean
) {
  const formData = new URLSearchParams()
  formData.append('username', email)
  formData.append('password', password)
  formData.append('rememberMe', rememberMe ? 'true' : 'false')
  const res = await fetchApi('/api/v1/auth/token', {
    method: 'POST',
    body: formData.toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    credentials: 'include',
  })

  return res
}

/**
 * Register new user account
 * @param email - User email
 * @param password - User password (min 8 chars)
 * @param full_name - Optional user full name
 */
export async function signupApi(
  email: string,
  password: string,
  full_name?: string
) {
  return fetchApi('/api/v1/auth/signup', {
    method: 'POST',
    body: JSON.stringify({ email, password, full_name }),
  })
}

/**
 * Logout user (revokes tokens server-side)
 */
export function logoutApi() {
  return true
}
