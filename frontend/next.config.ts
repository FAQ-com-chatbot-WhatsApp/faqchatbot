import type { NextConfig } from 'next'

const nextConfig: NextConfig = {
  output: 'standalone',
  async rewrites() {
    return [
      {
        source: '/waha/:path*',
        destination: 'http://waha:3000/api/:path*',
      },
    ]
  },
}

export default nextConfig
