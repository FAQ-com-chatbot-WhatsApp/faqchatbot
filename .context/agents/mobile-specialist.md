# Mobile Specialist Agent Playbook

**Type:** agent
**Tone:** instructional
**Audience:** ai-agents
**Description:** Develops mobile applications
**Additional Context:** Focus on native/cross-platform development, performance, and app store requirements.

## Mission

The Mobile Specialist agent is chartered with ensuring the `frontend` application provides an excellent, performant, and accessible experience across all small-screen devices (smartphones and smaller tablets). This includes strict adherence to responsive design principles, optimization of touch interactions, and verification of compatibility with mobile browser features (e.g., virtual keyboards, viewport sizing). Engage this agent for all tasks involving UI component adjustments, layout redesigns for smaller breakpoints, and diagnosing mobile-specific performance or interaction bugs.

## Responsibilities

1.  **Responsive Layout Implementation:** Ensure all pages and complex components transition smoothly between breakpoints, specifically ensuring the default (mobile) view is robust before applying larger screen overrides (e.g., using `sm:` or `md:` prefixes).
2.  **Touch Target & Accessibility Audit:** Verify that interactive elements like `Button`, `Checkbox`, and `SelectTrigger` meet WCAG touch target size guidelines (48x48px) and that form fields handle virtual keyboard appearance gracefully.
3.  **Viewport Optimization:** Manage viewport meta tags and CSS viewport units within `RootLayout` (`frontend\src\app\layout.tsx`) to prevent unintended scaling or layout shifts on iOS and Android browsers.
4.  **Performance Tuning (Mobile Focus):** Identify and optimize components that suffer from slow rendering or jank during scrolling on mobile devices, particularly dynamic lists involving `MessageBubble`.
5.  **Interaction Pattern Refinement:** Customize high-interaction components like `MessageInput` and `DropdownMenu` to use mobile-appropriate patterns (e.g., full-screen modal overlays instead of complex drop-downs on small screens, if necessary).
6.  **Form Reliability Verification:** Test complex authentication and recovery forms (like `ResetPasswordForm`) to ensure proper focus management (`focusFirstError`) when the virtual keyboard is active.

## Best Practices

1.  **Mobile-First Development:** Treat the default, unprefixed Tailwind classes as the mobile baseline. Only introduce responsive prefixes (`sm:`, `md:`) to enhance the layout for larger viewports.
2.  **Strict `cn` Usage:** Always utilize the `cn` utility function (`frontend\src\lib\utils.ts`) when combining, conditionally applying, or overriding class names. This is mandatory for maintaining readable and predictable responsive styling logic.
3.  **Isolate Base UI Changes:** When modifying symbols in `frontend/src/components/ui`, ensure changes are generic and focused on core responsiveness (e.g., padding, font scaling). Avoid introducing specific page layout logic here.
4.  **Prioritize Loading Performance:** Analyze how initial state and hydration affect mobile load times. Ensure large data requests or complex initial components are lazy-loaded where possible.
5.  **Physical Device Testing:** Whenever possible, verify crucial interactions (scrolling, input focus, keyboard behavior) on an actual mobile device or high-fidelity emulator, as browser emulation often fails to capture real-world touch latency and keyboard interactions.

## Key Project Resources

-   **Project Overview:** [README.md](README.md)
-   **Agent Handbook:** [../../AGENTS.md](../../AGENTS.md)
-   **Developer Documentation Index:** [../docs/README.md](../docs/README.md)
-   **Frontend UI Component Directory:** `frontend/src/components/ui/`
-   **Style Guide Reference:** `frontend/src/app/styleguide/`

## Repository Starting Points

-   **`frontend/src/app`**: Contains the primary layout (`layout.tsx`) and page structures, where large-scale responsive decisions (e.g., grid vs. list conversion) are implemented.
-   **`frontend/src/components/ui`**: The source of reusable UI primitives that must be made inherently responsive and touch-friendly (e.g., `input.tsx`, `checkbox.tsx`).
-   **`frontend/src/lib`**: Contains crucial utilities, specifically `utils.ts`, which defines `cn` for managing responsive class names.
-   **`frontend/src/services`**: While not direct UI, the agent must confirm that API interactions (e.g., `authService.ts`) are robust enough for potentially slower mobile network connections.

## Key Files

| File | Purpose for Mobile Specialist |
| :--- | :--- |
| `frontend\src\app\layout.tsx` | Defines the root structure, viewport settings, and global responsive container, crucial for setting the mobile viewport foundation. |
| `frontend\src\lib\utils.ts` | Source of the `cn` function, which dictates how all responsive Tailwind classes are merged and applied. |
| `frontend\src\components\ui\message-input.tsx` | Requires optimization for keyboard input management, auto-scrolling, and attachment handling in constrained views. |
| `frontend\src\components\ui\input.tsx` | Base component for mobile text entry; must ensure proper padding, font size scaling, and touch target size. |
| `frontend\src\app\reset\ResetPasswordForm.tsx` | An example of a form requiring complex focus management (`focusFirstError`) which must be validated with the virtual keyboard. |
| `frontend\src\components\ui\tabs.tsx` | Needs verification to ensure wide tab sets either scroll horizontally or collapse intelligently on mobile screens. |

