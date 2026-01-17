"use client"
import { Card, CardHeader, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import { toast } from "sonner"
import { User, Lock, Moon, Sun } from "lucide-react"
import Link from "next/link"
import { useState, useEffect } from "react"
import { fetchApi, normalizeApiError } from "@/lib/api"

export default function SignInPage() {
  const [error, setError] = useState<string | null>(null)
  const [isDark, setIsDark] = useState(false)
  const [mounted, setMounted] = useState(false)
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    setMounted(true)
    setIsDark(document.documentElement.classList.contains("dark"))
  }, [])

  const toggleDarkMode = () => {
    setIsDark(!isDark)
    document.documentElement.classList.toggle("dark")
  }

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError(null);
    setLoading(true);
    const form = e.currentTarget;
    const email = (form.email as HTMLInputElement).value;
    const password = (form.password as HTMLInputElement).value;
    const rememberMe = (form.remember as HTMLInputElement).checked;
    // Field label mapping for user-friendly error messages
    const fieldLabels: Record<string, string> = {
      username: 'E-mail',
      email: 'E-mail',
      password: 'Senha',
    };
    function mapLoginError(msg: string): string {
      // Converts "username: Field required" to "E-mail: campo obrigatório."
      return msg.split(' | ').map((part) => {
        const match = part.match(/^(\w+): (.+)$/);
        if (match) {
          const field = fieldLabels[match[1]] || match[1];
          if (match[2] === 'Field required') {
            return `${field}: campo obrigatório.`;
          }
          return `${field}: ${match[2]}`;
        }
        return part;
      }).join(' | ');
    }
    try {
      // Monta form-urlencoded para OAuth2
      const formData = new URLSearchParams();
      formData.append("username", email);
      formData.append("password", password);
      formData.append("rememberMe", rememberMe ? "true" : "false");
      await fetchApi("/api/v1/auth/token", {
        method: "POST",
        body: formData.toString(),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
      });
      // Redirecionar ou atualizar estado de login aqui
    } catch (err: any) {
      let msg = "Erro ao autenticar. Tente novamente.";
      if (err?.message) {
        msg = mapLoginError(err.message);
      }
      setError(msg);
      toast.error(msg);
    } finally {
      setLoading(false);
    }
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
          <h1 className="text-center text-3xl font-bold font-serif mb-2" data-testid="login-title">Login</h1>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* O erro agora aparece como toast/snackbar, não como alert fixo */}
          <form className="space-y-4" aria-label="login form" onSubmit={handleSubmit}>
            <div>
              <Label htmlFor="email" className="font-medium">Email</Label>
              <div className="relative">
                <Input id="email" type="email" placeholder="your@email.com" className="pl-10" aria-label="Email" data-testid="login-username" />
                <User className="absolute left-3 top-2.5 h-5 w-5 text-muted-foreground" />
              </div>
            </div>
            <div>
              <Label htmlFor="password" className="font-medium">Password</Label>
              <div className="relative">
                <Input id="password" type="password" placeholder="••••••••" className="pl-10" aria-label="Password" data-testid="login-password" />
                <Lock className="absolute left-3 top-2.5 h-5 w-5 text-muted-foreground" />
              </div>
            </div>
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Checkbox id="remember" aria-label="Remember me" data-testid="login-remember" />
                <Label htmlFor="remember" className="text-sm">Remember me</Label>
              </div>
              <Link href="/forgot" className="text-sm text-primary hover:underline" aria-label="Forgot your password?" data-testid="login-forgot">Forgot your password?</Link>
            </div>
            <Button className="w-full mt-2" type="submit" aria-label="Login" data-testid="login-submit" disabled={loading}>{loading ? "Enviando..." : "Login"}</Button>
          </form>
          <div className="text-center text-sm mt-4">
            New here? <Link href="/signup" className="text-primary font-medium hover:underline" aria-label="Sign Up" data-testid="login-signup">Sign Up</Link>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
