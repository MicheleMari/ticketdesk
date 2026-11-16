import * as AlertDialogPrimitive from "@radix-ui/react-alert-dialog";
import * as DialogPrimitive from "@radix-ui/react-dialog";
import * as DropdownMenuPrimitive from "@radix-ui/react-dropdown-menu";
import * as SelectPrimitive from "@radix-ui/react-select";
import * as PopoverPrimitive from "@radix-ui/react-popover";
import { CalendarDays, Check, ChevronDown, X } from "lucide-react";
import { DayPicker } from "react-day-picker";
import "react-day-picker/style.css";
import {
  Children,
  forwardRef,
  type ButtonHTMLAttributes,
  type ChangeEvent,
  type ComponentPropsWithoutRef,
  type InputHTMLAttributes,
  type ReactNode,
  type SelectHTMLAttributes,
  type TextareaHTMLAttributes,
} from "react";
import { cn } from "../lib/utils";

export function Button({
  className,
  variant = "primary",
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: "primary" | "secondary" | "ghost" | "danger";
}) {
  return (
    <button
      className={cn(
        "inline-flex h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/40 disabled:pointer-events-none disabled:opacity-50",
        {
          primary: "bg-accent text-white shadow-sm hover:bg-accent-strong",
          secondary:
            "border border-border bg-surface text-foreground hover:bg-surface-subtle",
          ghost: "text-muted hover:bg-surface-subtle hover:text-foreground",
          danger: "bg-danger text-white hover:bg-danger/90",
        }[variant],
        className,
      )}
      {...props}
    />
  );
}
export function IconButton({
  className,
  variant = "ghost",
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: "primary" | "secondary" | "ghost" | "danger";
}) {
  return (
    <Button
      variant={variant}
      className={cn(
        "icon-button h-9 w-9 shrink-0 p-0 [&>svg]:h-4 [&>svg]:w-4 [&>svg]:shrink-0",
        className,
      )}
      {...props}
    />
  );
}
export function Input({
  className,
  ...props
}: InputHTMLAttributes<HTMLInputElement>) {
  return (
    <input
      className={cn(
        "h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm text-foreground outline-none transition placeholder:text-muted/70 focus:border-accent focus:ring-2 focus:ring-accent/15 disabled:cursor-not-allowed disabled:opacity-50",
        className,
      )}
      {...props}
    />
  );
}
export const Textarea = forwardRef<
  HTMLTextAreaElement,
  TextareaHTMLAttributes<HTMLTextAreaElement>
>(function Textarea({ className, ...props }, ref) {
  return (
    <textarea
      ref={ref}
      className={cn(
        "min-h-32 w-full resize-y rounded-lg border border-border bg-surface px-3 py-2.5 text-sm text-foreground outline-none transition placeholder:text-muted/70 focus:border-accent focus:ring-2 focus:ring-accent/15 disabled:cursor-not-allowed disabled:opacity-50",
        className,
      )}
      {...props}
    />
  );
});

