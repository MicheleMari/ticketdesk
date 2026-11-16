import type { ApiErrorShape, Category, Comment, CompanyUser, Paginated, Ticket, TicketPriority, TicketStatus, User } from '../types'

const API_URL = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/$/, '') || '/api'
export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>
  constructor(status: number, payload: ApiErrorShape) { super(payload.message ?? 'Si è verificato un errore.'); this.status = status; this.errors = payload.errors }
}

let onUnauthorized: (() => void) | undefined
export const setUnauthorizedHandler = (handler: () => void) => { onUnauthorized = handler }

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = sessionStorage.getItem('ticketdesk_token')
  const response = await fetch(`${API_URL}${path}`, { ...options, headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(token ? { Authorization: `Bearer ${token}` } : {}), ...options.headers } })
  if (response.status === 401) { onUnauthorized?.(); throw new ApiError(401, { message: 'Sessione scaduta.' }) }
  let payload: ApiErrorShape & T = {} as ApiErrorShape & T
  if (response.status !== 204) { try { payload = await response.json() } catch { throw new ApiError(response.status, { message: 'Risposta non valida dal server.' }) } }
  if (!response.ok) throw new ApiError(response.status, payload)
  return payload
}

export const api = {
  login: (body: { email: string; password: string }) => request<{ token: string; user: User }>('/login', { method: 'POST', body: JSON.stringify(body) }),
  me: () => request<{ data: User }>('/user'),
  logout: () => request<void>('/logout', { method: 'POST' }),
  tickets: (params: URLSearchParams) => request<Paginated<Ticket>>(`/tickets?${params.toString()}`),
  ticket: (id: string | number) => request<{ data: Ticket }>(`/tickets/${id}`),
  createTicket: (body: { title: string; description: string; category_id: number; priority: TicketPriority; due_date: string | null }) => request<{ data: Ticket }>('/tickets', { method: 'POST', body: JSON.stringify(body) }),
  updateTicket: (id: number, body: Partial<{ title: string; description: string; category_id: number; priority: TicketPriority; due_date: string | null }>) => request<{ data: Ticket }>(`/tickets/${id}`, { method: 'PATCH', body: JSON.stringify(body) }),
  deleteTicket: (id: number) => request<void>(`/tickets/${id}`, { method: 'DELETE' }),
  status: (id: number, status: TicketStatus) => request<{ data: Ticket }>(`/tickets/${id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) }),
  assign: (id: number, assignee_id: number | null) => request<{ data: Ticket }>(`/tickets/${id}/assign`, { method: 'PATCH', body: JSON.stringify({ assignee_id }) }),
  categories: () => request<{ data: Category[] }>('/categories'),
  createCategory: (body: { name: string; color: string }) => request<{ data: Category }>('/categories', { method: 'POST', body: JSON.stringify(body) }),
  updateCategory: (id: number, body: { name: string; color: string }) => request<{ data: Category }>(`/categories/${id}`, { method: 'PATCH', body: JSON.stringify(body) }),
  deleteCategory: (id: number) => request<void>(`/categories/${id}`, { method: 'DELETE' }),
  users: () => request<{ data: CompanyUser[] }>('/users'),
  comments: (id: string | number) => request<{ data: Comment[] }>(`/tickets/${id}/comments`),
  createComment: (id: number, body: string) => request<{ data: Comment }>(`/tickets/${id}/comments`, { method: 'POST', body: JSON.stringify({ body }) }),
}
