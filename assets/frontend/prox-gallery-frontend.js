(function () {
  "use strict";
  function track(type, galleryId, imageId) {
    var config = window.proxGalleryTracking;
    if (!config || !config.ajaxUrl || !config.action || !config.nonce) return;
    var body = new URLSearchParams({
      action: config.action,
      _ajax_nonce: config.nonce,
      event_type: type,
      gallery_id: String(galleryId),
      image_id: String(imageId || 0),
    });
    if (
      navigator.sendBeacon &&
      navigator.sendBeacon(
        config.ajaxUrl,
        new Blob([body.toString()], {
          type: "application/x-www-form-urlencoded; charset=UTF-8",
        }),
      )
    )
      return;
    fetch(config.ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: body.toString(),
      keepalive: true,
    }).catch(function () {
      /* Analytics failure must not block image viewing. */
    });
  }

  function init() {
    if (document.querySelector(".prox-gallery-lightbox")) return;
    var links = Array.from(
      document.querySelectorAll("[data-prox-gallery-lightbox='1']"),
    );
    var visited = new Set();
    document
      .querySelectorAll(".prox-gallery__group[data-prox-gallery-id]")
      .forEach(function (group) {
        var id = Number(group.dataset.proxGalleryId);
        if (id > 0 && !visited.has(id)) {
          visited.add(id);
          track("gallery_visit", id, 0);
        }
      });
    if (!links.length) return;

    var root = document.createElement("div");
    root.className = "prox-gallery-lightbox";
    root.setAttribute("role", "dialog");
    root.setAttribute("aria-modal", "true");
    root.setAttribute("aria-label", "Image viewer");
    root.setAttribute("aria-hidden", "true");
    // Static markup only; all attachment text is assigned through textContent below.
    root.innerHTML =
      '<div class="prox-gallery-lightbox__backdrop"></div><div class="prox-gallery-lightbox__content"><div class="prox-gallery-lightbox__topbar"><div class="prox-gallery-lightbox__title" aria-live="polite"></div><button type="button" class="prox-gallery-lightbox__info-toggle" aria-label="Toggle description" aria-expanded="false">&#9432;</button></div><button type="button" class="prox-gallery-lightbox__nav prox-gallery-lightbox__nav--prev" aria-label="Previous image">&#10094;</button><img class="prox-gallery-lightbox__image" alt="" /><button type="button" class="prox-gallery-lightbox__nav prox-gallery-lightbox__nav--next" aria-label="Next image">&#10095;</button><button type="button" class="prox-gallery-lightbox__close" aria-label="Close image viewer">&times;</button><div class="prox-gallery-lightbox__info" hidden></div></div>';
    document.body.appendChild(root);
    var image = root.querySelector("img");
    var title = root.querySelector(".prox-gallery-lightbox__title");
    var info = root.querySelector(".prox-gallery-lightbox__info");
    var infoButton = root.querySelector(".prox-gallery-lightbox__info-toggle");
    var previousButton = root.querySelector(
      ".prox-gallery-lightbox__nav--prev",
    );
    var nextButton = root.querySelector(".prox-gallery-lightbox__nav--next");
    var closeButton = root.querySelector(".prox-gallery-lightbox__close");
    var items = [];
    var index = 0;
    var opener = null;
    var overflow = "";
    var inertNodes = [];
    var timers = [];
    var animating = false;

    function clearAnimation() {
      timers.forEach(clearTimeout);
      timers = [];
      animating = false;
      image.className = "prox-gallery-lightbox__image";
    }
    function assign(link) {
      image.src = link.href;
      image.alt =
        link.querySelector("img")?.alt || link.dataset.proxImageTitle || "";
      title.textContent =
        (link.dataset.proxImageTitle || "Image") +
        " · " +
        (index + 1) +
        " / " +
        items.length;
      info.textContent = link.dataset.proxImageDescription || "";
      info.hidden = true;
      infoButton.hidden = !info.textContent.trim();
      infoButton.setAttribute("aria-expanded", "false");
      previousButton.hidden = nextButton.hidden = items.length <= 1;
      track(
        "image_view",
        Number(link.dataset.proxGalleryId),
        Number(link.dataset.proxImageId),
      );
    }
    function show(next, direction, initial) {
      if (!items.length || animating) return;
      index = (next + items.length) % items.length;
      var link = items[index];
      var mode = link.dataset.proxGalleryTransition || "none";
      if (
        initial ||
        mode === "none" ||
        window.matchMedia?.("(prefers-reduced-motion: reduce)").matches
      ) {
        clearAnimation();
        assign(link);
        return;
      }
      var outName =
        mode === "slide"
          ? "slide-out-" + (direction < 0 ? "right" : "left")
          : mode + "-out";
      var inName =
        mode === "slide"
          ? "slide-in-" + (direction < 0 ? "left" : "right")
          : mode + "-in";
      if (!["fade", "slide", "explode", "implode"].includes(mode)) {
        assign(link);
        return;
      }
      animating = true;
      image.classList.add("is-transition-" + outName);
      timers.push(
        setTimeout(function () {
          image.className = "prox-gallery-lightbox__image";
          assign(link);
          image.classList.add("is-transition-" + inName);
          timers.push(setTimeout(clearAnimation, 220));
        }, 180),
      );
    }
    function close() {
      if (!root.classList.contains("is-open")) return;
      clearAnimation();
      root.classList.remove("is-open");
      root.setAttribute("aria-hidden", "true");
      document.body.style.overflow = overflow;
      inertNodes.forEach(function (entry) {
        entry[0].inert = entry[1];
      });
      inertNodes = [];
      image.removeAttribute("src");
      opener?.focus();
    }
    links.forEach(function (link) {
      link.addEventListener("click", function (event) {
        event.preventDefault();
        opener = link;
        var group = link.closest(".prox-gallery__group");
        // Browsing stays inside the collection that the visitor opened.
        items = links.filter(function (candidate) {
          return group
            ? candidate.closest(".prox-gallery__group") === group
            : candidate.dataset.proxGalleryId === link.dataset.proxGalleryId;
        });
        overflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";
        inertNodes = Array.from(document.body.children)
          .filter(function (node) {
            return node !== root;
          })
          .map(function (node) {
            var entry = [node, node.inert];
            node.inert = true;
            return entry;
          });
        clearAnimation();
        root.classList.add("is-open");
        root.setAttribute("aria-hidden", "false");
        show(items.indexOf(link), 1, true);
        closeButton.focus();
      });
    });
    closeButton.addEventListener("click", close);
    root
      .querySelector(".prox-gallery-lightbox__backdrop")
      .addEventListener("click", close);
    root
      .querySelector(".prox-gallery-lightbox__content")
      .addEventListener("click", function (event) {
        if (event.target === event.currentTarget) close();
      });
    previousButton.addEventListener("click", function () {
      show(index - 1, -1);
    });
    nextButton.addEventListener("click", function () {
      show(index + 1, 1);
    });
    infoButton.addEventListener("click", function () {
      info.hidden = !info.hidden;
      infoButton.setAttribute("aria-expanded", String(!info.hidden));
    });
    document.addEventListener("keydown", function (event) {
      if (!root.classList.contains("is-open")) return;
      if (event.key === "Escape") {
        event.preventDefault();
        close();
      } else if (event.key === "ArrowLeft" || event.key === "ArrowRight") {
        event.preventDefault();
        show(
          index + (event.key === "ArrowLeft" ? -1 : 1),
          event.key === "ArrowLeft" ? -1 : 1,
        );
      } else if (event.key === "Tab") {
        var buttons = Array.from(root.querySelectorAll("button")).filter(
          function (button) {
            return !button.hidden && !button.disabled;
          },
        );
        var first = buttons[0],
          last = buttons[buttons.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    });
  }
  if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", init, { once: true });
  else init();
})();
