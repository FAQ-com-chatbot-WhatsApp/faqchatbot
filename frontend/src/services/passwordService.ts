import { fetchApi } from '@/lib/api'

/**
 * Request password recovery email
 * @param email - User email address
 */
export async function requestPasswordRecovery(email: string) {
  const formData = new URLSearchParams()
  formData.append('email', email)
  return fetchApi('/api/v1/auth/password-recovery', {
    method: 'POST',
    body: formData.toString(),
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  })
}

/**
 * Reset password using recovery token
 * @param token - Password reset token from email
 * @param new_password - New password (min 8 chars)
 */
export async function resetPassword(token: string, new_password: string) {
  return fetchApi('/api/v1/auth/password-reset', {
    method: 'POST',
    body: JSON.stringify({ token, new_password }),
  })
}
