export const queryKeys = {
  tickets: (params: string) => ['tickets', params] as const,
  ticket: (id: string) => ['ticket', id] as const,
  categories: ['categories'] as const,
  users: ['users'] as const,
  comments: (id: string) => ['comments', id] as const,
}
