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

## Cadence
- Weekly: refresh proofs and check freshness.
- Monthly: regenerate benchmark report and baseline case-study payload.

## Commands
```bash
composer proof-refresh
composer proof-freshness-check
```
