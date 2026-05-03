$ErrorActionPreference = 'Stop'
docker compose --env-file ./deploy/docker/.env -f ./deploy/docker/compose.yaml logs -f --tail=200 $args
