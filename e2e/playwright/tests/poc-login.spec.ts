import { test, expect } from "@playwright/test";
import { loginAs } from "../helpers/auth";
import { assertNoSsrError } from "../helpers/pages";

/**
 * PoC: real browser OIDC login + basic navigation smoke.
 */
test.describe("PoC browser login", () => {
  test("admin can log in via Keycloak and open /home", async ({ page }) => {
    await loginAs(page, "admin");
    await page.goto("/home", { waitUntil: "domcontentloaded" });
    await assertNoSsrError(page);
    // /home may soft-redirect event members/owners to /{eventSlug}
    await expect(page).toHaveURL(/\/(home|tg\d+)/);
    await expect(page.locator("body")).toContainText(/Wannabe|testbruker|Home|TG|Gathering/i);
  });

  test("guest landing shows login link", async ({ page }) => {
    await page.goto("/", { waitUntil: "domcontentloaded" });
    await assertNoSsrError(page);
    await expect(page.getByRole("link", { name: /log in/i })).toBeVisible();
  });
});
