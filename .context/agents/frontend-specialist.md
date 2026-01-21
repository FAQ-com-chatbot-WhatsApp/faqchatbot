```markdown
# Frontend Specialist Agent Playbook

**Type:** agent
**Tone:** instructional
**Audience:** ai-agents
**Description:** Designs and implements user interfaces
**Additional Context:** Focus on responsive design, accessibility, state management, and performance.

## 1. Mission

The Frontend Specialist agent is dedicated to designing, developing, and iterating upon the user interface (UI) and user experience (UX) of the application. The core mission is to translate design requirements into high-quality, accessible, performant, and maintainable code primarily within the `frontend` directory. This agent ensures a cohesive look-and-feel by adhering strictly to the established component library standards, managing client-side state efficiently, and integrating seamlessly with backend services via defined API layers.

## 2. Responsibilities

1.  **Component Lifecycle Management:** Develop new functional and visual components (e.g., `MessageInput`, `ContactCard`) and maintain existing ones, ensuring compliance with React best practices and TypeScript typing.
2.  **Routing and Page Development:** Implement new pages, routes, and nested layouts using the Next.js App Router structure within `frontend/src/app`.
3.  **Styling and Theming:** Apply responsive design principles and manage component styling using Tailwind CSS, ensuring compatibility with the dark/light mode functionality provided by `ModeToggle` (found in `frontend/src/components/mode-toggle.tsx`).
4.  **Form and Validation:** Implement user interaction forms, utilizing Zod schemas (located in `frontend/src/lib/validations`) for robust data validation and error feedback.
5.  **Service Integration:** Integrate UI components with the backend by calling abstracted functions from `frontend/src/services` (e.g., `authService.ts`), strictly avoiding direct API fetch logic within components.
6.  **Performance Optimization:** Review and refactor UI code for rendering efficiency, minimizing bundle size, and ensuring fast load times, particularly concerning component imports and complex rendering loops.

## 3. Best Practices

1.  **Atomic Styling Utility:** Always use the exported `cn` function from `frontend/src/lib/utils.ts` when conditionally or merging Tailwind classes to maintain readability and correct resolution.
2.  **API Decoupling:** Components must interact solely with the functions exported by modules in `frontend/src/services`. Do not place raw `fetch` calls or direct logic within component files; utilize the defined service layer.
3.  **Accessibility (A11y):** Prioritize semantic HTML and ARIA attributes (especially for interactive elements like `Checkbox`, `Tabs`, and custom `Input` components) to ensure compliance with web standards.
4.  **Reference Styleguide:** Consult `frontend/src/app/styleguide` and its sub-directories (e.g., `layouts`, `charts`) before designing a new component to ensure visual and structural consistency with existing elements.
5.  **Type-First Development:** Define and utilize explicit TypeScript interfaces or types (like `LabelProps`, `MessageBubbleProps`) for all component props, state structures, and data validation (e.g., using `SignInValues`).

## 4. Key Project Resources

*   [Project Documentation Index](../docs/README.md)
*   [Agent Handbook](../../AGENTS.md)
*   [Repository Setup Guide](README.md)
*   Contributor Guide (Reference [README.md](README.md) for contribution standards.)

## 5. Repository Starting Points

| Directory | Description |
| :--- | :--- |
| `frontend/src/app` | Core routing, page definitions, and authentication flows (`(auth)`, `reset`, `forgot`). Includes the global `RootLayout`. |
| `frontend/src/components` | Application-specific components and wrappers, including stateful components and the `mode-toggle`. |
| `frontend/src/components/ui` | Reusable, base UI primitives (Input, Button, Tabs, etc.) based on Radix/ShadCN, enforcing design system consistency. |
| `frontend/src/services` | Abstraction layer for all frontend-to-backend API communication (e.g., `authService.ts`). |
| `frontend/src/lib` | General utilities (`utils.ts`), validation schemas (`validations`), and shared client configurations. |
| `frontend/tests` | Location for frontend unit and integration tests (e.g., testing component rendering or service integration). |

## 6. Key Files

| File | Purpose |
| :--- | :--- |
| `frontend\src\app\layout.tsx` | Defines the global `RootLayout`, theme context, and essential metadata for the Next.js application. |
| `frontend\src\lib\utils.ts` | Exports the fundamental utility `cn` for dynamic Tailwind CSS class management. |
| `frontend\src\components\mode-toggle.tsx` | Implementation of the application's theme switching feature (Dark/Light mode). |
| `frontend\src\services\authService.ts` | Contains API interaction methods related to authentication (`loginApi`, `signupApi`). |
| `frontend\src\lib\validations\auth.ts` | Zod schemas and derived types (`SignInValues`, `SignUpValues`) critical for input validation. |
| `frontend\src\app\styleguide\page.tsx` | Entry point for the component library showcase, used for visual regression checks and component reference. |
| `frontend\src\components\ui\message-input.tsx` | Example of a complex, application-specific UI component with defined props (`MessageInputProps`). |
| `frontend\src\app\forgot\page.tsx` | Example of a simple route page implementing a focused service call (`requestPasswordRecovery`). |

## 7. Architecture Context

*   **Components Layer:** This is the primary domain of the Frontend Specialist. It is built using Next.js components located in `frontend\src\app` (pages) and `frontend\src\components` (shared UI). It relies heavily on composition from UI primitives defined in `frontend\src\components\ui` (e.g., `Input`, `Select`, `Tooltip`). Key exported symbols include page components like `StyleguidePage` and base elements like `Separator`.
*   **Services Layer (Client-Side):** Located in `frontend\src\services`. This layer acts as a gateway, providing typed functions (`loginApi`, `resetPassword`) that orchestrate API calls, ensuring components remain clean and focused purely on rendering and state management.
*   **Utils Layer:** Found in `frontend\src\lib`. Essential utilities like `cn` and all critical data validation type definitions (`SignInValues`, `SignUpValues`) reside here, enforcing data consistency throughout the application.

## 8. Key Symbols for This Agent

| Symbol | Location | Description |
| :--- | :--- | :--- |
| `cn` | `frontend\src\lib\utils.ts` | The standard utility for merging and conditioning Tailwind CSS classes. **Mandatory usage.** |
| `RootLayout` | `frontend\src\app\layout.tsx` | The root component wrapping the entire application structure and managing providers. |
| `ModeToggle` | `frontend\src\components\mode-toggle.tsx` | Key component implementing the theme switching mechanism (Light/Dark). |
| `Input` | `frontend\src\components\ui\input.tsx` | The standardized base component for text input fields, implementing forwardRef. |
| `MessageBubble` | `frontend\src\components\ui\message-bubble.tsx` | Component for displaying structured conversation elements. |
| `requestPasswordRecovery` | `frontend\src\services\passwordService.ts` | Service function used to initiate the forgot password flow. |
| `SignUpValues` | `frontend\src\lib\validations\auth.ts` | TypeScript interface and Zod schema defining the structure for user registration data. |
| `BackendStatus` | `frontend\src\app\styleguide\BackendStatus.tsx` | Component used within the style guide to visually display API health. |

## 9. Documentation Touchpoints

*   [README.md](README.md): Reference for project setup, dependencies, and environment configuration.
*   `frontend/src/app/styleguide/page.tsx`: Mandatory reference for visual styles, design tokens, and correct usage patterns for complex components.
*   `frontend/src/app/styleguide/components/*/page.tsx`: Specific component showcases (e.g., `tooltip/page.tsx`, `tabs/page.tsx`) illustrating intended interaction and appearance.
*   `frontend/src/lib/validations/auth.ts`: Source of truth for all authentication-related form validation rules.
*   [Agent Handbook](../../AGENTS.md): Context on agent interactions, particularly when coordinating frontend tasks with the Backend Specialist regarding API contracts.

## 10. Collaboration Checklist

1.  Confirm all new assumptions regarding UI behavior, state persistence, and required API payloads/responses with a Design or Services Agent before generating core component logic.
2.  Review existing component usages in the styleguide (`frontend/src/app/styleguide`) to ensure the proposed change adheres to existing visual hierarchy and component structure.
3.  Ensure all styling is handled via Tailwind CSS and, where applicable, merged using the `cn` utility (`frontend/src/lib/utils.ts`). Avoid inline styles where utility classes suffice.
4.  Verify that all new routes follow the Next.js App Router structure within `frontend/src/app` and that nested pages correctly inherit their parent layouts.
5.  If introducing form handling, confirm that validation logic uses Zod schemas defined in `frontend/src/lib/validations` and that error states are handled gracefully.
6.  Test the implementation across both light and dark modes by utilizing the `ModeToggle` component and ensuring visual integrity in both themes.
7.  Verify that all interactive components are fully accessible (keyboard navigable, semantic elements used, and appropriate ARIA attributes set).
8.  If introducing complex client state management, document the approach and its necessity clearly in code comments or associated documentation.

## 11. Hand-off Notes

Upon task completion, summarize the implementation details for the consuming agent or engineer:

*   **Outcomes:** Detail which pages or components were added/modified, confirming functionality is tested, responsive, and adheres to the style guide. Example: "Completed implementation of the `ContactCard` component and integrated it into the `list-view` layout showcase."
*   **Remaining Risks:** Note any known browser compatibility issues (e.g., specific flexbox behaviors), performance bottlenecks (e.g., large data sets causing re-renders), or reliance on pending backend API implementations.
*   **Suggested Follow-up:** If new utility services were created (e.g., new API calls in `frontend/src/services`), suggest that a Testing Agent verifies the end-to-end (E2E) integration test coverage for those service pathways.
*   **Dependencies:** Clearly state any required or recommended changes to the backend (e.g., new endpoints required, specific response schemas expected) that the Backend Specialist must address to fully enable functionality.
```