type SelectProps = SelectHTMLAttributes<HTMLSelectElement> & {
  children: ReactNode;
  placeholder?: string;
  onValueChange?: (value: string) => void;
};
export const Select = forwardRef<HTMLButtonElement, SelectProps>(
  function Select(
    {
      className,
      children,
      value,
      defaultValue,
      onChange,
      onValueChange,
      placeholder = "Seleziona…",
      disabled,
      ...props
    },
    ref,
  ) {
    const options = Children.toArray(children);
    const currentValue = value === undefined ? defaultValue : value;
    const handleChange = (nextValue: string) => {
      const normalizedValue = nextValue === "__empty" ? "" : nextValue;
      const event = {
        target: {
          name: props.name,
          value: normalizedValue,
          valueAsNumber:
            normalizedValue === "" ? Number.NaN : Number(normalizedValue),
        },
        currentTarget: {
          name: props.name,
          value: normalizedValue,
          valueAsNumber:
            normalizedValue === "" ? Number.NaN : Number(normalizedValue),
        },
      } as unknown as ChangeEvent<HTMLSelectElement>;
      onChange?.(event);
      onValueChange?.(normalizedValue);
    };
    return (
      <SelectPrimitive.Root
        value={
          currentValue === undefined || currentValue === ""
            ? undefined
            : String(currentValue)
        }
        onValueChange={handleChange}
        disabled={disabled}
      >
        <SelectPrimitive.Trigger
          ref={ref}
          aria-label={props["aria-label"]}
          className={cn(
            "flex h-10 min-w-0 w-full overflow-hidden items-center justify-between gap-2 rounded-lg border border-border bg-surface px-3 text-sm text-foreground outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/15 disabled:cursor-not-allowed disabled:opacity-50",
            className,
          )}
        >
          <SelectPrimitive.Value
            className="min-w-0 flex-1 truncate whitespace-nowrap"
            placeholder={placeholder}
          />
          <ChevronDown className="h-4 w-4 text-muted" />
        </SelectPrimitive.Trigger>
        <SelectPrimitive.Portal>
          <SelectPrimitive.Content className="z-50 max-h-72 min-w-[var(--radix-select-trigger-width)] overflow-y-auto rounded-lg border border-border bg-surface p-1 text-foreground shadow-xl">
            <SelectPrimitive.Viewport>
              {options.map((option, index) => {
                if (
                  !option ||
                  typeof option !== "object" ||
                  !("props" in option)
                )
                  return null;
                const optionProps = option.props as {
                  value?: string | number;
                  children?: ReactNode;
                };
                return (
                  <SelectPrimitive.Item
                    key={`${String(optionProps.value)}-${index}`}
                    value={
                      optionProps.value === ""
                        ? "__empty"
                        : String(optionProps.value ?? "")
                    }
                    className="relative flex cursor-default select-none items-center rounded-md px-3 py-2 text-sm outline-none data-[highlighted]:bg-surface-subtle"
                  >
                    <SelectPrimitive.ItemText>
                      {optionProps.children}
                    </SelectPrimitive.ItemText>
                    <SelectPrimitive.ItemIndicator className="absolute right-3">
                      <Check className="h-4 w-4 text-accent" />
                    </SelectPrimitive.ItemIndicator>
                  </SelectPrimitive.Item>
                );
              })}
            </SelectPrimitive.Viewport>
          </SelectPrimitive.Content>
        </SelectPrimitive.Portal>
      </SelectPrimitive.Root>
    );
  },
);
export function Label({
  children,
  htmlFor,
  hint,
  required = false,
}: {
  children: ReactNode;
  htmlFor?: string;
  hint?: string;
  required?: boolean;
}) {
  return (
    <label
      htmlFor={htmlFor}
      className="mb-1.5 block text-sm font-medium text-foreground"
    >
      {children}
      {required && (
        <span className="ml-1 text-accent" aria-hidden="true">
          *
        </span>
      )}
      {hint && <span className="ml-1 font-normal text-muted">{hint}</span>}
    </label>
  );
}
export function Badge({
  children,
  tone = "neutral",
}: {
  children: ReactNode;
  tone?: "neutral" | "blue" | "amber" | "green" | "red" | "purple";
}) {
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold",
        {
          neutral: "bg-surface-subtle text-muted",
          blue: "bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300",
          amber:
            "bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300",
          green:
            "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300",
          red: "bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300",
          purple:
            "bg-violet-50 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300",
        }[tone],
      )}
    >
      {children}
    </span>
  );
}
export function Card({
  children,
  className,
}: {
  children: ReactNode;
  className?: string;
}) {
  return (
    <section
      className={cn(
        "rounded-xl border border-border bg-surface shadow-card",
        className,
      )}
    >
      {children}
    </section>
  );
}
export function Spinner({ className = "" }: { className?: string }) {
  return (
    <span
      className={cn(
        "h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent",
        className,
      )}
      aria-label="Caricamento"
    />
  );
}
export function EmptyState({
  icon,
  title,
  description,
  action,
}: {
  icon: ReactNode;
  title: string;
  description: string;
  action?: ReactNode;
}) {
  return (
    <div className="flex min-h-64 flex-col items-center justify-center px-6 text-center">
      <div className="mb-4 rounded-xl bg-surface-subtle p-3 text-muted">
        {icon}
      </div>
      <h3 className="text-sm font-semibold">{title}</h3>
      <p className="mt-1 max-w-sm text-sm text-muted">{description}</p>
      {action && <div className="mt-5">{action}</div>}
    </div>
  );
}
export function ErrorState({
  message = "Non è stato possibile caricare i dati.",
  retry,
}: {
  message?: string;
  retry?: () => void;
}) {
  return (
    <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-200">
      <p className="font-semibold">Qualcosa non ha funzionato</p>
      <p className="mt-1 opacity-80">{message}</p>
      {retry && (
        <Button className="mt-4" variant="secondary" onClick={retry}>
          Riprova
        </Button>
      )}
    </div>
  );
}
export function Skeleton({ className = "" }: { className?: string }) {
  return (
    <div
      className={cn("animate-pulse rounded-md bg-surface-subtle", className)}
    />
  );
}

