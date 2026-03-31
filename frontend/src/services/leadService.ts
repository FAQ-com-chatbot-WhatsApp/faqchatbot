import { fetchApi } from "@/lib/api"

export interface Lead {
  id: string
  phone_number: string
  name?: string | null
  email?: string | null
  status: string
  maturity_score: number
  assigned_to?: number | null
  created_at: string
  updated_at: string
}

export interface LeadListResponse {
  leads: Lead[]
  total: number
}

export interface LeadInteraction {
  id: number
  lead_id: number
  interaction_type: string
  description?: string
  created_at: string
  user_id?: number
}

export async function getLeads(params?: {
  page?: number
  size?: number
  search?: string
}): Promise<LeadListResponse> {
  const queryParams = new URLSearchParams()
  
  // Backend usa limit/offset, não page/size
  const limit = params?.size || 50
  const offset = params?.page ? (params.page - 1) * limit : 0
  
  queryParams.append("limit", limit.toString())
  queryParams.append("offset", offset.toString())
  if (params?.search) queryParams.append("phone_number", params.search)

  const url = `/api/v1/leads${queryParams.toString() ? `?${queryParams.toString()}` : ""}`
  
  return fetchApi<LeadListResponse>(url, {
    method: "GET",
  })
}

export async function getLead(id: string): Promise<Lead> {
  return fetchApi<Lead>(`/api/v1/leads/${id}`, {
    method: "GET",
  })
}

export async function getLeadInteractions(id: string): Promise<LeadInteraction[]> {
  return fetchApi<LeadInteraction[]>(`/api/v1/leads/${id}/interactions`, {
    method: "GET",
  })
}

export async function createLead(data: {
  phone_number: string
  name?: string
  email?: string
  source?: string
}): Promise<Lead> {
  return fetchApi<Lead>(`/api/v1/leads`, {
    method: "POST",
    body: JSON.stringify(data),
  })
}

export async function updateLead(
  id: string,
  data: { name?: string | null; email?: string | null }
): Promise<Lead> {
  return fetchApi<Lead>(`/api/v1/leads/${id}`, {
    method: "PATCH",
    body: JSON.stringify(data),
  })
}

export async function updateLeadMaturity(
  id: string,
  maturity_score: number
): Promise<Lead> {
  return fetchApi<Lead>(`/api/v1/leads/${id}/maturity`, {
    method: "PUT",
    body: JSON.stringify({ maturity_score }),
  })
}
