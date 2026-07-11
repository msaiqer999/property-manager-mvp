# Visual Identity

## Direction

Property Manager uses a warm professional identity: calm, trustworthy, modern, and friendly without feeling casual. The interface should support financial work, contracts, rent collection, expenses, and reports with clear hierarchy and restrained decoration.

## Palette

| Token | Value | Use |
| --- | --- | --- |
| `--brand-primary` | `#0F4C5C` | Main identity, primary actions, active navigation, important links |
| `--brand-primary-hover` | `#0B3B47` | Hover state for primary actions |
| `--brand-accent` | `#C79A56` | Restrained highlights, progress, subtle borders, identity detail |
| `--brand-background` | `#F7F5F0` | App page background |
| `--brand-surface` | `#FFFFFF` | Cards, modals, forms, tables, navigation surfaces |
| `--brand-border` | `#E5DED2` | Default borders and dividers |
| `--brand-text` | `#1F2933` | Primary text |
| `--brand-muted` | `#667085` | Secondary text and helper text |
| `--state-success` | `#2E7D5B` | Paid, active, completed, available, positive outcomes |
| `--state-warning` | `#B7791F` | Pending, partial, needs attention, ending soon |
| `--state-danger` | `#B42318` | Overdue, failed, expired, destructive or critical states |
| `--state-info` | `#2F6F8F` | Neutral guidance and non-critical information |

## Surface Usage

Use `brand-background` for the page shell and `brand-surface` for cards, forms, tables, modals, and menus. Keep card radius at 8px or less. Prefer a soft border with a subtle shadow over heavy elevation.

## Button Hierarchy

Primary buttons use `brand-primary` with white text and `brand-primary-hover` on hover. Use them for the main action on a page.

Secondary buttons use a white or warm neutral surface with brand-primary text and a warm border.

Accent buttons are limited to highlighted secondary actions. Do not use gold as the default action color.

Danger buttons use the semantic danger color only for destructive or critical actions.

## Status Rules

Use the shared `x-status-badge` component when rendering common statuses.

Success: paid, active, completed, available, vacant, ready.

Warning: pending, partial, needs attention, ending soon, maintenance, archived, voided, terminated.

Danger: overdue, partial overdue, failed, expired, critical.

Info or neutral: non-critical system information, cancelled, inactive, rented, and unknown states.

Never communicate status by color alone. Keep readable text inside each badge.

## Forms And Focus

Inputs, selects, textareas, file inputs, checkboxes, and radio buttons use warm borders and a primary-colored focus ring. Errors use semantic danger text and must remain clear in Arabic and English. Required fields should keep their text labels and validation messages; do not rely on color alone.

## Tables

Tables use warm neutral headers, soft row dividers, and readable row hover states. Preserve density for operational work. Keep horizontal overflow containers for mobile safety.

## Empty States And Progress

Empty states should use brand-surface cards, muted explanatory text, and one clear primary action. Use the accent color sparingly for progress or highlights, not as the main call-to-action color.

## RTL And Localization

The design must work in Arabic RTL and English LTR. Avoid layout assumptions based on left and right; use logical spacing and alignment utilities where practical. Long translated labels should wrap without breaking buttons, navigation, cards, or tables.

## Accessibility

Target WCAG AA where practical. Text contrast should remain clear, keyboard focus must be visible, disabled controls must remain readable, and hover states must not reduce readability. Do not remove labels or helper text to make a layout appear cleaner.

## Misuse To Avoid

- Gold as the main button color across the app.
- Success, warning, or danger colors used as decoration.
- Multiple primary buttons competing in the same action group.
- Page-specific color inventions when a token or shared class exists.
- Low-contrast muted text for financial amounts or critical dates.
- Decorative visual effects that distract from operational workflows.
- Status badges that use color without text.
