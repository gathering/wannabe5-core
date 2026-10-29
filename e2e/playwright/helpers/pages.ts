import { expect, type Page } from "@playwright/test";
import { EVENT_SLUG, SSR_ERROR_MARKERS, USERS } from "./env";

export type PageExpectation = {
  /** Path relative to BASE_URL */
  path: string;
  /** Short name for test titles */
  name: string;
  /**
   * How the page should behave for this role:
   * - ok: loads without SSR error markers (FE does not hard-block most admin routes;
   *   privilege is enforced by API + sidenav visibility — see user-pages nav test)
   */
  expect: "ok";
};

function eventPath(suffix: string): string {
  return `/${EVENT_SLUG}${suffix}`;
}

/** Pages a logged-out visitor can open. */
export const GUEST_PAGES: PageExpectation[] = [
  { path: "/", name: "landing", expect: "ok" },
  { path: "/events", name: "events", expect: "ok" },
  { path: "/goodbye", name: "goodbye", expect: "ok" },
  { path: "/liveness", name: "liveness", expect: "ok" },
];

/**
 * Pages a normal (non-admin) authenticated user should reach without SSR errors.
 * Mapped from frontend `pages/` + sidenav `NAV_ITEMS.user`.
 */
export function userPages(): PageExpectation[] {
  const uid = USERS.user.id;
  return [
    { path: "/home", name: "home", expect: "ok" },
    { path: "/welcome", name: "welcome", expect: "ok" },
    { path: "/welcome/profile-creation", name: "welcome-profile-creation", expect: "ok" },
    { path: "/welcome/complete", name: "welcome-complete", expect: "ok" },
    { path: "/profile", name: "profile", expect: "ok" },
    { path: `/profile/show/${uid}`, name: "profile-show-self", expect: "ok" },
    { path: `/profile/edit/${uid}`, name: "profile-edit-self", expect: "ok" },
    { path: `/profile/profile-picture/${uid}`, name: "profile-picture-self", expect: "ok" },
    { path: eventPath(""), name: "event-frontpage", expect: "ok" },
    { path: eventPath("/application"), name: "event-application", expect: "ok" },
    { path: eventPath("/tasks"), name: "event-tasks", expect: "ok" },
    { path: eventPath("/profile"), name: "event-profile", expect: "ok" },
    { path: eventPath(`/profile/show/${uid}`), name: "event-profile-show", expect: "ok" },
  ];
}

/**
 * Admin-only surfaces from sidenav `NAV_ITEMS.admin` (+ related detail routes).
 * Normal users should be denied; admin should load them.
 */
export function adminPages(): PageExpectation[] {
  const uid = USERS.admin.id;
  return [
    { path: "/access/user", name: "access-users", expect: "ok" },
    { path: `/access/user/edit/${uid}`, name: "access-user-edit", expect: "ok" },
    { path: "/access/attribute-type", name: "access-attribute-types", expect: "ok" },
    { path: "/access/attribute-type/create", name: "access-attribute-type-create", expect: "ok" },
    { path: "/debug/access", name: "debug-access", expect: "ok" },
    { path: "/communication/notification", name: "com-notifications", expect: "ok" },
    { path: "/communication/template", name: "com-templates", expect: "ok" },
    { path: "/communication/message", name: "com-messages", expect: "ok" },
    { path: eventPath("/edit"), name: "event-configure", expect: "ok" },
    { path: eventPath("/edit/questions"), name: "event-edit-questions", expect: "ok" },
    { path: eventPath("/pages/manage"), name: "event-pages-manage", expect: "ok" },
    { path: eventPath("/profile-images"), name: "event-profile-images", expect: "ok" },
    { path: eventPath("/tasks/manage"), name: "event-tasks-manage", expect: "ok" },
    { path: eventPath("/tasks/manage/create"), name: "event-tasks-create", expect: "ok" },
    { path: eventPath("/crew/manage"), name: "event-crew-manage", expect: "ok" },
    { path: eventPath("/crew/create"), name: "event-crew-create", expect: "ok" },
    { path: eventPath("/lists/manage"), name: "event-lists-manage", expect: "ok" },
    { path: eventPath("/notify"), name: "event-notify", expect: "ok" },
    { path: eventPath("/member"), name: "event-member-search", expect: "ok" },
    { path: eventPath("/crew"), name: "event-crew-overview", expect: "ok" },
  ];
}

/** Admin also exercises the same “normal user” paths (with admin identity). */
export function adminAsUserPages(): PageExpectation[] {
  const uid = USERS.admin.id;
  return [
    { path: "/home", name: "home", expect: "ok" },
    { path: "/profile", name: "profile", expect: "ok" },
    { path: `/profile/show/${uid}`, name: "profile-show-self", expect: "ok" },
    { path: eventPath(""), name: "event-frontpage", expect: "ok" },
    { path: eventPath("/tasks"), name: "event-tasks", expect: "ok" },
    { path: eventPath("/crew"), name: "event-crew", expect: "ok" },
    { path: eventPath("/application"), name: "event-application", expect: "ok" },
  ];
}

export async function assertNoSsrError(page: Page): Promise<void> {
  const body = await page.content();
  for (const marker of SSR_ERROR_MARKERS) {
    expect(body, `SSR marker ${marker}`).not.toContain(marker);
  }
}

/**
 * Visit a mapped page and assert it loads without SSR/runtime failure.
 */
export async function visitMappedPage(page: Page, spec: PageExpectation): Promise<void> {
  const response = await page.goto(spec.path, { waitUntil: "domcontentloaded" });
  const status = response?.status() ?? 0;
  expect(status, `${spec.name} HTTP status`).toBeLessThan(500);
  expect(status, `${spec.name} should not be 404`).not.toBe(404);
  await assertNoSsrError(page);
  expect(page.url(), `${spec.name} should not bounce to Keycloak`).not.toMatch(/keycloak/);
}
