.PHONY: infra-up infra-down infra-logs app-up app-stop dev-up dev-down

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
