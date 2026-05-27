# Implementation Plan (Option 1 + New Builder Folder + Copy Blocks/Configs)

I’m going to follow the decisions you just locked in (empty-string text fields, shared invoice/quote line items, workspace logo default with per-entity override) and **Option 1**: enforce at the app layer first, add DB constraints later.

You also clarified an important constraint: **we are NOT refactoring the old builder in place**. We will **build a new builder in its own folder**, **copy** the existing blocks/configs into it, stabilize it, then delete the old builder.

---

## Milestone 1 — Establish the new Builder “domain” (no behavior change yet)
- **Create new folder** (separate from quotes):
  - `resources/js/components/builder/`
    - `BuilderShell.vue` (or `DocumentBuilder.vue`)
    - `canvas/`, `inspector/`, `toolbar/`
    - `blocks/` (copied blocks live here)
    - `configs/` (copied configs live here)
  - `resources/js/types/builder.ts` (canonical TS types for the new builder)
- **Copy** (don’t move yet):
  - Existing builder blocks/components/configs into `components/builder/blocks` + `components/builder/configs`
- **Goal**: new folder compiles, but nothing in the app uses it yet.

Deliverable: you can import the new builder component somewhere without runtime errors (even if it renders a placeholder).

---

## Milestone 2 — Canonical payload normalization (backend + frontend types)
### Backend (read-path first)
- Update existing payload builders (`QuoteService::toBuilderPayload()`, `QuoteTemplateService::toBuilderPayload()`) to guarantee:
  - `cover_message`, `terms`, `notes` are always `''` (never `null`)
  - any other free-text fields follow the same rule
- Add a small shared helper (service-level) for normalization.

### Frontend (types + event payloads)
- Update new builder TS types so these fields are plain `string`.
- Update copied block components/events accordingly (e.g. `value: string` not `string | null`).

Deliverable: the “missing cover message/notes” class of hidden errors should be gone *even before the new builder is fully wired*.

---

## Milestone 3 — New Builder architecture (registry + actions) using copied blocks
- Implement a **block registry** in the new builder folder:
  - maps `type -> renderer + inspector + defaults + normalize`
- Add a single state/actions composable:
  - `useBuilderState()` (or similar)
  - all mutations go through actions (update block config, move block, add line item, etc.)
- The new builder should render a document by iterating layout blocks and resolving via registry (not a hardcoded switch scattered around).

Deliverable: new builder can load a payload and render blocks using the copied block components (even if some features like template-apply are still pending).

---

## Milestone 4 — Wire the app to the new Builder (behind a switch)
- Update one entrypoint page to use the new builder (likely `resources/js/pages/quotes/Create.vue` / edit page):
  - add a **feature switch** (config flag, env, or even temporary query param) so you can toggle old vs new safely during dev.
- Ensure the new builder supports the same core interactions you have today:
  - drag/reorder blocks
  - quick add line item
  - edit line item
  - update cover message / notes / terms
  - save draft

Deliverable: you can create/edit a quote using the new builder end-to-end while the old builder still exists as fallback.

---

## Milestone 5 — Branding logo override (workspace default + entity override)
- Backend payload exposes something like:
  - `branding.logo_url` resolved as:
    - entity override if uploaded
    - else workspace branding logo
- Frontend blocks that render logos read a single canonical field.

Deliverable: “custom logo everywhere” works for quotes/templates/invoices with the same logic.

---

## Milestone 6 — Stabilize, test, then delete the old builder
### Tests (minimum but meaningful)
- Backend feature tests:
  - assert builder payload returns `cover_message/terms/notes` as `string` and never `null`
- Frontend/unit (if you have infra):
  - test `normalize()` or registry defaults fill missing keys
- Once stable:
  - remove usages/imports of `resources/js/components/quotes/builder/*`
  - delete the old builder folder + any dead configs/blocks no longer referenced

Deliverable: old builder removed with confidence.

---

# Quick confirmation (so I don’t pick the wrong paths)
Before I start implementing: do you want the new builder root to be exactly:
- `resources/js/components/builder/...`

or do you prefer:
- `resources/js/builder/...` (domain folder) + thin wrapper component in `components/`

You said “builder folder component”, so I’m leaning `resources/js/components/builder` unless you tell me otherwise.

---

# Status
- **Implementation plan created** (matches Option 1 + new builder-only approach + copy blocks/configs first).
- Next step once you confirm the target folder: I’ll start Milestone 1 and then Milestone 2 (normalization + types) so we eliminate the null-text bugs early.