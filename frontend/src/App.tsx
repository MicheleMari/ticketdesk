import { BrowserRouter, Navigate, Route, Routes } from "react-router-dom";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { Toaster } from "sonner";
import { AuthProvider } from "./features/auth/AuthContext";
import { LoginPage } from "./features/auth/LoginPage";
import { CategoriesPage } from "./features/categories/CategoriesPage";
import { TicketDetailPage } from "./features/tickets/TicketDetailPage";
import { TicketFormPage } from "./features/tickets/TicketFormPage";
import { TicketsPage } from "./features/tickets/TicketsPage";
import { ProtectedLayout } from "./components/layout/AppShell";

const queryClient = new QueryClient({
  defaultOptions: { queries: { retry: 1, staleTime: 15_000 } },
});

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <BrowserRouter>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route element={<ProtectedLayout />}>
              <Route path="/tickets" element={<TicketsPage />} />
              <Route path="/tickets/new" element={<TicketFormPage />} />
              <Route path="/tickets/:id" element={<TicketDetailPage />} />
              <Route path="/tickets/:id/edit" element={<TicketFormPage />} />
              <Route path="/categories" element={<CategoriesPage />} />
            </Route>
            <Route path="*" element={<Navigate to="/tickets" replace />} />
          </Routes>
        </BrowserRouter>
        <Toaster position="bottom-right" richColors closeButton />
      </AuthProvider>
    </QueryClientProvider>
  );
}
