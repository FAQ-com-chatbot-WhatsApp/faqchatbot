"use client"

import { useState } from "react"
import { useUser } from "@/hooks/useUser"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Button } from "@/components/ui/button"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { User, Mail, Shield, Camera } from "lucide-react"

export default function ProfilePage() {
  const { user } = useUser()
  const [isEditing, setIsEditing] = useState(false)
  const [fullName, setFullName] = useState(user?.full_name || "")
  const [email, setEmail] = useState(user?.email || "")

  const handleSave = () => {
    // TODO: Implementar atualização de perfil via API
    setIsEditing(false)
  }

  return (
    <div className="p-8 space-y-6 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-bold">Meu Perfil</h1>
        <p className="text-muted-foreground">Gerencie suas informações pessoais</p>
      </div>

      {/* Avatar e Informações Básicas */}
      <Card>
        <CardHeader>
          <CardTitle>Informações Pessoais</CardTitle>
          <CardDescription>
            Atualize suas informações de perfil
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Avatar */}
          <div className="flex items-center gap-6">
            <Avatar className="h-24 w-24">
              <AvatarImage src="https://github.com/shadcn.png" />
              <AvatarFallback className="text-2xl">
                {user?.full_name?.charAt(0) || user?.email?.charAt(0) || "U"}
              </AvatarFallback>
            </Avatar>
            <div>
              <Button variant="outline" size="sm">
                <Camera className="mr-2 h-4 w-4" />
                Alterar Foto
              </Button>
              <p className="text-xs text-muted-foreground mt-2">
                JPG, PNG ou GIF. Máximo 2MB
              </p>
            </div>
          </div>

          {/* Formulário */}
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="fullName">
                <User className="inline mr-2 h-4 w-4" />
                Nome Completo
              </Label>
              <Input
                id="fullName"
                name="fullName"
                value={fullName}
                onChange={(e) => setFullName(e.target.value)}
                disabled={!isEditing}
                placeholder="Seu nome completo"
                autoComplete="name"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="email">
                <Mail className="inline mr-2 h-4 w-4" />
                Email
              </Label>
              <Input
                id="email"
                name="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                disabled={!isEditing}
                placeholder="seu@email.com"
                autoComplete="email"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="role">
                <Shield className="inline mr-2 h-4 w-4" />
                Função
              </Label>
              <Input
                id="role"
                name="role"
                value={user?.role === "admin" ? "Administrador" : "Usuário"}
                disabled
                autoComplete="off"
              />
            </div>
          </div>

          {/* Ações */}
          <div className="flex gap-2">
            {isEditing ? (
              <>
                <Button onClick={handleSave}>Salvar Alterações</Button>
                <Button variant="outline" onClick={() => setIsEditing(false)}>
                  Cancelar
                </Button>
              </>
            ) : (
              <Button onClick={() => setIsEditing(true)}>Editar Perfil</Button>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Segurança */}
      <Card>
        <CardHeader>
          <CardTitle>Segurança</CardTitle>
          <CardDescription>
            Mantenha sua conta segura
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <Alert>
            <AlertDescription>
              Para alterar sua senha, use a opção &ldquo;Esqueci minha senha&rdquo; na tela de login.
            </AlertDescription>
          </Alert>
        </CardContent>
      </Card>
    </div>
  )
}
