<?php
/**
 * Server-side render for cis/accessible-accordion-faq.
 *
 * The body of the <dl> comes from $content (the rendered cis/faq-item
 * children). Schema is built by walking $block->parsed_block['innerBlocks']
 * so we can extract plain-text question and answer values.
 *
 * Available variables (provided by WP_Block::render):
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks: a concatenation of
 *                           cis/faq-item dt/dd pairs.
 * @var WP_Block $block      Block instance.
 *
 * @package CIS_AAFS
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// 1. Sanitize + validate parent attributes.
// ---------------------------------------------------------------------------

$cis_aafs_title       = isset( $attributes['title'] ) && is_string( $attributes['title'] )
	? $attributes['title']
	: '';
$cis_aafs_title_level = isset( $attributes['titleLevel'] ) ? (int) $attributes['titleLevel'] : 2;
$cis_aafs_title_level = max( 2, min( 6, $cis_aafs_title_level ) );
$cis_aafs_collapsible = ! empty( $attributes['collapsible'] );
$cis_aafs_anchor_raw  = isset( $attributes['anchor'] ) && is_string( $attributes['anchor'] )
	? $attributes['anchor']
	: '';
$cis_aafs_anchor      = '' !== $cis_aafs_anchor_raw
	? sanitize_html_class( $cis_aafs_anchor_raw )
	: 'faq';

// ---------------------------------------------------------------------------
// 2. Build FAQPage JSON-LD schema by walking inner block data.
//
// Each cis/faq-item child holds its question in attributes and its answer
// content as inner blocks (paragraphs, lists, etc.). We render those inner
// blocks separately just to extract plain-text answer for the schema.
// This is independent of $content (which is the already-rendered HTML).
// ---------------------------------------------------------------------------

$cis_aafs_schema_items = array();
$cis_aafs_inner_blocks = isset( $block->parsed_block['innerBlocks'] ) && is_array( $block->parsed_block['innerBlocks'] )
	? $block->parsed_block['innerBlocks']
	: array();

foreach ( $cis_aafs_inner_blocks as $cis_aafs_item ) {
	if ( ! is_array( $cis_aafs_item ) ) {
		continue;
	}
	if ( ! isset( $cis_aafs_item['blockName'] ) || 'cis/faq-item' !== $cis_aafs_item['blockName'] ) {
		continue;
	}

	$cis_aafs_q = isset( $cis_aafs_item['attrs']['question'] ) && is_string( $cis_aafs_item['attrs']['question'] )
		? trim( wp_strip_all_tags( $cis_aafs_item['attrs']['question'] ) )
		: '';

	// Render the item's inner blocks (paragraphs, lists, …) to extract the
	// plain-text answer for the schema. render_block() is safe with sub-trees.
	$cis_aafs_answer_html = '';
	if ( isset( $cis_aafs_item['innerBlocks'] ) && is_array( $cis_aafs_item['innerBlocks'] ) ) {
		foreach ( $cis_aafs_item['innerBlocks'] as $cis_aafs_sub ) {
			if ( is_array( $cis_aafs_sub ) ) {
				$cis_aafs_answer_html .= render_block( $cis_aafs_sub );
			}
		}
	}
	$cis_aafs_a_text = trim( wp_strip_all_tags( $cis_aafs_answer_html ) );

	if ( '' === $cis_aafs_q || '' === $cis_aafs_a_text ) {
		continue;
	}

	$cis_aafs_schema_items[] = array(
		'@type'          => 'Question',
		'name'           => $cis_aafs_q,
		'acceptedAnswer' => array(
			'@type' => 'Answer',
			'text'  => $cis_aafs_a_text,
		),
	);
}

// Don't render the block at all if it has no complete Q/A pairs.
if ( empty( $cis_aafs_schema_items ) ) {
	return '';
}

// ---------------------------------------------------------------------------
// 3. Conditionally enqueue the tiny accordion script.
// ---------------------------------------------------------------------------

if ( $cis_aafs_collapsible ) {
	wp_enqueue_script( 'cis-aafs-toggle' );
}

// ---------------------------------------------------------------------------
// 4. Build wrapper attributes (merges in block supports: color, spacing, etc.)
// then deterministically prepend our id.
// ---------------------------------------------------------------------------

$cis_aafs_wrapper_classes = 'cis_accordion';
if ( $cis_aafs_collapsible ) {
	$cis_aafs_wrapper_classes .= ' cis_accordion--collapsible';
}

$cis_aafs_wrapper_attrs = get_block_wrapper_attributes(
	array(
		'class' => $cis_aafs_wrapper_classes,
	)
);
$cis_aafs_wrapper_attrs = preg_replace( '/\sid="[^"]*"/', '', $cis_aafs_wrapper_attrs );
$cis_aafs_wrapper_attrs = sprintf( 'id="%s" ', esc_attr( $cis_aafs_anchor ) ) . $cis_aafs_wrapper_attrs;

// ---------------------------------------------------------------------------
// 5. Encode JSON-LD with JSON_HEX_TAG so the payload can never contain a
// literal </script> sequence that would break the surrounding script tag.
// ---------------------------------------------------------------------------

$cis_aafs_schema = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => $cis_aafs_schema_items,
);
$cis_aafs_schema_json = wp_json_encode(
	$cis_aafs_schema,
	JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if ( false === $cis_aafs_schema_json ) {
	$cis_aafs_schema_json = '{}';
}

// ---------------------------------------------------------------------------
// 6. Render.
// ---------------------------------------------------------------------------
?>
<div <?php echo $cis_aafs_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() output + our esc_attr'd id prefix. ?>>
	<?php if ( '' !== $cis_aafs_title ) : ?>
		<?php
		printf(
			'<h%1$d class="cis_accordion__title">%2$s</h%1$d>',
			(int) $cis_aafs_title_level,
			esc_html( wp_strip_all_tags( $cis_aafs_title ) )
		);
		?>
	<?php endif; ?>

	<dl class="cis_accordion__list">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered cis/faq-item children (escape at their boundaries). ?>
	</dl>

	<script type="application/ld+json"><?php echo $cis_aafs_schema_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe by construction: tag-stripped values + JSON_HEX_TAG flag. ?></script>
</div>
