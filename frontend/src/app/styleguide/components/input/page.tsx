"use client"

import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Search, Mail } from "lucide-react"

export default function InputShowcase() {
  return (
    <div className="space-y-12">
      <div>
        <h1 className="text-3xl font-semibold mb-2">Input</h1>
        <p className="text-muted-foreground mb-8">
          Campo de entrada de texto com suporte a ícones e estados.
        </p>
      </div>

      {/* Basic */}
      <section className="space-y-4">
        <h2 className="text-2xl font-medium">Básico</h2>
        <div className="max-w-sm space-y-4">
          <Input id="basic-input" name="basic" placeholder="Digite algo..." autoComplete="off" />
          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input id="email" name="email" type="email" placeholder="email@example.com" autoComplete="email" />
          </div>
        </div>
      </section>

      {/* With Icons (Search Input) */}
      <section className="space-y-4">
        <h2 className="text-2xl font-medium">Com Ícone (Search Input)</h2>
        <div className="max-w-sm space-y-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <Input id="search-icon" name="search" className="pl-10" placeholder="Search..." autoComplete="off" />
          </div>
          <div className="relative">
            <Mail className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <Input id="email-icon" name="email-icon" className="pl-10" type="email" placeholder="Enter your email..." autoComplete="email" />
          </div>
        </div>
      </section>

      {/* States */}
      <section className="space-y-4">
        <h2 className="text-2xl font-medium">Estados</h2>
        <div className="max-w-sm space-y-4">
          <Input id="state-default" name="default" placeholder="Default" autoComplete="off" />
          <Input id="state-focused" name="focused" placeholder="Focused" className="ring-ring ring-[3px]" autoComplete="off" />
          <Input id="state-filled" name="filled" placeholder="Filled" defaultValue="John Doe" autoComplete="name" />
          <Input id="state-disabled" name="disabled" placeholder="Disabled" disabled autoComplete="off" />
          <Input id="state-error" name="error" placeholder="Error" aria-invalid className="border-destructive focus-visible:ring-destructive/20" autoComplete="off" />
        </div>
      </section>

      {/* Types */}
      <section className="space-y-4">
        <h2 className="text-2xl font-medium">Tipos</h2>
        <div className="max-w-sm space-y-4">
          <div className="space-y-2">
            <Label htmlFor="type-text">Text</Label>
            <Input id="type-text" name="text" type="text" placeholder="Text input" autoComplete="off" />
          </div>
          <div className="space-y-2">
            <Label htmlFor="type-email">Email</Label>
            <Input id="type-email" name="email-type" type="email" placeholder="email@example.com" autoComplete="email" />
          </div>
          <div className="space-y-2">
            <Label htmlFor="type-password">Password</Label>
            <Input id="type-password" name="password" type="password" placeholder="••••••••" autoComplete="current-password" />
          </div>
          <div className="space-y-2">
            <Label htmlFor="type-number">Number</Label>
            <Input id="type-number" name="number" type="number" placeholder="123" autoComplete="off" />
          </div>
          <div className="space-y-2">
            <Label htmlFor="type-date">Date</Label>
            <Input id="type-date" name="date" type="date" autoComplete="off" />
          </div>
        </div>
      </section>

      {/* Usage */}
      <section className="space-y-4">
        <h2 className="text-2xl font-medium">Uso</h2>
        <div className="rounded-lg border bg-card p-6 space-y-4">
          <h3 className="font-medium">Importação</h3>
          <pre className="bg-muted p-4 rounded overflow-x-auto">
            <code>{`import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"`}</code>
          </pre>

          <h3 className="font-medium mt-6">Exemplo Básico</h3>
          <pre className="bg-muted p-4 rounded overflow-x-auto">
            <code>{`<Input placeholder="Digite algo..." />

{/* Com label */}
<div className="space-y-2">
  <Label htmlFor="email">Email</Label>
  <Input id="email" type="email" />
</div>

{/* Search Input com ícone */}
<div className="relative">
  <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
  <Input className="pl-10" placeholder="Search..." />
</div>

{/* Estados */}
<Input disabled placeholder="Disabled" />
<Input aria-invalid placeholder="Error" />`}</code>
          </pre>

          <h3 className="font-medium mt-6">Props</h3>
          <div className="overflow-x-auto">
            <table className="w-full border-collapse">
              <thead>
                <tr className="border-b">
                  <th className="text-left p-2">Prop</th>
                  <th className="text-left p-2">Tipo</th>
                  <th className="text-left p-2">Descrição</th>
                </tr>
              </thead>
              <tbody>
                <tr className="border-b">
                  <td className="p-2 font-mono text-sm">type</td>
                  <td className="p-2 text-sm">string</td>
                  <td className="p-2 text-sm">text | email | password | number | date | etc.</td>
                </tr>
                <tr className="border-b">
                  <td className="p-2 font-mono text-sm">placeholder</td>
                  <td className="p-2 text-sm">string</td>
                  <td className="p-2 text-sm">Texto placeholder</td>
                </tr>
                <tr className="border-b">
                  <td className="p-2 font-mono text-sm">disabled</td>
                  <td className="p-2 text-sm">boolean</td>
                  <td className="p-2 text-sm">Estado desabilitado</td>
                </tr>
                <tr className="border-b">
                  <td className="p-2 font-mono text-sm">aria-invalid</td>
                  <td className="p-2 text-sm">boolean</td>
                  <td className="p-2 text-sm">Estado de erro (estilo automático)</td>
                </tr>
              </tbody>
            </table>
          </div>

          <h3 className="font-medium mt-6">Acessibilidade</h3>
          <ul className="list-disc list-inside space-y-2 text-sm">
            <li>Sempre associe com Label usando htmlFor/id</li>
            <li>Use aria-invalid para indicar erros de validação</li>
            <li>Forneça placeholder descritivo mas não substitua label</li>
            <li>Tipo apropriado ativa teclado móvel correto</li>
          </ul>
        </div>
      </section>
    </div>
  )
}
