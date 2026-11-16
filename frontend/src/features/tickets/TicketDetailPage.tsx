import { useState, type ReactNode } from "react";
import { ArrowLeft, ChevronDown, Pencil, Trash2 } from "lucide-react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useNavigate, useParams } from "react-router-dom";
import { ApiError, api } from "../../lib/api";
import { queryKeys } from "../../lib/query";
import {
  canAssignTicket,
  canChangeStatus,
  canDeleteTicket,
  canEditTicket,
} from "../../lib/permissions";
import { formatDate, isOverdue } from "../../lib/utils";
import { useAuth } from "../auth/AuthContext";
import type { TicketStatus } from "../../types";
import { AssigneeDisplay } from "./TicketTable";
import { Avatar } from "../../components/layout/AppShell";
import {
  CategoryBadge,
  ConfirmDialog,
  PriorityBadge,
  StatusBadge,
} from "../../components/common";
import {
  Button,
  Card,
  ErrorState,
  IconButton,
  Label,
  Select,
  Skeleton,
  Spinner,
  Textarea,
} from "../../components/ui";
import { BlurFade } from "../../components/magicui/blur-fade";

export function TicketDetailPage() {
  const { id = "" } = useParams();
  const { user } = useAuth();
  const navigate = useNavigate();
  const qc = useQueryClient();
  const ticket = useQuery({
    queryKey: queryKeys.ticket(id),
    queryFn: () => api.ticket(id),
  });
  const comments = useQuery({
    queryKey: queryKeys.comments(id),
    queryFn: () => api.comments(id),
  });
  const item = ticket.data?.data;
  const users = useQuery({
    queryKey: queryKeys.users,
    queryFn: api.users,
    enabled: Boolean(item && canAssignTicket(user, item)),
  });
  const statusMutation = useMutation({
    mutationFn: (status: TicketStatus) => api.status(Number(id), status),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.ticket(id) });
      void qc.invalidateQueries({ queryKey: ["tickets"] });
    },
    onError: () => notify("Impossibile aggiornare lo stato."),
  });
  const assignMutation = useMutation({
    mutationFn: (assignee: number | null) => api.assign(Number(id), assignee),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.ticket(id) });
      void qc.invalidateQueries({ queryKey: ["tickets"] });
    },
    onError: (error) =>
      notify(
        error instanceof ApiError
          ? error.message
          : "Impossibile assegnare il ticket.",
      ),
  });
  const commentMutation = useMutation({
    mutationFn: (body: string) => api.createComment(Number(id), body),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.comments(id) });
    },
    onError: () => notify("Impossibile aggiungere il commento."),
  });
  const deleteMutation = useMutation({
    mutationFn: () => api.deleteTicket(Number(id)),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ["tickets"] });
      navigate("/tickets");
    },
  });
  if (ticket.isLoading) return <TicketDetailSkeleton />;
  if (ticket.isError || !item)
    return (
      <ErrorState
        message="Ticket non trovato o non accessibile."
        retry={() => void ticket.refetch()}
      />
    );
  const editable = canEditTicket(user, item);
  const assignable = canAssignTicket(user, item);
  const statuses = nextStatuses(item.status, user?.role);
  return (
    <BlurFade>
      <div className="mb-6 flex items-center justify-between">
        <Button
          variant="ghost"
          onClick={() => navigate("/tickets")}
          className="h-auto gap-2 px-0 text-sm font-medium text-muted hover:bg-transparent hover:text-foreground"
        >
          <ArrowLeft className="h-4 w-4" />
          Tutti i ticket
        </Button>
        <div className="flex gap-2">
          {editable && (
            <Button
              variant="secondary"
              onClick={() => navigate("/tickets/" + id + "/edit")}
            >
              <Pencil className="h-4 w-4" />
              Modifica
            </Button>
          )}
          {canDeleteTicket(user) && (
            <ConfirmDialog
              trigger={
                <IconButton
                  variant="ghost"
                  className="text-red-600"
                  aria-label="Elimina ticket"
                >
                  <Trash2 className="h-4 w-4" />
                </IconButton>
              }
              title="Eliminare il ticket?"
              description="Questa operazione è definitiva e non può essere annullata."
              onConfirm={() => deleteMutation.mutate()}
            />
          )}
        </div>
      </div>
      <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div className="space-y-6">
          <Card className="p-5 sm:p-7">
            <div className="flex flex-wrap items-center gap-3">
              <span className="text-xs text-muted">#{item.id}</span>
              <StatusBadge status={item.status} />
              <PriorityBadge priority={item.priority} />
            </div>
            <h1 className="mt-5 text-2xl font-semibold tracking-tight">
              {item.title}
            </h1>
            <p className="mt-5 whitespace-pre-wrap text-[15px] leading-7 text-muted">
              {item.description}
            </p>
            <div className="mt-8 border-t border-border pt-4 text-xs text-muted">
              Creato il {formatDate(item.created_at, true)}
              {item.closed_at && (
                <span> · Chiuso il {formatDate(item.closed_at, true)}</span>
              )}
            </div>
          </Card>
          <Comments
            comments={comments.data?.data ?? []}
            isLoading={comments.isLoading}
            pending={commentMutation.isPending}
            onSubmit={(body) => commentMutation.mutate(body)}
          />
        </div>
        <aside className="space-y-4">
          <Card className="p-5">
            <h2 className="text-xs font-semibold uppercase tracking-[0.14em] text-muted">
              Dettagli
            </h2>
            <dl className="mt-5 space-y-5 text-sm">
              <DetailRow label="Categoria">
                <CategoryBadge
                  name={item.category?.name}
                  color={item.category?.color}
                />
              </DetailRow>
              <DetailRow label="Scadenza">
                <span
                  className={isOverdue(item) ? "font-medium text-red-600" : ""}
                >
                  {formatDate(item.due_date)}
                </span>
              </DetailRow>
              <DetailRow label="Assegnatario">
                {assignable ? (
                  <Select
                    value={String(item.assignee?.id ?? "")}
                    onValueChange={(value) =>
                      assignMutation.mutate(value ? Number(value) : null)
                    }
                    disabled={assignMutation.isPending}
                  >
                    <option value="">Non assegnato</option>
                    {users.data?.data.map((person) => (
                      <option key={person.id} value={person.id}>
                        {person.name}
                      </option>
                    ))}
                  </Select>
                ) : (
                  <AssigneeDisplay ticket={item} user={user} />
                )}
              </DetailRow>
            </dl>
          </Card>
          <Card className="p-5">
            <h2 className="text-xs font-semibold uppercase tracking-[0.14em] text-muted">
              Azioni stato
            </h2>
            <div className="mt-4 space-y-2">
              {statuses.map((next) => (
                <Button
                  key={next}
                  variant={next === "closed" ? "primary" : "secondary"}
                  className="w-full justify-between"
                  disabled={
                    !canChangeStatus(user, item) || statusMutation.isPending
                  }
                  onClick={() => statusMutation.mutate(next)}
                >
                  {next === "in_progress"
                    ? "Prendi in carico"
                    : next === "closed"
                      ? "Chiudi ticket"
                      : "Riapri ticket"}
                  <ChevronDown className="h-4 w-4 -rotate-90" />
                </Button>
              ))}
              {!statuses.length && (
                <p className="text-sm text-muted">
                  Nessuna transizione disponibile.
                </p>
              )}
            </div>
          </Card>
        </aside>
      </div>
    </BlurFade>
  );
}

