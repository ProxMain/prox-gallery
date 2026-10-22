import {
  act,
  fireEvent,
  render,
  screen,
  waitFor,
  within,
} from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { App } from "@/app";
import { createPreviewBackend } from "../../dev/preview-backend";

let backend;
beforeEach(() => {
  window.location.hash = "";
  backend = createPreviewBackend();
  window.ProxGalleryAdminConfig = backend.config;
  vi.stubGlobal("fetch", vi.fn(backend.request));
});
afterEach(() => vi.unstubAllGlobals());

describe("workspace workflows", () => {
  it("opens collections and filters without extra network requests", async () => {
    render(<App />);
    await screen.findByText("Wilderness, quietly");
    fireEvent.change(
      screen.getByRole("searchbox", { name: "Search galleries" }),
      { target: { value: "water" } },
    );
    expect(screen.getByText("Where the water rests")).toBeTruthy();
    expect(screen.queryByText("Wilderness, quietly")).toBeNull();
    expect(
      backend.calls.filter((call) => call === "galleries.list"),
    ).toHaveLength(1);
  });

  it("creates and edits with a labelled modal and explicit save", async () => {
    render(<App />);
    await screen.findByText("Wilderness, quietly");
    fireEvent.click(
      screen.getByRole("button", { name: "Create gallery", exact: true }),
    );
    const modal = screen.getByRole("dialog", { name: "Create a gallery" });
    fireEvent.change(within(modal).getByLabelText("Gallery name"), {
      target: { value: "New collection" },
    });
    fireEvent.click(
      within(modal).getByRole("button", { name: "Create gallery" }),
    );
    await screen.findByText("New collection");
    expect(backend.calls).not.toContain("galleries.create_page");
    fireEvent.click(
      screen.getByRole("button", { name: "Edit New collection" }),
    );
    fireEvent.change(screen.getByLabelText("Gallery name"), {
      target: { value: "Renamed collection" },
    });
    fireEvent.click(
      within(screen.getByRole("dialog", { name: "Edit gallery" })).getByRole(
        "button",
        { name: "Save changes" },
      ),
    );
    await screen.findByText("Renamed collection");
  });

  it("allows removing the final image from a gallery", async () => {
    render(<App />);
    await screen.findByText("Above it all");
    fireEvent.click(
      screen.getByRole("button", { name: "Manage images in Above it all" }),
    );
    const modal = screen.getByRole("dialog", { name: "Images · Above it all" });
    await within(modal).findByRole("button", { name: "Higher ground" });
    fireEvent.click(
      within(modal).getByRole("button", { name: "Higher ground" }),
    );
    fireEvent.click(
      within(modal).getByRole("button", { name: "Save selection" }),
    );
    await waitFor(() => expect(screen.queryByRole("dialog")).toBeNull());
    expect(backend.calls).toContain("galleries.set_images");
    expect(
      screen.getByRole("button", { name: "Manage images in Above it all" })
        .textContent,
    ).toContain("0");
  });

  it("keeps organisation edits on an image when a parent rerenders", async () => {
    render(<App />);
    fireEvent.click(screen.getByRole("button", { name: "Media library" }));
    await screen.findByRole("button", { name: "Edit Into the wild" });
    fireEvent.click(screen.getByRole("button", { name: "Edit Into the wild" }));
    const modal = screen.getByRole("dialog", { name: "Into the wild" });
    fireEvent.click(within(modal).getByRole("button", { name: "Organise" }));
    const categories = within(modal).getByLabelText(/Categories/);
    await waitFor(() => expect(categories.disabled).toBe(false));
    fireEvent.change(categories, { target: { value: "Nature, Landscape" } });
    fireEvent.click(
      within(modal).getByRole("button", { name: "Save categories" }),
    );
    await within(modal).findByText("Categories saved.");
    expect(categories.value).toBe("Nature, Landscape");
    expect(
      backend.calls.filter((call) => call === "media_category.list"),
    ).toHaveLength(1);
  });

  it("counts all known countries while only showing the top eight", async () => {
    render(<App />);
    fireEvent.click(screen.getByRole("button", { name: "Overview" }));
    const card = screen.getByText("Countries Tracked").closest(".pg-card");
    await waitFor(() => expect(card.textContent).toContain("9"));
  });

  it("keeps loaded settings editable and confirms save", async () => {
    render(<App />);
    fireEvent.click(
      screen.getByRole("button", { name: "Settings", exact: true }),
    );
    const save = screen.getByRole("button", { name: "Save changes" });
    await waitFor(() => expect(save.disabled).toBe(false));
    const columns = screen.getAllByLabelText("Columns")[0];
    fireEvent.change(columns, { target: { value: "3" } });
    fireEvent.click(save);
    await screen.findByText("Changes saved.");
    expect(columns.value).toBe("3");
  });
});
