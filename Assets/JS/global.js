/**
 * global.js — Library Management System
 * Core JS: Dark mode, Toast notifications, Modals,
 * Navbar, Fetch helpers, Notification badge
 */

// ── 1. DARK MODE ──────────────────────────────────────────────
(function () {
  const THEME_KEY = "lms-theme";
  const root = document.documentElement;

  function applyTheme(theme) {
    root.setAttribute("data-theme", theme);
    const icon = document.getElementById("theme-icon");
    if (icon) {
      icon.innerHTML =
        theme === "dark"
          ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>'
          : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
    }
  }

  // Apply saved theme immediately (before paint)
  const savedTheme = localStorage.getItem(THEME_KEY) || "light";
  applyTheme(savedTheme);

  document.addEventListener("DOMContentLoaded", function () {
    // Wire all .theme-toggle / #theme-toggle buttons
    document
      .querySelectorAll(".theme-toggle, #theme-toggle")
      .forEach(function (btn) {
        btn.addEventListener("click", function () {
          const current = root.getAttribute("data-theme") || "light";
          const next = current === "dark" ? "light" : "dark";
          applyTheme(next);
          localStorage.setItem(THEME_KEY, next);
        });
      });
  });
})();

// ── 2. TOAST NOTIFICATION SYSTEM ─────────────────────────────
window.toast = (function () {
  let container = null;

  function getContainer() {
    if (!container) {
      container = document.getElementById("toast-container");
      if (!container) {
        container = document.createElement("div");
        container.id = "toast-container";
        container.style.cssText =
          "position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:0.5rem;";
        document.body.appendChild(container);
      }
    }
    return container;
  }

  return function toast(message, type, duration) {
    type = type || "success";
    duration = duration || 7000;

    const el = document.createElement("div");
    el.className = "toast toast-" + type;
    el.setAttribute("role", "alert");
    el.innerHTML =
      '<span class="toast-icon">' +
      {
        success: "✓",
        error: "✕",
        warning: "⚠",
        info: "ℹ",
      }[type] +
      "</span>" +
      '<span class="toast-message">' +
      message +
      "</span>" +
      '<button class="toast-close" aria-label="Close">×</button>';

    el.querySelector(".toast-close").addEventListener("click", function () {
      dismiss(el);
    });

    getContainer().appendChild(el);

    // Trigger enter animation
    requestAnimationFrame(function () {
      el.classList.add("toast-visible");
    });

    // Auto-dismiss
    setTimeout(function () {
      dismiss(el);
    }, duration);
  };

  function dismiss(el) {
    el.classList.remove("toast-visible");
    el.classList.add("toast-hiding");
    el.addEventListener(
      "transitionend",
      function () {
        el.remove();
      },
      { once: true },
    );
  }
})();

// ── 3. MODAL SYSTEM ──────────────────────────────────────────
window.openModal = function (modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  modal.classList.add("modal-open");
  document.body.style.overflow = "hidden";
};

window.closeModal = function (modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  modal.classList.remove("modal-open");
  document.body.style.overflow = "";
};

document.addEventListener("DOMContentLoaded", function () {
  // Close modal on overlay click
  document.querySelectorAll(".modal-overlay").forEach(function (overlay) {
    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) {
        overlay.classList.remove("modal-open");
        document.body.style.overflow = "";
      }
    });
  });

  // Close modal on .modal-close button click
  document.querySelectorAll(".modal-close").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const modal = btn.closest(".modal-overlay");
      if (modal) {
        modal.classList.remove("modal-open");
        document.body.style.overflow = "";
      }
    });
  });

  // ESC key closes any open modal
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      document
        .querySelectorAll(".modal-overlay.modal-open")
        .forEach(function (m) {
          m.classList.remove("modal-open");
          document.body.style.overflow = "";
        });
    }
  });
});

// ── 4. NAVBAR ACTIVE LINK ────────────────────────────────────
document.addEventListener("DOMContentLoaded", function () {
  var path = window.location.pathname;
  document
    .querySelectorAll(".nav-link, .sidebar-link")
    .forEach(function (link) {
      var href = link.getAttribute("href") || "";
      // Match on filename portion
      var linkFile = href.split("/").pop();
      var pathFile = path.split("/").pop();
      if (linkFile && linkFile === pathFile) {
        link.classList.add("active");
      }
    });
});

