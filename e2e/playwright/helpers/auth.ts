import { expect, type Page } from "@playwright/test";
import { CORE, FRONTEND, USERS, type Role } from "./env";

/**
 * Full browser OIDC login via Keycloak (same path users take).
 * Lands on /home with wannabe_jwt set for the frontend origin.
 */
export async function loginAs(page: Page, role: Role): Promise<void> {
  const { username, password } = USERS[role];
  const redirect = `${FRONTEND}/home`;
  const loginUrl =
    `${CORE}/api/auth/login?redirect_url=` + encodeURIComponent(redirect);

  await page.goto(loginUrl, { waitUntil: "domcontentloaded" });

  // Keycloak username/password form
  await expect(page.locator("#username, input[name='username']").first()).toBeVisible({
    timeout: 30_000,
  });
  await page.locator("#username, input[name='username']").first().fill(username);
  await page.locator("#password, input[name='password']").first().fill(password);
  await page.locator("#kc-login, input[type='submit'], button[type='submit']").first().click();

  // Optional consent screen
  const accept = page.locator("input[name='accept'], #kc-accept, button:has-text('Yes')");
  if (await accept.first().isVisible({ timeout: 3_000 }).catch(() => false)) {
    await accept.first().click();
  }

  await page.waitForURL((url) => url.href.startsWith(FRONTEND), { timeout: 60_000 });
  await expect(page).not.toHaveURL(/keycloak/);

  const jwt = await page.context().cookies().then((cookies) =>
    cookies.find((c) => c.name === "wannabe_jwt")
  );
  expect(jwt?.value, "wannabe_jwt cookie after OIDC").toBeTruthy();
}

export async function logoutViaUi(page: Page): Promise<void> {
  // Best-effort: clear cookies and return to public landing.
  await page.context().clearCookies();
  await page.goto("/", { waitUntil: "domcontentloaded" });
}