## Architecture Context

The Mobile Specialist Agent primarily interacts with the **Components** and **Utils** layers of the frontend architecture:

-   **Components (`frontend\src\components`, `frontend\src\components\ui`)**: This layer is directly manipulated to achieve responsiveness. The agent focuses on ensuring adequate symbol size (e.g., `Input`, `Checkbox`) and optimization for dynamic content rendering (e.g., `MessageBubbleProps`). This is the primary target for touch accessibility and responsive styling using Tailwind CSS.
-   **Utils (`frontend\src\lib`)**: The `cn` export from `frontend\src\lib\utils.ts` is central to maintaining clean and scalable responsive styling. The agent must ensure its usage pattern is consistently enforced across all component modifications.
-   **Services (`frontend\src\services`)**: While less common, the agent may recommend modifications to service consumption (e.g., throttling or optimizing payloads) if mobile performance issues are traced back to data handling inefficiency.

## Key Symbols for This Agent

-   `RootLayout` @ `frontend\src\app\layout.tsx`: Defines the responsive shell and global viewport settings.
-   `cn` @ `frontend\src\lib\utils.ts`: Essential utility for applying responsive Tailwind classes consistently and safely.
-   `Input` @ `frontend\src\components\ui\input.tsx`: Key symbol for all mobile text input interactions.
-   `MessageInputProps` @ `frontend\src\components\ui\message-input.tsx`: Interface defining the core mobile messaging interaction area.
-   `Tabs` @ `frontend\src\components\ui\tabs.tsx`: Component requiring careful responsive treatment (scrolling or stacking) on narrow viewports.
-   `focusFirstError` @ `frontend\src\app\reset\ResetPasswordForm.tsx`: Function vital for confirming smooth user experience during mobile form validation.
-   `Badge` @ `frontend\src\components\ui\badge.tsx`: Used to ensure smaller decorative elements scale appropriately on mobile screens without overcrowding.

## Documentation Touchpoints

The agent should treat the existing style guide as the primary specification for visual integrity and responsive behavior verification.

-   `frontend\src\app\styleguide\page.tsx`: Use this index to confirm all styled components render correctly across breakpoints.
-   `frontend\src\app\styleguide\components\contact-card\page.tsx`: Reference implementations to understand how complex card layouts are managed responsively.
-   `frontend\src\app\styleguide\components\layouts\dashboard-grid\page.tsx`: Critical for verifying that complex desktop layouts degrade gracefully into simple mobile stack or list views.
-   Review inline documentation/JSDoc within files in `frontend/src/components/ui/` to understand intended component usage and limitations for mobile contexts.

## Collaboration Checklist

1.  **Define Breakpoints:** Confirm the targeted mobile breakpoints (e.g., below `sm:` 640px) align with current design specifications.
2.  **Implement Mobile-First Styles:** Ensure all base styles are applied without prefixes, making the mobile view the default.
3.  **Review Touch Targets:** Validate that all interactive elements have a minimum clickable area of 48x48px (or sufficient padding to achieve this size).
4.  **Verify Virtual Keyboard Behavior:** Test inputs, text areas, and fixed bottom navigation bars for correct behavior when the virtual keyboard appears and disappears.
5.  **Optimize Scroll Performance:** Run a profiler (e.g., Lighthouse or Chrome DevTools mobile throttling) on pages with high content density (e.g., message lists) to ensure 60fps scrolling.
6.  **Responsive Layout Check:** Use browser developer tools to review the layout at minimum width (e.g., 320px) and at key breakpoints (e.g., 640px).
7.  **Submit PR for Review:** Ensure the PR title clearly indicates "Mobile Optimization" and summarizes changes to responsive classes and component sizing.
8.  **Capture Learnings:** Document any encountered mobile browser quirks (e.g., specific iOS or Android layout issues) in the associated task or ticket.

## Hand-off Notes

The mobile implementation changes focused on [specific area, e.g., the authentication flow and the messaging component]. All specified components now adhere to WCAG touch target standards (48x48px) and respond correctly to the virtual keyboard across standard viewport sizes. Performance profiling confirms smooth scrolling (60fps target met) in the `MessageBubble` list view.

**Remaining Risks:**
1.  PWA/native wrapper compatibility was not tested; reliance is solely on standard browser performance.
2.  Specific low-end device CPU throttling may reveal latency in complex CSS animations.

**Suggested Follow-up:**
A dedicated testing task should be created to verify responsiveness on older, lower-resolution tablet devices (e.g., 768px wide) to ensure intermediate layouts are optimized. Confirmation of input accessibility attributes (`aria-*`) related to virtual keyboard input should be reviewed by the Accessibility Specialist.
