import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'Dashboard - GO',
  description: 'Project management dashboard with stats, tasks, and messaging.',
}

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return children
}
