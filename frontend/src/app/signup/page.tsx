"use client"
import { Card, CardHeader, CardContent } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Button } from "@/components/ui/button"
import { toast } from "sonner"
import Link from "next/link"
import { useState, useEffect } from "react"
import { Moon, Sun } from "lucide-react"
import { fetchApi } from "@/lib/api"


export default function SignUpPage() {
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  const [isDark, setIsDark] = useState(false)
  const [mounted, setMounted] = useState(false)

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
    setSuccess(null);
    setLoading(true);
    const form = e.currentTarget;
    const full_name = (form.full_name as HTMLInputElement).value;
    const email = (form.email as HTMLInputElement).value;
    const password = (form.password as HTMLInputElement).value;
    try {
      await fetchApi("/api/v1/auth/signup", {
        method: "POST",
        body: JSON.stringify({
          email,
          password,
          full_name: full_name || undefined,
        }),
      });
      setSuccess("Cadastro realizado com sucesso! Você pode fazer login.");
      toast.success("Cadastro realizado com sucesso! Você pode fazer login.");
      form.reset();
    } catch (err: any) {
      setError(err.message || "Erro ao cadastrar");
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
          <h1 className="text-center text-3xl font-bold font-serif mb-2" data-testid="signup-title">Sign Up</h1>
        </CardHeader>
        <CardContent className="space-y-6">
          {error && toast.error(error)}
          <form className="space-y-4" onSubmit={handleSubmit} aria-label="signup form">
            <div>
              <Label htmlFor="full_name" className="font-medium">Full Name</Label>
              <Input id="full_name" type="text" placeholder="Your name (optional)" aria-label="Full Name" data-testid="signup-fullname" />
            </div>
            <div>
              <Label htmlFor="email" className="font-medium">Email</Label>
              <Input id="email" type="email" placeholder="your@email.com" required aria-label="Email" data-testid="signup-email" />
            </div>
            <div>
              <Label htmlFor="password" className="font-medium">Password</Label>
              <Input id="password" type="password" placeholder="••••••••" required minLength={8} aria-label="Password" data-testid="signup-password" />
            </div>
            <Button className="w-full mt-2" type="submit" disabled={loading} aria-label="Sign Up" data-testid="signup-submit">{loading ? "Enviando..." : "Sign Up"}</Button>
          </form>
          <div className="text-center text-sm mt-4">
            Already have an account? <Link href="/signin" className="text-primary font-medium hover:underline" aria-label="Sign In" data-testid="signup-signin">Sign In</Link>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
