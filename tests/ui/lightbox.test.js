import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { beforeEach, describe, expect, it, vi } from "vitest";

const source = readFileSync(
  resolve("assets/frontend/prox-gallery-frontend.js"),
  "utf8",
);
const item = (id, gallery) =>
  `<a href="https://example.test/${id}.jpg" data-prox-gallery-lightbox="1" data-prox-gallery-id="${gallery}" data-prox-image-id="${id}" data-prox-image-title="Image ${id}"><img alt="Description ${id}" /></a>`;
beforeEach(() => {
  document.body.innerHTML = `<main><section class="prox-gallery__group" data-prox-gallery-id="1">${item(1, 1)}${item(2, 1)}</section><section class="prox-gallery__group" data-prox-gallery-id="2">${item(3, 2)}</section></main>`;
  window.proxGalleryTracking = undefined;
  window.eval(source);
  document.dispatchEvent(new Event("DOMContentLoaded"));
});

describe("public image viewer", () => {
  it("keeps navigation within the opened gallery and preserves alternative text", () => {
    const links = document.querySelectorAll("a");
    links[0].click();
    document.querySelector("[aria-label='Next image']").click();
    expect(document.querySelector(".prox-gallery-lightbox__image").src).toBe(
      "https://example.test/2.jpg",
    );
    expect(document.querySelector(".prox-gallery-lightbox__image").alt).toBe(
      "Description 2",
    );
    document.querySelector("[aria-label='Next image']").click();
    expect(document.querySelector(".prox-gallery-lightbox__image").src).toBe(
      "https://example.test/1.jpg",
    );
  });
  it("restores focus, scrolling and background interaction after Escape", () => {
    document.body.style.overflow = "scroll";
    const opener = document.querySelector("a");
    opener.click();
    expect(document.activeElement.getAttribute("aria-label")).toBe(
      "Close image viewer",
    );
    expect(document.querySelector("main").inert).toBe(true);
    document.dispatchEvent(
      new KeyboardEvent("keydown", { key: "Escape", bubbles: true }),
    );
    expect(document.activeElement).toBe(opener);
    expect(document.body.style.overflow).toBe("scroll");
    expect(document.querySelector("main").inert).not.toBe(true);
  });
  it("renders attachment descriptions as text", () => {
    const link = document.querySelector("a");
    link.dataset.proxImageDescription = "<img src=x onerror=alert(1)>";
    link.click();
    const info = document.querySelector(".prox-gallery-lightbox__info");
    expect(info.textContent).toBe("<img src=x onerror=alert(1)>");
    expect(info.querySelector("img")).toBeNull();
  });
});
