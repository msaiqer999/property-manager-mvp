# Localization Strategy

Property Manager / المدير العقاري should launch first with Arabic and English support, while keeping the architecture ready for additional languages.

## First Launch Languages

Required launch languages:

- Arabic.
- English.

Arabic support must include right-to-left layout readiness.

English support must include left-to-right layout readiness.

## Future Languages

The architecture should allow future support for:

- Urdu.
- Hindi.
- Bengali.
- Tagalog / Filipino.
- Simplified Chinese.
- Other languages required by UAE property operations teams.

## What Must Be Localized

Future development should localize:

- Navigation.
- Forms.
- Validation messages.
- Role labels.
- Status labels.
- Report labels.
- PDF labels.
- Notifications.
- Activity log descriptions.
- Tenant-facing content when the tenant portal is introduced.

## RTL and LTR Requirements

The user interface should support both:

- RTL for Arabic and Urdu.
- LTR for English, Hindi, Bengali, Tagalog, and Chinese.

Developers should avoid CSS or layout assumptions that only work in one direction.

## Stage 1 Guidance

During Stage 1:

- Keep UI text simple.
- Avoid hard-coded future workflow labels.
- Prefer translation files for new user-facing text.
- Ensure PDF exports can support Arabic text.
- Keep mobile layouts usable in Arabic and English.

## What Not To Build Yet

Do not build a complex translation management system during Stage 1.

Do not add tenant portal localization before the tenant portal exists.

Do not translate future marketplace or daily rental workflows before those modules are designed.

## Developer Guidance

New code should be written so that text can move into translation files easily.

Do not store business logic based on translated labels. Store stable internal keys and translate the labels for display.
