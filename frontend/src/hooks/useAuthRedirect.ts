'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { fetchApi } from '@/lib/api'

export function useAuthRedirect() {
  const router = useRouter()
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    // Check if user is authenticated by calling /users/me
    async function checkAuth() {
      try {
        await fetchApi('/api/v1/users/me')
        // User is authenticated
        setLoading(false)
      } catch (error: any) {
        // 401 means not authenticated - redirect will happen automatically
        // Other errors we just set loading false and let the page handle it
        setLoading(false)
      }
    }
    
    checkAuth()
  }, [router])

  return { loading }
}
