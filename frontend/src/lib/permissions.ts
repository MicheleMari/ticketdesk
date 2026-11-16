import type { Role, Ticket, User } from '../types'

export const canManageCategories = (user?: User | null) => user?.role === 'admin'
export const canEditTicket = (user: User | null | undefined, ticket: Ticket) => {
  if (!user || ticket.status === 'closed') return false
  if (user.role === 'admin') return true
  if (user.role === 'supervisor') return ticket.assignee?.id === undefined || ticket.assignee?.id === user.id
  return user.role === 'requester'
}
export const canDeleteTicket = (user?: User | null) => user?.role === 'admin'
export const canAssignTicket = (user: User | null | undefined, ticket: Ticket) => Boolean(user && ticket.status !== 'closed' && (user.role === 'admin' || (user.role === 'supervisor' && !ticket.assignee)))
export const canChangeStatus = (user: User | null | undefined, ticket: Ticket) => canEditTicket(user, ticket) || (ticket.status === 'closed' && user?.role === 'admin')
export const roleLabel = (role?: Role) => ({ admin: 'Amministratore', supervisor: 'Supervisore', requester: 'Richiedente' }[role ?? 'requester'])
