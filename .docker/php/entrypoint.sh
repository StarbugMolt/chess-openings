#!/bin/sh
set -eu
if [ "${1:-}" = apache2-foreground ]; then
    # Read private bind-mounted checkouts as their owner, without changing source permissions.
    developer_uid=${LOCAL_UID:-1000}
    if [ "$developer_uid" -gt 0 ] && [ "$(id -u www-data)" != "$developer_uid" ]; then
        # Reuse a colliding UID explicitly; Apache must still keep its www-data name.
        usermod --non-unique --uid "$developer_uid" www-data
    fi
    mkdir -p /app/backend/var
    chown -R www-data:www-data /app/backend/var
    chmod -R g+rwX /app/backend/var
fi
exec docker-php-entrypoint "$@"
