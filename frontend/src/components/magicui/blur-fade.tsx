import type { ReactNode } from 'react'
import { cn } from '../../lib/utils'

/** Magic UI-style entrance motion, kept motion-safe for reduced-motion users. */
export function BlurFade({ children, className = '' }: { children: ReactNode; className?: string }) {
  return <div className={cn('animate-fade-in', className)}>{children}</div>
}
