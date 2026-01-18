"use client"
import { Card, CardHeader, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Button } from "@/components/ui/button"
// ...existing code...
import Link from "next/link"

import { useState, useEffect, useRef } from "react"
import { Moon, Sun } from "lucide-react"

import { useFormFeedback } from "@/hooks/useFormFeedback"

// You likely need to import useAuth as well
import { useAuth } from "@/hooks/useAuth"

export default function SignupPage() {
  const { loading, error, success, signup, setError, setSuccess } = useAuth()
  const [isDark, setIsDark] = useState(false)
  const [mounted, setMounted] = useState(false)
  const emailRef = useRef<HTMLInputElement>(null)
  const passwordRef = useRef<HTMLInputElement>(null)
  const nameRef = useRef<HTMLInputElement>(null)

  // Padroniza feedback visual (toast e mensagem persistente)
  useFormFeedback(error, success)

  useEffect(() => {
    setMounted(true)
    setIsDark(document.documentElement.classList.contains("dark"))
  }, [])

  // Foco automático no campo com erro
  useEffect(() => {
    if (error) {
      if (error.toLowerCase().includes("name") && nameRef.current) {
        nameRef.current.focus()
      } else if (error.toLowerCase().includes("email") && emailRef.current) {
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
    setSuccess(null);
    const form = e.currentTarget;
    const full_name = (form.full_name as HTMLInputElement).value;
    const email = (form.email as HTMLInputElement).value;
    const password = (form.password as HTMLInputElement).value;
    const ok = await signup(email, password, full_name || undefined);
    if (ok) {
      form.reset();
    }
    // O feedback visual é tratado pelo hook useFormFeedback
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
          <h1 className="text-center text-3xl font-bold font-serif mb-2" data-testid="signup-title">Criar conta</h1>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Feedback visual persistente para acessibilidade */}
          {error && <div className="text-destructive text-sm" role="alert" aria-live="assertive" id="signup-error">{error}</div>}
          {success && <div className="text-success text-sm" role="status" aria-live="polite" id="signup-success">{success}</div>}
          <form className="space-y-4" onSubmit={handleSubmit} aria-label="signup form">
            <div>
              <Label htmlFor="full_name" className="font-medium">Nome completo</Label>
              <Input
                id="full_name"
                type="text"
                placeholder="Seu nome (opcional)"
                aria-label="Full Name"
                data-testid="signup-fullname"
                ref={nameRef}
                aria-describedby={error && error.toLowerCase().includes('name') ? 'signup-error' : undefined}
                aria-invalid={!!(error && error.toLowerCase().includes('name'))}
                autoComplete="name"
              />
            </div>
            <div>
              <Label htmlFor="email" className="font-medium">Email</Label>
              <Input
                id="email"
                type="email"
                placeholder="seu@email.com"
                required
                aria-label="Email"
                data-testid="signup-email"
                ref={emailRef}
                aria-describedby={error && error.toLowerCase().includes('email') ? 'signup-error' : undefined}
                aria-invalid={!!(error && error.toLowerCase().includes('email'))}
                autoComplete="email"
              />
            </div>
            <div>
              <Label htmlFor="password" className="font-medium">Senha</Label>
              <Input
                id="password"
                type="password"
                placeholder="••••••••"
                required
                minLength={8}
                aria-label="Senha"
                data-testid="signup-password"
                ref={passwordRef}
                aria-describedby={error && error.toLowerCase().includes('password') ? 'signup-error' : undefined}
                aria-invalid={!!(error && error.toLowerCase().includes('password'))}
                autoComplete="new-password"
              />
            </div>
            <Button className="w-full mt-2" type="submit" disabled={loading} aria-label="Criar conta" data-testid="signup-submit">{loading ? "Enviando..." : "Criar conta"}</Button>
          </form>
          <div className="text-center text-sm mt-4">
            Já tem uma conta? <Link href="/signin" className="text-primary font-medium hover:underline" aria-label="Entrar" data-testid="signup-signin">Entrar</Link>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
