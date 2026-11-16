import type { Ticket, TicketPriority, TicketStatus } from '../types'

export const cn = (...classes: Array<string | false | null | undefined>) => classes.filter(Boolean).join(' ')

export const statusLabel: Record<TicketStatus, string> = { open: 'Aperto', in_progress: 'In lavorazione', closed: 'Chiuso' }
export const priorityLabel: Record<TicketPriority, string> = { low: 'Bassa', medium: 'Media', high: 'Alta' }

export const formatDate = (value?: string | null, withTime = false) => {
  if (!value) return '—'
  return new Intl.DateTimeFormat('it-IT', withTime ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' }).format(new Date(value))
}

export const initials = (name: string) => name.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase()
export const isOverdue = (ticket: Ticket) => Boolean(ticket.due_date && ticket.status !== 'closed' && new Date(ticket.due_date) < new Date())
