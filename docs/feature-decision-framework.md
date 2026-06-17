# Feature Decision Framework

## Core Principle

Every feature must solve a real problem.

Property Manager / المدير العقاري should remain focused on property operations, landlord workflows, rental management, maintenance readiness, services, and future anonymized data intelligence. Features that do not serve the staged product vision should be delayed or rejected.

## Strategic Alignment Questions

Before approving a feature, answer:

- What user problem does this solve?
- Which user type has the problem?
- Is the problem real, repeated, and painful enough?
- Is this needed for Stage 1 Abu Dhabi landlord operations?
- Does it support owner-managed teams?
- Does it preserve workspace-level data isolation?
- Does it fit the rule that `Organization` means Workspace / Account?
- Does it avoid tenant accounts in Stage 1?
- Does it avoid open broker posting and public classifieds behavior?
- Does it avoid real estate sales workflows?
- Does it support future property management companies without building them too early?
- Does it require legal, security, privacy, payment, or regulatory review?
- Can the feature be tested clearly?
- Can it be explained simply to a landlord using a mobile phone?

## Classifications

### Build Now

Use this when the feature is necessary for Stage 1 operations, security, usability, or pilot readiness.

Criteria:

- Solves a current landlord or owner-managed team problem.
- Low product ambiguity.
- Fits current architecture.
- Can be tested.
- Does not introduce future-stage complexity.

### Architect Now, Build Later

Use this when the feature is strategically important but should not be implemented yet.

Criteria:

- Important for future stages.
- Requires current architecture not to block it.
- Implementation now would distract from Stage 1.
- Can be documented through concepts, boundaries, naming, or data design principles.

### Document For Future

Use this when the feature is outside near-term scope but worth preserving as a future idea.

Criteria:

- Not needed for Stage 1.
- Not needed for immediate architecture.
- Could become relevant later.
- Should not influence current UI or workflows yet.

### Experiment

Use this when the problem is real but the solution is uncertain.

Criteria:

- User demand needs validation.
- Multiple possible solutions exist.
- The experiment can be scoped safely.
- It does not weaken security, privacy, or current workflows.

### Reject

Use this when the feature conflicts with the product vision or creates unacceptable complexity.

Criteria:

- Conflicts with Property Manager positioning.
- Turns the product into sales, classifieds, or open broker marketplace.
- Exposes private data.
- Weakens trust.
- Adds complexity without a Stage 1 operational need.
- Cannot be tested or governed safely.

## Priority Scoring

Score each proposed feature from 1 to 5 in each category.

| Category | Score 1 | Score 3 | Score 5 |
|---|---|---|---|
| User pain | Rare or minor | Repeated but manageable | Frequent and costly |
| Stage alignment | Future-only | Supports near-term roadmap | Required for Stage 1 pilot |
| Security impact | Risky or unclear | Manageable | Improves security |
| Architecture fit | Conflicts with current model | Requires moderate change | Fits current model |
| UX simplicity | Confusing | Understandable | Simple and mobile-friendly |
| Commercial value | No clear value | Indirect value | Clear adoption or revenue value |

Suggested interpretation:

- 24 to 30: consider Build Now if risk gates pass.
- 18 to 23: consider Architect Now, Build Later or Experiment.
- 12 to 17: Document For Future.
- Below 12: Reject or monitor only.

Scores do not replace judgment. A high score cannot bypass legal, security, or privacy concerns.

## Architecture Gate

Before implementation, confirm:

- The feature preserves `organization_id` workspace isolation.
- The feature does not confuse Workspace with real property owner.
- The feature has clear model ownership.
- The feature can be protected by policies or support authorization.
- The feature has tests for role restrictions and cross-organization access.
- The feature does not force future modules into Stage 1.
- The feature does not overload long-term rental contracts with daily rental concepts.
- The feature can be rolled back or adjusted without large data risk.

## UX Gate

Before implementation, confirm:

