import { test } from "@playwright/test";
import { GUEST_PAGES, visitMappedPage } from "../helpers/pages";

test.describe("Guest pages", () => {
  for (const spec of GUEST_PAGES) {
    test(`guest can open ${spec.name} (${spec.path})`, async ({ page }) => {
      await visitMappedPage(page, spec);
    });
  }
});
