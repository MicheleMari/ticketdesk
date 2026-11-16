import { Controller, useForm, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { ArrowLeft } from "lucide-react";
import { useEffect, type ReactNode } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { ApiError, api } from "../../lib/api";
import { queryKeys } from "../../lib/query";
import {
  Button,
  Card,
  DatePicker,
  Input,
  Label,
  Select,
  Spinner,
  Textarea,
} from "../../components/ui";
import { PageHeader } from "../../components/common";
import { BlurFade } from "../../components/magicui/blur-fade";

const ticketSchema = z.object({
  title: z
    .string()
    .min(5, "Inserisci almeno 5 caratteri.")
    .max(120, "Massimo 120 caratteri."),
  description: z.string().min(20, "Inserisci almeno 20 caratteri."),
  category_id: z.number().int().positive("Seleziona una categoria."),
  priority: z.enum(["low", "medium", "high"]),
  due_date: z.string().nullable(),
});
type TicketFormValues = z.infer<typeof ticketSchema>;
function FieldError({ children }: { children?: ReactNode }) {
  return <p className="mt-1.5 text-xs text-red-600">{children}</p>;
}

export function TicketFormPage() {
  const { id } = useParams();
  const edit = Boolean(id);
  const navigate = useNavigate();
  const qc = useQueryClient();
  const categories = useQuery({
    queryKey: queryKeys.categories,
    queryFn: api.categories,
  });
  const ticket = useQuery({
    queryKey: queryKeys.ticket(id ?? ""),
    queryFn: () => api.ticket(id ?? ""),
    enabled: edit,
  });
  const form = useForm<TicketFormValues>({
    resolver: zodResolver(ticketSchema),
    defaultValues: {
      title: "",
      description: "",
      category_id: 0,
      priority: "medium",
      due_date: null,
    },
  });
  const dueDate = useWatch({ control: form.control, name: "due_date" });
  useEffect(() => {
    if (ticket.data?.data)
      form.reset({
        title: ticket.data.data.title,
        description: ticket.data.data.description,
        category_id: ticket.data.data.category?.id ?? 0,
        priority: ticket.data.data.priority,
        due_date: ticket.data.data.due_date,
      });
  }, [ticket.data, form]);
  const mutation = useMutation({
    mutationFn: (values: TicketFormValues) =>
      edit ? api.updateTicket(Number(id), values) : api.createTicket(values),
    onSuccess: (result) => {
      void qc.invalidateQueries({ queryKey: ["tickets"] });
      navigate("/tickets/" + result.data.id);
    },
    onError: (error) => toastError(error),
  });
  return (
    <BlurFade>
      <Button
        variant="ghost"
        onClick={() => navigate(-1)}
        className="mb-6 h-auto gap-2 px-0 text-sm font-medium text-muted hover:bg-transparent hover:text-foreground"
      >
        <ArrowLeft className="h-4 w-4" />
        Torna ai ticket
      </Button>
      <PageHeader
        eyebrow={edit ? "Modifica ticket" : "Nuova richiesta"}
        title={edit ? "Modifica ticket" : "Crea un nuovo ticket"}
        description={
          edit
            ? "Aggiorna i dettagli della richiesta."
            : "Descrivi il problema in modo chiaro per velocizzare la risoluzione."
        }
      />
      <Card className="max-w-3xl">
        <form
          onSubmit={form.handleSubmit((values) =>
            mutation.mutate({ ...values, due_date: values.due_date || null }),
          )}
          className="space-y-6 p-5 sm:p-7"
        >
          <div>
            <Label htmlFor="title" required>
              Titolo
            </Label>
            <Input
              id="title"
              placeholder="Es. Accesso al portale clienti"
              {...form.register("title")}
            />
            {form.formState.errors.title && (
              <FieldError>{form.formState.errors.title.message}</FieldError>
            )}
          </div>
          <div>
            <Label htmlFor="description" required>
              Descrizione
            </Label>
            <Textarea
              id="description"
              placeholder="Racconta cosa è successo, quando e con quale impatto…"
              {...form.register("description")}
            />
            {form.formState.errors.description && (
              <FieldError>
                {form.formState.errors.description.message}
              </FieldError>
            )}
          </div>
          <div className="grid gap-5 sm:grid-cols-2">
            <div>
              <Label htmlFor="category_id" required>
                Categoria
              </Label>
              <Controller
                control={form.control}
                name="category_id"
                render={({ field }) => (
                  <Select
                    id="category_id"
                    name={field.name}
                    value={field.value ? String(field.value) : ""}
                    onBlur={field.onBlur}
                    onValueChange={(value) => field.onChange(Number(value))}
                  >
                    <option value="0">Seleziona una categoria</option>
                    {categories.data?.data.map((category) => (
                      <option key={category.id} value={category.id}>
                        {category.name}
                      </option>
                    ))}
                  </Select>
                )}
              />
              {form.formState.errors.category_id && (
                <FieldError>
                  {form.formState.errors.category_id.message}
                </FieldError>
              )}
            </div>
            <div>
              <Label htmlFor="priority" required>
                Priorità
              </Label>
              <Select id="priority" {...form.register("priority")}>
                <option value="low">Bassa</option>
                <option value="medium">Media</option>
                <option value="high">Alta</option>
              </Select>
            </div>
            <div>
              <Label htmlFor="due_date" hint="opzionale">
                Scadenza
              </Label>
              <DatePicker
                value={dueDate}
                onChange={(value) => form.setValue("due_date", value)}
                minDate={new Date()}
              />
            </div>
          </div>
          <div className="flex flex-col-reverse justify-end gap-2 border-t border-border pt-5 sm:flex-row">
            <Button type="button" variant="ghost" onClick={() => navigate(-1)}>
              Annulla
            </Button>
            <Button type="submit" disabled={mutation.isPending}>
              {mutation.isPending && <Spinner />}
              {edit ? "Salva modifiche" : "Crea ticket"}
            </Button>
          </div>
        </form>
      </Card>
    </BlurFade>
  );
}
function toastError(error: unknown) {
  import("sonner").then(({ toast }) =>
    toast.error(
      error instanceof ApiError
        ? error.message
        : "Impossibile salvare il ticket.",
    ),
  );
}
