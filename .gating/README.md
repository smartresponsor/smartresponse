# Gating

This directory is the policy layer for the `App` repository.

## Contents

- `profile/component/app.yaml`: component profile consumed by `gating/gate`.
- `profile/rule-set/local-dev.yaml`: default local rule set for `gating:check`.
- `config/severity.yaml`: severity policy for the App gate runner.
- `ai/review-contract.v1.json`: scope semantics, model profiles, metadata contract, and exit codes.
- `ai/prompts/changed-review.v1.md`: the base prompt for diff-aware review.
- `ai/schemas/review-result.v1.schema.json`: the review result schema used by the validator.
- `ai/metadata-template.v1.json`: compact metadata envelope for Cloudflare AI Gateway.

## Operating rules

- `gating:check` is the repository gate runner backed by `gating/gate`.
- `codex-cf-review -Scope Changed -Path D:\PhpstormProjects\www\App` is the default daily review flow.
- `cf-ai-verify` and `cf-ai-test` are diagnostics, not daily rituals.
- `tools/gating/validate-ai-review.php` is the blocking gate for structured review output.
- `codex-cf-review` is advisory until its output is validated by the gate.
