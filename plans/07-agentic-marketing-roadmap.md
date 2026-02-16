# 07 - Agentic Marketing Roadmap

## Summary
Operate a proof-first marketing system for coding agents based on reproducible commands, machine-readable outputs, recurring evidence updates, and measurable GitHub-first distribution.

## Target Audience
- SMB SaaS teams using coding agents.
- Indie builders and OSS maintainers.

## Primary Channel
- GitHub proof content as the main acquisition and trust channel.

## Phase 1 Completion (Done)

### A. Proof Assets (Done)
- `docs/guides/Agents-Quickstart.md`
- `docs/guides/Agent-Use-Cases.md`
- `docs/guides/Why-Core-for-Agents.md`

### B. Comparative Credibility (Done)
- Expand `docs/AUDIT.md` with proof table rows linking each claim to command and expected artifact.
- Add reproducibility timestamp fields.

### C. Conversion Surface (Done)
- Update `README.md` top section with proof-backed CTA.
- Add docs navigation references for agent-specific guides.

### D. Community Pull Loop (Done)
- Add issue templates:
  - `agent-regression-report`
  - `agent-workflow-request`
  - `agent-proof-request`

## Phase 2 Workstreams (Active)

### 1) CI Proof Enforcement
- Explicit CI gates in `.github/workflows/tests.yml`:
  - `composer agent-snapshot-check`
  - `composer proof-freshness-check`
- Document expected gate behavior in `docs/guides/Agentic-Audit.md`.

### 2) Weekly Proof Publishing
- Add `tools/proof-weekly-report.php` for deterministic weekly markdown draft generation.
- Add `composer proof-weekly-report`.
- Publish format contract in `docs/guides/Proof-Weekly-Template.md`.

### 3) KPI Instrumentation
- Expand `docs/guides/Agent-KPIs.md` with weekly funnel capture fields and monthly review checklist.
- Maintain rolling metrics in `docs/guides/Agent-KPI-Log.md`.

### 4) Distribution Pack
- Add `docs/guides/Distribution-Playbook.md` with:
  - proof drop template
  - case study template
  - regression fix template
- Standardize canonical proof deep link:
  - `docs/AUDIT.md#71-proof-table-reproducible-claims`

## Phase 2 Acceptance
- CI fails when contract snapshots drift or proof artifacts exceed freshness policy.
- `composer proof-weekly-report` outputs deterministic sectioned markdown.
- KPI tracking has at least four consecutive weekly rows in `docs/guides/Agent-KPI-Log.md`.
- Distribution posts consistently anchor to canonical proof rows.
