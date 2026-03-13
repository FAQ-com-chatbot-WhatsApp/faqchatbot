import { fetchApi } from "@/lib/api"

export interface Conversation {
  id: string
  chat_id: string
  phone_number: string
  status: string
  lead_status: string
  lead_name?: string | null
  is_urgent: boolean
  lead_id: string | null
  assigned_to_user_id: number | null
  last_message?: string | null
  last_message_at?: string | null
  unread_count?: number
  created_at: string
  updated_at: string
}

export interface ConversationMessage {
  id: number
  direction: string
  from_phone: string
  to_phone: string
  body: string
  media_url?: string | null
  created_at: string
}

export interface ConversationListResponse {
  conversations: Conversation[]
  total: number
}

export async function getConversations(params?: {
  page?: number
  size?: number
  status?: string
  search?: string
}): Promise<ConversationListResponse> {
  const queryParams = new URLSearchParams()
  
  // Backend usa limit/offset, não page/size
  const limit = params?.size || 50
  const offset = params?.page ? (params.page - 1) * limit : 0
  
  queryParams.append("limit", limit.toString())
  queryParams.append("offset", offset.toString())
  if (params?.status) queryParams.append("status", params.status)
  if (params?.search) queryParams.append("phone_number", params.search)

  const url = `/api/v1/conversations${queryParams.toString() ? `?${queryParams.toString()}` : ""}`
  
  const response = await fetchApi<ConversationListResponse | Conversation[]>(url, {
    method: "GET",
  })
  
  // Backend retorna array direto quando phone_number não está presente
  // e objeto {conversations, total} quando phone_number está presente
  if (Array.isArray(response)) {
    return {
      conversations: response,
      total: response.length
    };
  }
  
  return response;
}

export async function getConversation(id: string): Promise<Conversation> {
  return fetchApi<Conversation>(`/api/v1/conversations/${id}`, {
    method: "GET",
  })
}

export async function getConversationMessages(id: string, limit: number = 50): Promise<ConversationMessage[]> {
  return fetchApi<ConversationMessage[]>(`/api/v1/conversations/${id}/messages?limit=${limit}`, {
    method: "GET",
  })
}

export async function updateConversationStatus(
  id: string,
  status: string
): Promise<Conversation> {
  return fetchApi<Conversation>(`/api/v1/conversations/${id}/status`, {
    method: "PUT",
    body: JSON.stringify({ status }),
  })
}

export async function markConversationAsRead(conversationId: string): Promise<void> {
  return fetchApi<void>(`/api/v1/conversations/${conversationId}/mark-read`, {
    method: "POST",
  })
}

export async function updateConversationStatus(
  conversationId: string,
  newStatus: "ACTIVE_BOT" | "ACTIVE_HUMAN"
): Promise<{ message: string; conversation_id: string; new_status: string }> {
  return fetchApi(`/api/v1/conversations/${conversationId}/status`, {
    method: "PUT",
    body: JSON.stringify({ new_status: newStatus }),
  })
}
