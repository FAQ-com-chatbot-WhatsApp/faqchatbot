'use client'

import { useState, useEffect } from 'react'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { PageHeader } from '@/components/ui/page-header'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
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
  RefreshCw,
  Brain,
  Play,
  Square,
} from 'lucide-react'
import { useUser } from '@/hooks/useUser'
import { useSession } from '@/hooks/useSession'

export default function SettingsPage() {
  const {
    user,
    isLoading: userLoading,
    error: userError,
    updateUser,
  } = useUser()
  const {
    currentSession,
    isLoading: sessionLoading,
    error: sessionError,
    startSession,
    stopSession,
    restartSession,
    refresh: refreshSession,
  } = useSession({
    sessionName: 'default',
    autoRefresh: false,
    refreshInterval: 30000,
  })

  const [fullName, setFullName] = useState('')
  const [isSaving, setIsSaving] = useState(false)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [selectedLLM, setSelectedLLM] = useState('gemini')
  const [qrDialogOpen, setQrDialogOpen] = useState(false)
  const [qrImageUrl, setQrImageUrl] = useState<string | null>(null)

  // Auto-open QR dialog when status changes to SCAN_QR_CODE
  useEffect(() => {
    const fetchQrImage = async () => {
      if (currentSession?.status === 'SCAN_QR_CODE') {
        try {
          const { getScreenshot } = await import('@/services/wahaService')
          const blob = await getScreenshot()
          const url = URL.createObjectURL(blob)

          // Revoke old URL before setting new one
          if (qrImageUrl) {
            URL.revokeObjectURL(qrImageUrl)
          }

          setQrImageUrl(url)
          setQrDialogOpen(true)
        } catch (err) {
          console.error('Failed to fetch QR screenshot:', err)
        }
      } else if (currentSession?.status === 'WORKING') {
        setQrDialogOpen(false)
        if (qrImageUrl) {
          URL.revokeObjectURL(qrImageUrl)
          setQrImageUrl(null)
        }
      }
    }

    fetchQrImage()

    // Auto-refresh QR screenshot every 5 seconds when waiting for scan
    let intervalId: NodeJS.Timeout | null = null
    if (currentSession?.status === 'SCAN_QR_CODE') {
      intervalId = setInterval(fetchQrImage, 5000)
    }

    return () => {
      if (intervalId) {
        clearInterval(intervalId)
      }
      if (qrImageUrl) {
        URL.revokeObjectURL(qrImageUrl)
      }
    }
  }, [currentSession?.status])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!fullName.trim()) {
      return
    }

    try {
      setIsSaving(true)
      setSuccessMessage(null)
      setErrorMessage(null)
      await updateUser({ full_name: fullName })
      setSuccessMessage('Perfil atualizado com sucesso!')
      setTimeout(() => setSuccessMessage(null), 3000)
    } catch (err) {
      console.error('Failed to update profile', err)
      const message =
        err instanceof Error ? err.message : 'Erro ao atualizar perfil'
      setErrorMessage(message)
      setTimeout(() => setErrorMessage(null), 5000)
    } finally {
      setIsSaving(false)
    }
  }

  const handleStartSession = async () => {
    try {
      setErrorMessage(null)
      await startSession()
      // Refresh imediatamente para pegar o novo status
      await refreshSession()
      setSuccessMessage('Sessão WhatsApp iniciada com sucesso!')
      setTimeout(() => setSuccessMessage(null), 3000)
    } catch (err: any) {
      console.error('Failed to start session', err)
      // Se já está iniciada (409), apenas atualiza status sem mostrar erro
      if (
        err?.status === 409 ||
        err?.message?.includes('409') ||
        err?.message?.includes('already')
      ) {
        console.log(
          '[handleStartSession] Session already started, refreshing status...'
        )
        await refreshSession()
        return
      }
      const message =
        err instanceof Error ? err.message : 'Erro ao iniciar sessão WhatsApp'
      setErrorMessage(message)
      setTimeout(() => setErrorMessage(null), 5000)
    }
  }

  const handleStopSession = async () => {
    try {
      setErrorMessage(null)
      await stopSession()
      // Refresh imediatamente para pegar o novo status
      await refreshSession()
      setSuccessMessage('Sessão WhatsApp parada com sucesso!')
      setTimeout(() => setSuccessMessage(null), 3000)
    } catch (err) {
      console.error('Failed to stop session', err)
      const message =
        err instanceof Error ? err.message : 'Erro ao parar sessão WhatsApp'
      setErrorMessage(message)
      setTimeout(() => setErrorMessage(null), 5000)
    }
  }

  const handleRestartSession = async () => {
    try {
      setErrorMessage(null)
      await restartSession()
      // Refresh imediatamente para pegar o novo status
      await refreshSession()
      setSuccessMessage('Sessão WhatsApp reiniciada com sucesso!')
      setTimeout(() => setSuccessMessage(null), 3000)
    } catch (err) {
      console.error('Failed to restart session', err)
      const message =
        err instanceof Error ? err.message : 'Erro ao reiniciar sessão WhatsApp'
      setErrorMessage(message)
      setTimeout(() => setErrorMessage(null), 5000)
    }
  }

  const getStatusBadge = (status: string) => {
    const statusMap: Record<
      string,
      {
        variant: 'default' | 'destructive' | 'secondary' | 'outline'
        label: string
      }
    > = {
      STOPPED: { variant: 'secondary', label: 'Parado' },
      STARTING: { variant: 'default', label: 'Iniciando...' },
      SCAN_QR_CODE: { variant: 'default', label: 'Aguardando QR Code' },
      WORKING: { variant: 'default', label: 'Conectado' },
      FAILED: { variant: 'destructive', label: 'Falha' },
    }
    const config = statusMap[status] || {
      variant: 'outline' as const,
      label: status,
    }
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
      <PageHeader
        title="Configurações"
        subtitle="Gerencie suas preferências e informações de perfil"
      />

      {(userError || sessionError || errorMessage) && (
        <Alert variant="destructive" className="mb-4">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>
            {errorMessage || userError || sessionError}
          </AlertDescription>
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
          <TabsTrigger value="ia">
            <Brain className="w-4 h-4 mr-2" />
            IA
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
                  <RefreshCw
                    className={`h-4 w-4 ${sessionLoading ? 'animate-spin' : ''}`}
                  />
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
                      <p className="text-xs text-muted-foreground">
                        Número conectado
                      </p>
                    </div>
                  )}
                </div>
              </div>

              {/* QR Code Dialog */}
              <Dialog open={qrDialogOpen} onOpenChange={setQrDialogOpen}>
                <DialogContent className="sm:max-w-md">
                  <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                      <QrCode className="w-5 h-5 text-green-600" />
                      Scan to log in
                    </DialogTitle>
                    <DialogDescription>
                      Escaneie o QR Code com seu WhatsApp
                    </DialogDescription>
                  </DialogHeader>
                  <div className="flex flex-col items-center justify-center py-6">
                    {qrImageUrl ? (
                      <>
                        <div className="bg-white p-4 rounded-lg shadow-lg">
                          <img
                            src={qrImageUrl}
                            alt="QR Code WhatsApp"
                            className="w-80 h-80 object-contain"
                          />
                        </div>
                        <div className="mt-6 space-y-2 text-sm text-muted-foreground">
                          <p className="flex items-center gap-2">
                            <span className="flex items-center justify-center w-5 h-5 rounded-full bg-muted text-xs">
                              1
                            </span>
                            Scan the QR code with your phone's camera
                          </p>
                          <p className="flex items-center gap-2">
                            <span className="flex items-center justify-center w-5 h-5 rounded-full bg-muted text-xs">
                              2
                            </span>
                            Tap the link to open WhatsApp
                          </p>
                          <p className="flex items-center gap-2">
                            <span className="flex items-center justify-center w-5 h-5 rounded-full bg-muted text-xs">
                              3
                            </span>
                            Scan the QR code again to link to your account
                          </p>
                        </div>
                        <div className="flex items-center gap-2 mt-4 text-xs text-muted-foreground">
                          <RefreshCw className="w-3 h-3 animate-spin" />
                          <span>Atualizando automaticamente</span>
                        </div>
                      </>
                    ) : (
                      <div className="text-center py-8">
                        <QrCode className="w-12 h-12 mx-auto mb-4 text-muted-foreground animate-pulse" />
                        <p className="text-sm text-muted-foreground">
                          Carregando QR Code...
                        </p>
                      </div>
                    )}
                  </div>
                </DialogContent>
              </Dialog>

              {/* Action Buttons - Circular Icons */}
              <div className="flex items-center justify-center gap-6">
                {/* Play/Start Button */}
                <Button
                  onClick={handleStartSession}
                  disabled={
                    sessionLoading ||
                    currentSession?.status === 'WORKING' ||
                    currentSession?.status === 'STARTING'
                  }
                  size="icon"
                  className="w-16 h-16 rounded-full bg-transparent border-2 border-green-500 hover:bg-green-500/10 text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md"
                  title="Iniciar conexão"
                >
                  <Play className="w-7 h-7" fill="currentColor" />
                </Button>

                {/* QR Code Button */}
                <Button
                  onClick={() => setQrDialogOpen(true)}
                  disabled={
                    sessionLoading || currentSession?.status !== 'SCAN_QR_CODE'
                  }
                  size="icon"
                  className="w-16 h-16 rounded-full bg-transparent border-2 border-purple-500 hover:bg-purple-500/10 text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md"
                  title="Ver QR Code"
                >
                  <QrCode className="w-7 h-7" />
                </Button>

                {/* Refresh/Restart Button */}
                <Button
                  onClick={handleRestartSession}
                  disabled={sessionLoading}
                  size="icon"
                  className="w-16 h-16 rounded-full bg-transparent border-2 border-blue-500 hover:bg-blue-500/10 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md"
                  title="Reiniciar conexão"
                >
                  <RotateCw className="w-7 h-7" />
                </Button>

                {/* Stop Button */}
                <Button
                  onClick={handleStopSession}
                  disabled={
                    sessionLoading || currentSession?.status === 'STOPPED'
                  }
                  size="icon"
                  className="w-16 h-16 rounded-full bg-transparent border-2 border-red-500 hover:bg-red-500/10 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md"
                  title="Parar conexão"
                >
                  <Square className="w-7 h-7" />
                </Button>
              </div>

              {/* Info Box */}
              <Alert>
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>
                  <strong>Importante:</strong> Mantenha apenas uma sessão ativa
                  por vez. Desconectar aqui não afeta o WhatsApp no seu celular.
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
                    onChange={e => setFullName(e.target.value)}
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
                <span className="text-sm text-muted-foreground">
                  {user?.id}
                </span>
              </div>
              <div className="flex justify-between py-2 border-b">
                <span className="text-sm font-medium">Status</span>
                <span
                  className={`text-sm ${user?.is_active ? 'text-green-600' : 'text-red-600'}`}
                >
                  {user?.is_active ? 'Ativa' : 'Inativa'}
                </span>
              </div>
              <div className="flex justify-between py-2">
                <span className="text-sm font-medium">Nível de Acesso</span>
                <span className="text-sm text-muted-foreground capitalize">
                  {user?.role}
                </span>
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
                  Para alterar sua senha, entre em contato com o administrador
                  do sistema
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

        <TabsContent value="ia" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Configurações de Inteligência Artificial</CardTitle>
              <CardDescription>
                Escolha o provedor de IA para suas conversas e respostas
                automáticas
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="llm-select">Provedor de IA</Label>
                <Select value={selectedLLM} onValueChange={setSelectedLLM}>
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Selecione o provedor de IA" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="gemini">Google Gemini</SelectItem>
                    <SelectItem value="groq">Groq</SelectItem>
                  </SelectContent>
                </Select>
                <p className="text-sm text-muted-foreground">
                  O provedor selecionado será usado para gerar respostas
                  automáticas nas conversas.
                </p>
              </div>

              <div className="space-y-2">
                <div className="space-y-2 text-sm text-muted-foreground">
                  <div>
                    <strong>Google Gemini:</strong> Modelo avançado da Google,
                    ótimo para conversas naturais e análise de contexto.
                  </div>
                  <div>
                    <strong>Groq:</strong> Focado em velocidade e eficiência,
                    ideal para respostas rápidas e processamento em tempo real.
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
