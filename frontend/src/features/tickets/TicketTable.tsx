import { useState } from "react";
import { ArrowLeft, Check, MoreHorizontal } from "lucide-react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ApiError, api } from "../../lib/api";
import { canChangeStatus } from "../../lib/permissions";
import { formatDate, isOverdue } from "../../lib/utils";
import { useAuth } from "../auth/AuthContext";
import type { Ticket } from "../../types";
import {
  CategoryBadge,
  ConfirmDialog,
  PriorityBadge,
  StatusBadge,
} from "../../components/common";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  IconButton,
  Skeleton,
} from "../../components/ui";
import { Avatar } from "../../components/layout/AppShell";
import { toast } from "sonner";

export function TicketTableSkeleton() {
  return (
    <div className="space-y-4 p-5">
      {Array.from({ length: 5 }).map((_, index) => (
        <div key={index} className="flex items-center gap-4">
          <Skeleton className="h-4 w-8" />
          <Skeleton className="h-4 flex-1" />
          <Skeleton className="h-5 w-24" />
          <Skeleton className="h-5 w-20" />
        </div>
      ))}
    </div>
  );
}

export function AssigneeDisplay({
  ticket,
  user,
}: {
  ticket: Ticket;
  user: ReturnType<typeof useAuth>["user"];
}) {
  if (!ticket.assignee)
    return <span className="text-xs text-muted">Non assegnato</span>;
  const current = user?.id === ticket.assignee.id;
  return (
    <div
      className="flex min-w-0 items-center gap-2 text-xs"
      title={ticket.assignee.name}
    >
      <Avatar name={ticket.assignee.name} small />
      <span className="min-w-0 truncate">
        {current ? (
          <>
            <span className="font-medium text-accent">Assegnato a me</span>
            <span className="ml-1 text-muted">({ticket.assignee.name})</span>
          </>
        ) : (
          ticket.assignee.name
        )}
      </span>
    </div>
  );
}

export function TicketMobileCard({
  ticket,
  user,
}: {
  ticket: Ticket;
  user: ReturnType<typeof useAuth>["user"];
}) {
  return (
    <div
      className="rounded-lg border border-border bg-surface p-4"
      onClick={() => window.location.assign("/tickets/" + ticket.id)}
    >
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <p className="text-xs text-muted">#{ticket.id}</p>
          <h3 className="mt-1 truncate font-semibold" title={ticket.title}>
            {ticket.title}
          </h3>
        </div>
        <div onClick={(event) => event.stopPropagation()}>
          <TicketQuickActions ticket={ticket} user={user} />
        </div>
      </div>
      <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs">
        <StatusBadge status={ticket.status} />
        <PriorityBadge priority={ticket.priority} />
        <CategoryBadge
          name={ticket.category?.name}
          color={ticket.category?.color}
        />
      </div>
      <div className="mt-4 flex items-center justify-between border-t border-border pt-3 text-xs text-muted">
        <AssigneeDisplay ticket={ticket} user={user} />
        <span className={isOverdue(ticket) ? "font-semibold text-red-600" : ""}>
          {formatDate(ticket.due_date)}
        </span>
      </div>
    </div>
  );
}

export function TicketQuickActions({
  ticket,
  user,
}: {
  ticket: Ticket;
  user: ReturnType<typeof useAuth>["user"];
}) {
  const queryClient = useQueryClient();
  const [closeDialogOpen, setCloseDialogOpen] = useState(false);
  const takeMutation = useMutation({
    mutationFn: async () => {
      if (user?.role === "supervisor" && !ticket.assignee)
        await api.assign(ticket.id, user.id);
      return api.status(ticket.id, "in_progress");
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ["tickets"] });
      toast.success("Ticket preso in carico.");
    },
    onError: (error) =>
      toast.error(
        error instanceof ApiError
          ? error.message
          : "Impossibile prendere in carico il ticket.",
      ),
  });
  const closeMutation = useMutation({
    mutationFn: () => api.status(ticket.id, "closed"),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ["tickets"] });
      toast.success("Ticket chiuso.");
    },
    onError: (error) =>
      toast.error(
        error instanceof ApiError
          ? error.message
          : "Impossibile chiudere il ticket.",
      ),
  });
  const canTake =
    ticket.status === "open" &&
    canChangeStatus(user, ticket) &&
    (user?.role !== "supervisor" ||
      !ticket.assignee ||
      ticket.assignee.id === user.id);
  const canClose =
    (ticket.status === "open" || ticket.status === "in_progress") &&
    canChangeStatus(user, ticket);
  if (!canTake && !canClose)
    return <span className="text-xs text-muted">—</span>;
  return (
    <div
      className="flex justify-end"
      onClick={(event) => event.stopPropagation()}
    >
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <IconButton
            variant="secondary"
            aria-label={"Azioni per ticket " + ticket.id}
          >
            <MoreHorizontal className="h-4 w-4" />
          </IconButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent>
          {canTake && (
            <DropdownMenuItem
              disabled={takeMutation.isPending}
              onSelect={() => takeMutation.mutate()}
            >
              <ArrowLeft className="h-4 w-4 rotate-180 text-accent" />
              {user?.role === "supervisor" && !ticket.assignee
                ? "Prendi in carico"
                : "Avvia lavorazione"}
            </DropdownMenuItem>
          )}
          {canClose && (
            <DropdownMenuItem
              className="text-emerald-700 data-[highlighted]:text-emerald-700"
              onSelect={() => setCloseDialogOpen(true)}
            >
              <Check className="h-4 w-4" />
              Chiudi ticket
            </DropdownMenuItem>
          )}
        </DropdownMenuContent>
      </DropdownMenu>
      <ConfirmDialog
        open={closeDialogOpen}
        onOpenChange={setCloseDialogOpen}
        title="Chiudere il ticket?"
        description="Il ticket verrà segnato come chiuso e non sarà più modificabile. Potrai riaprirlo solo se autorizzato."
        onConfirm={() => closeMutation.mutate()}
      />
    </div>
  );
}
