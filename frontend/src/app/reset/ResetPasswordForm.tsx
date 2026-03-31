'use client'

import * as React from 'react'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { resetPassword } from '@/services/passwordService'
import { useFormFeedback } from '@/hooks/useFormFeedback'
import { useSearchParams } from 'next/navigation'

export function ResetPasswordForm() {
  const searchParams = useSearchParams()

  const token = searchParams.get('token')
  const [newPassword, setNewPassword] = React.useState('')
  const [confirmPassword, setConfirmPassword] = React.useState('')
  const [loading, setLoading] = React.useState(false)
  const [error, setError] = React.useState<string | null>(null)
  const [success, setSuccess] = React.useState<string | null>(null)
  const newPasswordRef = React.useRef<HTMLInputElement>(null)
  const confirmPasswordRef = React.useRef<HTMLInputElement>(null)

  useFormFeedback(error, success)

  function focusFirstError() {
    if (!newPassword) {
      newPasswordRef.current?.focus()
      return
    }
    if (!confirmPassword) {
      confirmPasswordRef.current?.focus()
      return
    }
    if (newPassword !== confirmPassword) {
      confirmPasswordRef.current?.focus()
      return
    }
  }

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault()
    setError(null)
    setSuccess(null)
    if (!token) {
      setError(
        'Link inválido ou expirado. Solicite um novo email de recuperação.'
      )
      return
    }
    if (!newPassword || !confirmPassword) {
      setError('Preencha todos os campos obrigatórios.')
      focusFirstError()
      return
    }
    if (newPassword !== confirmPassword) {
      setError('As senhas não coincidem.')
      focusFirstError()
      return
    }
    setLoading(true)
    try {
      await resetPassword(token, newPassword)
      setSuccess('✓ Senha redefinida! Redirecionando...')
      setTimeout(() => (window.location.href = '/signin'), 2000)
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Erro ao redefinir senha')
      focusFirstError()
      setLoading(false)
    }
  }

  return (
    <form
      className="space-y-4"
      onSubmit={handleSubmit}
      aria-label="formulário de redefinição de senha"
      noValidate
      data-testid="reset-form"
    >
      <div>
        <Label htmlFor="new-password" className="font-medium">
          Nova senha
        </Label>
        <Input
          id="new-password"
          type="password"
          placeholder="Digite a nova senha"
          value={newPassword}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setNewPassword(e.target.value)}
          required
          minLength={8}
          autoComplete="new-password"
          aria-label="Nova senha"
          aria-invalid={!!error && !newPassword}
          aria-describedby={!!error && !newPassword ? 'reset-error' : undefined}
          ref={newPasswordRef}
          data-testid="reset-new-password"
        />
      </div>
      <div>
        <Label htmlFor="confirm-password" className="font-medium">
          Confirmar nova senha
        </Label>
        <Input
          id="confirm-password"
          type="password"
          placeholder="Confirme a nova senha"
          value={confirmPassword}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setConfirmPassword(e.target.value)}
          required
          minLength={8}
          autoComplete="new-password"
          aria-label="Confirmar nova senha"
          aria-invalid={
            !!error && (!confirmPassword || newPassword !== confirmPassword)
          }
          aria-describedby={
            !!error && (!confirmPassword || newPassword !== confirmPassword)
              ? 'reset-error'
              : undefined
          }
          ref={confirmPasswordRef}
          data-testid="reset-confirm-password"
        />
      </div>
      <Button
        className="w-full mt-2"
        type="submit"
        disabled={loading || !newPassword || !confirmPassword}
        aria-label="Redefinir senha"
        data-testid="reset-submit"
      >
        {loading ? (
          <span className="inline-flex items-center">
            <span className="loader mr-2" aria-hidden="true"></span>Enviando...
          </span>
        ) : (
          'Redefinir senha'
        )}
      </Button>
      {error && (
        <div
          id="reset-error"
          className="text-destructive text-sm"
          role="alert"
          aria-live="assertive"
          data-testid="reset-error"
        >
          {error}
        </div>
      )}
      {success && (
        <div
          className="text-green-600 text-sm bg-green-50 p-3 rounded border border-green-200"
          role="status"
          aria-live="polite"
          data-testid="reset-success"
        >
          <p className="font-semibold mb-1">{success}</p>
          <a
            href="/signin"
            className="text-blue-600 underline hover:text-blue-800 text-sm font-medium"
          >
            Clique aqui se não foi redirecionado automaticamente
          </a>
        </div>
      )}
      <span className="sr-only" aria-live="polite">
        {error ? `Erro: ${error}` : success ? `Sucesso: ${success}` : null}
      </span>
    </form>
  )
}
