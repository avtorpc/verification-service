#!/bin/sh
set -eu
ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/../../.." && pwd)
TEST_PG="mesto-registration-test-pg-$$"
TEST_PHP_IMAGE="${REGISTRATION_TEST_PHP_IMAGE:-mesto-web-web-service:latest}"
PG_CREATED=0
cleanup() {
 if [ "$PG_CREATED" = 1 ]; then docker rm -f "$TEST_PG" >/dev/null; fi
}
trap cleanup EXIT HUP INT TERM
docker run -d --name "$TEST_PG" --tmpfs /var/lib/postgresql/data -e POSTGRES_HOST_AUTH_METHOD=trust postgres:17 >/dev/null
PG_CREATED=1
for n in $(seq 1 30); do
 if docker exec "$TEST_PG" pg_isready -U postgres >/dev/null 2>&1; then break; fi
 if [ "$n" = 30 ]; then echo 'Disposable PostgreSQL not ready'; exit 1; fi
 sleep 1
done
for test in services/verification-service/tests/registration-contract.php services/verification-service/tests/registration-settings.php services/verification-service/tests/client-ip-contract.php; do
 docker run --rm --network "container:$TEST_PG" -v "$ROOT:/workspace" -w /workspace "$TEST_PHP_IMAGE" php "$test"
done