function nextStatuses(status: TicketStatus, role?: string): TicketStatus[] {
  if (status === "open") return ["in_progress", "closed"];
  if (status === "in_progress") return ["closed"];
  return role === "admin" ? ["open"] : [];
}
function DetailRow({
  label,
  children,
}: {
  label: string;
  children: ReactNode;
}) {
  return (
    <div>
      <dt className="mb-1.5 text-xs text-muted">{label}</dt>
      <dd>{children}</dd>
    </div>
  );
}
function Comments({
  comments,
  isLoading,
  onSubmit,
  pending,
}: {
  comments: {
    id: number;
    body: string;
    user: { name: string };
    created_at: string;
  }[];
  isLoading: boolean;
  onSubmit: (body: string) => void;
  pending: boolean;
}) {
  const [body, setBody] = useState("");
  return (
    <Card className="p-5 sm:p-7">
      <div className="flex items-center justify-between">
        <h2 className="text-base font-semibold">Discussione</h2>
        <span className="text-xs text-muted">{comments.length} commenti</span>
      </div>
      <div className="mt-6 space-y-6">
        {isLoading ? (
          <Skeleton className="h-16 w-full" />
        ) : comments.length ? (
          comments.map((comment) => (
            <div key={comment.id} className="flex gap-3">
              <Avatar name={comment.user.name} small />
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-baseline gap-2">
                  <span className="text-sm font-semibold">
                    {comment.user.name}
                  </span>
                  <span className="text-xs text-muted">
                    {formatDate(comment.created_at, true)}
                  </span>
                </div>
                <p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-muted">
                  {comment.body}
                </p>
              </div>
            </div>
          ))
        ) : (
          <p className="py-3 text-sm text-muted">
            Nessun commento. Avvia la discussione con il team.
          </p>
        )}
      </div>
      <form
        className="mt-7 border-t border-border pt-5"
        onSubmit={(event) => {
          event.preventDefault();
          if (body.trim()) {
            onSubmit(body.trim());
            setBody("");
          }
        }}
      >
        <Label htmlFor="comment-detail" required>
          Aggiungi un commento
        </Label>
        <Textarea
          id="comment-detail"
          value={body}
          onChange={(event) => setBody(event.target.value)}
          placeholder="Scrivi un aggiornamento…"
          className="min-h-24"
          maxLength={5000}
        />
        <div className="mt-3 flex justify-end">
          <Button type="submit" disabled={pending || !body.trim()}>
            {pending && <Spinner />}Invia commento
          </Button>
        </div>
      </form>
    </Card>
  );
}
function TicketDetailSkeleton() {
  return (
    <div className="space-y-6">
      <Skeleton className="h-6 w-32" />
      <div className="grid gap-6 xl:grid-cols-[1fr_320px]">
        <Skeleton className="h-96" />
        <Skeleton className="h-72" />
      </div>
    </div>
  );
}
function notify(message: string) {
  import("sonner").then(({ toast }) => toast.error(message));
}
