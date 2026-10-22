import { act, renderHook, waitFor } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { useLoadableCollection } from "@/lib/use-loadable-collection";
import { useGalleriesState } from "@/features/galleries/use-galleries-state";

const messages = { missingMessage: "Missing", loadErrorMessage: "Failed" };
describe("collection lifecycle", () => {
  it("deduplicates simultaneous loads and keeps the load callback stable after rerenders", async () => {
    let resolve;
    const controller = {};
    const request = vi.fn(
      () =>
        new Promise((done) => {
          resolve = done;
        }),
    );
    const { result, rerender } = renderHook(() =>
      useLoadableCollection({
        ...messages,
        controller,
        loadItems: () => request(),
      }),
    );
    const originalLoad = result.current.load;
    let first, second;
    act(() => {
      first = result.current.load();
      second = result.current.load();
    });
    expect(request).toHaveBeenCalledTimes(1);
    await act(async () => {
      resolve(["image"]);
      await Promise.all([first, second]);
    });
    rerender();
    expect(result.current.load).toBe(originalLoad);
    await act(async () => {
      await result.current.load();
    });
    expect(result.current.items).toEqual(["image"]);
    expect(request).toHaveBeenCalledTimes(1);
  });

  it("exposes an error without a render-triggered retry loop and can retry explicitly", async () => {
    const controller = {};
    const request = vi
      .fn()
      .mockRejectedValueOnce(new Error("Offline"))
      .mockResolvedValue(["recovered"]);
    const { result } = renderHook(() =>
      useLoadableCollection({ ...messages, controller, loadItems: request }),
    );
    await act(async () => {
      await result.current.load();
    });
    expect(result.current.error).toBe("Offline");
    expect(request).toHaveBeenCalledTimes(1);
    await act(async () => {
      await result.current.reload();
    });
    expect(result.current.items).toEqual(["recovered"]);
    expect(result.current.error).toBe("");
  });

  it("ignores a response from an obsolete controller", async () => {
    let resolveOld;
    const old = {};
    const next = {};
    const { result, rerender } = renderHook(
      ({ controller }) =>
        useLoadableCollection({
          ...messages,
          controller,
          loadItems: (value) =>
            value === old
              ? new Promise((resolve) => {
                  resolveOld = resolve;
                })
              : Promise.resolve(["new"]),
        }),
      { initialProps: { controller: old } },
    );
    let pending;
    act(() => {
      pending = result.current.load();
    });
    rerender({ controller: next });
    await act(async () => {
      await result.current.load();
      resolveOld(["old"]);
      await pending;
    });
    expect(result.current.items).toEqual(["new"]);
  });

  it("creates a gallery without publishing a WordPress page", async () => {
    const controller = {
      createGallery: vi.fn().mockResolvedValue({ item: { id: 7 } }),
      createGalleryPage: vi.fn(),
      listGalleries: vi.fn().mockResolvedValue({ items: [{ id: 7 }] }),
    };
    const { result } = renderHook(() => useGalleriesState(controller));
    await act(async () => {
      await result.current.createGallery({
        name: "Quiet moments",
        lightbox_override: false,
      });
    });
    expect(controller.createGallery).toHaveBeenCalledWith(
      "Quiet moments",
      "",
      "basic-grid",
      { lightbox_override: false },
    );
    expect(controller.createGalleryPage).not.toHaveBeenCalled();
    await waitFor(() => expect(result.current.galleries).toEqual([{ id: 7 }]));
  });
});
