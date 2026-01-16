# Clinica Go Project Guidelines

## 1. Core Project Documentation

*   [**Product Vision (PRODUCT.md)**](../PRODUCT.md): High-level vision, target users, and core features.
*   [**System Architecture (ARCHITECTURE.md)**](../ARCHITECTURE.md): Overall system architecture, technology stack, and design principles.
*   [**Contributing Guidelines (CONTRIBUTING.md)**](../CONTRIBUTING.md): Git workflow, code standards, and testing requirements.

## 2. Documentation Hierarchy: Epic → Feature → Task

The project uses a three-tier documentation structure to organize work from high-level scope to atomic implementation:

### Epic (Macro-Level)
**Purpose:** High-level overview of a complete functional area, spanning multiple features and weeks/months of work.  
**Template:** `.github/prompts/epic-template.prompt.md`  
**Location:** `docs/epic/{epic-name}/README.md`  
**Characteristics:**
- Technology-agnostic (describes logic, not implementation)
- Covers entire scope (e.g., Authentication & Sessions, Lead Management, Analytics)
- Includes: problem statement, objectives, success metrics, logical flows, functional/non-functional requirements, risks, test strategy
- Owner: Product/Engineering Lead
- Lifespan: Long-term reference document

**When to create an Epic:**
- New major feature area (e.g., Authentication, Conversation AI, Payment Integration)
- Cross-cutting concerns affecting multiple services
- Regulatory/compliance initiatives (e.g., LGPD compliance)
- Architectural changes with broad impact

### Feature (Implementation-Level)
**Purpose:** Specific implementation of a component or flow within an epic, with concrete tasks.  
**Template:** `.github/prompts/plan-feature-template.md`  
**Location:** `docs/epic/{epic-name}/{feature-name}.md`  
**Characteristics:**
- Implementation-specific (may reference code, APIs, schemas)
- 3-10 tasks, typically 3-7 days of work
- Includes: architecture/design, task checklist, open questions
- Owner: Developer or small team
- Lifespan: Active during implementation, archived after completion

**When to create a Feature:**
- Implementing a specific flow from an Epic (e.g., MFA Setup, Token Refresh)
- Adding a new API endpoint or service
- Database schema changes
- Integration with external service

### Task (Atomic-Level)
**Purpose:** Single, focused unit of work that can be completed in hours to 2 days.  
**Template:** `.github/prompts/task-template.prompt.md` (use sparingly)  
**Location:** `docs/epic/{epic-name}/tasks/{task-name}.md` (optional)  
**Characteristics:**
- Atomic and actionable
- Typically tracked as checklist items in Feature document
- Separate task document only if complex (>2 days, multiple developers, critical path)
- Includes: context, acceptance criteria, implementation steps, testing plan
- Owner: Individual developer
- Lifespan: Short-term, deleted/archived after completion

**When to create a Task document:**
- Task requires > 2 days of effort
- Multiple developers collaborating
- Complex logic requiring detailed specification
- High-risk or critical path item
- External dependencies or blockers

**When NOT to create a Task document:**
- Simple code changes (< 4 hours)
- Routine bug fixes or refactoring
- Standard CRUD operations
- Tasks already well-defined in parent Feature

### Hierarchy Example: Authentication Epic

```
docs/epic/authentication/
├── README.md (Epic: Authentication & Sessions)
├── mfa-setup.md (Feature: Multi-Factor Authentication Setup)
│   └── Tasks (checklist in feature doc):
│       - [ ] Create TOTP secret generation function
│       - [ ] Implement QR code generation
│       - [ ] Add backup codes storage
│       - [ ] Write unit tests
│       - [ ] Update API documentation
├── token-refresh.md (Feature: Token Refresh & Rotation)
└── tasks/ (optional, for complex tasks only)
    └── refresh-token-rotation-fix.md (Task: Fix JTI sync bug)
```

### Decision Tree: Which template to use?

```
Is it a new major functional area?
  YES → Create Epic (epic-template.prompt.md)
  NO ↓
  
Is it a specific implementation within an epic?
  YES → Create Feature (plan-feature-template.md)
  NO ↓
  
Is it a complex task requiring detailed spec?
  YES → Create Task document (task-template.prompt.md)
  NO → Add as checklist item in Feature document
```

**Important:** The `tasks/` directory is **optional and rarely used**. Most work is tracked at the Feature level. Only create task documents for:
- Tasks requiring >2 days of effort
- Multiple developers collaborating
- High-risk or critical path items
- External dependencies or blockers

For the authentication epic, **no tasks/ directory exists** because all features are granular enough (1-2 days each).

## 3. Backend Documentation (`/back/docs`)

The backend documentation is organized to mirror the source code structure.

*   [**Architecture Decisions (ADRs)**](../back/docs/architecture/decisions/): Records of key architectural decisions and their rationale.

## 4. Key Project Principles

*   **Clean Architecture (Adapted)**: The backend follows a pragmatic adaptation of Clean Architecture. Business logic is in `services`, data access in `repositories`, and HTTP handling in `controllers`.
*   **Documentation First**: New features should be documented following the Epic → Feature hierarchy before implementation.
*   **English as Standard**: All new code, documentation, and commit messages should be in English.
*   **Security and Compliance**: As a healthcare application, security (MFA, session management) and data privacy (LGPD) are critical.

## 5. 3-Step Workflow (Analyze, Execute, Verify)

To ensure completeness and prevent errors, every task must follow this workflow:

1.  **Analyze**: Before making changes, fully understand the request and the context. Identify all files, directories, and dependencies involved. Formulate a clear plan.
2.  **Execute**: Perform the planned actions, such as creating, editing, or moving files and directories.
3.  **Verify**: After execution, double-check the work. Ensure that all changes were made correctly and that no artifacts (like old files or empty directories) were left behind. Confirm that the final state matches the initial goal.

*Suggest to update these documents if you find any incomplete or conflicting information during your work.*
