# ADR-0001 — Stack: Laravel 13 + Inertia 3 + React 19 (official starter kit)

- **Status:** Accepted · 2026-10-07
- **Deciders:** product owner, tech-lead

## Context
The brief asked for "Laravel LTS + React 19 + the Laravel React starter kit + Tailwind". Laravel has not shipped LTS releases since
6.x. Every major gets 18 months of bug fixes and 2 years of security fixes. Laravel 13 (released 2026-03-17) is the current major,
with bug fixes until Q3 2027 and security fixes until Q1 2028, and it requires PHP ≥ 8.3. Laravel 12's bug-fix window has already ended.

The official React starter kit ships Inertia 3, React 19, TypeScript, Tailwind 4, shadcn/ui, Wayfinder, Fortify auth, the React Compiler
and the Vite+ toolchain (Oxlint, Oxfmt, Vitest).

## Decision
- Use **Laravel 13** (PHP 8.3 – 8.5 supported), scaffolded with `laravel new --react --pest` (no teams, no WorkOS).
- Use **Inertia** (server-side routing, controllers return pages) instead of a separate SPA + REST API for the MVP.
- Use **Pest 4** for PHP tests and **Vitest** (through Vite+) + React Testing Library for UI tests.
- Install **Laravel Boost** for AI-agent guidelines, skills and the docs/DB MCP server.

## Consequences
- ✅ Auth, layouts, UI kit, typed routes and tooling come out of the box. One deployable. No CORS or token auth.
- ✅ Thin controllers and Actions mean a `/api/v1` (Sanctum) can be added later without rewriting business logic.
- ⚠️ Plan the upgrade to Laravel 14 within ~12 months (around Q3 2027) to stay inside the bug-fix window.
- ⚠️ Backend and frontend are coupled through Inertia props, so the contract (`types/domain.ts`) must be maintained and tested.