export const Dialog = DialogPrimitive.Root;
export const DialogTrigger = DialogPrimitive.Trigger;
export function DialogContent({
  className,
  children,
}: {
  className?: string;
  children: ReactNode;
}) {
  return (
    <DialogPrimitive.Portal>
      <DialogPrimitive.Overlay className="fixed inset-0 z-50 bg-black/30 data-[state=open]:animate-fade-in" />
      <DialogPrimitive.Content
        className={cn(
          "fixed left-1/2 top-1/2 z-50 w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-xl border border-border bg-surface p-6 shadow-xl",
          className,
        )}
      >
        {children}
        <DialogPrimitive.Close asChild>
          <IconButton className="absolute right-3 top-3" aria-label="Chiudi">
            <X className="h-4 w-4" />
          </IconButton>
        </DialogPrimitive.Close>
      </DialogPrimitive.Content>
    </DialogPrimitive.Portal>
  );
}
export const AlertDialog = AlertDialogPrimitive.Root;
export const AlertDialogTrigger = AlertDialogPrimitive.Trigger;
export function AlertDialogContent({
  className,
  children,
}: {
  className?: string;
  children: ReactNode;
}) {
  return (
    <AlertDialogPrimitive.Portal>
      <AlertDialogPrimitive.Overlay className="fixed inset-0 z-50 bg-black/30" />
      <AlertDialogPrimitive.Content
        className={cn(
          "fixed left-1/2 top-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-xl border border-border bg-surface p-6 shadow-xl",
          className,
        )}
      >
        {children}
      </AlertDialogPrimitive.Content>
    </AlertDialogPrimitive.Portal>
  );
}
export const AlertDialogTitle = AlertDialogPrimitive.Title;
export const AlertDialogDescription = AlertDialogPrimitive.Description;
export const AlertDialogCancel = AlertDialogPrimitive.Cancel;
export const AlertDialogAction = AlertDialogPrimitive.Action;

export const Popover = PopoverPrimitive.Root;
export const PopoverTrigger = PopoverPrimitive.Trigger;
export function PopoverContent({
  className,
  children,
}: {
  className?: string;
  children: ReactNode;
}) {
  return (
    <PopoverPrimitive.Portal>
      <PopoverPrimitive.Content
        align="start"
        sideOffset={8}
        className={cn(
          "z-50 rounded-xl border border-border bg-surface p-3 text-foreground shadow-xl",
          className,
        )}
      >
        {children}
      </PopoverPrimitive.Content>
    </PopoverPrimitive.Portal>
  );
}

