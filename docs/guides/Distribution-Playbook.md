# Distribution Playbook

GitHub-first distribution pack for Core proof content.

Canonical deep link for proof references:
- `docs/AUDIT.md#71-proof-table-reproducible-claims`

## Cadence
- Weekly: one proof drop post.
- Monthly: one comparative or case-study post.

## Template 1: Proof Drop
Use when publishing weekly reproducible checks.

```md
Core weekly proof drop:

Claim: <claim>
Command: `<command>`
Artifact: `<artifact path>`
Result: <pass/fail + short note>

Full proof table:
https://github.com/coesion/core/blob/master/docs/AUDIT.md#71-proof-table-reproducible-claims
```

## Template 2: Case Study
Use when sharing practical agent workflow outcomes.

```md
Core agent case-study update:

Scenario: <what was tested>
Command: `<command>`
Artifact: `docs/guides/agent-case-study.baseline.json`
Outcome: <result summary>

Canonical proof rows:
https://github.com/coesion/core/blob/master/docs/AUDIT.md#71-proof-table-reproducible-claims
```

## Template 3: Regression Fixed with Reproducible Command
Use when a regression is fixed and verifiably closed.

```md
Core regression fix (reproducible):

Regression: <short description>
Repro command: `<command>`
Fix verification: `<command>`
Status: closed

Reference proof table:
https://github.com/coesion/core/blob/master/docs/AUDIT.md#71-proof-table-reproducible-claims
```

## Posting Notes
- Lead with command and result, not generic claims.
- Keep each post anchored to one proof artifact.
- Reuse weekly report output from `composer proof-weekly-report` as source material.
