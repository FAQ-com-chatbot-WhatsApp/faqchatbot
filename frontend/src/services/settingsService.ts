import { fetchApi } from '@/lib/api'

export interface AISettings {
  google_api_key: string | null
  gemini_model: string | null
  gemini_max_tokens: number | null
  gemini_temperature: number | null
  
  groq_api_key: string | null
  groq_model: string | null
  groq_max_tokens: number | null
  groq_temperature: number | null
  
  llm_primary_provider: string | null
  llm_enable_fallback: boolean | null
}

export interface AIModel {
  id: string
  name: string
}

export interface AIModelList {
  models: AIModel[]
}

export async function getAISettings(): Promise<AISettings> {
  return fetchApi<AISettings>('/api/v1/settings/ai')
}

export async function updateAISettings(settings: Partial<AISettings>): Promise<AISettings> {
  return fetchApi<AISettings>('/api/v1/settings/ai', {
    method: 'PUT',
    body: JSON.stringify(settings),
  })
}

export async function getAIModels(provider: string): Promise<AIModelList> {
  return fetchApi<AIModelList>(`/api/v1/settings/ai/models/${provider}`)
}