- The user can understand the feature without training.
- The feature works on a smartphone.
- Labels are clear for landlords and owner-managed teams.
- Arabic and English labels can be supported.
- The feature avoids unnecessary screens and steps.
- The primary action is obvious.
- Empty, error, and permission-denied states are understandable.

## Commercial Value Gate

Before implementation, confirm:

- The feature improves adoption, trust, retention, or future revenue potential.
- The feature helps the first Abu Dhabi target users.
- The feature does not add expensive operations before demand is proven.
- The feature does not require a marketplace, payment gateway, or external provider before the product is ready.

## Legal, Security, And Privacy Risk Gate

Before implementation, confirm:

- No legal claim is made without review.
- Payment, debt, notice, eviction, or collection wording is reviewed before production use.
- Tenant and owner data remains private.
- Activity logs capture sensitive administrative changes where appropriate.
- File uploads are validated and controlled.
- Reports do not expose another workspace's data.
- Future analytics use aggregated and anonymized data only.

This document does not provide legal advice.

## Required Feature Proposal Template

Use this template before approving a feature.

```text
Feature name:

Problem:

Target user:

Stage:

Proposed classification:
Build Now / Architect Now, Build Later / Document For Future / Experiment / Reject

User story:

Current workaround:

Expected benefit:

Scope included:

Scope excluded:

Architecture impact:

Authorization impact:

Data/privacy impact:

UX impact:

Mobile impact:

Arabic/English localization impact:

Testing plan:

Rollout plan:

Risks:

Decision:

Decision owner:

Decision date:
```

## Examples From Current Approved Vision

| Feature | Classification | Rationale |
|---|---|---|
| Team access hardening | Build Now | Stage 1 owner-managed teams need safe deactivation, owner protection, and access control. |
| Overdue amount acknowledgment | Validate and architect now | The problem may be real, but wording and legal handling should be reviewed before implementation. |
| Verified rental market | Architect Now, Build Later | Future listings must come only from real internal units, but public rental marketing is not Stage 1. |
| Maintenance tender marketplace | Architect Now, Build Later | Future tender and quote workflows matter, but Stage 1 should not implement marketplace behavior. |
| Daily rental | Document For Future | Short-term rental needs bookings, calendars, guests, cleaning, and inspections separate from long-term contracts. |
| Property sales listings | Reject | Sales listings conflict with the approved product position. |
| Data intelligence | Structure data now, analytics later | Keep data clean and private now; use aggregated and anonymized analytics only later. |
| Custom permissions | Architect Now, Build Later | Current roles are enough for MVP; custom permissions may be needed after real team feedback. |

## Decision Ownership

Feature decisions should have an explicit owner.

Recommended ownership:

- Product owner: problem, scope, priority, and stage fit.
- Technical lead: architecture, security, testability, and maintainability.
- Operations or pilot lead: real user feedback and workflow fit.
- Legal or compliance reviewer: only when wording, payments, notices, contracts, privacy, or regulations may be affected.

No feature should move to Build Now without a named decision owner.

## Feature Decision Log Template

| Date | Feature | Problem | Target user | Classification | Decision owner | Decision | Next review | Notes |
|---:|---|---|---|---|---|---|---:|---|
| YYYY-MM-DD | Example only | Real user problem | Owner / Manager / Accountant / Caretaker / Future tenant / Future company | Build Now / Architect Now, Build Later / Document For Future / Experiment / Reject | Name | Summary | YYYY-MM-DD | Keep notes practical |

## Review Cadence

Review the decision log monthly during MVP development and quarterly after pilot launch.

Move features between classifications only when new evidence appears, such as:

- Repeated pilot feedback.
- Security findings.
- Technical constraints.
- Official benchmark research.
- Revenue validation.
- Legal or privacy review.

## Final Rule

The best feature is not the biggest feature. The best feature is the one that solves a real operational problem, keeps the product trustworthy, and moves Property Manager / المدير العقاري closer to a stable landlord operations platform.
