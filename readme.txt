=== Accessible Accordion Block with FAQ Schema ===
Contributors: codeinkstudios
Tags: faq, accordion, schema, gutenberg, accessibility
Requires at least: 6.3
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 3.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, accessible Gutenberg block for FAQ accordions with optional FAQPage JSON-LD schema. Theme-inheriting, zero dependencies, no tracking.

== Description ==

Adds an **Accessible FAQ Accordion** block to the WordPress block editor. The block outputs semantic HTML with an optional configurable heading (H2–H6) and can emit **FAQPage JSON-LD schema** when toggled on per block.

**Highlights**

* **Accessible by default.** Two collapsible variants — native `<details>`/`<summary>` (zero JavaScript, recommended) or a custom `<button>` + ARIA wiring (`aria-expanded`, `aria-controls`, `role="region"`). Both are fully keyboard-operable.
* **Theme-inheriting.** No hardcoded typography or color — your theme's fonts, sizes, weights, and colors apply automatically. Uses `theme.json` spacing presets with sensible fallbacks.
* **Lightweight.** No jQuery, no build step, no external requests, no tracking. The optional frontend script (about 0.5 KB) loads only when at least one block on the page uses the legacy JS-accordion variant. The native `<details>` variant ships zero JavaScript.
* **Optional FAQPage schema.** JSON-LD is opt-in per block (off by default). Generated server-side from sanitized data when enabled. See the FAQ below for context on Google's May 2026 deprecation and why the markup is still worth emitting on the right pages.
* **Default `#faq` anchor.** Wrapper `<div>` is given `id="faq"` by default so visitors can link straight to `yoursite.com/page/#faq`. Override per-block via the "HTML anchor" field under Advanced.
* **Security-first.** All input is sanitized server-side; all output is escaped at the point of output; JSON-LD is hex-encoded against `</script>` breakout.
* **Three layout modes.** Open (default): all answers visible — no JavaScript. Native `<details>`: collapsible with the browser's built-in disclosure element, zero JavaScript. JS accordion: collapsible via a tiny `<button>` toggle script (kept for backward compatibility).

**Markup output**

When collapsible mode is **off** (open, default):

`<div id="faq" class="cis_accordion">
  <h2 class="cis_accordion__title">Frequently Asked Questions</h2>
  <dl class="cis_accordion__list">
    <dt class="cis_accordion__question">Question text</dt>
    <dd class="cis_accordion__answer"><p>Answer text</p></dd>
  </dl>
</div>`

When collapsible mode is **on** with **native `<details>`** (recommended):

`<div id="faq" class="cis_accordion cis_accordion--collapsible cis_accordion--native">
  <div class="cis_accordion__list">
    <details class="cis_accordion__item">
      <summary class="cis_accordion__question">Question text</summary>
      <div class="cis_accordion__answer"><p>Answer text</p></div>
    </details>
  </div>
</div>`

When collapsible mode is **on** with the **legacy JS accordion** (no native details):

`<div id="faq" class="cis_accordion cis_accordion--collapsible">
  <dl class="cis_accordion__list">
    <dt class="cis_accordion__question">
      <button class="cis_accordion__trigger" type="button" aria-expanded="false" aria-controls="…">
        <span class="cis_accordion__trigger-text">Question text</span>
      </button>
    </dt>
    <dd class="cis_accordion__answer" role="region" aria-labelledby="…" hidden>
      <p>Answer text</p>
    </dd>
  </dl>
</div>`

When the schema toggle is **on**, a `<script type="application/ld+json">` block containing the `FAQPage` schema is appended inside the wrapper `<div>` regardless of which layout mode is active.

== Installation ==

1. From the WordPress dashboard, go to **Plugins → Add New** and search for "Accessible Accordion Block with FAQ Schema".
2. Click **Install Now**, then **Activate**.
3. In any post or page, add the **Accessible FAQ Accordion** block from the block inserter.

Manual installation:

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate through the **Plugins** menu in WordPress.

== Frequently Asked Questions ==

= Does this plugin make any external requests? =

No. The plugin does not phone home, load remote assets, or include third-party services.

= Does it work with any theme? =

Yes. Typography, colors, and spacing inherit from your active theme. The plugin uses theme.json spacing presets (`--wp--preset--spacing--*`) where available and falls back to reasonable defaults otherwise.

= Is the JSON-LD schema configurable? =

The schema is generated automatically from the question/answer pairs you enter and conforms to schema.org's `FAQPage` type. Schema emission is **off by default** in v3.0+ — toggle it on per block via the **Schema** panel in the Inspector sidebar when this FAQ block is the primary content of the page (or when you want to provide structured Q/A signal to AI search surfaces).

= Why is FAQ schema off by default in v3.0? =

