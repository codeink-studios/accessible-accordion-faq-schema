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

// Legacy gate: blocks saved before v3.0 have no blockVersion attribute. Force
// the v2.x defaults (schema on, JS-accordion mode) so upgrading the plugin
// never silently regresses an existing site's markup. Once the post is opened
// in the v3 editor, edit() writes blockVersion=3 plus the preserved values
// and this branch stops applying. New v3 blocks get blockVersion=3 written
// on first edit() mount and honor whatever the operator sets.
$cis_aafs_block_version = isset( $attributes['blockVersion'] ) ? (int) $attributes['blockVersion'] : 0;
if ( $cis_aafs_block_version < 3 ) {
	$cis_aafs_enable_schema      = true;
	$cis_aafs_use_native_details = false;
} else {
	$cis_aafs_enable_schema      = ! empty( $attributes['enableSchema'] );
	$cis_aafs_use_native_details = ! empty( $attributes['useNativeDetails'] );
}

// ---------------------------------------------------------------------------
// 2. Walk inner blocks to detect complete Q/A pairs, and (when schema is
// enabled) build the FAQPage JSON-LD items in the same pass.
//
// Each cis/faq-item child holds its question in attributes and its answer
// content as inner blocks (paragraphs, lists, etc.). We render those inner
// blocks separately just to extract plain-text answers — independent of
// $content (which is the already-rendered child HTML for the body).
// ---------------------------------------------------------------------------

$cis_aafs_schema_items = array();
$cis_aafs_has_complete = false;
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
	// plain-text answer for the schema and the empty-block detection.
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

	$cis_aafs_has_complete = true;

	if ( $cis_aafs_enable_schema ) {
		$cis_aafs_schema_items[] = array(
			'@type'          => 'Question',
			'name'           => $cis_aafs_q,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $cis_aafs_a_text,
			),
		);
	}
}

// Don't render the block at all if it has no complete Q/A pairs.
if ( ! $cis_aafs_has_complete ) {
	return '';
}

// ---------------------------------------------------------------------------
// 3. Conditionally enqueue the tiny accordion script.
// Only needed for the legacy JS-button mode. Native <details>/<summary> mode
// ships zero JavaScript.
// ---------------------------------------------------------------------------

if ( $cis_aafs_collapsible && ! $cis_aafs_use_native_details ) {
	wp_enqueue_script( 'cis-aafs-toggle' );
}

// ---------------------------------------------------------------------------
// 4. Build wrapper attributes (merges in block supports: color, spacing, etc.)
// then deterministically prepend our id.
// ---------------------------------------------------------------------------

$cis_aafs_wrapper_classes = 'cis_accordion';
if ( $cis_aafs_collapsible ) {
	$cis_aafs_wrapper_classes .= ' cis_accordion--collapsible';
	if ( $cis_aafs_use_native_details ) {
		$cis_aafs_wrapper_classes .= ' cis_accordion--native';
	}
}

$cis_aafs_wrapper_attrs = get_block_wrapper_attributes(
	array(
		'class' => $cis_aafs_wrapper_classes,
	)
);
$cis_aafs_wrapper_attrs = preg_replace( '/\sid="[^"]*"/', '', $cis_aafs_wrapper_attrs );
$cis_aafs_wrapper_attrs = sprintf( 'id="%s" ', esc_attr( $cis_aafs_anchor ) ) . $cis_aafs_wrapper_attrs;

// ---------------------------------------------------------------------------
// 5. Encode JSON-LD only if schema emission is enabled. JSON_HEX_TAG keeps
// the payload from ever containing a literal </script> sequence that would
// break the surrounding script tag.
// ---------------------------------------------------------------------------

$cis_aafs_schema_json = '';
if ( $cis_aafs_enable_schema && ! empty( $cis_aafs_schema_items ) ) {
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
		$cis_aafs_schema_json = '';
	}
}

// ---------------------------------------------------------------------------
// 6. Render. Body wrapper is <dl> for the classic dt/dd layout (open mode and
// legacy JS-accordion mode), <div> when native <details> mode is on (since
// <details> can't live inside <dl> per the HTML spec).
// ---------------------------------------------------------------------------

$cis_aafs_list_tag = $cis_aafs_use_native_details ? 'div' : 'dl';
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

	<<?php echo esc_attr( $cis_aafs_list_tag ); ?> class="cis_accordion__list">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered cis/faq-item children (escape at their boundaries). ?>
	</<?php echo esc_attr( $cis_aafs_list_tag ); ?>>

	<?php if ( '' !== $cis_aafs_schema_json ) : ?>
		<script type="application/ld+json"><?php echo $cis_aafs_schema_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe by construction: tag-stripped values + JSON_HEX_TAG flag. ?></script>
	<?php endif; ?>
</div>
