export const FRONTEND = process.env.BASE_URL ?? "http://wannabe5.gathering.org:3000";
export const CORE = process.env.CORE_URL ?? "http://wannabe5.gathering.org:8000";
export const EVENT_SLUG = process.env.E2E_EVENT_SLUG ?? "tg26";

export const USERS = {
  admin: {
    username: process.env.E2E_ADMIN_USER ?? "testbruker",
    password: process.env.E2E_ADMIN_PASSWORD ?? "testbruker",
    id: process.env.E2E_ADMIN_ID ?? "eaf9efc2-adbb-4b27-b5a9-f6c60197ab56",
  },
  user: {
    username: process.env.E2E_USER_USER ?? "normalbruker",
    password: process.env.E2E_USER_PASSWORD ?? "normalbruker",
    id: process.env.E2E_USER_ID ?? "d7a57211-2187-44d2-a9bd-e5deba8fe5ae",
  },
} as const;

export type Role = keyof typeof USERS;

/** Markers that indicate a Next.js SSR / runtime failure. */
export const SSR_ERROR_MARKERS = [
  "Application error",
  "Runtime TypeError",
  "Runtime Error",
  "Cannot read properties",
  "Error serializing",
  "Internal Server Error",
  "OIDC callback failed",
  "Invalid OIDC state",
  "Unhandled Runtime Error",
  "Server Error",
] as const;
