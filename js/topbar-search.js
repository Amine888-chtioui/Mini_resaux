/**
 * Topbar Search — Live user search with dropdown
 * Uses /api/search.php (dedicated endpoint, no blocks table dependency)
 */
(function () {
  document.addEventListener("DOMContentLoaded", initTopbarSearch);

  function initTopbarSearch() {
    const wrap = document.querySelector(".topbar-search");
    if (!wrap) return;
    const input = wrap.querySelector("input");
    if (!input) return;

    /* ── CSS ── */
    if (!document.getElementById("ts-style")) {
      const style = document.createElement("style");
      style.id = "ts-style";
      style.textContent = `
        .topbar-search { position: relative; z-index: 1060; }
        .ts-dropdown {
          position: absolute;
          top: calc(100% + 8px);
          left: 0; right: 0;
          background: #fff;
          border-radius: 12px;
          box-shadow: 0 8px 32px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.10);
          z-index: 2000;
          overflow: hidden;
          animation: tsSlide .18s ease;
          min-width: 280px;
          max-height: 400px;
          overflow-y: auto;
        }
        @keyframes tsSlide {
          from { opacity:0; transform:translateY(-6px); }
          to   { opacity:1; transform:translateY(0); }
        }
        .ts-header {
          padding: 8px 14px 6px;
          font-size: 0.7rem; font-weight: 700;
          color: #65676b; letter-spacing: 0.8px; text-transform: uppercase;
          background: #f8f9fa; border-bottom: 1px solid #e9ecef;
        }
        .ts-item {
          display: flex; align-items: center; gap: 10px;
          padding: 10px 14px; cursor: pointer;
          transition: background .15s;
          text-decoration: none !important; color: inherit !important;
          border-bottom: 1px solid #f5f5f5;
        }
        .ts-item:last-child { border-bottom: none; }
        .ts-item:hover, .ts-item:focus { background: #e7f3ff; outline: none; }
        .ts-avatar {
          width: 42px; height: 42px; border-radius: 50%;
          display: flex; align-items: center; justify-content: center;
          font-weight: 800; font-size: 1.05rem; color: #fff; flex-shrink: 0;
        }
        .ts-info { flex: 1; min-width: 0; }
        .ts-name {
          font-weight: 700; font-size: 0.92rem; color: #1c1e21;
          white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .ts-sub { font-size: 0.76rem; color: #65676b; margin-top: 1px; }
        .ts-arrow { color: #adb5bd; font-size: 1rem; flex-shrink: 0; }
        .ts-empty {
          padding: 24px 14px; text-align: center;
          color: #65676b; font-size: 0.88rem;
        }
        .ts-empty i { font-size: 2rem; display: block; margin-bottom: 8px; opacity: .35; }
        .ts-loading {
          padding: 18px 14px;
          display: flex; align-items: center; justify-content: center; gap: 10px;
          color: #65676b; font-size: 0.85rem;
        }
        .ts-spinner {
          width: 18px; height: 18px;
          border: 2px solid #dddfe2; border-top-color: #1877f2;
          border-radius: 50%; animation: tsSpin .7s linear infinite; flex-shrink: 0;
        }
        @keyframes tsSpin { to { transform: rotate(360deg); } }
        .ts-highlight { color: #1877f2; font-weight: 800; }
        .ts-badge {
          display: inline-flex; align-items: center; gap: 3px;
          font-size: 0.7rem; font-weight: 700; padding: 1px 6px; border-radius: 99px;
        }
        .ts-badge-friend  { background:#e6f4ea; color:#2e7d32; }
        .ts-badge-pending { background:#fff3e0; color:#e65100; }
      `;
      document.head.appendChild(style);
    }

    /* ── State ── */
    let dropdown = null,
      timer = null,
      lastQ = "";

    /* ── Helpers ── */
    const palettes = [
      ["#1877f2", "#1565d8"],
      ["#42b72a", "#36a420"],
      ["#e53935", "#b71c1c"],
      ["#f7981c", "#e67e00"],
      ["#8e24aa", "#6a1b9a"],
      ["#00897b", "#00695c"],
      ["#1565d8", "#0d47a1"],
      ["#d81b60", "#880e4f"],
    ];
    const gradient = (name) => {
      const i = (name.charCodeAt(0) || 0) % palettes.length;
      return `linear-gradient(135deg,${palettes[i][0]},${palettes[i][1]})`;
    };
    const escHtml = (s) =>
      String(s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
    const highlight = (t, q) =>
      !q
        ? escHtml(t)
        : escHtml(t).replace(
            new RegExp(
              "(" + q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + ")",
              "gi",
            ),
            '<span class="ts-highlight">$1</span>',
          );
    const getBase = () => (typeof BASE_URL !== "undefined" ? BASE_URL : "");

    /* ── Dropdown helpers ── */
    const open = () => {
      if (!dropdown) {
        dropdown = document.createElement("div");
        dropdown.className = "ts-dropdown";
        wrap.appendChild(dropdown);
      }
    };
    const close = () => {
      if (dropdown) {
        dropdown.remove();
        dropdown = null;
      }
    };

    const showLoading = () => {
      open();
      dropdown.innerHTML = `<div class="ts-loading"><div class="ts-spinner"></div>Recherche…</div>`;
    };
    const showEmpty = (q) => {
      open();
      dropdown.innerHTML = `<div class="ts-empty"><i class="ri-user-search-line"></i>Aucun utilisateur pour « ${escHtml(q)} »</div>`;
    };
    const showError = (m) => {
      open();
      dropdown.innerHTML = `<div class="ts-empty"><i class="ri-error-warning-line"></i>${escHtml(m || "Erreur. Réessayez.")}</div>`;
    };

    function showResults(users, query) {
      open();
      if (!users.length) {
        showEmpty(query);
        return;
      }
      dropdown.innerHTML = `<div class="ts-header">👤 ${users.length} utilisateur${users.length > 1 ? "s" : ""} trouvé${users.length > 1 ? "s" : ""}</div>`;
      users.forEach((u) => {
        const a = document.createElement("a");
        a.className = "ts-item";
        a.href = `${getBase()}/profile.php?id=${u.id}`;
        a.tabIndex = 0;
        let badge =
          u.friendship_status === "friend"
            ? `<span class="ts-badge ts-badge-friend">👥 Ami</span>`
            : u.friendship_status === "pending"
              ? `<span class="ts-badge ts-badge-pending">⏳ En attente</span>`
              : "";
        const sub =
          badge ||
          (u.bio
            ? escHtml(u.bio.substring(0, 45)) + (u.bio.length > 45 ? "…" : "")
            : "Voir le profil");
        a.innerHTML = `
          <div class="ts-avatar" style="background:${gradient(u.username)}">${u.username.charAt(0).toUpperCase()}</div>
          <div class="ts-info">
            <div class="ts-name">${highlight(u.username, query)}</div>
            <div class="ts-sub">${sub}</div>
          </div>
          <i class="ri-arrow-right-s-line ts-arrow"></i>`;
        a.addEventListener("click", (e) => {
          e.preventDefault();
          close();
          input.value = "";
          window.location.href = `${getBase()}/profile.php?id=${u.id}`;
        });
        dropdown.appendChild(a);
      });
    }

    /* ── API call ── */
    async function fetchUsers(q) {
      const res = await fetch(
        `${getBase()}/api/search.php?q=${encodeURIComponent(q)}`,
      );
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      if (!data.success) throw new Error(data.error || "Erreur API");
      return data.users;
    }

    /* ── Input ── */
    input.addEventListener("input", () => {
      const q = input.value.trim();
      clearTimeout(timer);
      if (q.length < 2) {
        close();
        lastQ = "";
        return;
      }
      if (q === lastQ && dropdown) return;
      lastQ = q;
      showLoading();
      timer = setTimeout(async () => {
        if (input.value.trim() !== q) return;
        try {
          const u = await fetchUsers(q);
          if (input.value.trim() === q) showResults(u, q);
        } catch (e) {
          console.error("[Search]", e);
          showError("Impossible de charger les résultats.");
        }
      }, 350);
    });

    /* ── Outside click ── */
    document.addEventListener("click", (e) => {
      if (!wrap.contains(e.target)) close();
    });

    /* ── Keyboard nav ── */
    input.addEventListener("keydown", (e) => {
      if (!dropdown) return;
      const items = Array.from(dropdown.querySelectorAll(".ts-item"));
      const idx = items.indexOf(document.activeElement);
      if (e.key === "ArrowDown") {
        e.preventDefault();
        (items[Math.min(idx + 1, items.length - 1)] || items[0])?.focus();
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        idx > 0 ? items[idx - 1].focus() : input.focus();
      } else if (e.key === "Escape") {
        close();
        input.blur();
      }
    });

    /* ── Re-show on focus ── */
    input.addEventListener("focus", () => {
      const q = input.value.trim();
      if (q.length >= 2 && !dropdown) {
        showLoading();
        fetchUsers(q)
          .then((u) => {
            if (input.value.trim() === q) showResults(u, q);
          })
          .catch((e) => {
            console.error("[Search]", e);
            showError("Impossible de charger les résultats.");
          });
      }
    });
  }
})();
