import { fetchApi } from "@/lib/api"
import type { User, UserUpdate } from "@/types/user"

const USER_BASE = "/api/v1/users"

export async function getCurrentUser(): Promise<User> {
  return fetchApi<User>(`${USER_BASE}/me`, {
    method: "GET",
  })
}

export async function updateCurrentUser(data: UserUpdate): Promise<User> {
  return fetchApi<User>(`${USER_BASE}/me`, {
    method: "PATCH",
    body: JSON.stringify(data),
  })
}