export function DatePicker({
  value,
  onChange,
  placeholder = "Seleziona una data",
  minDate,
}: {
  value?: string | null;
  onChange: (value: string | null) => void;
  placeholder?: string;
  minDate?: Date;
}) {
  const selected = value ? new Date(`${value}T00:00:00`) : undefined;
  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button
          type="button"
          variant="secondary"
          className={cn(
            "w-full justify-between font-normal",
            !selected && "text-muted",
          )}
        >
          <span>
            {selected
              ? new Intl.DateTimeFormat("it-IT", {
                  dateStyle: "medium",
                }).format(selected)
              : placeholder}
          </span>
          <CalendarDays className="h-4 w-4" />
        </Button>
      </PopoverTrigger>
      <PopoverContent>
        <DayPicker
          mode="single"
          selected={selected}
          onSelect={(date) =>
            onChange(date ? date.toISOString().slice(0, 10) : null)
          }
          disabled={minDate ? { before: minDate } : undefined}
          classNames={{
            months: "flex flex-col",
            month: "space-y-3",
            month_caption:
              "flex items-center justify-between px-1 text-sm font-semibold",
            nav: "flex items-center gap-1",
            button_previous: "h-7 w-7 rounded-md hover:bg-surface-subtle",
            button_next: "h-7 w-7 rounded-md hover:bg-surface-subtle",
            month_grid: "w-full border-collapse",
            weekdays: "flex",
            weekday:
              "w-9 rounded-md text-center text-xs font-medium text-muted",
            week: "mt-1 flex w-full",
            day: "h-9 w-9 rounded-md text-center text-sm hover:bg-surface-subtle",
            day_button: "h-9 w-9 rounded-md",
            selected: "bg-accent text-white hover:bg-accent-strong",
            today: "font-bold text-accent",
            outside: "text-muted/50",
            disabled: "pointer-events-none opacity-40",
          }}
        />
      </PopoverContent>
    </Popover>
  );
}

export function ColorPicker({
  value,
  onChange,
}: {
  value: string;
  onChange: (value: string) => void;
}) {
  const palette = [
    "#7468F5",
    "#2563EB",
    "#16A34A",
    "#EA580C",
    "#DC2626",
    "#9333EA",
    "#0891B2",
    "#CA8A04",
  ];
  return (
    <div className="space-y-3">
      <div className="grid grid-cols-8 gap-2">
        {palette.map((color) => (
          <button
            type="button"
            key={color}
            aria-label={`Scegli colore ${color}`}
            onClick={() => onChange(color)}
            className={cn(
              "h-7 w-7 rounded-full border-2 border-transparent transition hover:scale-105",
              value.toLowerCase() === color.toLowerCase() &&
                "border-foreground ring-2 ring-accent/30",
            )}
            style={{ backgroundColor: color }}
          />
        ))}
      </div>
      <Input
        value={value}
        onChange={(event) => onChange(event.target.value)}
        placeholder="#7468F5"
        pattern="^#[0-9A-Fa-f]{6}$"
        aria-label="Codice colore esadecimale"
      />
    </div>
  );
}

export const DropdownMenu = DropdownMenuPrimitive.Root;
export const DropdownMenuTrigger = DropdownMenuPrimitive.Trigger;
export function DropdownMenuContent({
  className,
  children,
}: {
  className?: string;
  children: ReactNode;
}) {
  return (
    <DropdownMenuPrimitive.Portal>
      <DropdownMenuPrimitive.Content
        align="end"
        sideOffset={6}
        className={cn(
          "z-50 min-w-48 rounded-lg border border-border bg-surface p-1 text-foreground shadow-xl",
          className,
        )}
      >
        {children}
      </DropdownMenuPrimitive.Content>
    </DropdownMenuPrimitive.Portal>
  );
}
export function DropdownMenuItem({
  className,
  children,
  ...props
}: ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.Item>) {
  return (
    <DropdownMenuPrimitive.Item
      className={cn(
        "flex cursor-default select-none items-center gap-2 rounded-md px-3 py-2 text-sm outline-none data-[highlighted]:bg-surface-subtle data-[highlighted]:text-foreground",
        className,
      )}
      {...props}
    >
      {children}
    </DropdownMenuPrimitive.Item>
  );
}
