<?php
/**
 * Server-side render for cis/faq-item.
 *
 * Two output modes, selected by parent block context:
 *
 *   1. Open (collapsible context = false): <dt>/<dd> pair, no toggle.
 *   2. Collapsible (collapsible context = true): <details>/<summary> + answer
 *      wrapper. Zero JavaScript — the browser's native disclosure element
 *      handles open/close, keyboard, and accessibility.
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

// --------------------------------------------------------------------------
// 2. Sanitize question for output. Allow only minimal inline formatting.
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
// 3. Render.
// --------------------------------------------------------------------------

if ( $cis_aafs_item_collapsible ) :
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
else :
	// Mode: Open. Plain <dt>/<dd>, all answers visible.
	?>
<dt class="cis_accordion__question"><?php echo $cis_aafs_item_q_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() output above. ?></dt>
<dd class="cis_accordion__answer"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output of WP inner blocks (escape at their boundaries). ?></dd>
	<?php
endif;
