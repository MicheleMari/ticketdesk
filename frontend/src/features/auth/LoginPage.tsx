import { Navigate, useNavigate } from "react-router-dom";
import { useForm } from "react-hook-form";
import { useState } from "react";
import { ApiError } from "../../lib/api";
import { useAuth } from "./AuthContext";
import { Button, Input, Label, Spinner } from "../../components/ui";

export function LoginPage() {
  const { user, login } = useAuth();
  const navigate = useNavigate();
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const form = useForm<{ email: string; password: string }>({
    defaultValues: { email: "", password: "" },
  });
  if (user) return <Navigate to="/tickets" replace />;
  async function submit(values: { email: string; password: string }) {
    setLoading(true);
    setError("");
    try {
      await login(values.email, values.password);
      navigate("/tickets", { replace: true });
    } catch (err) {
      setError(
        err instanceof ApiError
          ? (err.errors?.email?.[0] ?? err.message)
          : "Impossibile raggiungere il server.",
      );
    } finally {
      setLoading(false);
    }
  }
  return (
    <div className="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
      <div className="hidden bg-[#191b29] p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div>
          <div className="flex items-center gap-2">
            <span className="grid h-9 w-9 place-items-center rounded-lg bg-[#7468f5] font-bold">
              T
            </span>
            <span className="font-semibold">TicketDesk</span>
          </div>
          <div className="mt-28 max-w-lg">
            <p className="mb-5 text-sm font-medium text-[#a9a4ff]">
              OPERATIONS, IN SYNC
            </p>
            <h1 className="text-5xl font-semibold leading-[1.08] tracking-tight">
              Tutto il lavoro del team, finalmente in un unico posto.
            </h1>
            <p className="mt-6 max-w-md text-base leading-7 text-white/60">
              Gestisci richieste, priorità e responsabilità con una vista chiara
              e condivisa.
            </p>
          </div>
        </div>
        <p className="text-xs text-white/40">
          © {new Date().getFullYear()} TicketDesk
        </p>
      </div>
      <div className="flex items-center justify-center bg-background px-5 py-12">
        <div className="w-full max-w-sm">
          <div className="mb-10 lg:hidden">
            <div className="flex items-center gap-2">
              <span className="grid h-8 w-8 place-items-center rounded-lg bg-accent font-bold text-white">
                T
              </span>
              <span className="font-semibold">TicketDesk</span>
            </div>
          </div>
          <div className="mb-8">
            <h2 className="text-2xl font-semibold tracking-tight">
              Bentornato
            </h2>
            <p className="mt-2 text-sm text-muted">
              Accedi al tuo spazio di lavoro.
            </p>
          </div>
          <form onSubmit={form.handleSubmit(submit)} className="space-y-5">
            <div>
              <Label htmlFor="email" required>
                Email
              </Label>
              <Input
                id="email"
                type="email"
                autoComplete="email"
                placeholder="nome@azienda.it"
                {...form.register("email", {
                  required: "Inserisci la tua email.",
                })}
              />
              {form.formState.errors.email && (
                <p className="mt-1.5 text-xs text-red-600">
                  {form.formState.errors.email.message}
                </p>
              )}
            </div>
            <div>
              <Label htmlFor="password" required>
                Password
              </Label>
              <Input
                id="password"
                type="password"
                autoComplete="current-password"
                placeholder="••••••••"
                {...form.register("password", {
                  required: "Inserisci la password.",
                })}
              />
              {form.formState.errors.password && (
                <p className="mt-1.5 text-xs text-red-600">
                  {form.formState.errors.password.message}
                </p>
              )}
            </div>
            {error && (
              <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">
                {error}
              </div>
            )}
            <Button type="submit" className="w-full" disabled={loading}>
              {loading && <Spinner />}Accedi
            </Button>
          </form>
          <p className="mt-8 text-center text-xs text-muted">
            Accesso riservato agli utenti autorizzati.
          </p>
        </div>
      </div>
    </div>
  );
}
