'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'

export function useAuthRedirect() {
  const router = useRouter()
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const hasToken = typeof window !== 'undefined' && !!localStorage.getItem('access_token')
    
    if (!hasToken) {
      router.replace('/signin')
    }
    
    setLoading(false)
  }, [])

  return { loading }
}
