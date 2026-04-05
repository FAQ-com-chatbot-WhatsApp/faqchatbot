'use client'

import { useState, useEffect, useCallback } from 'react'
import { PageHeader } from '@/components/ui/page-header'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { 
  Plus, 
  Trash2, 
  Edit2, 
  Save, 
  X, 
  ChevronRight, 
  ChevronDown, 
  Database, 
  Sparkles,
  Info,
  Search,
  BookOpen,
  Layers
} from 'lucide-react'
import { toast } from 'sonner'
import * as faqService from './faqService'

interface FaqAttributes {
  name?: string
  title?: string
  question?: string
  answer?: string
}

interface FaqItem {
  id: string
  name?: string
  title?: string
  question?: string
  answer?: string
  attributes?: FaqAttributes
}

export default function FaqPage() {
  // Data
  const [groups, setGroups] = useState<FaqItem[]>([])
  const [categories, setCategories] = useState<FaqItem[]>([])
  const [questions, setQuestions] = useState<FaqItem[]>([])

  // Selection
  const [selectedGroup, setSelectedGroup] = useState<string | null>(null)
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null)
  const [searchQuery, setSearchQuery] = useState('')

  // State Management
  const [loading, setLoading] = useState(false)
  const [syncing, setSyncing] = useState(false)
  const [showAddForm, setShowAddForm] = useState(false)

  // Form State
  const [newQuestion, setNewQuestion] = useState('')
  const [newAnswer, setNewAnswer] = useState('')

  // Editing State
  const [editingId, setEditingId] = useState<string | null>(null)
  const [editQuestion, setEditQuestion] = useState('')
  const [editAnswer, setEditAnswer] = useState('')

  // Fetching Logic
  const fetchGroups = useCallback(async () => {
    try {
      const data = await faqService.getGroups()
      setGroups(data as FaqItem[])
    } catch (error) {
      toast.error('Erro ao buscar grupos')
    }
  }, [])

  const fetchCategories = useCallback(async (groupId: string) => {
    try {
      setLoading(true)
      const data = await faqService.getCategories(groupId)
      setCategories(data as FaqItem[])
      setSelectedCategory(null)
      setQuestions([])
    } catch (error) {
      toast.error('Erro ao buscar categorias')
    } finally {
      setLoading(false)
    }
  }, [])

  const fetchQuestions = useCallback(async (categoryId: string) => {
    try {
      setLoading(true)
      const data = await faqService.getQuestions(categoryId)
      setQuestions(data as FaqItem[])
    } catch (error) {
      toast.error('Erro ao buscar perguntas')
    } finally {
      setLoading(false)
    }
  }, [])

  // Sync with Bot
  const handleSyncWithBot = async () => {
    try {
      setSyncing(true)
      await faqService.syncFaqWithBot()
      toast.success('Bot sincronizado com sucesso!')
    } catch (error: any) {
      toast.error(error.message || 'Falha na sincronização')
    } finally {
      setSyncing(false)
    }
  }

  // CRUD Actions
  const handleCreate = async () => {
    if (!newQuestion || !newAnswer || !selectedGroup || !selectedCategory) {
      toast.warning('Preencha todos os campos e selecione uma categoria')
      return
    }

    try {
      await faqService.createQuestion({
        question: newQuestion,
        id_faqs_group: selectedGroup,
        id_category: selectedCategory,
        answer: newAnswer,
        state: 1,
        language: 'pt-BR',
      })
      toast.success('Pergunta criada!')
      setNewQuestion('')
      setNewAnswer('')
      setShowAddForm(false)
      fetchQuestions(selectedCategory)
    } catch (error) {
      toast.error('Erro ao criar pergunta')
    }
  }

  const handleDelete = async (id: string) => {
    if (!confirm('Tem certeza que deseja excluir esta pergunta?')) return
    try {
      await faqService.deleteQuestion(id)
      toast.success('Pergunta excluída')
      if (selectedCategory) fetchQuestions(selectedCategory)
    } catch (error) {
      toast.error('Erro ao excluir')
    }
  }

  const handleUpdate = async () => {
    if (!editingId) return
    try {
      await faqService.updateQuestion(editingId, {
        question: editQuestion,
        answer: editAnswer,
      })
      toast.success('Pergunta atualizada')
      setEditingId(null)
      if (selectedCategory) fetchQuestions(selectedCategory)
    } catch (error) {
      toast.error('Erro ao atualizar')
    }
  }

  const startEdit = (q: FaqItem) => {
    setEditingId(q.id)
    setEditQuestion(q.question ?? q.attributes?.question ?? '')
    setEditAnswer(q.answer ?? q.attributes?.answer ?? '')
  }

  // Initial Load
  useEffect(() => {
    fetchGroups()
  }, [fetchGroups])

  // Filtering
  const filteredQuestions = questions.filter(q => {
    const text = (q.question || q.attributes?.question || '').toLowerCase()
    return text.includes(searchQuery.toLowerCase())
  })

  const getLabel = (item: FaqItem) => {
    return item.name || item.attributes?.name || item.title || item.attributes?.title || 'Sem nome'
  }

  return (
    <div className="flex flex-col h-full bg-background overflow-hidden p-6 md:p-10 space-y-8 max-w-[1600px] mx-auto">
      
      {/* Header Premium */}
      <header className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-4xl font-bold tracking-tight mb-2 text-foreground flex items-center gap-3">
            <Layers className="w-10 h-10 text-primary" />
            Knowledge Hub
          </h1>
          <p className="text-muted-foreground text-lg">
            Organize o conhecimento do repositório e alimente a inteligência do bot.
          </p>
        </div>
        
        <div className="flex items-center gap-3">
           <Button 
            onClick={handleSyncWithBot} 
            disabled={syncing}
            variant="default"
            size="lg"
            className="rounded-full px-6 shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
          >
            {syncing ? (
              <Sparkles className="w-5 h-5 animate-pulse mr-2" />
            ) : (
              <Sparkles className="w-5 h-5 mr-2" />
            )}
            {syncing ? 'Sincronizando...' : 'Alimentar Bot Context'}
          </Button>
        </div>
      </header>

      <Separator className="opacity-50" />

      <main className="flex-1 grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-10 overflow-hidden min-h-0">
        
        {/* Sidebar de Navegação */}
        <aside className="space-y-6 overflow-y-auto pr-4">
          
          <div className="space-y-2">
            <h2 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground ml-1">
              Grupos de FAQ
            </h2>
            <div className="space-y-1">
              {groups.map((g) => (
                <button
                  key={g.id}
                  role="tab"
                  aria-selected={selectedGroup === g.id}
                  onClick={() => {
                    setSelectedGroup(g.id)
                    fetchCategories(g.id)
                  }}
                  className={`w-full text-left p-3 rounded-xl transition-all flex items-center justify-between group ${
                    selectedGroup === g.id 
                      ? 'bg-primary text-primary-foreground shadow-md' 
                      : 'hover:bg-muted text-foreground'
                  }`}
                >
                  <span className="font-medium truncate">{getLabel(g)}</span>
                  {selectedGroup === g.id ? <ChevronDown className="w-4 h-4" /> : <ChevronRight className="w-4 h-4 text-muted-foreground group-hover:text-foreground" />}
                </button>
              ))}
            </div>
          </div>

          {selectedGroup && (
            <div className="space-y-2 animate-in slide-in-from-left-4 duration-300">
              <h2 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground ml-1">
                Categorias
              </h2>
              <div className="space-y-1 pl-2 border-l-2 ml-4 border-muted">
                {categories.map((c) => (
                  <button
                    key={c.id}
                    onClick={() => {
                      setSelectedCategory(c.id)
                      fetchQuestions(c.id)
                    }}
                    className={`w-full text-left p-2 rounded-lg transition-all text-sm flex items-center gap-2 ${
                      selectedCategory === c.id 
                        ? 'text-primary font-bold bg-primary/5' 
                        : 'text-muted-foreground hover:text-foreground'
                    }`}
                  >
                    <BookOpen className={`w-4 h-4 ${selectedCategory === c.id ? 'opacity-100' : 'opacity-40'}`} />
                    <span className="truncate">{getLabel(c)}</span>
                  </button>
                ))}
              </div>
            </div>
          )}
        </aside>

        {/* Área Principal de Conteúdo */}
        <section className="bg-card/50 backdrop-blur-sm border rounded-3xl overflow-hidden flex flex-col shadow-xl">
          
          {/* Toolbar da Lista */}
          <div className="p-6 border-b flex flex-col md:flex-row md:items-center justify-between gap-4 bg-muted/20">
            <div className="relative flex-1 max-w-md">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <Input 
                placeholder="Pesquisar nas perguntas..." 
                className="pl-10 h-10 bg-background/50 border-muted-foreground/20 rounded-full"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
            
            <Button 
              onClick={() => setShowAddForm(!showAddForm)}
              variant={showAddForm ? "ghost" : "outline"}
              className="rounded-full px-5"
              disabled={!selectedCategory}
            >
              <Plus className={`w-4 h-4 mr-2 ${showAddForm ? 'rotate-45' : ''} transition-transform`} />
              Nova Pergunta
            </Button>
          </div>

          <div className="flex-1 overflow-y-auto p-6 space-y-6">
            
            {!selectedCategory ? (
              <div className="h-full flex flex-col items-center justify-center text-center p-10 opacity-60">
                <div className="w-20 h-20 rounded-full bg-muted flex items-center justify-center mb-6">
                  <Layers className="w-10 h-10 text-muted-foreground" />
                </div>
                <h4 className="text-xl font-semibold mb-2">Selecione uma Categoria</h4>
                <p className="text-muted-foreground max-w-xs">
                  Escolha um grupo e uma categoria na barra lateral para gerenciar as perguntas.
                </p>
              </div>
            ) : loading ? (
               <div className="space-y-4">
                {[1, 2, 3].map(i => (
                  <div key={i} className="h-24 bg-muted animate-pulse rounded-2xl" />
                ))}
              </div>
            ) : filteredQuestions.length === 0 && !showAddForm ? (
              <div className="h-full flex flex-col items-center justify-center text-center p-10 opacity-60">
                <Info className="w-12 h-12 text-muted-foreground mb-4" />
                <h4 className="text-lg font-medium">Nenhuma pergunta encontrada</h4>
                <p className="text-sm">Clique em 'Nova Pergunta' para começar.</p>
              </div>
            ) : (
              <div className="grid gap-6">
                
                {showAddForm && (
                  <Card className="border-primary/20 bg-primary/[0.02] shadow-inner animate-in fade-in zoom-in-95 duration-300 rounded-2xl">
                    <CardHeader className="pb-3">
                      <CardTitle className="text-lg flex items-center gap-2">
                        <Plus className="w-5 h-5 text-primary" /> Adicionar ao Repositório
                      </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="grid gap-3">
                        <Input 
                          placeholder="Digite a pergunta clara e objetiva" 
                          value={newQuestion}
                          className="bg-background rounded-xl h-12"
                          onChange={(e) => setNewQuestion(e.target.value)}
                        />
                        <textarea 
                          placeholder="Digite a resposta que o bot deve usar como base" 
                          className="w-full min-h-[120px] p-4 rounded-xl bg-background border border-input focus:ring-2 focus:ring-primary focus:outline-none transition-all"
                          value={newAnswer}
                          onChange={(e) => setNewAnswer(e.target.value)}
                        />
                      </div>
                      <div className="flex gap-2 justify-end">
                        <Button variant="ghost" className="rounded-full px-6" onClick={() => setShowAddForm(false)}>Cancelar</Button>
                        <Button className="rounded-full px-8" onClick={handleCreate}>Salvar Conhecimento</Button>
                      </div>
                    </CardContent>
                  </Card>
                )}

                {filteredQuestions.map((q) => {
                  const isEditing = editingId === q.id
                  const questionText = q.question || q.attributes?.question
                  const answerText = q.answer || q.attributes?.answer

                  return (
                    <Card key={q.id} className="group overflow-hidden hover:shadow-lg transition-all rounded-2xl border-muted/60">
                      {isEditing ? (
                        <CardContent className="p-6 space-y-4 bg-muted/5 animate-in fade-in duration-300">
                          <Input 
                            value={editQuestion}
                            className="bg-background rounded-xl h-12 font-semibold"
                            onChange={(e) => setEditQuestion(e.target.value)}
                          />
                          <textarea 
                            className="w-full min-h-[120px] p-4 rounded-xl bg-background border border-input focus:ring-2 focus:ring-primary focus:outline-none transition-all"
                            value={editAnswer}
                            onChange={(e) => setEditAnswer(e.target.value)}
                          />
                          <div className="flex gap-2 justify-end">
                            <Button variant="ghost" size="sm" className="rounded-full" onClick={() => setEditingId(null)}>
                              <X className="w-4 h-4 mr-2" /> Cancelar
                            </Button>
                            <Button size="sm" className="rounded-full px-6" onClick={handleUpdate}>
                              <Save className="w-4 h-4 mr-2" /> Salvar Alterações
                            </Button>
                          </div>
                        </CardContent>
                      ) : (
                        <CardHeader className="p-6 relative">
                          <div className="flex flex-col md:flex-row justify-between gap-4">
                            <div className="space-y-3 flex-1">
                              <Badge variant="outline" className="bg-muted/30 text-muted-foreground border-none px-3 font-normal">
                                FAQ #{q.id}
                              </Badge>
                              <CardTitle className="text-xl font-bold text-foreground leading-snug">
                                {questionText}
                              </CardTitle>
                              <CardDescription className="text-base text-muted-foreground whitespace-pre-wrap leading-relaxed">
                                {answerText}
                              </CardDescription>
                            </div>
                            
                            <div className="flex md:flex-col gap-2 shrink-0 md:opacity-0 group-hover:opacity-100 transition-opacity">
                              <Button 
                                variant="outline" 
                                size="icon" 
                                aria-label="Editar Pergunta"
                                className="rounded-xl hover:text-primary hover:border-primary/50" 
                                onClick={() => startEdit(q)}
                              >
                                <Edit2 className="w-4 h-4" />
                              </Button>
                              <Button 
                                variant="outline" 
                                size="icon" 
                                aria-label="Excluir Pergunta"
                                className="rounded-xl hover:text-destructive hover:border-destructive/50" 
                                onClick={() => handleDelete(q.id)}
                              >
                                <Trash2 className="w-4 h-4" />
                              </Button>
                            </div>
                          </div>
                        </CardHeader>
                      )}
                    </Card>
                  )
                })}
              </div>
            )}
          </div>
          
          <div className="p-4 bg-primary/[0.02] border-t flex items-center justify-center gap-2 text-xs text-muted-foreground">
            <Database className="w-3 h-3" />
            Fonte: No Boss FAQ API (Sincronizado via {process.env.NEXT_PUBLIC_API_URL || '/api'})
          </div>
        </section>
      </main>
    </div>
  )
}