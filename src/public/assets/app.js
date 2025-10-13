/* ==========================================================================
   FUSS — Frontend JS (vanilla)
   - Header credits
   - Browse filters/results
   - Category thumbnails for cards
   ========================================================================== */

const $  = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (m) => ({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" }[m]));

/* ---------- Category → image mapping ------------------------------------ */
const CAT_IMAGES = {
  "academic help": "/assets/cat/academic.svg",
  "tech support":   "/assets/cat/tech.svg",
  "life skills":    "/assets/cat/life.svg",
  "practical":      "/assets/cat/practical.svg"
};
const DEFAULT_IMAGE = "/assets/cat/default.svg";
function thumbFor(category) {
  const key = (category || "").toLowerCase().trim();
  return CAT_IMAGES[key] || DEFAULT_IMAGE;
}

/* ---------- Header: credit balance -------------------------------------- */
(async function updateHeaderCredits() {
  const el = $("#nav-credit-balance");
  if (!el) return;
  try {
    const r = await fetch("/actions/me_balance.php", { credentials: "same-origin" });
    if (!r.ok) throw new Error("Bad status");
    const j = await r.json();
    if (typeof j.balance !== "undefined") el.textContent = j.balance;
  } catch { /* leave as — */ }
})();

/* ---------- Browse page -------------------------------------------------- */
(function browseModule() {
  const form           = $("#browse-form");
  const chips          = $("#active-chips");
  const sortSwitch     = $("#sort-switch");
  const offeredGrid    = $("#results");
  const requestedGrid  = $("#req-results");
  const offeredEmpty   = $("#offered-empty")   || { style: { display: "" } };
  const requestedEmpty = $("#requested-empty") || { style: { display: "" } };
  if (!form || !offeredGrid || !requestedGrid) return;

  const CSRF = (document.querySelector('meta[name="csrf-token"]')?.content) || '';

  const state = { q: "", category: "", year: "", sort: "new" };

  function readForm() {
    const fd = new FormData(form);
    state.q        = (fd.get("q") || "").toString().trim();
    state.category = (fd.get("category") || "").toString();
    state.year     = (fd.get("year") || "").toString();
  }

  function renderChips() {
    if (!chips) return;
    const items = [];
    if (state.q)        items.push(["q", state.q]);
    if (state.category) items.push(["category", state.category]);
    if (state.year)     items.push(["year", state.year]);
    chips.innerHTML = items.length
      ? items.map(([k,v]) => `<button type="button" class="chip" data-key="${k}" title="Remove">${esc(v)} ×</button>`).join("")
      : '<span class="muted">No active filters</span>';
  }
  chips?.addEventListener("click",(e)=>{
    const btn = e.target.closest(".chip"); if(!btn) return;
    const key = btn.getAttribute("data-key");
    if (key === "q")        form.querySelector('input[name="q"]').value = "";
    if (key === "category") form.querySelector('select[name="category"]').value = "";
    if (key === "year")     form.querySelector('input[name="year"][value=""]').checked = true;
    runSearch();
  });

  if (sortSwitch) {
    sortSwitch.addEventListener("click",(e)=>{
      const btn = e.target.closest("button[data-sort]"); if(!btn) return;
      state.sort = btn.getAttribute("data-sort") || "new";
      $$(".active", sortSwitch).forEach((b)=>b.classList.remove("active"));
      btn.classList.add("active");
      runSearch();
    });
  }

  const stars = (avg) => {
    const a = Math.round(Number(avg) || 0);
    const full = Math.max(0, Math.min(5, a));
    return "★★★★★☆☆☆☆☆".slice(5 - full, 10 - full);
  };

  /* UPDATED: adds inline background-image via thumbFor(category) */
  const offeredCard = (r) => `
    <a class="skill-card" href="/skill.php?id=${r.offer_id}">
      <div class="thumb" style="background-image:url('${thumbFor(r.category)}');"></div>
      <div class="meta">
        <div class="provider">${esc(r.full_name)}</div>
        <div class="title">${esc(r.name)}</div>
        <div class="sub">${esc(r.category)}</div>
        <div class="rating" title="${((r.avg_rating ?? 0) * 1).toFixed(1)}">
          <span class="stars">${stars(r.avg_rating)}</span>
          <span class="count">(${r.rating_count || 0})</span>
        </div>
      </div>
    </a>`;

  const requestedCard = (r) => `
    <article class="skill-card">
      <div class="thumb" style="background-image:url('${thumbFor(r.category)}');"></div>
      <div class="meta">
        <div class="provider">${esc(r.full_name)}</div>
        <div class="title">${esc(r.name)}</div>
        <div class="sub">${esc(r.category)} • requested</div>
        <form method="post" action="/actions/request_offer.php" class="inline-offer" style="margin-top:8px;display:flex;gap:6px;align-items:center">
          <input type="hidden" name="csrf" value="${esc(CSRF)}">
          <input type="hidden" name="request_id" value="${r.request_id}">
          <label style="font-size:.9rem">Hours
            <input type="number" name="hours" min="0.5" step="0.5" value="1" required style="width:70px;margin-left:6px">
          </label>
          <button class="btn">Offer to help</button>
        </form>
      </div>
    </article>`;

  async function runSearch(e) {
    if (e) e.preventDefault();
    readForm(); renderChips();

    const params = new URLSearchParams({
      q: state.q, category: state.category, year: state.year, sort: state.sort,
    });

    offeredGrid.innerHTML = '<p class="muted">Loading…</p>';
    requestedGrid.innerHTML = '';

    try {
      const res  = await fetch("/actions/browse.php?" + params.toString(), { credentials: "same-origin" });
      const text = await res.text();
      let data;
      try { data = JSON.parse(text); }
      catch { offeredGrid.innerHTML = '<p class="error">Could not load results (invalid JSON).</p>'; return; }

      if (data && data.error) {
        offeredGrid.innerHTML = `<p class="error">Could not load results: ${esc(data.message)}</p>`;
        return;
      }

      const offered   = Array.isArray(data?.offered)   ? data.offered   : [];
      const requested = Array.isArray(data?.requested) ? data.requested : [];

      offeredGrid.innerHTML   = offered.map(offeredCard).join("") || "";
      requestedGrid.innerHTML = requested.map(requestedCard).join("") || "";

      (offered.length   ? offeredEmpty.style.display   = "none" : offeredEmpty.style.display   = "block");
      (requested.length ? requestedEmpty.style.display = "none" : requestedEmpty.style.display = "block");
    } catch {
      offeredGrid.innerHTML = '<p class="error">Network error.</p>';
    }
  }

  form.addEventListener("submit", runSearch);
  $$("select,input[type=radio]", form).forEach((el) => el.addEventListener("change", runSearch));
  runSearch();
})();

/* ---------- Decorate any server-rendered cards (homepage etc.) ----------- */
(function decorateExistingCards(){
  $$(".skill-card").forEach(card => {
    const thumb = $(".thumb", card);
    if (!thumb) return;
    const sub = $(".sub", card);
    const cat = sub ? sub.textContent.toLowerCase() : "";
    if (!thumb.style.backgroundImage) {
      thumb.style.backgroundImage = `url('${thumbFor(cat)}')`;
      thumb.style.backgroundSize = "cover";
      thumb.style.backgroundPosition = "center";
    }
  });
})();


