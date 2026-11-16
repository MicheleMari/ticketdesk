import { useEffect, useState } from "react";
import { ClipboardList, Plus } from "lucide-react";
import { useQuery } from "@tanstack/react-query";
import { useSearchParams } from "react-router-dom";
import { ApiError, api } from "../../lib/api";
import { queryKeys } from "../../lib/query";
import { useAuth } from "../auth/AuthContext";
import { cn, formatDate, isOverdue } from "../../lib/utils";
import {
  CategoryBadge,
  FilterIcon,
  FilterSelect,
  PageHeader,
  Pagination,
  PriorityBadge,
  SearchInput,
  StatusBadge,
} from "../../components/common";
import { Button, Card, EmptyState, ErrorState } from "../../components/ui";
import { BlurFade } from "../../components/magicui/blur-fade";
import {
  TicketMobileCard,
  TicketQuickActions,
  TicketTableSkeleton,
  AssigneeDisplay,
} from "./TicketTable";

export function TicketsPage() {
  const { user } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const paramsString = searchParams.toString();
  const query = useQuery({
    queryKey: queryKeys.tickets(paramsString),
    queryFn: () => api.tickets(searchParams),
  });
  const categories = useQuery({
    queryKey: queryKeys.categories,
    queryFn: api.categories,
  });
  const users = useQuery({ queryKey: queryKeys.users, queryFn: api.users });
  const [search, setSearch] = useState(searchParams.get("q") ?? "");
  useEffect(() => {
    const timer = window.setTimeout(() => {
      const next = new URLSearchParams(searchParams);
      if (search) next.set("q", search);
      else next.delete("q");
      next.delete("page");
      if (next.toString() !== searchParams.toString()) setSearchParams(next);
    }, 350);
    return () => window.clearTimeout(timer);
  }, [search, searchParams, setSearchParams]);
  const setFilter = (key: string, value: string) => {
    const next = new URLSearchParams(searchParams);
    if (value) next.set(key, value);
    else next.delete(key);
    next.delete("page");
    setSearchParams(next);
  };
  const reset = () => {
    setSearch("");
    setSearchParams(new URLSearchParams());
  };
  const sort = searchParams.get("sort");
  const direction = searchParams.get("direction");
  const ordering = sort
    ? sort === "work_queue"
      ? "work_queue"
      : `${sort}:${direction ?? "desc"}`
    : "default";
  const setOrdering = (value: string) => {
    const next = new URLSearchParams(searchParams);
    next.delete("sort");
    next.delete("direction");
    next.delete("page");
    if (value !== "default") {
      if (value === "work_queue") {
        next.set("sort", value);
      } else {
        const [nextSort, nextDirection] = value.split(":");
        next.set("sort", nextSort);
        next.set("direction", nextDirection);
      }
    }
    setSearchParams(next);
  };
  return (
    <BlurFade>
      <PageHeader
        eyebrow="Workspace"
        title="Ticket"
        description={
          (query.data?.meta.total ?? 0) + " richieste nella tua area di lavoro"
        }
        action={
          <Button onClick={() => window.location.assign("/tickets/new")}>
            <Plus className="h-4 w-4" />
            Nuovo ticket
          </Button>
        }
      />
      <Card>
        <div className="grid min-w-0 gap-3 border-b border-border p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-[minmax(220px,1.5fr)_repeat(5,minmax(120px,1fr))_auto]">
          <SearchInput
            value={search}
            onChange={setSearch}
            onClear={() => setSearch("")}
          />
          <FilterSelect
            label="Stato"
            value={searchParams.get("status") ?? ""}
            onChange={(v) => setFilter("status", v)}
          >
            <option value="">Tutti gli stati</option>
            <option value="open">Aperto</option>
            <option value="in_progress">In lavorazione</option>
            <option value="closed">Chiuso</option>
          </FilterSelect>
          <FilterSelect
            label="Priorità"
            value={searchParams.get("priority") ?? ""}
            onChange={(v) => setFilter("priority", v)}
          >
            <option value="">Tutte le priorità</option>
            <option value="high">Alta</option>
            <option value="medium">Media</option>
            <option value="low">Bassa</option>
          </FilterSelect>
          <FilterSelect
            label="Categoria"
            value={searchParams.get("category_id") ?? ""}
            onChange={(v) => setFilter("category_id", v)}
          >
            <option value="">Tutte le categorie</option>
            {categories.data?.data.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </FilterSelect>
          {user?.role !== "requester" && (
            <FilterSelect
              label="Assegnatario"
              value={searchParams.get("assignee_id") ?? ""}
              onChange={(v) => setFilter("assignee_id", v)}
            >
              <option value="">Tutti gli assegnatari</option>
              {users.data?.data.map((person) => (
                <option key={person.id} value={person.id}>
                  {person.name}
                </option>
              ))}
            </FilterSelect>
          )}
          <FilterSelect
            label="Ordinamento"
            value={ordering}
            onChange={setOrdering}
          >
            <option value="default">Più recenti</option>
            <option value="created_at:asc">Più vecchi</option>
            <option value="created_at:desc">Più recenti</option>
            <option value="due_date:asc">Scadenza più vicina</option>
            <option value="due_date:desc">Scadenza più lontana</option>
            <option value="priority:desc">Priorità (desc)</option>
            <option value="priority:asc">Priorità (asc)</option>
            <option value="work_queue">Priorità e anzianità</option>
          </FilterSelect>
          <Button variant="ghost" className="h-9 px-2 text-xs" onClick={reset}>
            <FilterIcon />
            Reset
          </Button>
        </div>
        {query.isLoading ? (
          <TicketTableSkeleton />
        ) : query.isError ? (
          <div className="p-4">
            <ErrorState
              message={
                query.error instanceof ApiError
                  ? query.error.message
                  : undefined
              }
              retry={() => void query.refetch()}
            />
          </div>
        ) : query.data?.data.length ? (
          <>
            <div className="hidden overflow-hidden lg:block">
              <table className="w-full table-fixed text-left">
                <colgroup>
                  <col className="w-[31%]" />
                  <col className="w-[12%]" />
                  <col className="w-[10%]" />
                  <col className="w-[15%]" />
                  <col className="w-[14%]" />
                  <col className="w-[8%]" />
                  <col className="w-[10%]" />
                </colgroup>
                <thead className="border-b border-border bg-surface-subtle/60 text-xs font-semibold uppercase tracking-wide text-muted">
                  <tr>
                    <th className="px-3 py-3">Ticket</th>
                    <th className="whitespace-nowrap px-3 py-3">Stato</th>
                    <th className="whitespace-nowrap px-3 py-3">Priorità</th>
                    <th className="px-3 py-3">Categoria</th>
                    <th className="px-3 py-3">Assegnatario</th>
                    <th className="whitespace-nowrap px-3 py-3">Scadenza</th>
                    <th className="px-3 py-3 text-right">Azioni</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {query.data.data.map((ticket) => (
                    <tr
                      key={ticket.id}
                      className="group cursor-pointer hover:bg-surface-subtle/50"
                      onClick={() =>
                        window.location.assign("/tickets/" + ticket.id)
                      }
                    >
                      <td className="overflow-hidden px-3 py-4">
                        <div className="flex items-center gap-3">
                          <span className="text-xs text-muted">
                            #{ticket.id}
                          </span>
                          <span
                            className="truncate font-medium"
                            title={ticket.title}
                          >
                            {ticket.title}
                          </span>
                        </div>
                        <p className="mt-1 line-clamp-1 pl-7 text-xs text-muted">
                          Creato il {formatDate(ticket.created_at)}
                        </p>
                      </td>
                      <td className="whitespace-nowrap px-3 py-4">
                        <StatusBadge status={ticket.status} />
                      </td>
                      <td className="whitespace-nowrap px-3 py-4">
                        <PriorityBadge priority={ticket.priority} />
                      </td>
                      <td className="overflow-hidden px-3 py-4">
                        <CategoryBadge
                          name={ticket.category?.name}
                          color={ticket.category?.color}
                        />
                      </td>
                      <td className="overflow-hidden px-3 py-4">
                        <AssigneeDisplay ticket={ticket} user={user} />
                      </td>
                      <td
                        className={cn(
                          "whitespace-nowrap px-3 py-4 text-xs",
                          isOverdue(ticket) && "font-semibold text-red-600",
                        )}
                      >
                        {formatDate(ticket.due_date)}
                      </td>
                      <td className="px-3 py-4 text-right">
                        <TicketQuickActions ticket={ticket} user={user} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="space-y-3 p-3 lg:hidden">
              {query.data.data.map((ticket) => (
                <TicketMobileCard key={ticket.id} ticket={ticket} user={user} />
              ))}
            </div>
            <Pagination
              page={query.data.meta.current_page}
              lastPage={query.data.meta.last_page}
              onChange={(page) => setFilter("page", String(page))}
            />
          </>
        ) : (
          <EmptyState
            icon={<ClipboardList className="h-5 w-5" />}
            title="Nessun ticket trovato"
            description={
              search || paramsString
                ? "Prova a modificare i filtri o a cancellare la ricerca."
                : "Crea il primo ticket per iniziare a organizzare il lavoro."
            }
            action={
              <Button
                onClick={
                  search || paramsString
                    ? reset
                    : () => window.location.assign("/tickets/new")
                }
              >
                {search || paramsString ? "Reset filtri" : "Crea un ticket"}
              </Button>
            }
          />
        )}
      </Card>
    </BlurFade>
  );
}
