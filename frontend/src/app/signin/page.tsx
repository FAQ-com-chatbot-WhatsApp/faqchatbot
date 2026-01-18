"use client"
import { Card, CardHeader, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
// ...existing code...
import { User, Lock, Moon, Sun } from "lucide-react"
import Link from "next/link"

import { useState, useEffect, useRef } from "react"

import { useFormFeedback } from "@/hooks/useFormFeedback"

// You likely need to import useAuth as well
import { useAuth } from "@/hooks/useAuth"

export default function SignInPage() {
  const [isDark, setIsDark] = useState(false)
  const [mounted, setMounted] = useState(false)
  const { loading, error, success, login, setError } = useAuth()
  const emailRef = useRef<HTMLInputElement>(null)
  const passwordRef = useRef<HTMLInputElement>(null)

  // Padroniza feedback visual (toast e mensagem persistente)
  useFormFeedback(error, success)

  useEffect(() => {
    setMounted(true)
    setIsDark(document.documentElement.classList.contains("dark"))
  }, [])

  // Foco automático no campo com erro
  useEffect(() => {
    if (error) {
      if (error.toLowerCase().includes("email") && emailRef.current) {
        emailRef.current.focus()
      } else if (passwordRef.current) {
        passwordRef.current.focus()
      }
    }
  }, [error])

  const toggleDarkMode = () => {
    setIsDark(!isDark)
    document.documentElement.classList.toggle("dark")
  }

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError(null);
    const form = e.currentTarget;
    const email = (form.email as HTMLInputElement).value;
    const password = (form.password as HTMLInputElement).value;
    const rememberMe = (form.remember as HTMLInputElement).checked;
    await login(email, password, rememberMe);
    // O feedback visual é tratado pelo hook useFormFeedback
    // Redirecionar ou atualizar estado de login aqui se necessário
  }

  if (!mounted) {
    return (
      <div className="p-8 max-w-7xl mx-auto">
        <div className="h-screen flex items-center justify-center">
          <div className="animate-pulse text-muted-foreground">Loading...</div>
        </div>
      </div>
    )
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-background">
      <div className="absolute top-6 right-6">
        <Button variant="outline" size="icon" aria-label="Toggle theme" onClick={toggleDarkMode}>
          {isDark ? <Sun className="size-5" /> : <Moon className="size-5" />}
        </Button>
      </div>
      <Card className="w-full max-w-md shadow-lg border rounded-2xl bg-card">
        <CardHeader>
          <h1 className="text-center text-3xl font-bold font-serif mb-2" data-testid="login-title">Entrar</h1>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* O erro agora aparece como toast/snackbar, não como alert fixo */}
          {/* Exemplo de feedback visual persistente para acessibilidade */}
          {error && <div className="text-destructive text-sm" role="alert" aria-live="assertive" id="login-error">{error}</div>}
          {success && <div className="text-success text-sm" role="status" aria-live="polite" id="login-success">{success}</div>}
          <form className="space-y-4" aria-label="login form" onSubmit={handleSubmit}>
            <div>
              <Label htmlFor="email" className="font-medium">Email</Label>
              <div className="relative">
                <Input
                  id="email"
                  type="email"
                  placeholder="seu@email.com"
                  className="pl-10"
                  aria-label="Email"
                  data-testid="login-username"
                  ref={emailRef}
                  aria-describedby={error && error.toLowerCase().includes('email') ? 'login-error' : undefined}
                  aria-invalid={!!(error && error.toLowerCase().includes('email'))}
                  autoComplete="email"
                />
                <User className="absolute left-3 top-2.5 h-5 w-5 text-muted-foreground" />
              </div>
            </div>
            <div>
              <Label htmlFor="password" className="font-medium">Senha</Label>
              <div className="relative">
                <Input
                  id="password"
                  type="password"
                  placeholder="••••••••"
                  className="pl-10"
                  aria-label="Senha"
                  data-testid="login-password"
                  ref={passwordRef}
                  aria-describedby={error && !error.toLowerCase().includes('email') ? 'login-error' : undefined}
                  aria-invalid={!!(error && !error.toLowerCase().includes('email'))}
                  autoComplete="current-password"
                />
                <Lock className="absolute left-3 top-2.5 h-5 w-5 text-muted-foreground" />
              </div>
            </div>
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Checkbox id="remember" aria-label="Remember me" data-testid="login-remember" />
                <Label htmlFor="remember" className="text-sm">Lembrar de mim</Label>
              </div>
              <Link href="/forgot" className="text-sm text-primary hover:underline" aria-label="Esqueceu a senha?" data-testid="login-forgot">Esqueceu a senha?</Link>
            </div>
            <Button className="w-full mt-2" type="submit" aria-label="Entrar" data-testid="login-submit" disabled={loading}>{loading ? "Enviando..." : "Entrar"}</Button>
          </form>
          <div className="text-center text-sm mt-4">
            Novo por aqui? <Link href="/signup" className="text-primary font-medium hover:underline" aria-label="Criar conta" data-testid="login-signup">Criar conta</Link>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
