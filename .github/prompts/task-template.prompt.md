# Task Template

**Instructions:** Use this template for complex tasks that require detailed specification. Most tasks should remain as simple checklist items in the Feature document. Only create a separate task document if the work requires extensive context, multiple sub-steps, or cross-team coordination.

---

# Task: [Task Name]

**Status:** [Not Started | In Progress | Blocked | Completed]  
**Assignee:** [Developer name]  
**Estimated Effort:** [Hours or days]  
**Parent Feature:** [Link to feature document]

## Context

*Why is this task necessary? What problem does it solve? Keep it brief (2-3 sentences).*

[Your context here]

## Acceptance Criteria

*Define clear, testable conditions that must be met for this task to be considered complete.*

- [ ] [Criterion 1: e.g., "Function returns expected output for valid input"]
- [ ] [Criterion 2: e.g., "Error handling covers edge cases X, Y, Z"]
- [ ] [Criterion 3: e.g., "Unit tests achieve 90%+ coverage"]
- [ ] [Criterion 4: e.g., "Documentation updated in relevant files"]

## Implementation Steps

*Break down the work into sequential steps. Be specific.*

1. [Step 1: e.g., "Create repository method for data access"]
2. [Step 2: e.g., "Implement service layer business logic"]
3. [Step 3: e.g., "Add controller endpoint with validation"]
4. [Step 4: e.g., "Write unit tests for happy path and error cases"]
5. [Step 5: e.g., "Update OpenAPI schema and Postman collection"]

## Technical Notes

*Any technical details, gotchas, or considerations.*

**Dependencies:**
- [Dependency 1: e.g., "Requires PR #123 to be merged first"]
- [Dependency 2: e.g., "Needs Redis configuration update in staging"]

**Design Decisions:**
- [Decision 1: e.g., "Using bcrypt over argon2 for compatibility"]
- [Decision 2: e.g., "Cache TTL set to 5 minutes based on usage patterns"]

**Edge Cases:**
- [Edge case 1: e.g., "Handle null values in optional fields"]
- [Edge case 2: e.g., "Rate limit exceeded returns 429 with Retry-After header"]

## Testing Plan

*How will this be validated?*

**Unit Tests:**
- [Test 1: e.g., "test_create_user_success()"]
- [Test 2: e.g., "test_create_user_duplicate_email()"]

**Integration Tests:**
- [Test 1: e.g., "End-to-end flow with real DB"]

**Manual Testing:**
- [Step 1: e.g., "Use Postman to verify endpoint response"]
- [Step 2: e.g., "Check logs for expected audit entries"]

## Blockers & Questions

*Track anything preventing progress or needing clarification.*

**Blockers:**
- [Blocker 1: e.g., "Waiting for API key from vendor"]

**Open Questions:**
- [Question 1: e.g., "Should we support batch operations?"]
- [Question 2: e.g., "What is the max file size limit?"]

---

## Template Usage Notes

*Delete this section before using.*

**When to create a Task document:**
- Task requires > 2 days of work
- Multiple developers involved
- Complex logic or cross-cutting concerns
- External dependencies or integrations
- High risk or critical path item

**When NOT to create a Task document:**
- Simple code changes (< 4 hours)
- Routine refactoring or bug fixes
- Standard CRUD operations
- Tasks already well-defined in Feature doc

**Where to Store:**
- Tasks: `docs/epic/{epic-name}/tasks/{task-name}.md`
- Link back to parent Feature document
- Reference in Feature's task checklist

**Maintenance:**
- Update Status as work progresses
- Mark acceptance criteria as completed
- Document decisions and blockers
- Archive or delete after completion (optional)
