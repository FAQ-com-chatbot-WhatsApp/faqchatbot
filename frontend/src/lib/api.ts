// In development: use Next.js proxy (/api/* rewrites to backend:3333/api/*)
// In production (Docker): use internal Docker URL for SSR, same-origin for client
const BASE_URL =
  typeof window === 'undefined'
    ? process.env.API_URL || 'http://go:3333' // Server-side (SSR in Docker)
    : '' // Client-side uses same-origin via Next.js rewrite proxy

export async function fetchApi<T>(
  endpoint: string,
  options?: RequestInit & {
    timeout?: number
    onError?: (err: Error, endpoint: string) => void
  }
): Promise<T> {
  let controller: AbortController | undefined
  let timeoutId: NodeJS.Timeout | undefined
  let signal = options?.signal
  if (options?.timeout) {
    controller = new AbortController()
    signal = controller.signal
    timeoutId = setTimeout(() => controller!.abort(), options.timeout)
  }
  try {
    const headers: Record<string, string> = {}
    if (options?.headers) {
      if (options.headers instanceof Headers) {
        for (const [key, value] of options.headers.entries()) {
          headers[key] = value
        }
      } else if (Array.isArray(options.headers)) {
        for (const [key, value] of options.headers) {
          headers[key] = value
        }
      } else {
        Object.assign(headers, options.headers as Record<string, string>)
      }
    }
    if (!headers['Content-Type'] && !headers['content-type']) {
      headers['Content-Type'] = 'application/json'
    }
    const res = await fetch(`${BASE_URL}${endpoint}`, {
      // always include cookies for auth traffic unless explicitly overridden
      credentials: options?.credentials ?? 'include',
      ...options,
      signal,
      headers,
    })
    if (!res.ok) {
      let errorMessage = `API error: ${res.status}`
      try {
        const data = await res.json()
        errorMessage = normalizeApiError(data, res.status, endpoint)
      } catch (e) {}
      const error = new Error(errorMessage)
      ;(error as any).status = res.status

      // Global 401 handler - redirect to signin (except for signin/signup pages)
      if (res.status === 401 && typeof window !== 'undefined') {
        const isAuthPage =
          window.location.pathname.startsWith('/signin') ||
          window.location.pathname.startsWith('/signup') ||
          window.location.pathname.startsWith('/reset')
        if (!isAuthPage) {
          window.location.href = '/signin'
        }
      }

      if (typeof options?.onError === 'function') {
        options.onError(error, endpoint)
      }
      throw error
    }
    return res.json()
  } catch (err: any) {
    if (typeof options?.onError === 'function') {
      options.onError(err, endpoint)
    }
    throw err
  } finally {
    if (timeoutId) clearTimeout(timeoutId)
  }
}

export function normalizeApiError(
  data: any,
  status?: number,
  endpoint?: string
): string {
  if (!data) return 'Ocorreu um erro inesperado. Tente novamente.'

  // Handle 401 Unauthorized
  if (status === 401) {
    // Only treat as password reset error if it's actually a reset endpoint
    if (endpoint && endpoint.includes('/reset')) {
      return 'O link de redefinição é inválido ou está corrompido.'
    }
    // For all other 401s, it's an authentication issue
    return 'Sessão expirada. Por favor, faça login novamente.'
  }

  // Handle 403 Forbidden
  if (status === 403) {
    return 'Você não tem permissão para realizar esta ação.'
  }

  // Handle 502 Bad Gateway (WAHA/external service errors)
  if (status === 502) {
    if (endpoint && endpoint.includes('/waha')) {
      return 'Erro ao conectar com o serviço WhatsApp. Tente novamente em alguns instantes.'
    }
    return 'Erro de conexão com serviço externo. Tente novamente.'
  }

  // FastAPI: string detail (ex: token inválido)
  if (typeof data.detail === 'string') {
    const detail = data.detail.toLowerCase()

    // Password reset specific errors (only for reset endpoints)
    if (endpoint && endpoint.includes('/reset')) {
      if (detail.includes('expired')) {
        return 'Este link de redefinição expirou.'
      }
      if (detail.includes('already used')) {
        return 'Este link de redefinição já foi utilizado.'
      }
      if (
        detail.includes('invalid signature') ||
        detail.includes('invalid token')
      ) {
        return 'O link de redefinição é inválido ou está corrompido.'
      }
    }

    if (detail.includes('not found')) {
      return 'Recurso não encontrado.'
    }
    return data.detail // Return original message
  }

  // FastAPI: validation error array
  if (Array.isArray(data.detail)) {
    const messages = data.detail
      .map((err: any) => {
        if (err.msg && err.loc) {
          // Campos obrigatórios
          if (err.msg.toLowerCase().includes('field required')) {
            return null // Não mostrar nomes técnicos
          }
          // Senha muito curta
          if (
            err.loc.includes('new_password') &&
            err.msg.toLowerCase().includes('shorter than')
          ) {
            return 'A senha é muito curta. Use pelo menos 8 caracteres.'
          }
        }
        return null
      })
      .filter(Boolean)
    if (messages.length > 0) {
      return messages.join(' ')
    }
    // Se só campos obrigatórios faltando
    if (
      data.detail.some(
        (err: any) =>
          err.msg && err.msg.toLowerCase().includes('field required')
      )
    ) {
      return 'Preencha todos os campos obrigatórios.'
    }
    return 'Ocorreu um erro ao validar os dados. Tente novamente!'
  }

  if (data.message) {
    if (typeof data.message === 'string') {
      const msg = data.message.toLowerCase()
      if (msg.includes('expired')) {
        return 'Este link de redefinição expirou. Solicite um novo para redefinir sua senha.'
      }
      if (msg.includes('already used')) {
        return 'Este link de redefinição já foi utilizado. Solicite um novo para redefinir sua senha.'
      }
      // Password reset specific (only for reset endpoints)
      if (endpoint && endpoint.includes('/reset')) {
        if (
          msg.includes('invalid signature') ||
          msg.includes('invalid token')
        ) {
          return 'O link de redefinição é inválido ou está corrompido. Solicite um novo para redefinir sua senha.'
        }
      }
      if (msg.includes('not found')) {
        return 'Recurso não encontrado.'
      }
      return 'Ocorreu um erro ao validar o link. Tente novamente!'
    }
  }

  return 'Ocorreu um erro inesperado. Tente novamente.'
}
