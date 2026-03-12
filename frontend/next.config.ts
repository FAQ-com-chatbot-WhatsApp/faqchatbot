import type { NextConfig } from 'next'

const nextConfig: NextConfig = {
  output: 'standalone',
  
  // Proxy API requests to backend
  // BACKEND_URL is set in docker-compose (http://go:3333 in Docker)
  // Falls back to localhost:3333 for local development
  async rewrites() {
    const backendUrl = process.env.BACKEND_URL || 'http://localhost:3333'
    
    return [
      {
        source: '/api/:path*',
        destination: `${backendUrl}/api/:path*`,
      },
    ]
  },
}

export default nextConfig
