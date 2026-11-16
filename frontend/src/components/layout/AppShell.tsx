import {
  Navigate,
  NavLink,
  Outlet,
  useLocation,
  useNavigate,
} from "react-router-dom";
import {
  CircleHelp,
  ClipboardList,
  LogOut,
  Menu,
  Moon,
  Sun,
  Tag,
  X,
} from "lucide-react";
import { useEffect, useState } from "react";
import { useAuth } from "../../features/auth/AuthContext";
import { canManageCategories, roleLabel } from "../../lib/permissions";
import { cn, initials } from "../../lib/utils";
import { Button, IconButton, Spinner } from "../ui";

export function Avatar({
  name,
  small = false,
}: {
  name: string;
  small?: boolean;
}) {
  return (
    <span
      className={cn(
        "grid shrink-0 place-items-center rounded-full bg-accent/10 font-semibold text-accent",
        small ? "h-6 w-6 text-[10px]" : "h-8 w-8 text-xs",
      )}
    >
      {initials(name)}
    </span>
  );
}

export function ProtectedLayout() {
  const { user, isLoading } = useAuth();
  if (isLoading)
    return (
      <div className="grid min-h-screen place-items-center">
        <Spinner className="h-6 w-6 text-accent" />
      </div>
    );
  return user ? <AppShell /> : <Navigate to="/login" replace />;
}

export function AppShell() {
  const { user, logout } = useAuth();
  const [open, setOpen] = useState(false);
  const [dark, setDark] = useState(() =>
    document.documentElement.classList.contains("dark"),
  );
  const location = useLocation();
  const navigate = useNavigate();
  useEffect(() => {
    document.documentElement.classList.toggle("dark", dark);
  }, [dark]);
  const nav = [
    { to: "/tickets", label: "Ticket", icon: ClipboardList },
    ...(canManageCategories(user)
      ? [{ to: "/categories", label: "Categorie", icon: Tag }]
      : []),
  ];
  const section = location.pathname.startsWith("/categories")
    ? "Categorie"
    : location.pathname.startsWith("/tickets/")
      ? "Dettaglio ticket"
      : "Ticket";
  return (
    <div className="min-h-screen bg-background text-foreground">
      <aside
        className={cn(
          "fixed inset-y-0 left-0 z-40 w-64 border-r border-border bg-surface transition-transform lg:translate-x-0",
          open ? "translate-x-0" : "-translate-x-full",
        )}
      >
        <div className="flex h-full flex-col px-4 py-5">
          <div className="flex items-center justify-between px-2">
            <Button
              variant="ghost"
              onClick={() => navigate("/tickets")}
              className="h-auto justify-start gap-2 p-0 text-left"
            >
              <span className="grid h-8 w-8 place-items-center rounded-lg bg-accent text-sm font-bold text-white">
                T
              </span>
              <span className="font-semibold tracking-tight">TicketDesk</span>
            </Button>
            <IconButton
              className="text-muted lg:hidden"
              onClick={() => setOpen(false)}
              aria-label="Chiudi menu"
            >
              <X className="h-5 w-5" />
            </IconButton>
          </div>
          <div className="mt-10 px-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">
            Workspace
          </div>
          <nav className="mt-3 space-y-1">
            {nav.map(({ to, label, icon: Icon }) => (
              <NavLink
                key={to}
                to={to}
                onClick={() => setOpen(false)}
                className={({ isActive }) =>
                  cn(
                    "flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition",
                    isActive
                      ? "bg-accent/10 text-accent"
                      : "text-muted hover:bg-surface-subtle hover:text-foreground",
                  )
                }
              >
                <Icon className="h-4 w-4" />
                {label}
              </NavLink>
            ))}
          </nav>
          <div className="mt-auto space-y-4">
            <div className="rounded-xl border border-border bg-surface-subtle p-3">
              <div className="flex items-center gap-2">
                <Avatar name={user?.name ?? ""} />
                <div className="min-w-0">
                  <p className="truncate text-sm font-semibold">{user?.name}</p>
                  <p className="truncate text-xs text-muted">
                    {roleLabel(user?.role)}
                  </p>
                </div>
              </div>
              <p className="mt-3 truncate text-xs text-muted">{user?.email}</p>
            </div>
            <Button
              variant="ghost"
              onClick={() => void logout()}
              className="w-full justify-start gap-3 px-3 text-muted"
            >
              <LogOut className="h-4 w-4" />
              Esci
            </Button>
          </div>
        </div>
      </aside>
      <div className="lg:pl-64">
        <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-border bg-background/90 px-4 backdrop-blur sm:px-8">
          <div className="flex items-center gap-3">
            <IconButton
              onClick={() => setOpen(true)}
              className="text-muted lg:hidden"
              aria-label="Apri menu"
            >
              <Menu className="h-5 w-5" />
            </IconButton>
            <div className="hidden items-center gap-2 text-sm text-muted sm:flex">
              <span>Workspace</span>
              <span>/</span>
              <span className="text-foreground">{section}</span>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <IconButton
              className="text-muted"
              onClick={() => setDark((value) => !value)}
              aria-label={dark ? "Attiva tema chiaro" : "Attiva tema scuro"}
            >
              {dark ? (
                <Sun className="h-4 w-4" />
              ) : (
                <Moon className="h-4 w-4" />
              )}
            </IconButton>
            <IconButton className="text-muted" aria-label="Aiuto">
              <CircleHelp className="h-4 w-4" />
            </IconButton>
          </div>
        </header>
        <main className="mx-auto max-w-[1500px] px-4 py-7 sm:px-8 sm:py-10">
          <Outlet />
        </main>
      </div>
      {open && (
        <Button
          variant="ghost"
          className="fixed inset-0 z-30 h-auto w-full rounded-none bg-black/20 lg:hidden"
          onClick={() => setOpen(false)}
          aria-label="Chiudi menu"
        />
      )}
    </div>
  );
}
