# AI Customer Support Platform

AI-powered customer-support SaaS. Full spec: **`.claude/SRS.md`** (canonical requirements).

## How we build this: MVP-by-MVP

> **Rule: build in small shippable MVPs, never all features at once.**
> Each MVP must run end-to-end and be demoable before the next one starts.
> Do NOT scaffold the whole SRS up front.

### MVP roadmap (proposed — confirm scope before starting each)

- **MVP 0 — Foundation**
  Align stack: Laravel + Vue 3 + Inertia/SPA, switch DB to **MySQL**, keep **`cache=database`** and `queue=database` (no Redis). Base migrations for `users`, `roles`, `permissions`. **Single-app, not multi-tenant** — core functionality only.

- **MVP 1 — Auth + RBAC + Dashboard shell**
  Login, register, email verification, password reset, 2FA, Google auth, 4 roles (Owner / Administrator / Support Agent / Viewer). Empty dashboard layout with widget placeholders. **Build core functionality, NOT a SaaS** — no org/tenant scoping, no billing.

- **MVP 2 — Customer Management**
  Customer CRUD, profile, tags, notes, customer timeline. Real dashboard widgets driven by customer data.

- **MVP 3 — Conversations + Website Live Chat**
  Conversation list/window, message types, conversation actions, floating chat widget, real-time (Laravel Reverb / WebSockets), typing indicators, read receipts.

- **MVP 4 — AI Assistant (core)**
  Provider abstraction (Gemini / OpenAI / Groq / Ollama), AI chat on conversations, system-prompt/temperature/model config, conversation memory (session), AI suggested replies.

- **MVP 5 — Knowledge Base + RAG**
  Document upload (PDF/DOCX/TXT/MD/CSV), text extraction → chunking → embeddings → vector store, semantic/keyword/hybrid search, KB categories + FAQs, version history.
  Note: MySQL has no pgvector. Decide vector storage at this MVP — MySQL 9 `VECTOR` type, embeddings as JSON with in-app cosine similarity, or a dedicated vector store.

- **MVP 8 — Production hardening** *(optional for portfolio)*
  Multi-tenant + Stripe billing only if SaaS is later desired; otherwise focus on Docker deploy, optimization, and polish.

- **MVP 6 — Function Calling + Tickets + Human Handoff**
  Tool/function calling (customer, orders, appointments, tickets, billing), ticket system, escalation triggers, agent handoff dashboard (summary, suggested reply, AI notes), long-term AI memory.

- **MVP 7 — WhatsApp + Notifications + Analytics**
  WhatsApp webhooks (in/out, templates, interactive), multi-channel notifications, analytics reports + AI insights.

## Current state (as of setup)
- Fresh Laravel skeleton (`laravel/framework: ^13.8`, PHP 8.3). **No Vue/Inertia installed yet.**
- DB = `sqlite`, cache/queue = `database`. **Target is MySQL** (cache/queue stay on `database` — no Redis) — migrate in MVP 0.
- This is a **portfolio project, single-app (not multi-tenant SaaS)**. Build core functionality first.
- Nothing from the SRS is implemented yet.

## Compliance (org policy — fintech / SAMA / PDPL)
- Never use real customer PII, IDs, IBANs, card data, or financial records in code, tests, seeds, or examples — use synthetic/masked data only.
- Treat AI output as decision-support; customer-facing, credit/risk, or regulatory content needs human review.
- Escalate compliance/data-protection ambiguity to the compliance/legal team.

## Conventions
- Match existing Laravel/Pint style. Run `vendor/bin/pint` before committing.
- Tests with PHPUnit (`php artisan test`). Add tests per MVP.
