import type { Metadata } from 'next'
import { Geist, Lora } from 'next/font/google'
import './globals.css'

const geist = Geist({
  variable: '--font-geist',
  subsets: ['latin'],
})

const lora = Lora({
  variable: '--font-lora',
  subsets: ['latin'],
})

export const metadata: Metadata = {
  title: 'Clínica Go - Design System',
  description: 'Design system for Clínica Go application',
}

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  return (
    <html lang="en">
      <body
        className={`${geist.variable} ${lora.variable} antialiased font-sans`}
        suppressHydrationWarning
      >
        {children}
      </body>
    </html>
  )
}
