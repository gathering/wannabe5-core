import { test, expect } from "@playwright/test";
import { loginAs } from "../helpers/auth";
import { CORE, EVENT_SLUG } from "../helpers/env";
import { assertNoSsrError } from "../helpers/pages";

type Json = Record<string, unknown>;

async function apiJson(page: import("@playwright/test").Page, path: string): Promise<Json | null> {
  const res = await page.request.get(`${CORE}${path}`);
  if (!res.ok()) {
    return null;
  }
  try {
    return (await res.json()) as Json;
  } catch {
    return null;
  }
}

test.describe("Admin dynamic detail pages", () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, "admin");
  });

  test("crew / task / page / list / template detail routes load", async ({ page }) => {
    const events = await apiJson(page, "/api/crew/events?hidden=1&inactive=1");
    const list = (events?.data as Array<{ id: number; slug: string }> | undefined) ?? [];
    const event = list.find((e) => e.slug === EVENT_SLUG) ?? list[0];
    expect(event?.id, "seeded event").toBeTruthy();
    const eid = event!.id;

    const crews = await apiJson(page, `/api/crew/events/${eid}/crews`);
    const crewId = ((crews?.data as Array<{ id: number }> | undefined) ?? [])[0]?.id;

    const tasks = await apiJson(page, `/api/tasking/events/${eid}`);
    const taskPayload = tasks?.data ?? tasks;
    const taskList = Array.isArray(taskPayload)
      ? taskPayload
      : Array.isArray((taskPayload as Json)?.data)
        ? ((taskPayload as Json).data as unknown[])
        : [];
    const taskId = (taskList[0] as { id?: number } | undefined)?.id;

    const pages = await apiJson(page, `/api/crew/events/${eid}/pages`);
    const pageList = (pages?.data as Array<{ id: number }> | undefined) ?? [];
    const pageId = pageList[0]?.id;

    const lists = await apiJson(page, "/api/communication/lists");
    const listPayload = lists?.data ?? lists;
    const listArr = Array.isArray(listPayload) ? listPayload : [];
    const listId = (listArr[0] as { id?: number } | undefined)?.id;

    const templates = await apiJson(page, "/api/communication/templates");
    const tplPayload = templates?.data ?? templates;
    const tplArr = Array.isArray(tplPayload) ? tplPayload : [];
    const templateId = (tplArr[0] as { id?: number } | undefined)?.id;

    const paths: string[] = [];
    if (crewId) {
      paths.push(
        `/${EVENT_SLUG}/crew/${crewId}`,
        `/${EVENT_SLUG}/crew/${crewId}/show`,
        `/${EVENT_SLUG}/crew/${crewId}/edit`,
        `/${EVENT_SLUG}/crew/${crewId}/applications/show`,
        `/${EVENT_SLUG}/crew/${crewId}/applications/questions`,
        `/${EVENT_SLUG}/crew/${crewId}/tasks`
      );
    }
    if (taskId) {
      paths.push(
        `/${EVENT_SLUG}/tasks/${taskId}`,
        `/${EVENT_SLUG}/tasks/manage/${taskId}/responses`
      );
    }
    if (pageId) {
      paths.push(`/${EVENT_SLUG}/pages/${pageId}`, `/${EVENT_SLUG}/pages/${pageId}/edit`);
    }
    if (listId) {
      paths.push(`/${EVENT_SLUG}/lists/${listId}`);
    }
    if (templateId) {
      paths.push(`/communication/template/edit/${templateId}`);
    }

    expect(paths.length, "expected seeded detail targets").toBeGreaterThan(0);

    for (const path of paths) {
      const res = await page.goto(path, { waitUntil: "domcontentloaded" });
      expect(res?.status() ?? 0, path).toBeLessThan(500);
      await assertNoSsrError(page);
    }
  });
});
