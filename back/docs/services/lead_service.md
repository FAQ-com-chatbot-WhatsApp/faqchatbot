---
title: Lead Service
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Lead Service (`lead_service.py`)

## Brief description of the requirements and goals of the feature
This service contains all the business logic for managing leads. It handles the entire lifecycle of a lead, from its creation to its conversion or loss. Responsibilities include creating, updating, assigning, and tracking the status and maturity of leads.

## Architecture and design
The `LeadService` acts as an abstraction layer over the `LeadRepository`, enforcing business rules and orchestrating lead-related operations.

- **CRUD and State Management**: Provides methods to create, update, and manage the state of leads.
    - `create_from_conversation`: Creates a new lead, typically when a new conversation starts.
    - `update_maturity`: Updates the lead's score, which is a key metric for tracking their engagement level.
    - `convert` / `mark_lost`: Manages the final status of a lead.
    - `soft_delete` / `restore`: Implements a soft-delete pattern, allowing leads to be hidden without being permanently removed from the database.

- **Assignment Logic**:
    - `assign_to_user`: Manually assigns a lead to a specific user (e.g., a secretary).
    - `auto_assign_lead`: Implements an automatic assignment strategy. It finds available secretaries and uses a simple load-balancing approach (round-robin based on the number of currently active leads) to distribute new leads evenly.

- **Querying and Filtering**:
    - `list_leads`: A flexible method to retrieve a list of leads with various filters, such as status, assigned user, and minimum score. This is crucial for the application's dashboard and reporting features.
    - `get_unassigned_leads`: A specific query to find leads that need to be assigned.

## Tasks
- [x] Create new leads from conversations.
- [x] Update the maturity score of a lead.
- [x] Manually assign a lead to a user.
- [x] Automatically assign leads to available users using a load-balancing strategy.
- [x] Mark leads as converted or lost.
- [x] Provide a flexible method for listing and filtering leads.
- [x] Implement soft-delete and restore functionality for leads.

## Open questions
1. Is the current round-robin logic for `auto_assign_lead` sufficient, or should a more complex assignment strategy be considered (e.g., based on user skills or availability)?
2. Should there be a mechanism to automatically mark leads as "lost" if they are inactive for a certain period?
3. What are the specific criteria that determine the different lead statuses (`ENGAGED`, `INTERESTED`, etc.), and is this logic handled elsewhere?
