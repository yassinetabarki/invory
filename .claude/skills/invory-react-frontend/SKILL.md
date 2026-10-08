---
name: invory-react-frontend
description: Invory frontend conventions for React 19 + TypeScript + Inertia 3 + Wayfinder + Tailwind 4 + shadcn/ui (Laravel React starter kit, Vite+ toolchain) — page/component structure, the props contract in types/domain.ts, forms and validation errors, decimal formatting without float maths, accessibility, React Compiler rules, and Vitest + React Testing Library test patterns. Use when writing or reviewing anything in resources/js or resources/css.
---

# Invory — React frontend conventions

Read `docs/ARCHITECTURE.md` (sections 6–8) and the `invory-costing-domain` skill first.
When Laravel Boost is installed, its `inertia-react-development`, `wayfinder-development` and `tailwindcss-development` skills
and the `search-docs` / `browser-logs` MCP tools are available. **Check Inertia 3 APIs there before using anything you're unsure of.**

## Golden rules

1. **Use the starter kit's building blocks.** Use the existing layouts (`AppLayout` with breadcrumbs), shadcn components in `components/ui`
   (add new ones with `npx shadcn@latest add <name>`, never hand-write or edit them), `cn()` from `lib/utils`, lucide icons, and sonner toasts.
2. **Pages are thin.** A page in `pages/` reads typed props, renders `<Head title>`, and composes components from `components/<module>/`.
   Business UI logic lives in components and hooks that can be tested without Inertia.
3. **Types come from the contract.** Import entity types from `@/types/domain`. Never use `any`. Type page props explicitly.
   If the backend sends something that isn't in `domain.ts`, stop and report it. Don't invent types.
4. **Routes come from Wayfinder.** Import from `@/routes/...` (named routes) or `@/actions/App/Http/Controllers/...` (controller actions).
   **Never hard-code URLs.** Generated folders (`actions/`, `routes/`, `wayfinder/`) are never edited.
5. **Never compute money or capacity.** Decimals arrive as strings (`Decimal = string`). Only *format* them with `lib/format.ts`. Never use `Number(a) * Number(b)`.
6. **React Compiler is on.** Write pure components following the Rules of React. Don't add `useMemo`/`useCallback`/`memo` unless profiling shows a need.
   Never mutate props or state. Derive values during render instead of syncing them with `useEffect`.
7. **React 19 idioms.** Pass `ref` as a normal prop (no `forwardRef`). Use one component per file, named exports for components, and default export only for pages.
8. **Accessibility (WCAG 2.2 AA).** Every input needs a `<Label htmlFor>`. Errors are linked with `aria-describedby` and `aria-invalid`.
   Icon-only buttons need an `aria-label`. Never convey status by colour alone (badge text plus colour). Keep focus visible and the UI keyboard-operable.
   Tables use `<th scope>`.
9. **Responsive.** Tables collapse to stacked cards below `md`. Forms are single-column on mobile.
10. **No business data in `localStorage`.** The only persisted UI state is preferences such as appearance, which the starter kit already handles.

## Structure
```
pages/materials/index.tsx            → <MaterialsTable>, <FiltersBar>, <Pagination>
pages/products/show.tsx              → <BomTable>, <CostBreakdownCard>, <CapacityBadge>
pages/products/{create,edit}.tsx     → <ProductForm> (contains <BomEditor>)
components/shared/money.tsx          → <Money value={"35.7540"} />   (reads currency/locale from shared props)
components/shared/quantity.tsx       → <Quantity value={"1.9800"} unit="m" />
lib/format.ts                        → formatMoney / formatQuantity / formatPercent (pure, unit-tested)
types/domain.ts                      → contract (owned by backend-developer, read-only for you unless the tech-lead asks)
```
File names are kebab-case and components are PascalCase. Tests sit next to the file as `*.test.tsx`.

## Formatting decimals without floats
`lib/format.ts` exists (S-00). Use it, don't re-implement it. Intl formats numeric **strings** exactly, with no float conversion.
```ts
formatMoney(value, { currency, locale, precise? })   // currency's own digits: "35.7540" → "TND 35.754" (NBSP) / "€35.75" / "¥36"
                                                     // precise + 0 < |v| < one minor unit → 4 digits: TND "0.0004" → "TND 0.0004", EUR "0.0040" → "€0.0040"
// Default currency is TND (server `app.currency`, fallback in useAppSettings). Never hard-code fraction digits for money.
formatQuantity(value, { locale, unit? })              // "1.9800", unit 'm' → "1.98 m" (≤ 4 digits, trailing zeros dropped)
formatPercent(value, locale)                          // "10.00" → "10%" (already percent points, never divided)
// All accept Decimal | null | undefined and return EMPTY_VALUE ('—') for null/empty/malformed input.
```
In components, use `<Money value precise? />` and `<Quantity value unit />` (`components/shared`). They read `currency`/`locale` via `useAppSettings()`.
Page-level tests mock Inertia with `resources/js/test/inertia.tsx` (`createInertiaModule`, `setPageProps`, `resetInertiaMock`, `inertiaMock.router`). Its header shows how to use it.

