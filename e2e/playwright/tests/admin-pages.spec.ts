import { test } from "@playwright/test";
import { loginAs } from "../helpers/auth";
import {
  adminAsUserPages,
  adminPages,
  visitMappedPage,
} from "../helpers/pages";

test.describe("Admin user page map", () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, "admin");
  });

  for (const spec of adminAsUserPages()) {
    test(`admin user-path: ${spec.name}`, async ({ page }) => {
      await visitMappedPage(page, spec);
    });
  }

  for (const spec of adminPages()) {
    test(`admin surface: ${spec.name}`, async ({ page }) => {
      await visitMappedPage(page, spec);
    });
  }
});
