import { test, expect } from "@playwright/test";
import { loginAs } from "../helpers/auth";
import { EVENT_SLUG } from "../helpers/env";
import { userPages, visitMappedPage } from "../helpers/pages";

test.describe("Normal user page map", () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, "user");
  });

  for (const spec of userPages()) {
    test(`user can open ${spec.name}`, async ({ page }) => {
      await visitMappedPage(page, spec);
    });
  }

  test("admin sidenav entries are hidden for normal user", async ({ page }) => {
    // Event context so admin event links would appear for owners.
    await page.goto(`/${EVENT_SLUG}`, { waitUntil: "domcontentloaded" });

    const menuButton = page.getByRole("button", { name: /menu/i }).or(page.locator("[aria-label='menu']"));
    if (await menuButton.first().isVisible().catch(() => false)) {
      await menuButton.first().click();
    }

    // These labels come from NAV_ITEMS.admin in navigation.js
    for (const label of ["Attribute types", "Configure", "Profile images", "Create new"]) {
      await expect(page.getByRole("link", { name: label })).toHaveCount(0);
    }
  });
});
