# Aqar Metrics UI/UX Redesign Plan

**Product:** مؤشرات عقارية (Aqar Metrics)  
**Document status:** Proposed implementation plan  
**Prepared:** 2026-10-14  
**UI foundation:** [Tabler UI](https://docs.tabler.io/ui) on Bootstrap 5  
**Primary language and direction:** Arabic, RTL

## 1. Goal

Redesign Aqar Metrics as a modern real-estate feasibility and financial-analysis workspace. The new interface should feel polished, focused, and trustworthy while preserving the application's existing calculations, permissions, routes, validation, translations, and persistence behavior.

The redesign should make these jobs noticeably easier:

- Find, filter, compare, and manage feasibility projects.
- Enter a large set of project assumptions without losing context or progress.
- Understand project results and warnings quickly.
- Explore cash flow, sensitivity, scenarios, and sharing controls.
- Manage users and permissions with less visual noise.

This is a UI/UX modernization, not a domain rewrite. Financial formulas and authorization remain the source of truth on the server.

## 2. Recommended direction

Use the open-source Tabler UI package as the application shell and component foundation. It is built on Bootstrap 5, supports RTL, responsive layouts, color modes, forms, tables, navigation, and print styles. This aligns with the current Bootstrap-based frontend and allows an incremental migration.

If the commercial template from [tabler.io/admin-template](https://tabler.io/admin-template) has been purchased, its assets and layouts can be used under that license. Otherwise, use the MIT-licensed Tabler UI package and reproduce the required application screens with project-owned Blade templates.

The visual concept is **a calm, premium financial workbench**:

- Deep emerald remains the main brand color.
- Warm gold is reserved for highlights and high-value indicators.
- Neutral gray surfaces replace the current cream-heavy background.
- White and dark cards use subtle borders and small shadows.
- Data density stays high, but spacing and hierarchy make screens easier to scan.
- Icons clarify navigation and actions; labels remain visible for accessibility.
- Motion is short and functional: sidebar transitions, loading states, tab changes, and chart updates.
- Arabic typography, RTL behavior, and mixed Arabic/Latin financial data are treated as first-class requirements.

Avoid decorative glass effects, large gradients, oversized rounded cards, and animation that distracts from financial data.

## 3. Current-state findings

The repository currently contains these user-facing areas:

| Area | Current implementation | Main redesign need |
| --- | --- | --- |
| Global shell | One horizontal dark-green navbar | Persistent navigation, breadcrumbs, user menu, responsive mobile behavior, theme control |
| Login | Centered Bootstrap card | Stronger brand presence, clearer authentication layout, refined form states |
| Dashboard | Welcome card with two links | Useful overview, recent work, project status summary, clear next actions |
| Project index | Filter card and server-side DataTable | Better table hierarchy, compact actions, meaningful empty/loading states, saved screen space |
| Project create/edit | One very long form with many financial sections and dynamic schedules | Section navigation, progress visibility, sticky actions, better validation and preview placement |
| Project results | Header, warnings, sticky pill tabs, KPI cards, CSS charts, detailed tables | Stronger decision summary, clearer tabs, chart hierarchy, easier action discovery |
| Advanced analysis | Cash-flow table, sensitivity form, scenarios, sharing | More coherent dashboard layout, compact controls, stronger result visualization |
| Project comparison | Native multi-select and wide comparison table | Easier selection, project identity, pinned metric column, visual winner/variance cues |
| User management | Basic table and create/edit forms | Consistent page shell, avatars/initials, role/status treatment, responsive actions |

The current frontend is small enough for an incremental redesign, but the project form and result pages are high-risk because they contain dense business data and substantial JavaScript behavior.

## 4. Success criteria

The redesign is successful when:

- A first-time user can identify the primary navigation and create a project without explanation.
- A returning user can reach a recent project or start a new one from the dashboard in one click.
- The project form shows the current section, completed sections, validation failures, and available actions at all times.
- The most important project result indicators are visible without scrolling on a typical desktop screen.
- Tables remain readable and operable at 360 px, 768 px, 1024 px, and 1440 px widths.
- Every interactive control is keyboard accessible and has a visible focus state.
- Arabic text, icons, dropdowns, tables, number fields, and offcanvas panels behave correctly in RTL.
- Light, dark, and auto themes render without unreadable custom components if dark mode is included in the release.
- Existing feature tests continue to pass and focused tests cover new navigation, permission visibility, and form behavior.
- The production Vite build has no missing assets, duplicate Bootstrap runtime, or frontend console errors.

## 5. Information architecture

### Primary navigation

Use a vertical Tabler sidebar on the right in RTL layouts. On desktop it can fold into an icon rail; on tablet and mobile it becomes an offcanvas panel.

Recommended navigation:

1. **لوحة التحكم** — dashboard
2. **المشروعات** — project list
3. **مقارنة المشروعات** — comparison
4. **المستخدمون** — permission-gated administration

The lower sidebar area contains the signed-in user, role, theme control, and logout action. Permission checks must use the same server-side policies already used by controllers and views.

### Top bar

Use a compact top bar for:

- Mobile navigation toggle.
- Breadcrumbs or current section context.
- Context-sensitive primary action, such as “إنشاء مشروع”.
- Optional light/dark/auto theme control.
- User dropdown on smaller layouts when the sidebar user block is hidden.

### Page structure

Every admin page should use the same structure:

1. Breadcrumb or short eyebrow.
2. Page title and one-sentence description.
3. Primary and secondary actions.
4. Optional KPI strip, filters, warnings, or status information.
5. Main page content.

Use one `<h1>` and one `<main>` per page, semantic `aside`, `nav`, `header`, and `footer` landmarks, and a skip link before navigation.

## 6. Visual system

### Color tokens

Keep the existing brand recognizable while mapping it to Tabler variables.

| Token | Proposed light value | Use |
| --- | --- | --- |
| Brand primary | `#176B5B` | Primary buttons, active navigation, links |
| Brand primary dark | `#0F4F45` | Sidebar/nav emphasis, hover state |
| Brand accent | `#D3A84F` | Selected highlights, premium/financial emphasis |
| Page background | `#F6F8FA` | Main application canvas |
| Surface | `#FFFFFF` | Cards, tables, menus |
| Main text | `#182433` | Headings and important data |
| Muted text | `#667382` | Descriptions and metadata |
| Border | `#E3E8EE` | Cards, inputs, dividers |
| Positive | Tabler green | Profitable/completed states |
| Warning | Tabler yellow/orange | Incomplete schedules and assumptions |
| Negative | Tabler red | Errors, destructive actions, negative metrics |
| Informational | Tabler azure | Help, calculation notes, neutral status |

Final values must pass WCAG AA contrast in light and dark themes. Financial meaning must never rely on color alone; pair color with a label, icon, sign, or pattern.

### Typography

- Use a self-hosted Arabic WOFF2 family such as **Noto Sans Arabic** with only the required weights (400, 500, 600, 700).
- Use Tabler's compatible Latin fallback for email addresses, codes, and numbers.
- Keep page titles compact: approximately 28–32 px desktop and 24–28 px mobile.
- Use `font-variant-numeric: tabular-nums` for financial tables and KPIs.
- Apply `dir="ltr"` only to isolated numeric values, dates, email addresses, and formulas that require it.
- Standardize numeric precision and currency placement through the existing resource/view formatting layer.

### Spacing and shape

- Base spacing rhythm: 4, 8, 12, 16, 24, and 32 px.
- Card radius: 10–12 px.
- Input and button radius: 8 px.
- Prefer borders over large shadows. Use a small shadow only for elevated menus, sticky action bars, and floating panels.
- Default desktop content should be fluid with a sensible maximum for forms; large result tables may use the full available width.

### Icons

Use one icon family throughout, preferably Tabler Icons. Use inline SVG or a locally bundled icon source to avoid a blocking external font request. Every icon-only control requires a translated accessible label and tooltip.

## 7. Shared component plan

Create a small Blade component/partial layer before migrating feature pages.

| Component | Purpose |
| --- | --- |
| Admin shell | Sidebar, top bar, main region, footer, skip link |
| Page header | Breadcrumb, title, description, action slot |
| Status badge | Project/user status with consistent label and semantic color |
| KPI card | Label, value, unit, optional comparison, help text |
| Empty state | Icon/illustration, message, permitted primary action |
| Filter panel | Search, filters, apply/reset controls, mobile collapse |
| Data table wrapper | Loading, responsive scroll, empty state, pagination, action menu |
| Section card | Consistent heading, description, actions, collapsible body where appropriate |
| Form field | Label, unit, help, validation, required state |
| Sticky form actions | Save, preview, cancel, validation summary |
| Alert/callout | Errors, warnings, calculation assumptions, success messages |
| Confirm modal | Accessible confirmation for archive/removal actions |
| Skeleton/loading state | Table and KPI loading feedback without layout shift |

Prefer server-rendered Blade markup and progressive enhancement. Do not require JavaScript for primary navigation, page content, or form submission.

## 8. Screen-by-screen redesign

### 8.1 Login

Use a responsive split layout:

- Brand panel with logo/mark, product name, and a concise value statement on desktop.
- Focused sign-in card with email, password, remember checkbox, validation, and one primary action.
- Collapse to a single column on mobile.
- Keep the form fully usable without decorative media.

Add password visibility as an accessible button if desired. Do not introduce social login or password reset unless the backend supports them.

### 8.2 Dashboard

Replace the welcome-only card with a useful overview:

- Welcome header and primary “إنشاء مشروع” action.
- KPI cards for accessible project count, drafts, completed projects, and recently updated projects.
- “Recent projects” list with name, location, status, owner, and last update.
- Quick actions for project list, comparison, and user management when authorized.
- Empty state for accounts without projects.

Dashboard metrics require focused, permission-scoped queries. Load only the counts and records displayed, and avoid per-card queries when they can be aggregated.

### 8.3 Project index

- Use the shared page header and make “إنشاء مشروع” the only solid primary button.
- Keep comparison as a secondary icon button.
- Present search and status filters in a compact toolbar above the table; allow collapse on mobile.
- Keep server-side DataTables processing.
- Show project name and location as the primary/secondary text pair.
- Render status with a shared badge component.
- Align currency, duration, and updated time consistently.
- Replace four inline row buttons with one primary “عرض” action plus a three-dot menu for edit, duplicate, and archive.
- Preserve permission checks for every row action.
- Replace browser confirmation with the shared accessible modal after the shell is stable.
- Add a designed loading state and an actionable empty state.

On mobile, keep horizontal scrolling for complete data and consider a responsive details row for secondary fields. Do not remove fields that users need to compare.

### 8.4 Project create/edit workflow

This is the highest-priority usability change.

Keep one server-backed form and the current combined GET/POST controller contract. Add a client-side section navigator without splitting persistence into multiple backend steps.

Recommended sections:

1. Project basics
2. Duration and collection schedule
3. Land assumptions
4. Residential sales
5. Commercial sales
6. Construction and infrastructure
7. Operating expenses
8. Hotel assumptions, shown only when applicable
9. Finance and advanced assumptions
10. Review and preview

Desktop layout:

- Sticky vertical step navigation on the right.
- Current section content in the center.
- Optional sticky summary/preview panel on wide screens.

Mobile layout:

- Compact horizontal progress header.
- One section at a time.
- Sticky bottom action bar with previous, next, and save/preview actions.

Behavior requirements:

- Switching sections must not clear or disable values.
- Validation errors open the first failing section and mark all sections containing errors.
- A top validation summary links to each failing field.
- Preserve the existing live schedule calculation and preview endpoint.
- Move preview results into a side panel or review step rather than leaving them below the full form.
- Keep project duration and collection totals visible while editing the schedule.
- Show the 100% schedule requirement through value, text, and progress state.
- Keep save/cancel actions reachable without scrolling to the bottom.
- Clearly distinguish user inputs, computed values, assumptions, and warnings.
- Preserve draft behavior; do not add autosave until a separate persistence design is approved.

The form JavaScript should be separated by responsibility during implementation: section navigation, schedule editor, and preview. Preserve current calculations and add tests before moving behavior.

### 8.5 Project results

Organize the results page as a decision dashboard:

- Project identity header with status, owner, location, currency, duration, and last update.
- Primary action “تعديل المشروع”; secondary actions in a dropdown.
- High-visibility warning/callout region immediately below the header.
- First row of decision KPIs: total revenue, total cost, estimated profit, profit margin, return on cost, and any existing core indicators.
- Tabs for summary, inputs, revenues, costs, and calculation details.
- Sticky tab bar below the global top bar.
- Charts before large tables, with the related data table retained for exact values and accessibility.
- Consistent legends and units across charts.
- Expandable calculation notes instead of a large always-open block.

Initially restyle the existing CSS charts to reduce migration risk. Evaluate ApexCharts only if interaction, responsiveness, and accessibility materially improve. If adopted, load it only on results and analysis pages and ensure charts resize after sidebar folding.

### 8.6 Advanced analysis

- Use a compact cash-flow KPI row above the annual cash-flow table.
- Add clear unavailable/insufficient-data states that explain what input is missing.
- Keep the cash-flow table horizontally scrollable with sticky year and total columns where practical.
- Present sensitivity inputs as a single focused control card.
- After calculation, show a small chart plus the exact result table.
- Place saved scenarios in a list with status/modified metadata and clear open/delete actions when allowed.
- Place sharing in a separate card or drawer so it does not compete with analysis results.
- Use a confirmation dialog for removing viewers.

### 8.7 Project comparison

- Replace the six-row native multi-select with a searchable project picker.
- Show selected projects as removable chips containing name, currency, and status.
- Warn about mixed currencies before rendering the comparison.
- Keep metric names in a sticky first column.
- Give each selected project a consistent color marker used in headers and charts.
- Highlight best/worst values only where the business meaning is unambiguous.
- Add an empty state describing the minimum number of projects required.

If the searchable picker needs remote results, implement it as a permission-scoped, paginated Select2 endpoint following the repository's established lookup contract. Do not expose projects outside the current user's scope.

### 8.8 User management

- Use the shared page header and table wrapper.
- Add an initials avatar beside the user's name.
- Display role and status as separate semantic badges.
- Move secondary edit action into a compact action menu on narrow screens.
- Reuse the shared form field, validation, and sticky action patterns on create/edit pages.

## 9. Technical implementation strategy

### Asset integration

Before changing dependencies, confirm the current stable package versions compatible with the installed Vite and Bootstrap setup. Dependency changes require explicit approval under the project rules.

Recommended target:

- Bundle Tabler through Vite; do not use a production CDN.
- Use Tabler's RTL stylesheet/build for Arabic pages.
- Ensure only one Bootstrap JavaScript runtime is loaded.
- Keep DataTables server-side behavior and adapt its Bootstrap 5 skin to the Tabler tokens.
- Move the current DataTables CDN assets into the build in a later step if dependency approval is granted.
- Bundle only the icons and optional chart code actually used.
- Set the saved color mode before paint to prevent a light/dark flash.

### Blade structure

Suggested ownership:

| File/area | Planned responsibility |
| --- | --- |
| `resources/views/layouts/admin.blade.php` | Tabler page shell and shared asset hooks |
| `resources/views/layouts/partials/` | Sidebar, top bar, footer, flash messages |
| `resources/views/components/admin/` | Page header, KPI, badge, empty state, form section, action menu |
| `resources/views/admin/dashboard.blade.php` | Dashboard composition |
| `resources/views/admin/projects/index.blade.php` | Project table and filter composition |
| `resources/views/admin/projects/_form.blade.php` | Project form section composition |
| Existing project partials | Preserve domain-specific fields and result sections |
| `resources/views/admin/projects/show.blade.php` | Results shell, actions, and tabs |
| `resources/views/admin/projects/analysis.blade.php` | Analysis dashboard composition |
| `resources/views/admin/projects/compare.blade.php` | Project selection and comparison |
| `resources/css/app.css` | Tabler import, brand tokens, limited global overrides |
| `resources/css/components/` | Complex project form, tables, results, and chart styling if the stylesheet is split |
| `resources/js/app.js` | Shared Tabler/Bootstrap initialization |
| `resources/js/admin/` | Theme, sidebar, project form, table, and result-page behaviors if JavaScript is split |

Confirm local conventions before adding the proposed folders. Existing route names and controller contracts should remain unchanged.

### Migration approach

Build the new shell and shared components alongside the current layout. Migrate low-risk pages first, then the project form and analytical pages. Keep the old layout available on the branch until all authenticated screens are visually and functionally verified.

Do not combine this work with calculation-service, repository, route, or authorization refactors.

## 10. Implementation phases

Effort is expressed as developer days for planning purposes, not calendar commitments.

### Phase 0 — Baseline and design confirmation (1–2 days)

- [ ] Capture desktop, tablet, and mobile screenshots of all existing screens.
- [ ] Record current critical flows and frontend console state.
- [ ] Confirm Tabler UI versus a licensed commercial template.
- [ ] Confirm light-only release or light/dark/auto release.
- [ ] Approve the color, typography, density, and sidebar direction.
- [ ] Define the browser/device QA matrix.

**Exit:** approved visual direction and migration decisions.

### Phase 1 — Foundation and shell (2–3 days)

- [ ] Add approved Tabler and icon assets through Vite.
- [ ] Implement RTL admin shell, responsive sidebar, top bar, footer, and skip link.
- [ ] Add brand tokens, type scale, spacing, focus, and color-mode behavior.
- [ ] Build shared page-header, badge, alert, button, action-menu, and empty-state components.
- [ ] Verify permission-gated navigation.
- [ ] Verify print behavior and mobile offcanvas behavior.

**Exit:** a stable shell that can host both old and redesigned feature content.

### Phase 2 — Login, dashboard, users, and project list (3–4 days)

- [ ] Redesign login.
- [ ] Build the useful dashboard with scoped metrics and recent projects.
- [ ] Redesign project filters, DataTable, actions, loading, and empty states.
- [ ] Redesign user index and forms.
- [ ] Add archive/remove confirmation modal behavior.
- [ ] Test role/permission variations.

**Exit:** all lower-risk workflows use the new system.

### Phase 3 — Project form workflow (5–7 days)

- [ ] Add section navigation and completion/error states.
- [ ] Restyle every field, unit, helper message, and validation state.
- [ ] Redesign collection schedule tables and total indicators.
- [ ] Add sticky desktop and mobile actions.
- [ ] Move preview into the review/summary experience.
- [ ] Split JavaScript by responsibility while preserving existing calculations.
- [ ] Test create/edit, duration changes, hotel conditionals, schedules, preview, draft, and validation failures.

**Exit:** the complete project form is easier to navigate and functionally equivalent.

### Phase 4 — Results, analysis, and comparison (5–7 days)

- [ ] Redesign project result header, KPIs, warnings, tabs, charts, and tables.
- [ ] Redesign cash-flow and sensitivity workflows.
- [ ] Redesign scenarios and sharing panels.
- [ ] Redesign comparison selection and comparison table.
- [ ] Add a chart library only if the approved design requires it.
- [ ] Test calculations, number formatting, empty/unavailable states, mixed currencies, and exports.

**Exit:** all decision and analysis screens use the new visual hierarchy.

### Phase 5 — QA, performance, and release (2–4 days)

- [ ] Run responsive checks at agreed widths.
- [ ] Run keyboard-only and screen-reader spot checks.
- [ ] Check WCAG AA contrast in every color mode.
- [ ] Verify RTL alignment, dropdown placement, offcanvas direction, and mixed-direction values.
- [ ] Run the production Vite build and inspect bundle sizes.
- [ ] Run focused Laravel tests, full relevant suite, and Pint.
- [ ] Check browser console and network errors.
- [ ] Compare before/after screenshots and resolve regressions.
- [ ] Release behind the agreed deployment process and monitor errors.

**Exit:** verified redesign ready for production.

**Estimated total:** 18–27 developer days, depending on dark mode, chart replacement, the comparison picker, and the level of automated browser coverage.

## 11. Verification plan

### Functional regression

At minimum, verify:

- Login, logout, inactive-user behavior, and validation.
- Navigation visibility for each role/permission combination.
- Project list HTML and AJAX DataTables response.
- Project search, status filtering, ordering, pagination, and action visibility.
- Project create/edit submission and all validation paths.
- Dynamic duration and collection schedule behavior.
- Preview success and error states.
- Project show, duplicate, archive, export, and permission denial.
- Analysis, sensitivity, scenarios, and viewer sharing/removal.
- Comparison selection, mixed-currency warning, and result output.
- User create/edit and status behavior.

### Visual and responsive QA

Test representative pages at:

- 360 × 800 mobile
- 768 × 1024 tablet
- 1024 × 768 compact desktop/tablet landscape
- 1440 × 900 desktop
- 1920 × 1080 wide desktop for dense tables

Check Chrome and at least one other supported current browser. Test 200% zoom and long Arabic translations.

### Accessibility

- Keyboard access to sidebar, dropdowns, tabs, tables, modals, and form sections.
- Visible focus on every interactive element.
- Correct landmark and heading hierarchy.
- `aria-current` for active navigation and current form step.
- Programmatic labels for icon-only controls.
- Focus trapping and restoration for offcanvas panels and modals.
- Error summary linked to invalid fields.
- Text/icon reinforcement for status and financial meaning.
- Reduced-motion support.

### Performance

- No duplicate Bootstrap CSS or JavaScript.
- No globally loaded chart or table code on pages that do not use it.
- Locally served production assets with Vite hashes and browser caching.
- Self-hosted, subset WOFF2 fonts with intentional `font-display` behavior.
- No unexpected layout shift from icons, charts, tables, or loading states.
- No new N+1 queries on dashboard, list, results, or analysis pages.

## 12. Risks and mitigations

| Risk | Mitigation |
| --- | --- |
| Tabler and existing Bootstrap imports conflict | Replace imports deliberately and verify there is one CSS/JS foundation |
| RTL regressions in sidebar, dropdowns, charts, and tables | Test every component in RTL at each supported breakpoint |
| Project form redesign changes submitted values | Keep one form, never disable hidden fields, and add request/feature tests before migration |
| Validation errors are hidden in another step | Mark failing steps, open the first failing step, and provide linked error summary |
| Dense financial tables become less useful on mobile | Preserve full table data with horizontal scroll, sticky labels, and optional detail rows |
| Dark mode leaves custom charts or DataTables unreadable | Ship dark mode only after every custom component has explicit theme tokens and QA |
| New dashboard metrics add query cost | Use scoped aggregate queries and a short recent-project query; inspect query count |
| Chart replacement changes interpretation | Preserve exact tables, units, labels, and server-provided values; add visual charts as a secondary representation |
| Commercial template assets are used without the right license | Default to MIT Tabler UI unless a valid commercial license is confirmed |

## 13. Out of scope for this redesign

- Changing financial formulas or calculation versions.
- Reworking route names, controller contracts, database schema, or authorization rules unless a specific UI feature requires it.
- Adding autosave or multi-request wizard persistence.
- Adding social login, password reset, notifications, or collaboration features without separate product requirements.
- Replacing server-side DataTables with a client-side data grid.
- Building a public marketing website.

## 14. Release definition of done

The redesign is complete when:

- All authenticated screens use the approved Tabler-based shell and shared components.
- The project form, preview, results, analysis, comparison, and user workflows are functionally equivalent or have separately approved improvements.
- Permissions are enforced on the server and mirrored in navigation/actions.
- Arabic RTL, mixed-direction values, mobile layouts, keyboard navigation, and color contrast pass QA.
- Production assets build successfully and load without CDN dependencies or console errors.
- Relevant tests pass, changed PHP files are formatted, and no calculation or persistence regression is found.
- Before/after screenshots and a short release checklist are available for review.

## 15. Recommended implementation order

Start with the shell and project index. They establish the design language with limited business-logic risk. Move next to the project form, because it offers the largest usability gain and needs the most careful regression testing. Finish with results, analysis, and comparison after shared KPI, table, callout, and form patterns are stable.

Reference material:

- [Tabler UI introduction](https://docs.tabler.io/ui/getting-started)
- [Tabler page layouts](https://docs.tabler.io/ui/layout/page-layouts)
- [Tabler components](https://docs.tabler.io/ui/components)
- [Tabler colors and accessibility notes](https://docs.tabler.io/ui/base/colors)
- [Tabler print behavior](https://docs.tabler.io/ui/utilities/printing)
