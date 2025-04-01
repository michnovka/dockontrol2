# DOCKontrol

DOCKontrol is a powerful tool built using Symfony 7.2 to manage and control WireGuard VPN servers with node management capabilities.

## Requirements

- Symfony 7.2
- PHP >= 8.4
- PHP Extension Requirements:
    - ext-ctype
    - ext-iconv
    - ext-redis
    - ext-sodium
    - ext-sysvsem
    - ext-sysvmsg
    - ext-sysvshm
    - ext-pcntl

## Environment Variables

The application uses the following `.env` and `.env.local` files for configuration. Ensure all required variables are set as needed:

- `MAINTENANCE_MODE`: Either `0` or `1` to toggle the maintenance mode of the app.
- `WIREGUARD_SERVER_VPN_IP`: The IP address assigned to the WireGuard VPN server.
- `WIREGUARD_SERVER_VPN_SUBNET`: The subnet configuration for the WireGuard VPN server.
- `WIREGUARD_SERVER_PORT`: The port on which the WireGuard server listens.

Additionally, the following variables configure integrated services:

### Meilisearch

- `MEILISEARCH_API_KEY`: API key for Meilisearch.
- `MEILISEARCH_LISTEN_IP`: The IP address on which Meilisearch listens (default: 127.0.0.1).
- `MEILISEARCH_LISTEN_PORT`: The port on which Meilisearch listens (default: 7700).
- `MEILISEARCH_PREFIX`: Prefix for Meilisearch indices.

### Redis

- `REDIS_LISTEN_IP`: The IP address on which Redis listens (default: 127.0.0.1).
- `REDIS_LISTEN_PORT`: The port on which Redis listens (default: 6379).
- `REDIS_PASSWORD`: Password for Redis authentication.

Standard Symfony environment variables, such as `APP_ENV` and `APP_SECRET`, should also be configured appropriately.

## Commands

### Migration Commands

- `maintenance:migrate-from-v1`
    - Executes all existing migration queries for converting legacy databases.

- `doctrine:migrations:migrate`
    - Executes the initial migration, including all SQL commands to create tables, indexes, and constraints.

- `maintenance:reset-time-tos-accepted`
  - This command updates the timeTosAccepted field in the User table to NULL for all users. 
  - The next time users visit the application, they will be required to accept the Terms of Service again before continuing.

### WireGuard Commands

- `wireguard:generate-keypair`
    - Generates a WireGuard keypair for secure communication.

- `wireguard:dump-server-config`
    - Generates and dumps the server configuration for all DOCKontrol nodes.

- `wireguard:dump-node-config [nodeId]`
    - Dumps the node configuration for the provided node ID.

### Application Commands

- `app:action-queue`
    - Cron job command that executes all pending actions in the queue.

- `app:db-cleanup`
    - Cleans up the database by removing obsolete data.

- `app:monitor`
    - Monitors the nodes to ensure their health and availability.

## Meilisearch Setup

1. Set up `MEILISEARCH_API_KEY` in your `.env.local`.
2. Configure `MEILISEARCH_LISTEN_IP` and `MEILISEARCH_LISTEN_PORT` to build the `MEILI_SEARCH_URL`.
    - Default values: IP `127.0.0.1` and port `7700` (adjust as needed).
3. Start the Docker server for Meilisearch.
4. Run the `meilisearch:import` command to populate all indices with data.

## Redis Setup

1. Set up `REDIS_PASSWORD` in your `.env.local`.
2. Configure `REDIS_LISTEN_IP` and `REDIS_LISTEN_PORT` to build the `REDIS_URL`.
    - Default values: IP `127.0.0.1` and port `6379` (adjust as needed).
3. Start the Docker server for Redis usage.

---

Ensure all environment variables are correctly configured before running the application. 
For detailed command usage, please refer to the in-app documentation.
