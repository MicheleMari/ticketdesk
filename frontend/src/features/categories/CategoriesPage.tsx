import { useState } from "react";
import { Pencil, Plus, Tag, Trash2 } from "lucide-react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Navigate } from "react-router-dom";
import { ApiError, api } from "../../lib/api";
import { queryKeys } from "../../lib/query";
import { useAuth } from "../auth/AuthContext";
import type { Category } from "../../types";
import { PageHeader, ConfirmDialog } from "../../components/common";
import {
  Button,
  Card,
  ColorPicker,
  EmptyState,
  IconButton,
  Input,
  Label,
  Skeleton,
  Spinner,
} from "../../components/ui";
import { BlurFade } from "../../components/magicui/blur-fade";

export function CategoriesPage() {
  const { user } = useAuth();
  const qc = useQueryClient();
  const categories = useQuery({
    queryKey: queryKeys.categories,
    queryFn: api.categories,
  });
  const [editing, setEditing] = useState<Category | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [form, setForm] = useState({ name: "", color: "#7468F5" });
  const save = useMutation({
    mutationFn: () =>
      editing ? api.updateCategory(editing.id, form) : api.createCategory(form),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.categories });
      const wasEditing = Boolean(editing);
      setEditing(null);
      setFormOpen(false);
      setForm({ name: "", color: "#7468F5" });
      import("sonner").then(({ toast }) =>
        toast.success(
          wasEditing ? "Categoria aggiornata." : "Categoria creata.",
        ),
      );
    },
    onError: (error) =>
      import("sonner").then(({ toast }) =>
        toast.error(
          error instanceof ApiError
            ? error.message
            : "Impossibile salvare la categoria.",
        ),
      ),
  });
  const remove = useMutation({
    mutationFn: (categoryId: number) => api.deleteCategory(categoryId),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.categories });
      import("sonner").then(({ toast }) =>
        toast.success("Categoria eliminata."),
      );
    },
    onError: (error) =>
      import("sonner").then(({ toast }) =>
        toast.error(
          error instanceof ApiError && error.status === 409
            ? "La categoria è utilizzata da uno o più ticket."
            : "Impossibile eliminare la categoria.",
        ),
      ),
  });
  if (user?.role !== "admin") return <Navigate to="/tickets" replace />;
  return (
    <BlurFade>
      <PageHeader
        eyebrow="Amministrazione"
        title="Categorie"
        description="Organizza i ticket con etichette chiare e riconoscibili."
        action={
          <Button
            onClick={() => {
              setEditing(null);
              setForm({ name: "", color: "#7468F5" });
              setFormOpen(true);
            }}
          >
            <Plus className="h-4 w-4" />
            Nuova categoria
          </Button>
        }
      />
      <div
        className={
          formOpen
            ? "grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]"
            : "grid gap-6 grid-cols-1"
        }
      >
        <Card>
          {categories.isLoading ? (
            <div className="space-y-4 p-5">
              <Skeleton className="h-10 w-full" />
              <Skeleton className="h-10 w-full" />
            </div>
          ) : categories.data?.data.length ? (
            <div className="divide-y divide-border">
              {categories.data.data.map((category) => (
                <div
                  className="flex items-center justify-between gap-4 px-5 py-4"
                  key={category.id}
                >
                  <div className="flex items-center gap-3">
                    <span
                      className="h-3 w-3 rounded-full"
                      style={{ backgroundColor: category.color }}
                    />
                    <span className="text-sm font-medium">{category.name}</span>
                  </div>
                  <div className="flex gap-1">
                    <Button
                      variant="ghost"
                      className="h-8 px-2"
                      aria-label={"Modifica " + category.name}
                      onClick={() => {
                        setEditing(category);
                        setForm({ name: category.name, color: category.color });
                        setFormOpen(true);
                      }}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <ConfirmDialog
                      trigger={
                        <IconButton
                          variant="ghost"
                          className="h-8 w-8 text-red-600"
                          aria-label={"Elimina " + category.name}
                        >
                          <Trash2 className="h-4 w-4" />
                        </IconButton>
                      }
                      title={"Eliminare la categoria “" + category.name + "”?"}
                      description="La categoria può essere eliminata solo se non è associata a ticket."
                      onConfirm={() => remove.mutate(category.id)}
                    />
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <EmptyState
              icon={<Tag className="h-5 w-5" />}
              title="Nessuna categoria"
              description="Crea una categoria per iniziare a organizzare i ticket."
            />
          )}
        </Card>
        {formOpen && (
          <Card className="h-fit p-5">
            <h2 className="text-base font-semibold">
              {editing ? "Modifica categoria" : "Crea categoria"}
            </h2>
            <p className="mt-1 text-sm text-muted">
              Scegli un nome breve e un colore riconoscibile.
            </p>
            <form
              className="mt-5 space-y-4"
              onSubmit={(event) => {
                event.preventDefault();
                if (form.name.trim()) save.mutate();
              }}
            >
              <div>
                <Label htmlFor="category-name" required>
                  Nome
                </Label>
                <Input
                  id="category-name"
                  value={form.name}
                  onChange={(event) =>
                    setForm({ ...form, name: event.target.value })
                  }
                  maxLength={100}
                  required
                />
              </div>
              <div>
                <Label htmlFor="category-color" required>
                  Colore
                </Label>
                <ColorPicker
                  value={form.color}
                  onChange={(color) => setForm({ ...form, color })}
                />
              </div>
              <div className="flex justify-end gap-2 pt-2">
                {editing && (
                  <Button
                    type="button"
                    variant="ghost"
                    onClick={() => {
                      setEditing(null);
                      setFormOpen(false);
                    }}
                  >
                    Annulla
                  </Button>
                )}
                <Button type="submit" disabled={save.isPending}>
                  {save.isPending && <Spinner />}
                  {editing ? "Salva modifiche" : "Crea categoria"}
                </Button>
              </div>
            </form>
          </Card>
        )}
      </div>
    </BlurFade>
  );
}
