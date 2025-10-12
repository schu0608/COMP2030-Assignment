<?php require_once dirname(__DIR__).'/inc/init.inc.php';
$pageTitle = 'Browse Skills';
include dirname(__DIR__).'/templates/header.php';
?>
<h1>Find skills</h1>

<div class="browse-layout">
  <!-- LEFT FILTERS -->
  <aside class="filter-card">
    <h3>Keywords</h3>
    <div id="active-chips" class="chips"></div>

    <form id="browse-form" class="filters stack">
      <label class="stack-sm">
        <span class="label">Search</span>
        <input name="q" placeholder="Search skills or people…">
      </label>

      <label class="stack-sm">
        <span class="label">Category</span>
        <select name="category">
          <option value="">Any</option>
          <option>Academic Help</option>
          <option>Tech Support</option>
          <option>Life Skills</option>
          <option>Practical</option>
        </select>
      </label>

      <fieldset class="stack-sm">
        <legend class="label">Skill level (year)</legend>
        <label class="radio"><input type="radio" name="year" value="" checked> Any</label>
        <label class="radio"><input type="radio" name="year" value="1"> 1st Year</label>
        <label class="radio"><input type="radio" name="year" value="2"> 2nd Year</label>
        <label class="radio"><input type="radio" name="year" value="3"> 3rd Year</label>
        <label class="radio"><input type="radio" name="year" value="4"> 4th Year</label>
        <label class="radio"><input type="radio" name="year" value="5"> 5th Year</label>
      </fieldset>

      <button class="btn" type="submit">Apply</button>
    </form>
  </aside>

  <!-- RIGHT CONTENT -->
  <section class="results-col">
    <div class="toolbar">
      <div class="toolbar-left">
        <div class="search-inline">
          <input form="browse-form" name="q" placeholder="Search…" />
        </div>
      </div>
      <div class="toolbar-right">
        <div class="pill-switch" id="sort-switch" data-value="new">
          <button type="button" data-sort="new" class="active">New</button>
          <button type="button" data-sort="rating">Rating</button>
        </div>
      </div>
    </div>

    <div id="offered-empty" class="muted" style="display:none">No offered skills match your filters.</div>
    <section id="results" class="card-grid"></section>

    <h2 style="margin-top:2rem">Requested skills (Earn FUSSCredits)</h2>
    <div id="requested-empty" class="muted" style="display:none">No requested skills found.</div>
    <section id="req-results" class="card-grid"></section>
  </section>
</div>

<?php include dirname(__DIR__) . '/templates/footer.php'; ?>