// ── 5. MOBILE NAVBAR ─────────────────────────────────────────
document.addEventListener("DOMContentLoaded", function () {
  var hamburger = document.getElementById("hamburger");
  var navLinks = document.getElementById("navbar-links");
  if (hamburger && navLinks) {
    hamburger.addEventListener("click", function () {
      navLinks.classList.toggle("open");
    });
    // Close on link click
    navLinks.querySelectorAll("a").forEach(function (a) {
      a.addEventListener("click", function () {
        navLinks.classList.remove("open");
      });
    });
  }
});

// ── 6. USER MENU DROPDOWN ────────────────────────────────────
document.addEventListener("DOMContentLoaded", function () {
  var trigger = document.querySelector(".user-menu-trigger");
  var dropdown = document.querySelector(".user-dropdown");
  if (trigger && dropdown) {
    trigger.addEventListener("click", function (e) {
      e.stopPropagation();
      dropdown.classList.toggle("active");
    });

    document.addEventListener("click", function () {
      dropdown.classList.remove("active");
    });
  }
});

// ── 7. NOTIFICATION BADGE ────────────────────────────────────
document.addEventListener("DOMContentLoaded", function () {
  var badge = document.getElementById("notif-count");
  if (!badge) return;
  fetch(
    "/PHP_programs/Library-MS/PHP/Notifications.php?action=count",
    { credentials: "same-origin" },
  )
    .then(function (r) {
      return r.json();
    })
    .then(function (data) {
      if (data && data.count > 0) {
        badge.textContent = data.count;
        badge.style.display = "inline-flex";
      } else {
        badge.style.display = "none";
      }
    })
    .catch(function () {
      badge.style.display = "none";
    });
});

// ── 8. CSRF & FETCH HELPERS ──────────────────────────────────
window.getCSRFToken = function () {
  var input = document.querySelector('input[name="csrf_token"]');
  return input ? input.value : "";
};

/**
 * POST JSON to a URL.
 * Automatically injects csrf_token into the body.
 * Returns parsed JSON.
 */
window.postJSON = function (url, data) {
  data = data || {};
  data.csrf_token = window.getCSRFToken();
  return fetch(url, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json; charset=utf-8" },
    body: JSON.stringify(data),
  }).then(function (r) {
    return r.json();
  });
};

/**
 * POST FormData (multipart) to a URL.
 * Automatically appends csrf_token.
 * Returns parsed JSON.
 */
window.postForm = function (url, formData) {
  if (!formData.get("csrf_token")) {
    formData.append("csrf_token", window.getCSRFToken());
  }
  return fetch(url, {
    method: "POST",
    credentials: "same-origin",
    body: formData,
  }).then(function (r) {
    return r.json();
  });
};

// ── 9. CONFIRM DIALOG (custom) ───────────────────────────────
window.confirmAction = function (message) {
  return new Promise(function (resolve) {
    var overlay = document.getElementById("confirm-overlay");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.id = "confirm-overlay";
      overlay.style.cssText =
        "position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;";
      overlay.innerHTML =
        '<div style="background:var(--surface,#fff);border-radius:12px;padding:2rem;max-width:400px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);">' +
        '<p id="confirm-msg" style="margin:0 0 1.5rem;font-size:1rem;color:var(--text,#1a2332);line-height:1.5;"></p>' +
        '<div style="display:flex;gap:0.75rem;justify-content:flex-end;">' +
        '<button id="confirm-no"  style="padding:0.5rem 1.25rem;border:1px solid var(--border,#e2e8f0);border-radius:8px;background:transparent;cursor:pointer;">Cancel</button>' +
        '<button id="confirm-yes" style="padding:0.5rem 1.25rem;border:none;border-radius:8px;background:#dc2626;color:#fff;cursor:pointer;">Confirm</button>' +
        "</div>" +
        "</div>";
      document.body.appendChild(overlay);
    }
    document.getElementById("confirm-msg").textContent =
      message || "Are you sure?";
    overlay.style.display = "flex";
    document.getElementById("confirm-yes").onclick = function () {
      overlay.style.display = "none";
      resolve(true);
    };
    document.getElementById("confirm-no").onclick = function () {
      overlay.style.display = "none";
      resolve(false);
    };
  });
};
