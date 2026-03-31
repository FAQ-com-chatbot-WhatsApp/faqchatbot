import { fetchApi } from "@/lib/api"

export interface Notification {
  id: string
  user_id: number
  type: string
  title: string
  message: string
  read: boolean
  created_at: string
}

export async function getNotifications(params?: {
  unread_only?: boolean
  limit?: number
}): Promise<Notification[]> {
  const queryParams = new URLSearchParams()
  if (params?.unread_only) queryParams.append("unread_only", "true")
  if (params?.limit) queryParams.append("limit", params.limit.toString())

  const url = `/api/v1/notifications${queryParams.toString() ? `?${queryParams.toString()}` : ""}`
  
  return fetchApi<Notification[]>(url, {
    method: "GET",
  })
}

export async function getUnreadCount(): Promise<{ count: number }> {
  return fetchApi<{ count: number }>(`/api/v1/notifications/unread-count`, {
    method: "GET",
  })
}

export async function markNotificationAsRead(id: string): Promise<Notification> {
  return fetchApi<Notification>(`/api/v1/notifications/${id}/read`, {
    method: "PUT",
  })
}
