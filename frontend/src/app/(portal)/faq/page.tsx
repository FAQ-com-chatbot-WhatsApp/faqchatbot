'use client'

import { useState, useEffect, useCallback } from 'react'
import { PageHeader } from '@/components/ui/page-header'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'

import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

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
  const [groups, setGroups] = useState<FaqItem[]>([])
  const [categories, setCategories] = useState<FaqItem[]>([])
  const [questions, setQuestions] = useState<FaqItem[]>([])

  const [selectedGroup, setSelectedGroup] = useState('')
  const [selectedCategory, setSelectedCategory] = useState('')

  const [newQuestion, setNewQuestion] = useState('')
  const [newAnswer, setNewAnswer] = useState('')

  const [editingId, setEditingId] = useState<string | null>(null)
  const [editQuestion, setEditQuestion] = useState('')
  const [editAnswer, setEditAnswer] = useState('')

  const TOKEN = `Bearer c2hhMjU2OjI6ZTkwMjY4ODFhMmYzNzA1NDMyMWE5YWYzY2NlOWY4NTM1MWZkYTIzZDJmNDJkOGU0ZGZmM2E1ODllMmZiMTNlMg`
  const BASE_URL = "/faq-api"

  const safeList = (data: unknown): FaqItem[] => {
    if (Array.isArray(data)) return data as FaqItem[]
    const d = data as Record<string, unknown>
    if (Array.isArray(d?.data)) return d.data as FaqItem[]
    const nested = d?.data as Record<string, unknown>
    if (Array.isArray(nested?.items)) return nested.items as FaqItem[]
    return []
  }

  const fetchGroups = useCallback(async () => {
    const res = await fetch(
      `${BASE_URL}/v1/nobossfaq/groups?state=1&language=pt-BR`,
      {
        headers: {
          Authorization: TOKEN,
          Accept: "application/json, application/vnd.api+json",
        },
      }
    )

    const data = await res.json()
    setGroups(safeList(data))
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const fetchCategories = async (groupId: string) => {
    const res = await fetch(
      `${BASE_URL}/v1/nobossfaq/categories?group_id=${groupId}&language=pt-BR`,
      {
        headers: {
          Authorization: TOKEN,
          Accept: "application/json, application/vnd.api+json",
        },
      }
    )

    const data = await res.json()
    setCategories(safeList(data))
  }

  const fetchQuestions = async (categoryId: string) => {
    const res = await fetch(
      `${BASE_URL}/v1/nobossfaq/questions?category_id=${categoryId}&language=pt-BR`,
      {
        headers: {
          Authorization: TOKEN,
          Accept: "application/json, application/vnd.api+json",
        },
      }
    )

    const data = await res.json()
    setQuestions(safeList(data))
  }

  const createQuestion = async () => {
    if (!newQuestion || !newAnswer || !selectedGroup || !selectedCategory) {
      alert('Preencha tudo!')
      return
    }

    await fetch(`${BASE_URL}/v1/nobossfaq/questions`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: TOKEN,
        Accept: "application/json, application/vnd.api+json",
      },
      body: JSON.stringify({
        question: newQuestion,
        id_faqs_group: selectedGroup,
        id_category: selectedCategory,
        answer: newAnswer,
        state: 1,
        language: 'pt-BR',
      }),
    })

    setNewQuestion('')
    setNewAnswer('')
    fetchQuestions(selectedCategory)
  }

  const deleteQuestion = async (id: string) => {
    await fetch(`${BASE_URL}/v1/nobossfaq/questions/${id}`, {
      method: 'DELETE',
      headers: {
        Authorization: TOKEN,
        Accept: "application/json, application/vnd.api+json",
      },
    })

    fetchQuestions(selectedCategory)
  }

  const startEdit = (q: FaqItem) => {
    setEditingId(q.id)
    setEditQuestion(q.question ?? q.attributes?.question ?? '')
    setEditAnswer(q.answer ?? q.attributes?.answer ?? '')
  }

  const updateQuestion = async () => {
    if (!editingId) return

    await fetch(`${BASE_URL}/v1/nobossfaq/questions/${editingId}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        Authorization: TOKEN,
        Accept: "application/json, application/vnd.api+json",
      },
      body: JSON.stringify({
        question: editQuestion,
        answer: editAnswer,
      }),
    })

    setEditingId(null)
    setEditQuestion('')
    setEditAnswer('')

    fetchQuestions(selectedCategory)
  }

  useEffect(() => {
    fetchGroups()
  }, [fetchGroups])

  return (
    <div className="p-6 max-w-7xl">
      <PageHeader title="Repositório" />

      <Tabs defaultValue="groups">
        <TabsList>
          <TabsTrigger value="groups">Grupos</TabsTrigger>
          <TabsTrigger value="categories">Categorias</TabsTrigger>
          <TabsTrigger value="questions">Perguntas</TabsTrigger>
        </TabsList>

        <TabsContent value="groups">
          <Card>
            <CardHeader>
              <CardTitle>Grupos</CardTitle>
            </CardHeader>
            <CardContent>
              {groups.map((g) => (
                <div
                  key={g.id}
                  className="p-2 border rounded mb-2 cursor-pointer"
                  onClick={() => {
                    setSelectedGroup(g.id)
                    fetchCategories(g.id)
                  }}
                >
                  {g.name || g.attributes?.name || 'Sem nome'}
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="categories">
          <Card>
            <CardHeader>
              <CardTitle>Categorias</CardTitle>
            </CardHeader>
            <CardContent>
              {categories.map((c) => (
                <div
                  key={c.id}
                  className="p-2 border rounded mb-2 cursor-pointer"
                  onClick={() => {
                    setSelectedCategory(c.id)
                    fetchQuestions(c.id)
                  }}
                >
                  {c.name || c.attributes?.name || c.title || c.attributes?.title || 'Sem nome'}
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="questions">
          <Card>
            <CardHeader>
              <CardTitle>Perguntas</CardTitle>
            </CardHeader>
            <CardContent>

              <div className="mb-4 space-y-2">
                <select
                  className="w-full border p-2 rounded"
                  value={selectedGroup}
                  onChange={(e) => {
                    setSelectedGroup(e.target.value)
                    fetchCategories(e.target.value)
                  }}
                >
                  <option value="">Selecione um grupo</option>
                  {groups.map((g) => (
                    <option key={g.id} value={g.id}>
                      {g.name || g.attributes?.name}
                    </option>
                  ))}
                </select>

                <select
                  className="w-full border p-2 rounded"
                  value={selectedCategory}
                  onChange={(e) => {
                    setSelectedCategory(e.target.value)
                    fetchQuestions(e.target.value)
                  }}
                >
                  <option value="">Selecione uma categoria</option>
                  {categories.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name || c.attributes?.name || c.title || c.attributes?.title || 'Sem nome'}
                    </option>
                  ))}
                </select>

                <Input
                  placeholder="Pergunta"
                  value={newQuestion}
                  onChange={(e) => setNewQuestion(e.target.value)}
                />

                <Input
                  placeholder="Resposta"
                  value={newAnswer}
                  onChange={(e) => setNewAnswer(e.target.value)}
                />

                <Button onClick={createQuestion}>
                  Criar Pergunta
                </Button>
              </div>

              {questions.map((q) => {
                const isEditing = editingId === q.id

                return (
                  <div key={q.id} className="p-3 border rounded mb-2">

                    {isEditing ? (
                      <>
                        <Input
                          value={editQuestion}
                          onChange={(e) => setEditQuestion(e.target.value)}
                        />

                        <Input
                          value={editAnswer}
                          onChange={(e) => setEditAnswer(e.target.value)}
                        />

                        <div className="flex gap-2 mt-2">
                          <Button onClick={updateQuestion}>
                            Salvar
                          </Button>

                          <Button
                            variant="outline"
                            onClick={() => setEditingId(null)}
                          >
                            Cancelar
                          </Button>
                        </div>
                      </>
                    ) : (
                      <>
                        <p className="font-semibold">
                          {q.question || q.attributes?.question}
                        </p>

                        <p className="text-sm text-muted-foreground">
                          {q.answer || q.attributes?.answer}
                        </p>

                        <div className="flex gap-2 mt-2">
                          <Button onClick={() => startEdit(q)}>
                            Editar
                          </Button>

                          <Button
                            variant="destructive"
                            onClick={() => deleteQuestion(q.id)}
                          >
                            Deletar
                          </Button>
                        </div>
                      </>
                    )}

                  </div>
                )
              })}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}