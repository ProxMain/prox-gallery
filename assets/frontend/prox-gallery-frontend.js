(function () {
  function trackingConfig() {
    if (!window.proxGalleryTracking) {
      return null;
    }

    var ajaxUrl = window.proxGalleryTracking.ajaxUrl || "";
    var action = window.proxGalleryTracking.action || "";

    if (!ajaxUrl || !action) {
      return null;
    }

    return {
      ajaxUrl: ajaxUrl,
      action: action
    };
  }

  function sendTrackingEvent(eventType, galleryId, imageId) {
    var config = trackingConfig();

    if (!config) {
      return;
    }

    var params = new URLSearchParams();
    params.append("action", config.action);
    params.append("event_type", eventType);

    if (galleryId > 0) {
      params.append("gallery_id", String(galleryId));
    }

    if (imageId > 0) {
      params.append("image_id", String(imageId));
    }

    if (navigator.sendBeacon) {
      var blob = new Blob([params.toString()], { type: "application/x-www-form-urlencoded; charset=UTF-8" });
      navigator.sendBeacon(config.ajaxUrl, blob);
      return;
    }

    fetch(config.ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
      },
      body: params.toString(),
      keepalive: true
    }).catch(function () {});
  }

  function buildLightbox() {
    var root = document.createElement("div");
    root.className = "prox-gallery-lightbox";
    root.setAttribute("aria-hidden", "true");
    root.innerHTML =
      '<div class="prox-gallery-lightbox__backdrop"></div>' +
      '<div class="prox-gallery-lightbox__content">' +
      '<button type="button" class="prox-gallery-lightbox__nav prox-gallery-lightbox__nav--prev" aria-label="Previous">&#10094;</button>' +
      '<img class="prox-gallery-lightbox__image" alt="" />' +
      '<button type="button" class="prox-gallery-lightbox__nav prox-gallery-lightbox__nav--next" aria-label="Next">&#10095;</button>' +
      '<button type="button" class="prox-gallery-lightbox__close" aria-label="Close">&times;</button>' +
      '<div class="prox-gallery-lightbox__caption"></div>' +
      "</div>";
    document.body.appendChild(root);
    return root;
  }

  function init() {
    var links = document.querySelectorAll("[data-prox-gallery-lightbox='1']");
    var gallerySections = document.querySelectorAll("[data-prox-gallery-id]");
    var visitedGalleryIds = {};

    gallerySections.forEach(function (section) {
      var rawGalleryId = section.getAttribute("data-prox-gallery-id") || "";
      var galleryId = parseInt(rawGalleryId, 10);

      if (!galleryId || visitedGalleryIds[galleryId]) {
        return;
      }

      visitedGalleryIds[galleryId] = true;
      sendTrackingEvent("gallery_visit", galleryId, 0);
    });

    if (!links.length) {
      return;
    }

    var lightbox = buildLightbox();
    var image = lightbox.querySelector(".prox-gallery-lightbox__image");
    var caption = lightbox.querySelector(".prox-gallery-lightbox__caption");
    var closeButton = lightbox.querySelector(".prox-gallery-lightbox__close");
    var backdrop = lightbox.querySelector(".prox-gallery-lightbox__backdrop");
    var prevButton = lightbox.querySelector(".prox-gallery-lightbox__nav--prev");
    var nextButton = lightbox.querySelector(".prox-gallery-lightbox__nav--next");
    var items = Array.prototype.slice.call(links);
    var currentIndex = -1;
    var isAnimating = false;
    var previousHtmlOverflow = "";
    var previousHtmlPaddingRight = "";
    var previousBodyOverflow = "";
    var previousBodyPaddingRight = "";

    function lockBodyScroll() {
      previousHtmlOverflow = document.documentElement.style.overflow;
      previousHtmlPaddingRight = document.documentElement.style.paddingRight;
      previousBodyOverflow = document.body.style.overflow;
      previousBodyPaddingRight = document.body.style.paddingRight;

      var scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
      document.documentElement.style.overflow = "hidden";
      document.body.style.overflow = "hidden";

      if (scrollbarWidth > 0) {
        document.documentElement.style.paddingRight = scrollbarWidth + "px";
        document.body.style.paddingRight = scrollbarWidth + "px";
      }
    }

    function unlockBodyScroll() {
      document.documentElement.style.overflow = previousHtmlOverflow;
      document.documentElement.style.paddingRight = previousHtmlPaddingRight;
      document.body.style.overflow = previousBodyOverflow;
      document.body.style.paddingRight = previousBodyPaddingRight;
    }

    function transitionOutClass(mode, direction) {
      if (mode === "fade") {
        return "is-transition-fade-out";
      }

      if (mode === "slide") {
        return direction === "prev" ? "is-transition-slide-out-right" : "is-transition-slide-out-left";
      }

      if (mode === "explode") {
        return "is-transition-explode-out";
      }

      if (mode === "implode") {
        return "is-transition-implode-out";
      }

      return "";
    }

    function transitionInClass(mode, direction) {
      if (mode === "fade") {
        return "is-transition-fade-in";
      }

      if (mode === "slide") {
        return direction === "prev" ? "is-transition-slide-in-left" : "is-transition-slide-in-right";
      }

      if (mode === "explode") {
        return "is-transition-explode-in";
      }

      if (mode === "implode") {
        return "is-transition-implode-in";
      }

      return "";
    }

    function clearTransitionClasses() {
      if (!image) {
        return;
      }

      image.classList.remove(
        "is-transition-fade-out",
        "is-transition-fade-in",
        "is-transition-slide-out-left",
        "is-transition-slide-out-right",
        "is-transition-slide-in-left",
        "is-transition-slide-in-right",
        "is-transition-explode-out",
        "is-transition-explode-in",
        "is-transition-implode-out",
        "is-transition-implode-in"
      );
    }

    function showIndex(index, direction) {
      if (!items.length || !image) {
        return;
      }

      if (isAnimating) {
        return;
      }

      var max = items.length - 1;
      var nextIndex = index;

      if (nextIndex < 0) {
        nextIndex = max;
      }

      if (nextIndex > max) {
        nextIndex = 0;
      }

      currentIndex = nextIndex;

      var link = items[currentIndex];
      var href = link.getAttribute("href") || "";
      var text = link.getAttribute("data-prox-gallery-caption") || "";
      var mode = (link.getAttribute("data-prox-gallery-transition") || "none").toLowerCase();

      if (!href) {
        return;
      }

      clearTransitionClasses();
      if (mode === "none" || image.getAttribute("src") === "") {
        image.setAttribute("src", href);
        if (caption) {
          caption.textContent = text;
        }
        return;
      }

      isAnimating = true;
      var outClass = transitionOutClass(mode, direction || "next");
      var inClass = transitionInClass(mode, direction || "next");

      if (outClass) {
        image.classList.add(outClass);
      }

      window.setTimeout(function () {
        clearTransitionClasses();
        image.setAttribute("src", href);
        if (caption) {
          caption.textContent = text;
        }

        if (inClass) {
          image.classList.add(inClass);
        }

        window.setTimeout(function () {
          clearTransitionClasses();
          isAnimating = false;
        }, 220);
      }, 180);
    }

    function close() {
      lightbox.classList.remove("is-open");
      lightbox.setAttribute("aria-hidden", "true");
      unlockBodyScroll();
      currentIndex = -1;
      if (image) {
        image.setAttribute("src", "");
      }
      if (caption) {
        caption.textContent = "";
      }
    }

    links.forEach(function (link) {
      link.addEventListener("click", function (event) {
        event.preventDefault();
        var index = items.indexOf(link);
        if (index < 0) {
          return;
        }
        var rawGalleryId = link.getAttribute("data-prox-gallery-id") || "";
        var rawImageId = link.getAttribute("data-prox-image-id") || "";
        var galleryId = parseInt(rawGalleryId, 10);
        var imageId = parseInt(rawImageId, 10);
        sendTrackingEvent("image_view", galleryId || 0, imageId || 0);
        showIndex(index, "next");
        lockBodyScroll();
        lightbox.classList.add("is-open");
        lightbox.setAttribute("aria-hidden", "false");
      });
    });

    if (closeButton) {
      closeButton.addEventListener("click", close);
    }

    if (backdrop) {
      backdrop.addEventListener("click", close);
    }

    if (prevButton) {
      prevButton.addEventListener("click", function () {
        if (isAnimating) {
          return;
        }

        if (items.length && currentIndex >= 0) {
          var prevIndex = currentIndex - 1 < 0 ? items.length - 1 : currentIndex - 1;
          var prevLink = items[prevIndex];
          var prevGalleryId = parseInt(prevLink.getAttribute("data-prox-gallery-id") || "0", 10) || 0;
          var prevImageId = parseInt(prevLink.getAttribute("data-prox-image-id") || "0", 10) || 0;
          sendTrackingEvent("image_view", prevGalleryId, prevImageId);
        }
        showIndex(currentIndex - 1, "prev");
      });
    }

    if (nextButton) {
      nextButton.addEventListener("click", function () {
        if (isAnimating) {
          return;
        }

        if (items.length && currentIndex >= 0) {
          var nextIndex = currentIndex + 1 > items.length - 1 ? 0 : currentIndex + 1;
          var nextLink = items[nextIndex];
          var nextGalleryId = parseInt(nextLink.getAttribute("data-prox-gallery-id") || "0", 10) || 0;
          var nextImageId = parseInt(nextLink.getAttribute("data-prox-image-id") || "0", 10) || 0;
          sendTrackingEvent("image_view", nextGalleryId, nextImageId);
        }
        showIndex(currentIndex + 1, "next");
      });
    }

    document.addEventListener("keydown", function (event) {
      if (!lightbox.classList.contains("is-open")) {
        return;
      }

      if (event.key === "Escape") {
        close();
        return;
      }

      if (event.key === "ArrowLeft") {
        if (isAnimating) {
          return;
        }

        if (items.length && currentIndex >= 0) {
          var leftIndex = currentIndex - 1 < 0 ? items.length - 1 : currentIndex - 1;
          var leftLink = items[leftIndex];
          var leftGalleryId = parseInt(leftLink.getAttribute("data-prox-gallery-id") || "0", 10) || 0;
          var leftImageId = parseInt(leftLink.getAttribute("data-prox-image-id") || "0", 10) || 0;
          sendTrackingEvent("image_view", leftGalleryId, leftImageId);
        }
        showIndex(currentIndex - 1, "prev");
        return;
      }

      if (event.key === "ArrowRight") {
        if (isAnimating) {
          return;
        }

        if (items.length && currentIndex >= 0) {
          var rightIndex = currentIndex + 1 > items.length - 1 ? 0 : currentIndex + 1;
          var rightLink = items[rightIndex];
          var rightGalleryId = parseInt(rightLink.getAttribute("data-prox-gallery-id") || "0", 10) || 0;
          var rightImageId = parseInt(rightLink.getAttribute("data-prox-image-id") || "0", 10) || 0;
          sendTrackingEvent("image_view", rightGalleryId, rightImageId);
        }
        showIndex(currentIndex + 1, "next");
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