## Forms
- **Simple forms** (material): Inertia `<Form {...MaterialController.store.form()}>` with a render-prop `{({ errors, processing }) => …}`.
- **Dynamic forms** (product + BOM editor): `useForm<ProductFormData>()` and `form.submit(ProductController.update(product.id))` (Wayfinder object).
  BOM rows live in `form.data.components`. Use stable row keys (a client-generated `uid`, never the array index).
  Nested errors arrive as `errors['components.0.quantity']`. Show them on the matching row.
- Decimal inputs: `<Input type="text" inputMode="decimal">` holding **string** state. Don't use `type="number"`, because it coerces and breaks locales.
  Show the material's unit as an input suffix (`m`, `pc`…).
- The material picker in the BOM editor is a searchable combobox (shadcn `Command` + `Popover`). It disables materials already used on another row.
- Disable submit while `processing`. Success and error feedback is **automatic**: the backend calls `Inertia::flash('toast', …)`, and the
  starter kit's `<Toaster>` (`components/ui/sonner.tsx` + `hooks/use-flash-toast.ts`) shows it. Don't add flash props or your own toast plumbing.
- Destructive actions use the shared `<ConfirmDeleteDialog>` (shadcn `AlertDialog`) and `router.delete(...)`.

## Lists
- Filters (search, category, low stock) are controlled inputs synced to the URL with `router.get(index.url(), params, { preserveState: true, replace: true })`.
  Search input changes are debounced by 300 ms.
- Always render loading/empty states: `<EmptyState>` with a call to action ("Add your first material").
- Products list columns: SKU, Name, Category, Status, **Unit cost**, **Can produce** (`<CapacityBadge>`: 0 → destructive "Out of stock",
  `null` → muted "No composition", otherwise the number) and **Bottleneck** (link to the material).

## Testing (Vitest via Vite+, React Testing Library, jsdom)
- Run with `npx vp test` (one-off) or `npx vp test watch`. Import test APIs from **`vite-plus/test`**, not `vitest`.
- Setup (S-00): devDependencies `jsdom @testing-library/react @testing-library/user-event @testing-library/jest-dom`; in `vite.config.ts`
  add `test: { environment: 'jsdom', setupFiles: ['resources/js/test/setup.ts'], include: ['resources/js/**/*.test.{ts,tsx}'] }`;
  in `setup.ts`: `import * as matchers from '@testing-library/jest-dom/matchers'; import { expect, afterEach } from 'vite-plus/test'; import { cleanup } from '@testing-library/react'; expect.extend(matchers); afterEach(cleanup);`
- Wayfinder files (`resources/js/actions|routes|wayfinder`, git-ignored) must exist before tests and `types:check`. Generate them with `npm run build`
  (or `npm run dev`), which uses the starter kit's `formVariants: true` config. **If you run the artisan command by hand, pass `--with-form`**
  (`php artisan wayfinder:generate --with-form`). Without it the `.form()` helpers are missing and `types:check` fails on every page that uses `<Form>`.
  After adding or renaming a route, regenerate before running `types:check`.
- Query **by role and label** (`getByRole('button', { name: /add material/i })`). Avoid test IDs unless there's no accessible handle.
  Interact with `userEvent.setup()`. Assert what the user sees.
- Mock Inertia only at the boundary, in a shared helper `resources/js/test/inertia.tsx`
  (`vi.mock('@inertiajs/react', …)` keeping real exports, stubbing `router`, `usePage`, `Head`, `Link`).
- Minimum coverage per story: formatters (including the worked example → `TND 35.754` with the default currency and `€35.75` with EUR), every component with branching
  (`CapacityBadge` states, `StockBadge`), and the **BomEditor** (add row, remove row, prevent duplicate material, show nested error, unit suffix).

```tsx
import { describe, expect, it } from 'vite-plus/test';
import { render, screen } from '@testing-library/react';
import { CapacityBadge } from './capacity-badge';

describe('CapacityBadge', () => {
    it('flags products that cannot be produced', () => {
        render(<CapacityBadge value={0} />);
        expect(screen.getByText(/out of stock/i)).toBeInTheDocument();
    });
    it('explains missing composition', () => {
        render(<CapacityBadge value={null} />);
        expect(screen.getByText(/no composition/i)).toBeInTheDocument();
    });
});
```

## Commands
```
npm run dev                 # Vite dev server, native (the app itself runs in Sail: `.\sail up -d` → http://localhost)
npx vp test                 # Vitest
npm run check               # oxfmt + oxlint (type-aware, warnings = errors)
npm run check:fix           # auto-fix format/lint
npm run types:check         # tsc --noEmit
npm run build               # must pass before handoff
```

## Pre-handoff checklist
- [ ] Pages typed with props from `@/types/domain`; no `any`, no hard-coded URLs
- [ ] No arithmetic on money or quantities; all display goes through `lib/format.ts` / `<Money>` / `<Quantity>`
- [ ] Empty, loading, error and validation states handled; flash toasts work
- [ ] Keyboard and screen-reader pass: labels, focus order, aria on errors, icon buttons labelled
- [ ] Works at 375 px wide and in dark mode (starter kit appearance toggle)
- [ ] Sidebar nav updated for new sections
- [ ] Tests added; `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` all green, with output pasted in the handoff
