import { test, expect } from "@playwright/test";
import { loginAs } from "../helpers/auth";
import { CORE, USERS } from "../helpers/env";

test("ordinary user cannot grant permissions or access message data", async ({ page }) => {
  await loginAs(page, "user");
  const result = await page.request.post(`${CORE}/api/access/attributes`, {
    data: { source: "access", user_id: USERS.user.id, key: "event:owner", value: ["*"] },
  });
  expect(result.status()).toBe(403);
  expect((await page.request.get(`${CORE}/api/communication/messages`)).status()).toBe(403);
});

test("OIDC session renews while a copied JWT alone cannot renew", async ({ page, browser }) => {
  await loginAs(page, "user");
  expect((await page.request.post(`${CORE}/api/auth/refresh-session`)).status()).toBe(200);
  const jwt = (await page.context().cookies()).find(cookie => cookie.name === "wannabe_jwt");
  expect(jwt).toBeTruthy();
  const isolated = await browser.newContext();
  try {
    await isolated.addCookies([jwt!]);
    expect((await isolated.request.post(`${CORE}/api/auth/refresh-session`)).status()).toBe(401);
  } finally {
    await isolated.close();
  }
});
