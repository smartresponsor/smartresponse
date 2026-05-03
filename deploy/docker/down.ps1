$ErrorActionPreference = 'Stop'
docker compose --env-file ./deploy/docker/.env -f ./deploy/docker/compose.yaml down $args
