# Agent KPIs

This guide defines recurring metrics for agent-focused product and marketing execution.

## Core KPIs
- `agent_e2e_success_rate`
- `time_to_green_sec`
- `edits_count`
- `failures_recovered`

## Proof KPIs
- Claim reproducibility coverage (claims with command + artifact + date).
- Proof artifact freshness age in days.
- Weekly proof report completion (`composer proof-weekly-report` generated and reviewed).

## Weekly Funnel Capture Fields
- `week_start` (ISO date)
- `repo_visits`
- `clone_count`
- `stars_delta`
- `issues_agent_regression_report`
- `issues_agent_workflow_request`
- `issues_agent_proof_request`
- `proof_commands_run_count` (manual log until automation is added)

## Cadence
- Weekly: refresh proofs and check freshness.
- Monthly: regenerate benchmark report and baseline case-study payload.
- Monthly: review KPI movement against distribution actions and adjust posting mix.

## Commands
```bash
composer proof-refresh
composer proof-freshness-check
composer proof-weekly-report
composer marketing-weekly-cycle
```

## Weekly Automation
Use one command to run the weekly operational loop:

```bash
composer marketing-weekly-cycle -- \
  --repo-visits=0 \
  --clone-count=0 \
  --stars-delta=0 \
  --issues-agent-regression-report=0 \
  --issues-agent-workflow-request=0 \
  --issues-agent-proof-request=0 \
  --proof-commands-run-count=4 \
  --notes="Update with real GitHub traffic numbers"
```

Outputs:
- `docs/guides/proof-weekly-YYYY-MM-DD.md`
- updates row for the week in `docs/guides/Agent-KPI-Log.md`
- `docs/guides/distribution-proof-drop-YYYY-MM-DD.md`

### Optional GitHub API Ingestion
When a token is available, the same command can auto-ingest:
- `repo_visits` and `clone_count` (traffic endpoints)
- issue counts for `agent+bug`, `agent+enhancement`, `agent+documentation`
- `stars_delta` (weekly stargazer delta)

```bash
GITHUB_TOKEN=... composer marketing-weekly-cycle -- \
  --github-fetch=1 \
  --github-owner=coesion \
  --github-repo=core \
  --notes="Weekly KPI refresh from GitHub API"
```

Notes:
- Explicit numeric flags (for example `--repo-visits=...`) override fetched values.
- If GitHub endpoints are unavailable, the script keeps existing/manual values and logs a warning.

## Monthly Review Checklist
- Compare monthly totals for visits, clones, stars, and agent-template issue counts.
- Identify which proof posts produced visible KPI movement.
- Remove or rewrite low-performing distribution copy/templates.
- Confirm all canonical claim links still point to `docs/AUDIT.md` proof rows.
- Define one experiment for next month (audience/channel/message) and track its impact.
