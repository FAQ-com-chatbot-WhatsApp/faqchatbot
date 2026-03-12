import { fetchApi } from "@/lib/api"

export interface Conversation {
  id: number
  lead_id: number
  status: "ACTIVE" | "RESOLVED" | "CLOSED"
  started_at: string
  ended_at?: string
  last_message_at?: string
  assigned_to?: number
  lead: {
    id: number
    phone_number: string
    name?: string
    email?: string
  }
}

export interface ConversationMessage {
  id: number
  conversation_id: number
  sender_type: "LEAD" | "BOT" | "AGENT"
  content: string
  created_at: string
  metadata?: Record<string, any>
}

export interface ConversationListResponse {
  items: Conversation[]
  total: number
  page: number
  size: number
  pages: number
}

export interface ConversationMessagesResponse {
  items: ConversationMessage[]
  total: number
}

export async function getConversations(params?: {
  page?: number
  size?: number
  status?: string
  search?: string
}): Promise<ConversationListResponse> {
  const queryParams = new URLSearchParams()
  
  if (params?.page) queryParams.append("page", params.page.toString())
  if (params?.size) queryParams.append("size", params.size.toString())
  if (params?.status) queryParams.append("status", params.status)
  if (params?.search) queryParams.append("search", params.search)

  const url = `/api/v1/conversations${queryParams.toString() ? `?${queryParams.toString()}` : ""}`
  
  return fetchApi<ConversationListResponse>(url, {
    method: "GET",
  })
}

export async function getConversation(id: number): Promise<Conversation> {
  return fetchApi<Conversation>(`/api/v1/conversations/${id}`, {
    method: "GET",
  })
}

export async function getConversationMessages(id: number): Promise<ConversationMessagesResponse> {
  return fetchApi<ConversationMessagesResponse>(`/api/v1/conversations/${id}/messages`, {
    method: "GET",
  })
}

export async function updateConversationStatus(
  id: number,
  status: "ACTIVE" | "RESOLVED" | "CLOSED"
): Promise<Conversation> {
  return fetchApi<Conversation>(`/api/v1/conversations/${id}/status`, {
    method: "PUT",
    body: JSON.stringify({ status }),
  })
}
