# AI review gate

Use this script after `codex-cf-review` writes a JSON result.

## Local flow

```powershell
codex-cf-review -Environment ci -Scope Changed -Path D:\PhpstormProjects\www\App -Json -OutputLastMessage .\var\gating\review-result.json
php .\tools\gating\validate-ai-review.php --result .\var\gating\review-result.json
```

## CI flow

- Run deterministic checks first.
- Run `codex-cf-review` as an advisory report generator.
- Feed its JSON output to `tools/gating/validate-ai-review.php`.
- Block only on schema failures or severe findings.
- The reusable GitHub Actions entrypoint is [`App/.github/workflows/ai-review-gate.yml`](D:/PhpstormProjects/www/App/.github/workflows/ai-review-gate.yml).
