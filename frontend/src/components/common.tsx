import {
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  CircleAlert,
  Filter,
  Search,
  X,
} from "lucide-react";
import type { ReactNode } from "react";
import { useState } from "react";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogTrigger,
  AlertDialogTitle,
  Button,
  IconButton,
  Input,
  Select,
  Spinner,
} from "./ui";
import { cn, priorityLabel, statusLabel } from "../lib/utils";
import type { TicketPriority, TicketStatus } from "../types";

export function PageHeader({
  eyebrow,
  title,
  description,
  action,
}: {
  eyebrow?: string;
  title: string;
  description?: string;
  action?: ReactNode;
}) {
  return (
    <div className="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <div className="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-accent">
          {eyebrow ?? "TicketDesk"}
        </div>
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
          {title}
        </h1>
        {description && (
          <p className="mt-2 text-sm text-muted">{description}</p>
        )}
      </div>
      {action && <div className="shrink-0">{action}</div>}
    </div>
  );
}
export function StatusBadge({ status }: { status: TicketStatus }) {
  return (
    <BadgeDot
      tone={
        status === "open"
          ? "blue"
          : status === "in_progress"
            ? "amber"
            : "green"
      }
    >
      {statusLabel[status]}
    </BadgeDot>
  );
}
export function PriorityBadge({ priority }: { priority: TicketPriority }) {
  return (
    <BadgeDot
      tone={
        priority === "high"
          ? "red"
          : priority === "medium"
            ? "amber"
            : "neutral"
      }
    >
      {priorityLabel[priority]}
    </BadgeDot>
  );
}
function BadgeDot({
  children,
  tone,
}: {
  children: ReactNode;
  tone: "blue" | "amber" | "green" | "red" | "neutral";
}) {
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1.5 text-xs font-medium",
        {
          blue: "text-blue-700 dark:text-blue-300",
          amber: "text-amber-700 dark:text-amber-300",
          green: "text-emerald-700 dark:text-emerald-300",
          red: "text-red-700 dark:text-red-300",
          neutral: "text-muted",
        }[tone],
      )}
    >
      <span
        className={cn(
          "h-1.5 w-1.5 rounded-full",
          {
            blue: "bg-blue-500",
            amber: "bg-amber-500",
            green: "bg-emerald-500",
            red: "bg-red-500",
            neutral: "bg-slate-400",
          }[tone],
        )}
      />
      {children}
    </span>
  );
}
export function CategoryBadge({
  name,
  color,
}: {
  name?: string;
  color?: string;
}) {
  if (!name) return <span className="text-muted">—</span>;
  return (
    <span className="inline-flex items-center gap-2 text-xs font-medium">
      <span
        className="h-2 w-2 rounded-full"
        style={{ backgroundColor: color }}
      />
      {name}
    </span>
  );
}
export function SearchInput({
  value,
  onChange,
  onClear,
}: {
  value: string;
  onChange: (value: string) => void;
  onClear?: () => void;
}) {
  return (
    <div className="relative min-w-0 flex-1 sm:max-w-xs">
      <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
      <Input
        value={value}
        onChange={(event) => onChange(event.target.value)}
        placeholder="Cerca per titolo…"
        className="pl-9 pr-9"
        aria-label="Cerca ticket"
      />
      {value && (
        <IconButton
          onClick={onClear}
          className="absolute right-1 top-1/2 h-8 w-8 -translate-y-1/2"
          aria-label="Cancella ricerca"
        >
          <X className="h-4 w-4" />
        </IconButton>
      )}
    </div>
  );
}
export function Pagination({
  page,
  lastPage,
  onChange,
}: {
  page: number;
  lastPage: number;
  onChange: (page: number) => void;
}) {
  if (lastPage <= 1) return null;
  return (
    <div className="flex items-center justify-between border-t border-border px-4 py-3 text-sm text-muted">
      <span>
        Pagina {page} di {lastPage}
      </span>
      <div className="flex gap-2">
        <Button
          variant="secondary"
          className="h-8 px-2"
          disabled={page === 1}
          onClick={() => onChange(page - 1)}
          aria-label="Pagina precedente"
        >
          <ChevronLeft className="h-4 w-4" />
        </Button>
        <Button
          variant="secondary"
          className="h-8 px-2"
          disabled={page === lastPage}
          onClick={() => onChange(page + 1)}
          aria-label="Pagina successiva"
        >
          <ChevronRight className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}
export function LoadingBlock({ label = "Caricamento…" }: { label?: string }) {
  return (
    <div className="flex items-center gap-2 text-sm text-muted">
      <Spinner />
      {label}
    </div>
  );
}
export function FilterIcon() {
  return <Filter className="h-4 w-4" />;
}
export function SuccessIcon() {
  return <CheckCircle2 className="h-5 w-5 text-emerald-500" />;
}
export function AlertIcon() {
  return <CircleAlert className="h-5 w-5 text-red-500" />;
}
export function FilterSelect({
  value,
  onChange,
  children,
  label,
}: {
  value: string;
  onChange: (value: string) => void;
  children: ReactNode;
  label: string;
}) {
  const placeholder =
    {
      Stato: "Tutti gli stati",
      Priorità: "Tutte le priorità",
      Categoria: "Tutte le categorie",
      Assegnatario: "Tutti gli utenti",
    }[label] ?? label;
  return (
    <div className="min-w-0 flex-1 sm:min-w-[150px] sm:flex-none xl:min-w-0">
      <Select
        aria-label={label}
        value={value}
        placeholder={placeholder}
        onChange={(event) => onChange(event.target.value)}
        className="h-9 w-full"
      >
        {children}
      </Select>
    </div>
  );
}

export function ConfirmDialog({
  trigger,
  title,
  description,
  onConfirm,
  open,
  onOpenChange,
}: {
  trigger?: ReactNode;
  title: string;
  description: string;
  onConfirm: () => void;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
}) {
  const [step, setStep] = useState<1 | 2>(1);
  return (
    <AlertDialog
      open={open}
      onOpenChange={(nextOpen) => {
        if (!nextOpen) setStep(1);
        onOpenChange?.(nextOpen);
      }}
    >
      {trigger && (
        <AlertDialogPrimitiveTrigger>{trigger}</AlertDialogPrimitiveTrigger>
      )}
      <AlertDialogContent>
        <AlertDialogTitle>
          {step === 1 ? title : "Conferma definitiva"}
        </AlertDialogTitle>
        <AlertDialogDescription className="mt-2 text-sm text-muted">
          {step === 1
            ? description
            : "Questa è l'ultima conferma. L'azione non potrà essere annullata."}
        </AlertDialogDescription>
        <div className="mt-6 flex justify-end gap-2">
          <AlertDialogCancel asChild>
            <Button variant="ghost">Annulla</Button>
          </AlertDialogCancel>
          {step === 1 ? (
            <Button variant="danger" onClick={() => setStep(2)}>
              Continua
            </Button>
          ) : (
            <AlertDialogAction asChild>
              <Button variant="danger" onClick={onConfirm}>
                Conferma definitivamente
              </Button>
            </AlertDialogAction>
          )}
        </div>
      </AlertDialogContent>
    </AlertDialog>
  );
}

function AlertDialogPrimitiveTrigger({ children }: { children: ReactNode }) {
  return <AlertDialogTrigger asChild>{children}</AlertDialogTrigger>;
}
