# Changed review prompt

You are reviewing a limited change set.

Default scope is `Changed`.
Use the diff and nearby context. Do not scan the entire workspace unless the selected scope requires it.

Required focus:
- correctness
- Symfony-oriented structure
- default `App\` namespace
- zero-controller canon where applicable
- PSR-4
- DI wiring
- tests
- accidental deletions
- component responsibility
- public surface changes
- release and migration risk

Output:
- concise summary
- findings sorted by severity
- what to verify next
- a JSON object matching `.gating/ai/schemas/review-result.v1.schema.json`

Advisory only: do not block the workflow by itself.
