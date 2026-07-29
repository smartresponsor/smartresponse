.DEFAULT_GOAL := dev-up

AI_REVIEW_RESULT ?= var\\gating\\review-result.json

.PHONY: infra-up infra-down infra-logs app-up app-stop dev-up dev-down deploy deploy-dry-run ai-review-validate gating-check

infra-up:
	powershell -ExecutionPolicy Bypass -File .\\deploy\\docker\\up.ps1

infra-down:
	powershell -ExecutionPolicy Bypass -File .\\deploy\\docker\\down.ps1

infra-logs:
	powershell -ExecutionPolicy Bypass -File .\\deploy\\docker\\logs.ps1

app-up:
	powershell -ExecutionPolicy Bypass -File .\\deploy\\dev\\up.ps1

app-stop:
	powershell -ExecutionPolicy Bypass -File .\\deploy\\dev\\down.ps1

dev-up:
	powershell -ExecutionPolicy Bypass -File .\\deploy\\dev\\up.ps1

dev-down:
	powershell -ExecutionPolicy Bypass -File .\\deploy\\dev\\down.ps1

deploy:
	powershell -ExecutionPolicy Bypass -File .\\deploy.ps1 -Force

deploy-dry-run:
	powershell -ExecutionPolicy Bypass -File .\\deploy.ps1 -DryRun

ai-review-validate:
	php .\\tools\\gating\\validate-ai-review.php --result "$(AI_REVIEW_RESULT)"

gating-check:
	php .\\bin\\console gating:check --target=.
