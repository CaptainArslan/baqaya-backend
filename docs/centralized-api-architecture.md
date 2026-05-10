# Centralized API Architecture (React Native, Endpoint-driven)

## Added Structure

- `src/config/apiEndpoints.ts` (single source of truth for endpoints)
- `src/config/apiConfig.ts` (BASE_URL, API_VERSION, timeout)
- `src/services/apiClient.fts' (single network gateway)
- `src/services/AuthService.ts`
- `src/services/CustomerService.ts`
- `src/services/SyncService.ts`

## Core Guarantees

- No endpoint hardcoding outside `apiEndpoints`.
- No direct `fetch` calls outside `apiClient`.
- Base URL switching is centralized via `EXPO_PUBLIC_API_BASE_URL`.

## Environment Flags

- `EXPO_PUBLIC_API_BASE_URL`
- `EXPO_PUBLIC_API_TIMEOUT_MS`

## How to Switch

- Change `EXPO_PUBLIC_API_BASE_URL` to move dev/staging/prod.

