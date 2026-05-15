<?php
/**
 * Server-side render for cis/accessible-accordion-faq.
 *
 * All output is sanitized at the point of escaping. JSON-LD schema is built
 * from values that have been through wp_strip_all_tags(), then encoded with
 * JSON_HEX_TAG to prevent any possible </script> breakout.
 *
 * Available variables (provided by WP_Block::render):
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Saved block content (always empty — dynamic block).
 * @var WP_Block $block      Block instance.
 *
 * @package CIS_AAFS
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// 1. Sanitize + validate attributes.
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

// Validate FAQs: must be array of objects with question + answer strings.
$cis_aafs_faqs = array();
if ( isset( $attributes['faqs'] ) && is_array( $attributes['faqs'] ) ) {
	foreach ( $attributes['faqs'] as $cis_aafs_faq ) {
		if ( ! is_array( $cis_aafs_faq ) ) {
			continue;
		}
		$cis_aafs_q = isset( $cis_aafs_faq['question'] ) && is_string( $cis_aafs_faq['question'] )
			? trim( $cis_aafs_faq['question'] )
			: '';
		$cis_aafs_a = isset( $cis_aafs_faq['answer'] ) && is_string( $cis_aafs_faq['answer'] )
			? trim( $cis_aafs_faq['answer'] )
			: '';
		if ( '' === $cis_aafs_q || '' === $cis_aafs_a ) {
			continue;
		}
		$cis_aafs_faqs[] = array(
			'question' => $cis_aafs_q,
			'answer'   => $cis_aafs_a,
		);
	}
}

// Nothing to render if there are no complete pairs.
if ( empty( $cis_aafs_faqs ) ) {
	return '';
}

// ---------------------------------------------------------------------------
// 2. Generate stable, collision-resistant ID base for ARIA wiring.
// ---------------------------------------------------------------------------

$cis_aafs_instance = substr(
	md5( $cis_aafs_anchor . wp_json_encode( $cis_aafs_faqs ) . (string) $cis_aafs_collapsible ),
	0,
	8
);

// ---------------------------------------------------------------------------
// 3. Conditionally enqueue the tiny accordion script.
// ---------------------------------------------------------------------------

if ( $cis_aafs_collapsible ) {
	wp_enqueue_script( 'cis-aafs-toggle' );
}

// ---------------------------------------------------------------------------
// 4. Build wrapper attributes (merges in block supports: color, spacing, etc.).
// ---------------------------------------------------------------------------

$cis_aafs_wrapper_classes = 'cis_accordion';
if ( $cis_aafs_collapsible ) {
	$cis_aafs_wrapper_classes .= ' cis_accordion--collapsible';
}

$cis_aafs_wrapper_attrs = get_block_wrapper_attributes(
	array(
		'class' => $cis_aafs_wrapper_classes,
		'id'    => $cis_aafs_anchor,
	)
);

// ---------------------------------------------------------------------------
// 5. Allowed inline HTML in questions (strict allowlist).
// ---------------------------------------------------------------------------

$cis_aafs_q_allowed = array(
	'strong' => array(),
	'b'      => array(),
	'em'     => array(),
	'i'      => array(),
);

// ---------------------------------------------------------------------------
// 6. Build FAQPage JSON-LD schema.
// ---------------------------------------------------------------------------

$cis_aafs_schema = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => array(),
);
foreach ( $cis_aafs_faqs as $cis_aafs_faq ) {
	// Schema fields are plain text. Strip all HTML and decode entities so
	// search engines see clean prose.
	$cis_aafs_schema['mainEntity'][] = array(
		'@type'          => 'Question',
		'name'           => wp_strip_all_tags( $cis_aafs_faq['question'] ),
		'acceptedAnswer' => array(
			'@type' => 'Answer',
			'text'  => wp_strip_all_tags( $cis_aafs_faq['answer'] ),
		),
	);
}
// JSON_HEX_TAG converts < and > into < / > — guarantees the JSON
// payload cannot contain a literal </script> that would break out of the
// surrounding <script> element.
$cis_aafs_schema_json = wp_json_encode(
	$cis_aafs_schema,
	JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if ( false === $cis_aafs_schema_json ) {
	$cis_aafs_schema_json = '{}';
}

// ---------------------------------------------------------------------------
// 7. Render.
//
// Note: WP wraps this file in `ob_start(); require $file; return ob_get_clean();`,
// so we just echo HTML directly — no inner buffering, no return value.
// ---------------------------------------------------------------------------
?>
<div <?php echo $cis_aafs_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped output. ?>>
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
		<?php
		foreach ( $cis_aafs_faqs as $cis_aafs_index => $cis_aafs_faq ) :
			$cis_aafs_q_html = wp_kses( $cis_aafs_faq['question'], $cis_aafs_q_allowed );
			$cis_aafs_a_html = wp_kses_post( $cis_aafs_faq['answer'] );

			// Wrap answer in <p> if it doesn't already start with a block-level tag.
			if ( ! preg_match( '/^\s*<(p|ul|ol|div|blockquote|h[1-6])\b/i', $cis_aafs_a_html ) ) {
				$cis_aafs_a_html = '<p>' . $cis_aafs_a_html . '</p>';
			}

			$cis_aafs_q_id = sprintf( 'cis-aafs-%s-%d-q', $cis_aafs_instance, $cis_aafs_index + 1 );
			$cis_aafs_a_id = sprintf( 'cis-aafs-%s-%d-a', $cis_aafs_instance, $cis_aafs_index + 1 );
			?>
			<?php if ( $cis_aafs_collapsible ) : ?>
				<dt class="cis_accordion__question">
					<button
						type="button"
						class="cis_accordion__trigger"
						id="<?php echo esc_attr( $cis_aafs_q_id ); ?>"
						aria-expanded="false"
						aria-controls="<?php echo esc_attr( $cis_aafs_a_id ); ?>"
					>
						<span class="cis_accordion__trigger-text"><?php echo $cis_aafs_q_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() output above. ?></span>
					</button>
				</dt>
				<dd
					id="<?php echo esc_attr( $cis_aafs_a_id ); ?>"
					class="cis_accordion__answer"
					role="region"
					aria-labelledby="<?php echo esc_attr( $cis_aafs_q_id ); ?>"
					hidden
				>
					<?php echo $cis_aafs_a_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post() output above. ?>
				</dd>
			<?php else : ?>
				<dt class="cis_accordion__question"><?php echo $cis_aafs_q_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() output above. ?></dt>
				<dd class="cis_accordion__answer"><?php echo $cis_aafs_a_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post() output above. ?></dd>
			<?php endif; ?>
		<?php endforeach; ?>
	</dl>

	<script type="application/ld+json"><?php echo $cis_aafs_schema_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe by construction: values stripped of HTML + JSON_HEX_TAG flag prevents </script> breakout. ?></script>
</div>

