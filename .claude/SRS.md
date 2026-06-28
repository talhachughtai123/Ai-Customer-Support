# AI Customer Support Platform — Software Requirements Specification (SRS)

> Version 1.0 · Author: Talha Javed
> Stack (target): Laravel, Vue 3, PostgreSQL + pgvector, Redis, Docker, AI APIs (Gemini / OpenAI / Groq / Ollama)
> This is the canonical source spec. See `.claude/CLAUDE.md` for how we build it (MVP-by-MVP).

## 1. Project Overview
A SaaS application that automates customer support using LLMs across multiple channels (website live chat, WhatsApp). It answers questions, retrieves business data, creates tickets, and hands off to human agents when needed. Built on modern AI concepts: RAG, Function Calling, Conversation Memory, Knowledge Base Search, Human Handoff, AI Analytics.

## 2. Objectives
- Reduce support workload · instant responses · higher CSAT · automate repetitive tasks · AI + human collaboration · production-ready AI integration.

## 3. User Roles
- **Owner** — manage org, billing, AI config, invite team, view analytics
- **Administrator** — manage customers, conversations, assign agents, configure knowledge base
- **Support Agent** — reply to conversations, handle escalations, manage tickets
- **Viewer** — read-only

## 4. Authentication
Login, Registration, Email Verification, Forgot/Reset Password, 2FA, Google Auth, RBAC.

## 5. Dashboard
Widgets: Total/Open/Closed Conversations, AI & Human Resolution Rate, CSAT, Active Agents, Total Customers, Avg Response Time, Messages Today.
Charts: Daily Messages, Monthly Conversations, AI Usage, Ticket Status, CSAT, Escalation Rate.

## 6. Customer Management
Fields: Full Name, Email, Phone, Profile Picture, Preferred Language, Country, Tags, Registration Date, Total Conversations, Total Orders, Notes.
Timeline: Website Visits, Chat History, Orders, Tickets, AI Conversations, Human Conversations.

## 7. Conversation Module
Lists: Open, Waiting, Assigned, Closed, Spam.
Window supports: Text, Images, Videos, Documents, Voice Messages, Emojis, Reactions.
Actions: Assign Agent, Transfer, Internal Note, Tags, Close, Reopen, Merge, Delete, Convert to Ticket.

## 8. Website Live Chat
Floating widget, Custom Branding, Dark Mode, Typing Indicator, Read Receipts, Emoji Picker, Attachments, Offline Message, Business Hours.
Customization: Widget Color, Logo, Welcome Message, Position, Language.

## 9. WhatsApp Integration
Receive/Send Messages (Images, Videos, Documents, Voice Notes), Interactive Buttons, Lists, Quick Replies, Message Templates.
Webhooks: Incoming Messages, Delivery Status, Read Receipts, Message Failures.

## 10. AI Assistant
Capabilities: Answer FAQs, understand context, remember messages, detect language, translate, summarize, collect info, book appointments, create tickets, escalate, search knowledge base.
Config: System Prompt, Temperature, Max Tokens, Model Selection, Provider Selection.
Providers: Google Gemini, OpenAI, Groq, Ollama.

## 11. Retrieval-Augmented Generation (RAG)
Docs: PDF, DOCX, TXT, Markdown, CSV.
Pipeline: Upload → Extract Text → Chunk → Embeddings → Vector DB → Semantic Search → AI Response.
Search types: Semantic, Keyword, Hybrid.

## 12. Function Calling
- Customer: Get Info, Update
- Orders: Track, Cancel, Refund Status
- Appointments: Book, Reschedule, Cancel
- Support: Create/Update/Assign Ticket
- Auth: Reset Password, Verify Account
- Billing: Subscription Status, Payment History

## 13. Knowledge Base
Features: Categories, FAQs, Website Import, File Upload, AI Search, Version History.
Categories: Shipping, Refund, Payments, Products, Account, Technical Support.

## 14. Human Handoff
Escalation triggers: AI confidence below threshold, customer requests human, payment issues, refund requests, angry-customer detection, complex queries.
Agent dashboard: Conversation Summary, Suggested Reply, Customer Profile, AI Notes.

## 15. Ticket System
Fields: Ticket Number, Subject, Category, Priority, Assigned Agent, Status, Due Date.
Priorities: Low, Medium, High, Critical.
Statuses: Open, Pending, In Progress, Resolved, Closed.

## 16. AI Suggested Replies
Professional / Friendly / Short / Detailed reply variants. One-click insertion into the editor.

## 17. Analytics
Reports: Total Conversations, AI Resolved, Human Resolved, Escalated, Avg Response Time, CSAT, Agent Performance, Most Common Questions, Peak Support Hours.
AI Insights (e.g. "Shipping questions increased by 28% this week.").

## 18. Notifications
Channels: Email, Browser, Desktop, Slack, WhatsApp, Push.
Triggers: New Conversation, New Ticket, Escalation, Assignment, Mention, AI Failure.

## 19. AI Memory
Remember: Customer Name, Language, Purchase History, Previous Issues, Preferences.
Types: Session Memory, Long-Term Memory.

## 20. Integrations
Communication: WhatsApp, Live Chat, Email (SMTP, Gmail). Payments: Stripe. CRM: Custom CRM API. Storage: Local.
Future: Shopify, WooCommerce, Slack, Telegram.

## 21. Admin Panel
Manage: Users, Teams, Roles, AI Settings, Knowledge Base, Billing, Integrations, Activity Logs, API Keys, Webhooks.

## 22. Database — Core Tables
users, roles, permissions, customers, conversations, messages, conversation_assignments, tickets, ticket_replies, knowledge_documents, knowledge_chunks, embeddings, ai_prompts, ai_memories, integrations, notifications, analytics, activity_logs.

## 23. API — REST Endpoints
- Auth: `POST /login`, `POST /register`, `POST /logout`
- Customers: `GET /customers`, `POST /customers`, `PUT /customers/{id}`
- Conversations: `GET /conversations`, `POST /messages`, `POST /assign`
- Tickets: `GET /tickets`, `POST /tickets`
- AI: `POST /ai/chat`, `POST /ai/function`, `POST /ai/search`
- Webhooks: `POST /webhooks/whatsapp`, `POST /webhooks/chat`

## 24. System Architecture
Client → Laravel API → Queue → AI Service → Knowledge Base → PostgreSQL + pgvector → Redis → External APIs.

## 25. Future Enhancements
Voice AI (STT/TTS), Multi-Agent AI, CRM Sync, AI Workflow Builder, Custom AI Agents, Mobile Apps, Multi-Tenant SaaS, Subscription Billing, Plugin Marketplace.

## 26. Development Roadmap (SRS original phases)
- **Phase 1** — Auth, Dashboard, Customer Management, Conversation Module, Website Chat
- **Phase 2** — AI Chat, Knowledge Base, RAG, AI Memory, Function Calling
- **Phase 3** — WhatsApp, Human Handoff, Ticket System, Analytics, Notifications
- **Phase 4** — Multi-Tenant, Billing, Team Management, API Integrations, Production Deployment
