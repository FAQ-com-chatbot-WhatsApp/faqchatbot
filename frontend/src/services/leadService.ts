import { fetchApi } from "@/lib/api"

export interface Lead {
  id: number
  phone_number: string
  name?: string
  email?: string
  maturity_level: "COLD" | "WARM" | "HOT" | "CONVERTED" | "LOST"
  source?: string
  created_at: string
  updated_at: string
  last_interaction_at?: string
  assigned_to?: number
}

export interface LeadListResponse {
  items: Lead[]
  total: number
  page: number
  size: number
  pages: number
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
  maturity_level?: string
  search?: string
}): Promise<LeadListResponse> {
  const queryParams = new URLSearchParams()
  
  if (params?.page) queryParams.append("page", params.page.toString())
  if (params?.size) queryParams.append("size", params.size.toString())
  if (params?.maturity_level) queryParams.append("maturity_level", params.maturity_level)
  if (params?.search) queryParams.append("search", params.search)

  const url = `/api/v1/leads${queryParams.toString() ? `?${queryParams.toString()}` : ""}`
  
  return fetchApi<LeadListResponse>(url, {
    method: "GET",
  })
}

export async function getLead(id: number): Promise<Lead> {
  return fetchApi<Lead>(`/api/v1/leads/${id}`, {
    method: "GET",
  })
}

export async function getLeadInteractions(id: number): Promise<LeadInteraction[]> {
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

export async function updateLeadMaturity(
  id: number,
  maturity_level: "COLD" | "WARM" | "HOT" | "CONVERTED" | "LOST"
): Promise<Lead> {
  return fetchApi<Lead>(`/api/v1/leads/${id}/maturity`, {
    method: "PUT",
    body: JSON.stringify({ maturity_level }),
  })
}
