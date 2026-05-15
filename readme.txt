=== Accessible Accordion Block with FAQ Schema ===
Contributors: codeinkstudios
Tags: faq, accordion, schema, gutenberg, accessibility
Requires at least: 6.3
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, accessible Gutenberg block for FAQ accordions with FAQPage JSON-LD schema. Theme-inheriting, zero dependencies, no tracking.

== Description ==

Adds an **Accessible FAQ Accordion** block to the WordPress block editor. The block outputs a semantic `<dl>` definition list with an optional configurable heading (H2–H6) and emits **FAQPage JSON-LD schema** for search engines.

**Highlights**

* **Accessible by default.** When the optional collapsible mode is enabled, each question becomes a real `<button>` with `aria-expanded`, `aria-controls`, and the answer panel uses `role="region"` with `aria-labelledby`, fully keyboard-operable.
* **Theme-inheriting.** No hardcoded typography or color — your theme's fonts, sizes, weights, and colors apply automatically. Uses `theme.json` spacing presets with sensible fallbacks.
* **Lightweight.** No jQuery, no build step, no external requests, no tracking. The frontend script (about 0.5 KB) loads only when at least one FAQ block on the page uses the collapsible option.
* **SEO-ready.** Automatic `FAQPage` JSON-LD schema, generated server-side from sanitized data.
* **Default `#faq` anchor.** Wrapper `<div>` is given `id="faq"` by default so visitors can link straight to `yoursite.com/page/#faq`. Override per-block via the "HTML anchor" field under Advanced.
* **Security-first.** All input is sanitized server-side; all output is escaped at the point of output; JSON-LD is hex-encoded against `</script>` breakout.
* **Two modes.** Off (default): all answers visible — no JavaScript loaded. On: collapsible accordion with full a11y.

**Markup output**

When collapsible mode is **off** (default):

`<div id="faq" class="cis_accordion">
  <h2 class="cis_accordion__title">Frequently Asked Questions</h2>
  <dl class="cis_accordion__list">
    <dt class="cis_accordion__question">Question text</dt>
    <dd class="cis_accordion__answer"><p>Answer text</p></dd>
  </dl>
  <script type="application/ld+json">…FAQPage schema…</script>
</div>`

When collapsible mode is **on**:

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
  <script type="application/ld+json">…FAQPage schema…</script>
</div>`

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

The schema is generated automatically from the question/answer pairs you enter. It conforms to schema.org's `FAQPage` type. Note: Google has limited rich-result eligibility for FAQ schema (typically authoritative government and health sites), but the schema is still valid structured data.

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

= 1.0.1 =
* Removed the question's bottom border when its answer is expanded, so an open item reads as one continuous block.
* Added a subtle 180ms fade + slide reveal animation when an answer expands. Gated behind `prefers-reduced-motion: no-preference` — users with motion-sensitivity preferences get the instant default.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.1 =
Visual polish: cleaner open state and an opt-out-respecting expand animation.

= 1.0.0 =
Initial release.
