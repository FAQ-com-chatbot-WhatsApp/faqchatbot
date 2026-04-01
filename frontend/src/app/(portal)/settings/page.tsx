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
  RotateCw,
  QrCode,
  Phone,
  RefreshCw,
  Brain,
  Play,
  Square,
  Loader2,
  Save,
  Check,
  Settings2,
  Network,
  Cpu,
} from 'lucide-react'
import { useUser } from '@/hooks/useUser'
import { useSession } from '@/hooks/useSession'
import { 
  getAISettings, 
  updateAISettings, 
  getAIModels,
  type AISettings, 
  type AIModel 
} from '@/services/settingsService'
import { Switch } from '@/components/ui/switch'

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
    autoRefresh: true, // Enable auto-refresh (now smart-polls 5s/30s)
    refreshInterval: 10000, // 10s base interval when WORKING
  })

  const [fullName, setFullName] = useState('')
  const [isSaving, setIsSaving] = useState(false)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [qrDialogOpen, setQrDialogOpen] = useState(false)
  const [qrImageUrl, setQrImageUrl] = useState<string | null>(null)

  /**
   * Smart QR Image Management
   * Fetches QR screenshot whenever the session moves to SCAN_QR_CODE or the status is polled.
   */
  useEffect(() => {
    let intervalId: NodeJS.Timeout | null = null
    const isActive = currentSession?.status === 'SCAN_QR_CODE'

    const fetchQr = async () => {
      try {
        const { getScreenshot } = await import('@/services/wahaService')
        const blob = await getScreenshot()
        const url = URL.createObjectURL(blob)
        setQrImageUrl(prev => {
          if (prev) URL.revokeObjectURL(prev)
          return url
        })
        if (!qrDialogOpen) setQrDialogOpen(true)
      } catch (err) {
        console.error('[QR] Failed to fetch QR:', err)
      }
    }

    if (isActive) {
      fetchQr()
      // Refresh the image every 15 seconds while scanning (independent of status polling)
      intervalId = setInterval(fetchQr, 15000)
    } else {
      setQrDialogOpen(false)
      if (qrImageUrl) {
        URL.revokeObjectURL(qrImageUrl)
        setQrImageUrl(null)
      }
    }

  }, [currentSession?.status, qrDialogOpen])

  // IA Settings state
  const [iaSettings, setIaSettings] = useState<AISettings | null>(null)
  const [iaLoading, setIaLoading] = useState(true)
  const [iaSaving, setIaSaving] = useState(false)
  const [geminiModels, setGeminiModels] = useState<AIModel[]>([])
  const [groqModels, setGroqModels] = useState<AIModel[]>([])
  const [loadingModels, setLoadingModels] = useState({ gemini: false, groq: false })

  const fetchAIModels = async (provider: 'gemini' | 'groq') => {
    setLoadingModels(prev => ({ ...prev, [provider]: true }))
    try {
      const response = await getAIModels(provider)
      if (provider === 'gemini') setGeminiModels(response.models)
      else setGroqModels(response.models)
    } catch (err) {
      console.error(`[IA] Failed to fetch ${provider} models:`, err)
    } finally {
      setLoadingModels(prev => ({ ...prev, [provider]: false }))
    }
  }

  // Fetch AI settings on mount
  useEffect(() => {
    const fetchSettings = async () => {
      try {
        setIaLoading(true)
        const data = await getAISettings()
        setIaSettings(data)
        
        // Load initial model lists
        if (data) {
          fetchAIModels('gemini')
          fetchAIModels('groq')
        }
      } catch (err) {
        console.error('[IA] Failed to load settings:', err)
        setErrorMessage('Erro ao carregar configurações de IA.')
      } finally {
        setIaLoading(false)
      }
    }
    fetchSettings()
  }, [])

  const handleUpdateAISetting = (key: keyof AISettings, value: any) => {
    if (!iaSettings) return
    setIaSettings({ ...iaSettings, [key]: value })
  }

  const handleSaveAISettings = async () => {
    if (!iaSettings) return
    try {
      setIaSaving(true)
      setErrorMessage(null)
      await updateAISettings(iaSettings)
      setSuccessMessage('Configurações de IA salvas com sucesso!')
      setTimeout(() => setSuccessMessage(null), 3000)
    } catch (err) {
      console.error('[IA] Failed to save settings:', err)
      setErrorMessage('Erro ao salvar configurações de IA.')
    } finally {
      setIaSaving(false)
    }
  }

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
    } catch (err: unknown) {
      const typedErr = err as { status?: number; message?: string }
      // Se já está iniciada (409), apenas atualiza status sem mostrar erro
      if (
        typedErr?.status === 409 ||
        typedErr?.message?.includes('409') ||
        typedErr?.message?.includes('already')
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
                <DialogContent className="sm:max-w-2xl">
                  <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                      <QrCode className="w-5 h-5 text-green-600" />
                      Escaneie para conectar
                    </DialogTitle>
                    <DialogDescription>
                      Use o WhatsApp do seu celular para escanear o QR Code
                    </DialogDescription>
                  </DialogHeader>
                  <div className="flex flex-col items-center justify-center py-6">
                    {qrImageUrl ? (
                      <>
                        <div className="w-full bg-white p-2 rounded-xl shadow-md border overflow-hidden">
                          {/* eslint-disable-next-line @next/next/no-img-element */}
                          <img
                            src={qrImageUrl}
                            alt="QR Code WhatsApp"
                            className="w-full h-auto min-h-[300px] max-h-[70vh] object-contain mx-auto"
                          />
                        </div>
                        <div className="mt-6 space-y-2 text-sm text-muted-foreground">
                          <p className="flex items-center gap-2">
                            <span className="flex items-center justify-center w-5 h-5 rounded-full bg-muted text-xs">
                              1
                            </span>
                            Abra o WhatsApp no seu celular
                          </p>
                          <p className="flex items-center gap-2">
                            <span className="flex items-center justify-center w-5 h-5 rounded-full bg-muted text-xs">
                              2
                            </span>
                            Toque em Menu ou Configurações e selecione Aparelhos
                            conectados
                          </p>
                          <p className="flex items-center gap-2">
                            <span className="flex items-center justify-center w-5 h-5 rounded-full bg-muted text-xs">
                              3
                            </span>
                            Toque em Conectar um aparelho e aponte o celular
                            para esta tela
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
                    currentSession?.status === 'STARTING' ||
                    currentSession?.status === 'WORKING'
                  }
                  size="icon"
                  className={`w-16 h-16 rounded-full bg-transparent border-2 ${
                    currentSession?.status === 'STARTING'
                      ? 'border-yellow-500 text-yellow-600'
                      : 'border-green-500 text-green-600 hover:bg-green-500/10 hover:text-green-700'
                  } transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md`}
                  title="Iniciar conexão"
                >
                  {currentSession?.status === 'STARTING' ? (
                    <Loader2 className="w-7 h-7 animate-spin" />
                  ) : (
                    <Play className="w-7 h-7" fill="currentColor" />
                  )}
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
                  {sessionLoading ? (
                    <Loader2 className="w-7 h-7 animate-spin" />
                  ) : (
                    <RotateCw className="w-7 h-7" />
                  )}
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

        <TabsContent value="ia" className="space-y-6">
          <Card className="border-primary/10">
            <CardHeader>
              <div className="flex items-center justify-between">
                <div className="space-y-1">
                  <CardTitle className="text-2xl flex items-center gap-2">
                    <Brain className="w-6 h-6 text-primary" />
                    Inteligência Artificial
                  </CardTitle>
                  <CardDescription>
                    Gerencie os provedores de LLM e configure o failover automático.
                  </CardDescription>
                </div>
                <Button 
                  onClick={handleSaveAISettings} 
                  disabled={iaSaving || iaLoading}
                  className="gap-2"
                >
                  {iaSaving ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <Save className="w-4 h-4" />
                  )}
                  Salvar Configurações
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              {iaLoading ? (
                <div className="flex flex-col items-center justify-center py-12 gap-4">
                  <Loader2 className="w-8 h-8 animate-spin text-primary" />
                  <p className="text-muted-foreground animate-pulse">Carregando configurações...</p>
                </div>
              ) : (
                <div className="space-y-8">
                  {/* Global AI Config */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-muted/30 rounded-xl border border-primary/5">
                    <div className="space-y-3">
                      <Label className="flex items-center gap-2 text-sm font-semibold">
                        <Settings2 className="w-4 h-4" />
                        Provedor Primário
                      </Label>
                      <Select 
                        value={iaSettings?.llm_primary_provider || 'groq'} 
                        onValueChange={(val) => handleUpdateAISetting('llm_primary_provider', val)}
                      >
                        <SelectTrigger className="w-full bg-background border-primary/20">
                          <SelectValue placeholder="Selecione o provedor" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="gemini">Google Gemini (Sugerido para Visão)</SelectItem>
                          <SelectItem value="groq">Groq (Sugerido para Velocidade)</SelectItem>
                        </SelectContent>
                      </Select>
                      <p className="text-xs text-muted-foreground">
                        Este provedor será a primeira tentativa para todas as conversas.
                      </p>
                    </div>

                    <div className="flex flex-col justify-center space-y-4">
                      <div className="flex items-center justify-between p-3 bg-background rounded-lg border border-primary/10 shadow-sm">
                        <div className="space-y-0.5">
                          <Label className="flex items-center gap-2 text-sm font-semibold">
                            <Network className="w-4 h-4 text-blue-500" />
                            Failover Automático
                          </Label>
                          <p className="text-xs text-muted-foreground">
                            Ativa o provedor secundário se o primário falhar.
                          </p>
                        </div>
                        <Switch 
                          checked={iaSettings?.llm_enable_fallback ?? true}
                          onCheckedChange={(val) => handleUpdateAISetting('llm_enable_fallback', val)}
                        />
                      </div>
                    </div>
                  </div>

                  {/* Provider Specific Tabs */}
                  <Tabs defaultValue="gemini_config" className="w-full">
                    <TabsList className="grid w-full grid-cols-2 mb-6">
                      <TabsTrigger value="gemini_config" className="gap-2">
                        <Cpu className="w-4 h-4" /> Google Gemini
                      </TabsTrigger>
                      <TabsTrigger value="groq_config" className="gap-2">
                        <Cpu className="w-4 h-4" /> Groq Cloud
                      </TabsTrigger>
                    </TabsList>

                    {/* Gemini Settings */}
                    <TabsContent value="gemini_config" className="space-y-6">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                          <Label>Google API Key</Label>
                          <Input 
                            type="password" 
                            className="font-mono bg-muted/20"
                            placeholder="AIzaSy..."
                            value={iaSettings?.google_api_key || ''}
                            onChange={(e) => handleUpdateAISetting('google_api_key', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <div className="flex items-center justify-between">
                            <Label>Modelo Gemini</Label>
                            <Button 
                              variant="ghost" 
                              size="icon" 
                              className="h-6 w-6" 
                              onClick={() => fetchAIModels('gemini')}
                              disabled={loadingModels.gemini}
                            >
                              <Loader2 className={`w-3 h-3 ${loadingModels.gemini ? 'animate-spin' : ''}`} />
                            </Button>
                          </div>
                          <Select 
                            value={iaSettings?.gemini_model || ''} 
                            onValueChange={(val) => handleUpdateAISetting('gemini_model', val)}
                          >
                            <SelectTrigger className="w-full bg-background border-primary/20">
                              <SelectValue placeholder="Selecione o modelo" />
                            </SelectTrigger>
                            <SelectContent>
                              {geminiModels.length > 0 ? (
                                geminiModels.map(m => (
                                  <SelectItem key={m.id} value={m.id}>{m.name}</SelectItem>
                                ))
                              ) : (
                                <SelectItem value={iaSettings?.gemini_model || 'gemini-1.5-flash'}>
                                  {iaSettings?.gemini_model || 'gemini-1.5-flash'} (Default)
                                </SelectItem>
                              )}
                            </SelectContent>
                          </Select>
                        </div>
                        <div className="space-y-2">
                          <Label>Max Tokens</Label>
                          <Input 
                            type="number"
                            value={iaSettings?.gemini_max_tokens || 2048}
                            onChange={(e) => handleUpdateAISetting('gemini_max_tokens', parseInt(e.target.value))}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Temperatura ({iaSettings?.gemini_temperature})</Label>
                          <Input 
                            type="range"
                            min="0"
                            max="2"
                            step="0.1"
                            value={iaSettings?.gemini_temperature || 0.7}
                            onChange={(e) => handleUpdateAISetting('gemini_temperature', parseFloat(e.target.value))}
                            className="cursor-pointer"
                          />
                        </div>
                      </div>
                    </TabsContent>

                    {/* Groq Settings */}
                    <TabsContent value="groq_config" className="space-y-6">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                          <Label>Groq API Key</Label>
                          <Input 
                            type="password" 
                            className="font-mono bg-muted/20"
                            placeholder="gsk_..."
                            value={iaSettings?.groq_api_key || ''}
                            onChange={(e) => handleUpdateAISetting('groq_api_key', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <div className="flex items-center justify-between">
                            <Label>Modelo Groq</Label>
                            <Button 
                              variant="ghost" 
                              size="icon" 
                              className="h-6 w-6" 
                              onClick={() => fetchAIModels('groq')}
                              disabled={loadingModels.groq}
                            >
                              <Loader2 className={`w-3 h-3 ${loadingModels.groq ? 'animate-spin' : ''}`} />
                            </Button>
                          </div>
                          <Select 
                            value={iaSettings?.groq_model || ''} 
                            onValueChange={(val) => handleUpdateAISetting('groq_model', val)}
                          >
                            <SelectTrigger className="w-full bg-background border-primary/20">
                              <SelectValue placeholder="Selecione o modelo" />
                            </SelectTrigger>
                            <SelectContent>
                              {groqModels.length > 0 ? (
                                groqModels.map(m => (
                                  <SelectItem key={m.id} value={m.id}>{m.id}</SelectItem>
                                ))
                              ) : (
                                <SelectItem value={iaSettings?.groq_model || 'llama-3.3-70b-versatile'}>
                                  {iaSettings?.groq_model || 'llama-3.3-70b-versatile'} (Default)
                                </SelectItem>
                              )}
                            </SelectContent>
                          </Select>
                        </div>
                        <div className="space-y-2">
                          <Label>Max Tokens</Label>
                          <Input 
                            type="number"
                            value={iaSettings?.groq_max_tokens || 2048}
                            onChange={(e) => handleUpdateAISetting('groq_max_tokens', parseInt(e.target.value))}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Temperatura ({iaSettings?.groq_temperature})</Label>
                          <Input 
                            type="range"
                            min="0"
                            max="2"
                            step="0.1"
                            value={iaSettings?.groq_temperature || 0.7}
                            onChange={(e) => handleUpdateAISetting('groq_temperature', parseFloat(e.target.value))}
                            className="cursor-pointer"
                          />
                        </div>
                      </div>
                    </TabsContent>
                  </Tabs>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
