<?php
/**
 * Server-side render for cis/faq-item.
 *
 * Three output modes, selected by parent block context:
 *
 *   1. Open (collapsible context = false): <dt>/<dd> pair, no toggle.
 *   2. Native details (collapsible + useNativeDetails context both true):
 *      <details>/<summary> + answer wrapper. Zero JavaScript.
 *   3. JS accordion (collapsible = true, useNativeDetails = false): <dt> with
 *      <button> + ARIA wiring + hidden <dd>. Toggled by the parent block's
 *      enqueued frontend script.
 *
 * Available variables (provided by WP_Block::render):
 *
 * @var array    $attributes Block attributes (question).
 * @var string   $content    Rendered inner blocks: paragraphs, lists, etc.
 * @var WP_Block $block      Block instance (provides $block->context).
 *
 * @package CIS_AAFS
 */

defined( 'ABSPATH' ) || exit;

// --------------------------------------------------------------------------
// 1. Extract + validate inputs.
// --------------------------------------------------------------------------

$cis_aafs_item_q = isset( $attributes['question'] ) && is_string( $attributes['question'] )
	? trim( $attributes['question'] )
	: '';

// Skip rendering if both question and answer are empty. We strip tags from
// $content to detect "empty paragraph" cases (e.g., a <p></p> placeholder).
if ( '' === $cis_aafs_item_q && '' === trim( wp_strip_all_tags( (string) $content ) ) ) {
	return;
}

$cis_aafs_item_collapsible = isset( $block->context['cis/accessible-accordion-faq/collapsible'] )
	&& $block->context['cis/accessible-accordion-faq/collapsible'];
$cis_aafs_item_native      = isset( $block->context['cis/accessible-accordion-faq/useNativeDetails'] )
	&& $block->context['cis/accessible-accordion-faq/useNativeDetails'];

// --------------------------------------------------------------------------
// 2. Build per-instance IDs for ARIA wiring (JS-accordion mode only).
//
// Native <details> mode doesn't need them: the browser ties the summary to
// its parent details element via the DOM. Uses a per-request global counter
// to guarantee uniqueness across all instances on the page.
// --------------------------------------------------------------------------

if ( $cis_aafs_item_collapsible && ! $cis_aafs_item_native ) {
	// `static` is function-scoped in PHP and we're at script top-level here
	// (this file is required by WP_Block::render). Use a global instead.
	global $cis_aafs_item_counter;
	if ( ! isset( $cis_aafs_item_counter ) ) {
		$cis_aafs_item_counter = 0;
	}
	++$cis_aafs_item_counter;
	$cis_aafs_item_seed = substr(
		md5( (string) get_the_ID() . '-' . $cis_aafs_item_counter . '-' . $cis_aafs_item_q ),
		0,
		10
	);
	$cis_aafs_item_q_id = 'cis-aafs-' . $cis_aafs_item_seed . '-q';
	$cis_aafs_item_a_id = 'cis-aafs-' . $cis_aafs_item_seed . '-a';
}

// --------------------------------------------------------------------------
// 3. Sanitize question for output. Allow only minimal inline formatting.
// --------------------------------------------------------------------------

$cis_aafs_item_q_allowed = array(
	'strong' => array(),
	'b'      => array(),
	'em'     => array(),
	'i'      => array(),
);
$cis_aafs_item_q_html = wp_kses( $cis_aafs_item_q, $cis_aafs_item_q_allowed );

// $content is already escaped via the inner blocks' own renderers
// (core/paragraph, core/list, etc. all output safe HTML). Re-escaping
// would double-escape entities.

// --------------------------------------------------------------------------
// 4. Render.
// --------------------------------------------------------------------------

if ( $cis_aafs_item_collapsible && $cis_aafs_item_native ) :
	// Mode: Native <details>/<summary>. Zero JS. Parent renders a <div> list
	// wrapper (not <dl>) in this mode so <details> is valid HTML.
	?>
<details class="cis_accordion__item">
	<summary class="cis_accordion__question"><?php echo $cis_aafs_item_q_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() output above. ?></summary>
	<div class="cis_accordion__answer">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output of WP inner blocks (escape at their boundaries). ?>
	</div>
</details>
	<?php
elseif ( $cis_aafs_item_collapsible ) :
	// Mode: Legacy JS-accordion. <button> trigger with full ARIA wiring,
	// hidden <dd> answer revealed by the enqueued toggle script.
	?>
<dt class="cis_accordion__question">
	<button
		type="button"
		class="cis_accordion__trigger"
		id="<?php echo esc_attr( $cis_aafs_item_q_id ); ?>"
		aria-expanded="false"
		aria-controls="<?php echo esc_attr( $cis_aafs_item_a_id ); ?>"
	>
		<span class="cis_accordion__trigger-text"><?php echo $cis_aafs_item_q_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() output above. ?></span>
	</button>
</dt>
<dd
	id="<?php echo esc_attr( $cis_aafs_item_a_id ); ?>"
	class="cis_accordion__answer"
	role="region"
	aria-labelledby="<?php echo esc_attr( $cis_aafs_item_q_id ); ?>"
	hidden
>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output of WP inner blocks (core/paragraph, core/list, etc. — escape at their boundaries). ?>
</dd>
	<?php
else :
	// Mode: Open. Plain <dt>/<dd>, all answers visible.
	?>
<dt class="cis_accordion__question"><?php echo $cis_aafs_item_q_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() output above. ?></dt>
<dd class="cis_accordion__answer"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output of WP inner blocks (escape at their boundaries). ?></dd>
	<?php
endif;
