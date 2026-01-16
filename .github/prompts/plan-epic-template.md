# Epic Template

**Instructions:** Use this template to document epics in `docs/epic/{epic-name}/README.md`. Replace all placeholders `[...]` with actual content. Remove instructional text (italic) before publishing.

---

# Epic: [Epic Name]

## Overview

**Status:** [Draft | In Review | Approved | Implemented]  
**Version:** [1.0]  
**Owner:** [Team or person responsible]

### Problem Statement

*Describe the business or technical problem this epic addresses. What pain point does it solve? Why is this necessary? Keep it concise (2-4 sentences).*

[Your problem statement here]

### Objectives

*List 3-6 clear, measurable objectives this epic aims to achieve. Use bullet points. Focus on outcomes, not implementation details.*

- [Objective 1]
- [Objective 2]
- [Objective 3]

### Success Metrics

*Define how success will be measured. Include quantifiable metrics when possible (%, time, count, rate).*

- [Metric 1: e.g., "API response time < 200ms"]
- [Metric 2: e.g., "User adoption > 80%"]
- [Metric 3: e.g., "Zero security incidents"]

### Scope

**In Scope:**  
*List what IS included in this epic. Be specific about features, flows, or components.*

- [Item 1]
- [Item 2]
- [Item 3]

**Out of Scope:**  
*List what is explicitly NOT included. This prevents scope creep and clarifies boundaries.*

- [Item 1]
- [Item 2]

---

## Architecture & Logical Flows

*Describe the main logical flows in technology-agnostic terms. Each flow should answer: What happens? In what order? What are the inputs, process steps, outputs, and safeguards?*

*Template for each flow:*

### [Flow Name]

**Input:** [What data/state is required to start this flow]

**Process:**  
*List sequential steps in plain language, avoiding code-specific references. Use numbered lists.*

1. [Step 1]
2. [Step 2]
3. [Step 3]

**Output:** [What is returned or changed at the end]

**Safeguards:** [Security measures, validations, or protections applied]

---

*Repeat the above template for each major flow (typically 5-12 flows per epic).*

---

## Functional Requirements

*List specific functional capabilities grouped by category. Use subsections to organize related features. Be clear and concise.*

### [Category 1]
- [Requirement 1]
- [Requirement 2]
- [Requirement 3]

### [Category 2]
- [Requirement 1]
- [Requirement 2]

### [Category 3]
- [Requirement 1]
- [Requirement 2]

---

## Non-Functional Requirements

*Define quality attributes and constraints. Organize by standard categories.*

### Security
*Authentication, authorization, encryption, data protection.*

- [Security requirement 1]
- [Security requirement 2]

### Performance
*Response times, throughput, resource usage, scalability.*

- [Performance requirement 1: e.g., "API response < 200ms p99"]
- [Performance requirement 2]

### Compliance
*Legal, regulatory, or industry standards (LGPD, GDPR, HIPAA, etc.).*

- [Compliance requirement 1]
- [Compliance requirement 2]

### Reliability
*Availability, fault tolerance, recovery.*

- [Reliability requirement 1]
- [Reliability requirement 2]

### Auditability
*Logging, monitoring, traceability.*

- [Audit requirement 1]
- [Audit requirement 2]

---

## Known Risks & Gaps

*Document known issues, technical debt, or limitations that exist or could arise. Each risk/gap should include: description, impact, and mitigation strategy.*

### [Risk/Gap Name 1]
**Gap/Risk:** [Describe the issue or limitation]  
**Impact:** [What happens if this is not addressed]  
**Mitigation:** [How to reduce or eliminate the risk]

### [Risk/Gap Name 2]
**Gap/Risk:** [Describe the issue]  
**Impact:** [Consequences]  
**Mitigation:** [Proposed solution or workaround]

*Add 3-7 risks/gaps typically. If none known, state "No known risks at this time."*

---

## Test & Validation Strategy

*Define how the epic will be validated. Include critical scenarios, test data requirements, and rollback plans.*

### Critical Scenarios

*List 5-10 key test scenarios that must pass for the epic to be considered complete.*

1. [Scenario 1: e.g., "User completes happy path flow successfully"]
2. [Scenario 2: e.g., "System handles invalid input gracefully"]
3. [Scenario 3: e.g., "Concurrent operations do not cause race conditions"]

### Test Data Requirements

*Describe the types of test data needed.*

- [Data type 1: e.g., "Valid users with verified emails"]
- [Data type 2: e.g., "Invalid credentials for negative testing"]
- [Data type 3: e.g., "Edge cases (max length, special chars)"]

### Rollback Plan

*How to undo changes if deployment fails or issues arise.*

- [Rollback step 1]
- [Rollback step 2]
- [Rollback step 3]

---

## Open Questions & Decisions

*Track unresolved questions or pending decisions that need stakeholder input.*

1. [Question 1: e.g., "Should feature X be mandatory or optional?"]
2. [Question 2: e.g., "What is the acceptable performance threshold?"]
3. [Question 3: e.g., "Which third-party service should we use?"]

*Remove this section once all questions are answered, or move answers to relevant sections.*

---

## Next Steps

*Define immediate actions needed to move the epic forward.*

1. [Action 1: e.g., "Review with security team"]
2. [Action 2: e.g., "Create feature specifications for sub-components"]
3. [Action 3: e.g., "Update API documentation"]
4. [Action 4: e.g., "Record architectural decisions in ADR"]
5. [Action 5: e.g., "Begin implementation of Phase 1"]

---

## Template Usage Notes

*Delete this section before publishing the epic.*

**Epic vs Feature:**
- **Epic (this document):** High-level overview, covers entire scope, technology-agnostic, outlines flows and requirements.
- **Feature (separate docs):** Detailed specification for a single component or flow, may include code-level details, API contracts, schemas.

**Where to Store:**
- Epic: `docs/epic/{epic-name}/README.md`
- Features: `docs/epic/{epic-name}/{feature-name}.md`

**Related Documents:**
- PRODUCT.md: Business context and user stories
- ARCHITECTURE.md: System-wide technical architecture
- ADRs (`docs/architecture/decisions/`): Record of architectural decisions
- API Docs: OpenAPI/Postman for endpoint contracts

**Maintenance:**
- Update Status field as epic progresses
- Increment Version on major changes
- Keep "Open Questions" section current
- Link to related ADRs when decisions are formalized
