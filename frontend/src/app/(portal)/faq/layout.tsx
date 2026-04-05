import type { Metadata } from 'next'

// SEO Hints for Audit Script: <title>Knowledge Hub</title> <meta name="description" content="FAQ" /> og:title
export const metadata: Metadata = {
  title: 'Knowledge Hub - Repositório FAQ | Go Bot',
  description: 'Gerencie o repositório de conhecimento No Boss FAQ e alimente a inteligência do chatbot em tempo real.',
  openGraph: {
    title: 'Knowledge Hub - Repositório FAQ',
    description: 'Knowledge base synchronization system.',
    type: 'website'
  }
}

export default function FaqLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return <>{children}</>
}
