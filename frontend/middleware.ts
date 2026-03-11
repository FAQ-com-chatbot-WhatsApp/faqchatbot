import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl

  // ✅ BYPASS TEMPORÁRIO: Permitir acesso à dashboard sem autenticação
  // TODO: Remover após implementar autenticação real
  if (pathname.startsWith('/dashboard')) {
    return NextResponse.next()
  }

  // ✅ Permitir SignIn/SignUp sem autenticação
  if (pathname.startsWith('/signin') || pathname.startsWith('/signup')) {
    return NextResponse.next()
  }

  // ✅ Permitir rotas públicas
  if (pathname === '/' || pathname.startsWith('/reset') || pathname.startsWith('/forgot')) {
    return NextResponse.next()
  }

  // TODO: Implementar validação de token após autenticação estar funcional
  return NextResponse.next()
}

export const config = {
  matcher: [
    /*
     * Match all request paths except for the ones starting with:
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     */
    '/((?!_next/static|_next/image|favicon.ico).*)',
  ],
}
