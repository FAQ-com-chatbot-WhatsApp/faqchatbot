'use client'

import { useState } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { PageHeader } from '@/components/ui/page-header'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge'
import {
  AlertCircle,
  CheckCircle2,
  User,
  Shield,
  Smartphone,
  Power,
  PowerOff,
  RotateCw,
  QrCode,
  Phone,
  RefreshCw
} from 'lucide-react'
import { useUser } from '@/hooks/useUser'
import { useSession } from '@/hooks/useSession'
import Image from 'next/image'

export default function SettingsPage() {
  const { user, isLoading: userLoading, error: userError, updateUser } = useUser()
  const {
    currentSession,
    isLoading: sessionLoading,
    error: sessionError,
    startSession,
    stopSession,
    restartSession,
    refresh: refreshSession
  } = useSession({ sessionName: 'default', autoRefresh: true, refreshInterval: 5000 })

  const [fullName, setFullName] = useState('')
  const [isSaving, setIsSaving] = useState(false)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!fullName.trim()) {
      return
    }

    try {
      setIsSaving(true)
      setSuccessMessage(null)
      await updateUser({ full_name: fullName })
      setSuccessMessage('Perfil atualizado com sucesso!')
      setTimeout(() => setSuccessMessage(null), 3000)
    } catch (err) {
      console.error('Failed to update profile', err)
    } finally {
      setIsSaving(false)
    }
  }

  const handleStartSession = async () => {
    try {
      await startSession()
      setSuccessMessage('Sessão WhatsApp iniciada!')
      setTimeout(() => setSuccessMessage(null), 3000)
    } catch (err) {
      console.error('Failed to start session', err)
    }
  }

  const handleStopSession = async () => {
    try {
      await stopSession()
      setSuccessMessage('Sessão WhatsApp parada!')
      setTimeout(() => setSuccessMessage(null), 3000)
    } catch (err) {
      console.error('Failed to stop session', err)
    }
  }

  const handleRestartSession = async () => {
    try {
      await restartSession()
      setSuccessMessage('Sessão WhatsApp reiniciada!')
      setTimeout(() => setSuccessMessage(null), 3000)
    } catch (err) {
      console.error('Failed to restart session', err)
    }
  }

  const getStatusBadge = (status: string) => {
    const statusMap: Record<string, { variant: 'default' | 'destructive' | 'secondary' | 'outline', label: string }> = {
      STOPPED: { variant: 'secondary', label: 'Parado' },
      STARTING: { variant: 'default', label: 'Iniciando...' },
      SCAN_QR_CODE: { variant: 'default', label: 'Aguardando QR Code' },
      WORKING: { variant: 'default', label: 'Conectado' },
      FAILED: { variant: 'destructive', label: 'Falha' },
    }
    const config = statusMap[status] || { variant: 'outline' as const, label: status }
    return <Badge variant={config.variant}>{config.label}</Badge>
  }

  if (userLoading && !user) {
    return (
      <div className="p-6 max-w-7xl">
        <PageHeader title="Configurações" />
        <LoadingSpinner />
      </div>
    )
  }

  return (
    <div className="p-6 max-w-7xl">
      <PageHeader title="Configurações" subtitle="Gerencie suas preferências e informações de perfil" />

      {(userError || sessionError) && (
        <Alert variant="destructive" className="mb-4">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>{userError || sessionError}</AlertDescription>
        </Alert>
      )}

      {successMessage && (
        <Alert className="mb-4 border-green-500 text-green-700 dark:text-green-400">
          <CheckCircle2 className="h-4 w-4" />
          <AlertDescription>{successMessage}</AlertDescription>
        </Alert>
      )}

      <Tabs defaultValue="whatsapp" className="space-y-4">
        <TabsList>
          <TabsTrigger value="whatsapp">
            <Smartphone className="w-4 h-4 mr-2" />
            WhatsApp
          </TabsTrigger>
          <TabsTrigger value="profile">
            <User className="w-4 h-4 mr-2" />
            Perfil
          </TabsTrigger>
          <TabsTrigger value="security">
            <Shield className="w-4 h-4 mr-2" />
            Segurança
          </TabsTrigger>
        </TabsList>

        <TabsContent value="whatsapp" className="space-y-4">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>Conexão WhatsApp</CardTitle>
                  <CardDescription>
                    Gerencie a conexão do seu WhatsApp com o sistema
                  </CardDescription>
                </div>
                <Button
                  variant="outline"
                  size="icon"
                  onClick={refreshSession}
                  disabled={sessionLoading}
                >
                  <RefreshCw className={`h-4 w-4 ${sessionLoading ? 'animate-spin' : ''}`} />
                </Button>
              </div>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Status Card */}
              <div className="space-y-4">
                <div className="flex items-center justify-between p-4 border rounded-lg">
                  <div className="space-y-1">
                    <p className="text-sm font-medium">Status da Conexão</p>
                    <div className="flex items-center gap-2">
                      {currentSession && getStatusBadge(currentSession.status)}
                    </div>
                  </div>
                  {currentSession?.connected_phone && (
                    <div className="text-right">
                      <p className="text-sm font-medium flex items-center gap-2">
                        <Phone className="h-4 w-4" />
                        {currentSession.connected_phone}
                      </p>
                      <p className="text-xs text-muted-foreground">Número conectado</p>
                    </div>
                  )}
                </div>
              </div>

              {/* QR Code Display */}
              {currentSession?.status === 'SCAN_QR_CODE' && currentSession?.qr_code && (
                <div className="space-y-4">
                  <div className="flex flex-col items-center justify-center p-6 border rounded-lg bg-muted/50">
                    <QrCode className="h-8 w-8 mb-4 text-muted-foreground" />
                    <p className="text-sm font-medium mb-4">Escaneie o QR Code com seu WhatsApp</p>
                    <div className="bg-white p-4 rounded-lg">
                      <Image
                        src={`data:image/png;base64,${currentSession.qr_code}`}
                        alt="QR Code WhatsApp"
                        width={256}
                        height={256}
                      />
                    </div>
                    <p className="text-xs text-muted-foreground mt-4 text-center">
                      1. Abra o WhatsApp no seu celular<br />
                      2. Toque em Menu ou Configurações<br />
                      3. Toque em Aparelhos conectados<br />
                      4. Toque em Conectar um aparelho<br />
                      5. Aponte seu celular para esta tela
                    </p>
                  </div>
                </div>
              )}

              {/* Action Buttons */}
              <div className="flex gap-3">
                <Button
                  onClick={handleStartSession}
                  disabled={sessionLoading || currentSession?.status === 'WORKING' || currentSession?.status === 'STARTING'}
                  className="flex-1"
                >
                  <Power className="w-4 h-4 mr-2" />
                  Iniciar Conexão
                </Button>
                <Button
                  variant="outline"
                  onClick={handleStopSession}
                  disabled={sessionLoading || currentSession?.status === 'STOPPED'}
                  className="flex-1"
                >
                  <PowerOff className="w-4 h-4 mr-2" />
                  Parar Conexão
                </Button>
                <Button
                  variant="outline"
                  onClick={handleRestartSession}
                  disabled={sessionLoading}
                  className="flex-1"
                >
                  <RotateCw className="w-4 h-4 mr-2" />
                  Reiniciar
                </Button>
              </div>

              {/* Info Box */}
              <Alert>
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>
                  <strong>Importante:</strong> Mantenha apenas uma sessão ativa por vez.
                  Desconectar aqui não afeta o WhatsApp no seu celular.
                </AlertDescription>
              </Alert>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="profile" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Informações do Perfil</CardTitle>
              <CardDescription>
                Atualize suas informações pessoais aqui
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input
                    id="email"
                    name="email"
                    type="email"
                    value={user?.email || ''}
                    disabled
                    className="bg-muted"
                    autoComplete="email"
                  />
                  <p className="text-sm text-muted-foreground">
                    O email não pode ser alterado
                  </p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="fullName">Nome Completo</Label>
                  <Input
                    id="fullName"
                    name="fullName"
                    type="text"
                    placeholder={user?.full_name || 'Seu nome completo'}
                    value={fullName}
                    onChange={(e) => setFullName(e.target.value)}
                    autoComplete="name"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="role">Função</Label>
                  <Input
                    id="role"
                    type="text"
                    value={user?.role || ''}
                    disabled
                    className="bg-muted"
                  />
                  <p className="text-sm text-muted-foreground">
                    A função é gerenciada por administradores
                  </p>
                </div>

                <Button type="submit" disabled={isSaving || !fullName.trim()}>
                  {isSaving ? 'Salvando...' : 'Salvar Alterações'}
                </Button>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Informações da Conta</CardTitle>
              <CardDescription>
                Detalhes do seu acesso e permissões
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-2">
              <div className="flex justify-between py-2 border-b">
                <span className="text-sm font-medium">ID da Conta</span>
                <span className="text-sm text-muted-foreground">{user?.id}</span>
              </div>
              <div className="flex justify-between py-2 border-b">
                <span className="text-sm font-medium">Status</span>
                <span className={`text-sm ${user?.is_active ? 'text-green-600' : 'text-red-600'}`}>
                  {user?.is_active ? 'Ativa' : 'Inativa'}
                </span>
              </div>
              <div className="flex justify-between py-2">
                <span className="text-sm font-medium">Nível de Acesso</span>
                <span className="text-sm text-muted-foreground capitalize">{user?.role}</span>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="security" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Segurança da Conta</CardTitle>
              <CardDescription>
                Gerencie configurações de segurança e privacidade
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Alterar Senha</Label>
                <p className="text-sm text-muted-foreground mb-4">
                  Para alterar sua senha, entre em contato com o administrador do sistema
                </p>
              </div>

              <div className="space-y-2">
                <Label>Autenticação de Dois Fatores</Label>
                <p className="text-sm text-muted-foreground mb-4">
                  Em desenvolvimento
                </p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