Google removed FAQ rich results from Search on 7 May 2026 (full removal slated for June 2026). The markup itself remains valid structured data — Bing still consumes it and LLM-based search surfaces likely do too — but it no longer earns a Search rich result. The cleanest default is therefore "off, opt in when accurate," which mirrors the approach recommended by structured-data advocates (e.g. Joost de Valk's [FAQ schema cycle](https://joost.blog/faq-schema-cycle/)). Use schema where the FAQ is the page's main content, or where you specifically want to feed structured Q/A to AI engines. Skip it on product, service, or article pages where a Q/A block is just one section of a larger page.

Existing v2.x blocks keep schema **on** after upgrading — the off-by-default rule applies only to newly inserted v3.0 blocks. You can turn it off on existing blocks via the same toggle.

= What's the difference between the native &lt;details&gt; mode and the JS accordion? =

Native mode uses the browser's built-in `<details>`/`<summary>` disclosure element. Zero JavaScript ships to the page, native keyboard accessibility, native screen-reader semantics. Recommended for new blocks. The JS accordion is the v2.x implementation: a `<button>` with `aria-expanded`/`aria-controls` toggled by a small (~0.5 KB) script. Both are accessible; the native variant is simpler and lighter. Existing v2.x blocks stay on the JS accordion to preserve their current behavior after upgrade.

= Why use a `<dl>` (definition list)? =

A definition list is the most semantically appropriate native HTML structure for question/answer pairs. The `<dt>` (term) holds the question and `<dd>` (definition) holds the answer.

= Does the collapsible mode work without JavaScript? =

The collapsible/accordion behavior requires JavaScript to toggle visibility. If JavaScript is disabled, collapsible mode degrades gracefully: answers remain visible (because hidden state is set in HTML, but content is still in the DOM and the `hidden` attribute keeps it accessible to assistive tech that respects it). For maximum no-JS accessibility, leave collapsible mode off — all answers are always visible and fully indexable.

= Can I have multiple FAQ blocks on the same page? =

Yes. By default each block gets the anchor `#faq`. If you have more than one block on a page, set unique anchors via the **Advanced → HTML anchor** field on each block.

== Screenshots ==

1. The block in the editor: add question/answer pairs, reorder, set a title.
2. Sidebar controls: heading level (H2–H6) and collapsible toggle.
3. Frontend output: theme-inheriting, semantic, accessible.

== Changelog ==

= 3.0.0 =
* **FAQPage JSON-LD schema is now opt-in.** Off by default for newly inserted blocks. Toggle on per block via the new **Schema** panel in the Inspector sidebar. Existing v2.x blocks continue to emit schema on upgrade (their behavior is preserved); the new default applies to new blocks only. Context: Google removed FAQ rich results from Search on 7 May 2026.
* **Native `<details>`/`<summary>` collapsible mode added.** When Collapsible is on, a second toggle ("Use native &lt;details&gt; element") switches from the v2.x JS-button implementation to the browser's native disclosure element. Zero JavaScript on the page. New blocks default to native mode; existing v2.x collapsible blocks keep the JS accordion until manually switched.
* Internal: added `blockVersion` attribute for migration detection. Legacy v2.x blocks render with the old defaults until opened in the editor, at which point they migrate cleanly with no visible change.
* Updated FAQ documentation to reflect Google's May 2026 FAQ rich-result deprecation.

= 2.0.1 =
* Fixed: HTML anchor field saved an empty value and the wrapper id always rendered as the default `faq`. WordPress's built-in `supports.anchor` feature sources the anchor value from the saved HTML's `id` attribute, which fails for dynamic blocks (no saved HTML). Replaced with a custom anchor attribute and matching field in the Advanced sidebar that stores the value in the block comment JSON like a normal attribute.

= 2.0.0 =
* **Breaking change in the editor.** Answers now use native WordPress block editing — press Enter for a new paragraph, Shift+Enter for a line break, Cmd/Ctrl+K for a link. Lists, headings, blockquotes, and code blocks are all supported inside answers via the standard block inserter.
* Architecture: the block is now split into a parent (Accessible FAQ Accordion) and a child (FAQ Item). Each child holds its question as a string and its answer as nested blocks.
* Existing v1.x FAQ blocks will not be editable in the new UI and will render empty on the frontend. Delete and re-create any FAQ blocks created in v1.x. No sites were known to be using v1.x in production at the time of this release.
* Drag-and-drop reordering and the standard add/move/delete block controls now apply to FAQ items via the WordPress block toolbar.

= 1.1.1 =
* Fixed: HTML anchor (and default `#faq`) not appearing in the wrapper `<div>` on the frontend in some WordPress versions. The wrapper id is now applied deterministically.

= 1.1.0 =
* Multi-paragraph answers. Each FAQ answer can now have any number of paragraphs, added via an "+ Add paragraph" control. Each paragraph is its own rich-text field with full link / bold / italic support.
* Documented in the editor placeholder that links are added via Cmd/Ctrl+K (this has worked since 1.0.0 — now discoverable).
* Backward compatible: existing FAQs with single-string answers continue to render correctly and are auto-migrated to the new structure on next edit.

= 1.0.1 =
* Removed the question's bottom border when its answer is expanded, so an open item reads as one continuous block.
* Added a subtle 180ms fade + slide reveal animation when an answer expands. Gated behind `prefers-reduced-motion: no-preference` — users with motion-sensitivity preferences get the instant default.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 3.0.0 =
FAQPage JSON-LD schema is now opt-in (off by default) for newly inserted blocks. Existing FAQ blocks keep emitting schema after the upgrade — no action needed. New collapsible blocks now use native `<details>`/`<summary>` markup by default (zero JavaScript). Existing collapsible blocks keep the v2.x JS accordion until manually switched.

= 2.0.1 =
Fixes the HTML anchor field saving an empty value (the wrapper id was always rendering as the default "faq"). Any anchor you previously typed needs to be re-entered after upgrading.

= 2.0.0 =
Major editor refactor: native WordPress block editing inside FAQ answers (Enter = new paragraph, Shift+Enter = line break, lists/headings/links supported). Existing v1.x FAQ blocks must be deleted and re-created.

= 1.1.1 =
Fixes the wrapper id / HTML anchor not being applied on the frontend.

= 1.1.0 =
Adds multi-paragraph answer support. Backward compatible with existing 1.0.x FAQs.

= 1.0.1 =
Visual polish: cleaner open state and an opt-out-respecting expand animation.

= 1.0.0 =
Initial release.
