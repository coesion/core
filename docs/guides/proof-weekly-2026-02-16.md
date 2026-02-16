# Weekly Proof Report

- Verification date: 2026-02-16
- Canonical proof table: docs/AUDIT.md#71-proof-table-reproducible-claims
- Posting cadence: 1 weekly proof post + 1 monthly comparative/case-study post

## Claim Checks

### 1) Audit contract is machine-readable
- Claim: Audit contract is machine-readable
- Command: `php tools/agent-audit.php --format=json --pretty`
- Artifact path: `docs/AUDIT.md`
- Artifact exists: yes
- Artifact age (days): 0
- Verification date: 2026-02-16
- Delta vs previous week: n/a (set after first comparison week)
- Status: PASS

### 2) Contract snapshot is deterministic
- Claim: Contract snapshot is deterministic
- Command: `php tools/agent-snapshot.php --type=contracts --fail-on-diff=tests/fixtures/snapshots/contracts.json`
- Artifact path: `tests/fixtures/snapshots/contracts.json`
- Artifact exists: yes
- Artifact age (days): 0
- Verification date: 2026-02-16
- Delta vs previous week: n/a (set after first comparison week)
- Status: PASS

### 3) Case-study output is machine-readable
- Claim: Case-study output is machine-readable
- Command: `php tools/agent-case-study.php --preset=baseline --out=docs/guides/agent-case-study.baseline.json`
- Artifact path: `docs/guides/agent-case-study.baseline.json`
- Artifact exists: yes
- Artifact age (days): 0
- Verification date: 2026-02-16
- Delta vs previous week: n/a (set after first comparison week)
- Status: PASS

### 4) Proof freshness is enforceable
- Claim: Proof freshness is enforceable
- Command: `composer proof-freshness-check`
- Artifact path: `docs/AUDIT.md`
- Artifact exists: yes
- Artifact age (days): 0
- Verification date: 2026-02-16
- Delta vs previous week: n/a (set after first comparison week)
- Status: PASS

## Notes

- Replace delta placeholders once at least 2 weekly reports are available.
- Keep any social/distribution post linked to the canonical proof table in `docs/AUDIT.md`.
