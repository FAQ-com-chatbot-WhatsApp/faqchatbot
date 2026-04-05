const BASE_URL = '/faq-api'

const TOKEN = 'Bearer c2hhMjU2OjI6ZTkwMjY4ODFhMmYzNzA1NDMyMWE5YWYzY2NlOWY4NTM1MWZkYTIzZDJmNDJkOGU0ZGZmM2E1ODllMmZiMTNlMg'

const getHeaders = () => ({
  Accept: 'application/json, application/vnd.api+json',
  Authorization: TOKEN,
})

const normalize = (data: unknown): unknown[] => {
  if (!data) return []
  const d = data as { data?: unknown[] | { items?: unknown[] } }
  if (!d?.data) return []

  return Array.isArray(d.data)
    ? d.data
    : (d.data as { items?: unknown[] }).items || []
}

//  GRUPOS
export async function getGroups() {
  const res = await fetch(
    `${BASE_URL}/v1/nobossfaq/groups?state=1&language=pt-BR`,
    { headers: getHeaders() }
  )

  const data = await res.json()

  if (!res.ok) throw new Error('Erro ao buscar grupos')

  return normalize(data)
}

//  CATEGORIAS
export async function getCategories(groupId: number | string) {
  const res = await fetch(
    `${BASE_URL}/v1/nobossfaq/categories?group_id=${groupId}&language=pt-BR`,
    { headers: getHeaders() }
  )

  const data = await res.json()
  console.log('CATEGORIES RAW:', data)

  if (!res.ok) throw new Error('Erro ao buscar categorias')

  return normalize(data)
}

//  PERGUNTAS
export async function getQuestions(categoryId: number | string) {
  const res = await fetch(
    `${BASE_URL}/v1/nobossfaq/questions?category_id=${categoryId}&language=pt-BR`,
    { headers: getHeaders() }
  )

  const data = await res.json()
  console.log('QUESTIONS RAW:', data)

  if (!res.ok) throw new Error('Erro ao buscar perguntas')

  return normalize(data)
}

//  CRIAR
export async function createQuestion(data: Record<string, unknown>) {
  const res = await fetch(`${BASE_URL}/v1/nobossfaq/questions`, {
    method: 'POST',
    headers: {
      ...getHeaders(),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data),
  })

  const result = await res.json()

  if (!res.ok) throw new Error('Erro ao criar pergunta')

  return result
}

export async function updateQuestion(id: string, data: Record<string, unknown>) {
  const res = await fetch(
    `${BASE_URL}/v1/nobossfaq/questions/${id}`,
    {
      method: 'PATCH',
      headers: {
        ...getHeaders(),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }
  )

  const result = await res.json()

  if (!res.ok) throw new Error('Erro ao atualizar pergunta')

  return result
}

// DELETAR
export async function deleteQuestion(id: string) {
  const res = await fetch(
    `${BASE_URL}/v1/nobossfaq/questions/${id}`,
    {
      method: 'DELETE',
      headers: getHeaders(),
    }
  )

  if (!res.ok) throw new Error('Erro ao deletar pergunta')

  return true
}

// SINCRONIZAR COM BOT
export async function syncFaqWithBot() {
  // Passamos as credenciais que estamos usando no front para o back realizar a ponte
  const res = await fetch('/api/faq/sync', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json' 
    },
    body: JSON.stringify({
      base_url: BASE_URL,
      token: TOKEN
    }),
  })

  if (!res.ok) {
    const errorData = await res.json().catch(() => ({ detail: 'Erro ao sincronizar' }))
    throw new Error(errorData.detail || 'Erro ao sincronizar com bot')
  }
  
  return res.json()
}