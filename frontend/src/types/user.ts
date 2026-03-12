export interface User {
  id: number
  email: string
  full_name: string | null
  role: string
  is_active: boolean
}

export interface UserUpdate {
  full_name?: string | null
}

export interface UserListResponse {
  users: User[]
  total: number
  skip: number
  limit: number
}
