# Prox Gallery Agent Guide

This file defines the code style and architecture rules to follow when working in this repository.

## Core Principles

These principles always apply:
- SOLID
- POLA (Principle of Least Astonishment)
- DRY
- KISS
- Single Source of Truth

Interpretation for this repository:
- Prefer small, composable units with one reason to change
- Keep behavior predictable and unsurprising
- Do not duplicate logic, contracts, or derived rules across layers
- Choose the simplest structure that still fits the codebase
- Every important rule or data contract should have one clear owner

## Must

### Architecture
- Must keep backend controllers thin
- Must place backend business logic in services, policies, and flows
- Must use policies for permissions, constraints, and rule decisions
- Must use flows for orchestration and multi-step lifecycle behavior
- Must keep one clear source of truth for important state, configuration, and derived rules
- Must use constructor injection on the backend
- Must keep frontend business logic in typed hooks, helpers, state modules, or controllers where practical
- Must keep presentational components focused on rendering and interaction wiring

### Style
- Must follow SOLID
- Must follow POLA
- Must follow DRY
- Must follow KISS
- Must use `declare(strict_types=1);` in PHP files
- Must prefer explicit types for important contracts
- Must keep naming aligned with the current codebase conventions
- Must use the `@/` alias for admin source imports where appropriate

### Data And Rules
- Must define each important business rule in one canonical place
- Must centralize repeated data shaping when the same contract is used in multiple places
- Must treat duplicated validation or rule evaluation as a refactor target
- Must prefer DTOs or typed helpers when associative arrays become ambiguous

### Verification
- Must run relevant verification after changes when the environment supports it
- Must prefer `composer analyse` and PHPUnit for backend-impacting work
- Must prefer `npm run build` for admin frontend work
- Must report when a useful verification step could not be run

## Must Not

### Architecture
- Must not put meaningful business logic directly in WordPress hooks if it belongs in a service
- Must not let controllers become rule engines
- Must not duplicate business rules across controllers, services, frontend state, and UI
- Must not create parallel sources of truth for the same domain data
- Must not bypass existing policies or flows with ad hoc special-case logic

### Frontend
- Must not add ad hoc fetch calls when an action controller should own transport behavior
- Must not place complex data normalization directly inside large screen components unless clearly temporary
- Must not hardcode root-relative asset paths in the Vite admin app
- Must not introduce a second visual system into the admin without an intentional redesign

### Backend
- Must not spread large untyped array contracts across many files without a clear owner
- Must not mix orchestration, policy, persistence, and presentation concerns into one class
- Must not add silent magic behavior where explicit policy or flow logic would be clearer

### General
- Must not optimize for cleverness over readability
- Must not duplicate code when a shared helper, service, policy, or flow is the correct owner
- Must not introduce surprising behavior that violates least-astonishment

## Architecture

### Backend
- Platform: WordPress plugin
- Language: PHP 8.1+
- Autoloading: PSR-4 via `Prox\ProxGallery\`
- Dependency wiring: container-based registration in `src/Bootstrap`
- Structure: modular, service-oriented backend

Use these layers consistently:
- `Controllers`: WordPress entrypoints, AJAX handlers, CLI handlers, block registration
- `Services`: business logic and orchestration
- `Models`: persistence-facing WordPress/data access objects
- `DTO`: structured transport objects when arrays become too loose
- `Contracts`: interfaces for swappable services/repositories
- `Policies`, `States`, `Support`: cross-cutting concerns

Rules:
- Keep controllers thin
- Put business logic in services
- Prefer constructor injection
- Public methods should be typed
- Use `declare(strict_types=1);`
- Avoid spreading large associative array contracts across multiple files
- Rules and decisions should be determined by policies and flows where applicable, not ad hoc controller logic
- Prefer one canonical source for configuration, derived state, and rule evaluation

### Frontend Admin
- Platform: React admin app built with Vite
- Styling: Tailwind utilities
- UI primitives: Radix/shadcn-style local components in `assets/admin/src/components/ui`
- Structure: feature-based folders under `assets/admin/src/features`
- Transport/business access: action controllers under `assets/admin/src/modules`

Use these layers consistently:
- `components/ui`: reusable dumb primitives
- `core`: shared shell and top-level layout pieces
- `features/*/components`: feature UI
- `features/*/hooks` and feature state files: feature business logic
- `lib`: shared frontend logic and config access
- `modules/*/controllers`: AJAX transport layer

Rules:
- Keep business logic out of large JSX screens where possible
- Prefer TypeScript for hooks, helpers, state, and controller-facing logic
- Keep presentational components focused on rendering and user interaction wiring
- Reuse action controllers instead of adding ad hoc fetch calls
- Use the `@/` alias for admin source imports
- Keep one source of truth for frontend state instead of duplicating derived values across components

## Naming

- Backend classes:
  - `*Controller` for entrypoints
  - `*Service` for business logic
  - `*Model` for persistence/data access
  - `*Dto` for structured payloads
  - `*Policy`, `*State`, `*Registrar`, `*Manager` when those patterns apply
- Frontend:
  - React components: PascalCase
  - hooks: `use-*` filenames or `use*` exports
  - typed business-logic files: prefer `.ts`
  - UI components may remain `.jsx`/`.tsx` depending on complexity

## Style

### PHP
- Follow the existing strict, explicit style already used in `src/`
- Prefer early returns over deep nesting
- Keep methods focused and readable
- Use docblocks only when they add shape information or clarify non-obvious contracts
- Avoid magic arrays when a DTO or typed helper would reduce ambiguity

### Frontend
- Follow the existing functional React style
- Keep imports grouped and stable
- Prefer small helper functions for repeated formatting/normalization
- Use explicit types where data contracts matter
- Do not introduce a second styling system
- Preserve the current dark/branded admin visual language unless intentionally redesigning

## Verification

Before considering work complete, prefer these checks when relevant:

### Backend
- `composer test`
- `composer analyse`
- `composer fix` when style corrections are needed

### Frontend
- `npm run build`

Current repo note:
- `typescript` is not currently installed as a local package, even though `tsconfig.json` exists
- ESLint/Prettier are not configured yet

## Guardrails

- Do not bypass the container/service structure on the backend
- Do not place business logic directly into WordPress hooks when it belongs in a service
- Do not add direct asset root paths in the Vite admin app; import assets through the module system
- Do not add duplicate data-shaping logic when a controller/service/helper already owns it
- Do not mix unrelated visual systems in the admin
- Do not duplicate rule evaluation in multiple layers when a policy, flow, or canonical service should own it
- Prefer policies for permissions, constraints, and decision rules
- Prefer flows for lifecycle orchestration and multi-step behavior
- If two places can become inconsistent, refactor toward a single source of truth

## Preferred Next-Step Safeguards

These are not installed yet, but future work should aim toward them:
- Frontend linting with ESLint
- Installed `typescript` dependency and `tsc --noEmit`
- A single project-wide check command for backend and frontend validation
- Optional readability/complexity checks once the base lint stack is in place
