export type Role = 'admin' | 'supervisor' | 'requester'
export type TicketStatus = 'open' | 'in_progress' | 'closed'
export type TicketPriority = 'low' | 'medium' | 'high'

export interface User {
  id: number
  name: string
  email?: string
  role: Role
  company_id: number
}

export interface CompanyUser {
  id: number
  name: string
  role: Role
}

export interface Category {
  id: number
  name: string
  color: string
}

export interface Ticket {
  id: number
  title: string
  description: string
  status: TicketStatus
  priority: TicketPriority
  due_date: string | null
  closed_at: string | null
  created_at: string | null
  updated_at: string | null
  category?: Category | null
  assignee?: { id: number; name: string } | null
}

export interface Comment {
  id: number
  body: string
  user: { id: number; name: string }
  created_at: string
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

export interface ApiErrorShape {
  message?: string
  errors?: Record<string, string[]>
}
