<?php
/**
 * Plugin Name:       Accessible Accordion Block with FAQ Schema
 * Plugin URI:        https://github.com/codeink-studios/accessible-accordion-faq-schema
 * Description:       Gutenberg block for accessible FAQ accordions with optional FAQPage JSON-LD schema. Theme-inheriting, no dependencies, no external services.
 * Version:           3.1.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            CodeInk Studios
 * Author URI:        https://code.ink
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       accessible-accordion-faq-schema
 * Domain Path:       /languages
 *
 * @package CIS_AAFS
 */

defined( 'ABSPATH' ) || exit;

define( 'CIS_AAFS_VERSION', '3.1.0' );
define( 'CIS_AAFS_FILE', __FILE__ );
define( 'CIS_AAFS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CIS_AAFS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register both blocks. Collapsible mode uses the browser's native
 * <details>/<summary> element so no frontend JavaScript is ever shipped.
 *
 * @since 1.0.0
 * @return void
 */
function cis_aafs_register() {
	// Parent block (provides context, hosts cis/faq-item children).
	register_block_type( CIS_AAFS_DIR . 'src/faq-block' );

	// Child block (one Q/A pair). Parent restriction is declared in its
	// block.json — it can only be inserted inside the parent.
	register_block_type( CIS_AAFS_DIR . 'src/faq-item' );

	// Register — but do NOT enqueue — the frontend stylesheet. The parent
	// block's render.php enqueues it, which is the only reliable way to emit
	// it exclusively on pages where the block actually appears.
	//
	// wp_enqueue_block_style() is deliberately not used here. It only routes
	// through the render_block filter when wp_should_load_block_assets_on_demand()
	// is true, which defaults to wp_should_load_separate_core_block_assets() —
	// false on classic themes. On those sites core silently falls back to a
	// plain site-wide wp_enqueue_scripts action, so the stylesheet loaded on
	// every page. That was the v3.0.2 leak this replaces.
	wp_register_style(
		'cis-aafs-style',
		CIS_AAFS_URL . 'src/faq-block/style.css',
		array(),
		CIS_AAFS_VERSION
	);

	// Lets core inline the stylesheet instead of emitting a blocking <link>.
	// wp_maybe_inline_styles() runs on wp_footer priority 1 precisely to catch
	// late-enqueued styles like ours.
	wp_style_add_data( 'cis-aafs-style', 'path', CIS_AAFS_DIR . 'src/faq-block/style.css' );
}
add_action( 'init', 'cis_aafs_register' );

/**
 * Normalize a legacy v1.x `faqs` attribute into a predictable shape.
 *
 * v1.x kept every Q/A pair in a single `faqs` array attribute on the parent
 * block; there were no child blocks. v2.0.0 moved to InnerBlocks children
 * without shipping a migration, so from v2 onward this data was never read
 * and the FAQ rendered as nothing. This lets the front end recover it without
 * anyone having to open and re-save the post.
 *
 * Two answer shapes exist in the wild: a single string (v1.0.x) and an array
 * of paragraph strings (v1.1.0+). Both are accepted, matching the v1 renderer.
 *
 * @since 3.1.0
 * @param mixed $faqs Raw `faqs` attribute value.
 * @return array List of array{question:string, paragraphs:string[]}.
 */
function cis_aafs_normalize_legacy_faqs( $faqs ) {
	$normalized = array();

	if ( ! is_array( $faqs ) ) {
		return $normalized;
	}

	foreach ( $faqs as $faq ) {
		if ( ! is_array( $faq ) ) {
			continue;
		}

		$question = isset( $faq['question'] ) && is_string( $faq['question'] )
			? trim( $faq['question'] )
			: '';
		if ( '' === $question ) {
			continue;
		}

		$paragraphs = array();
		if ( isset( $faq['answer'] ) ) {
			if ( is_array( $faq['answer'] ) ) {
				foreach ( $faq['answer'] as $para ) {
					if ( is_string( $para ) ) {
						$paragraphs[] = $para;
					}
				}
			} elseif ( is_string( $faq['answer'] ) ) {
				$paragraphs[] = $faq['answer'];
			}
		}

		$paragraphs = array_values(
			array_filter(
				array_map( 'trim', $paragraphs ),
				static function ( $para ) {
					return '' !== $para;
				}
			)
		);

		// v1 required both halves before it would render a row. Keep that.
		if ( empty( $paragraphs ) ) {
			continue;
		}

		$normalized[] = array(
			'question'   => $question,
			'paragraphs' => $paragraphs,
		);
	}

	return $normalized;
}

/**
 * Render legacy v1.x FAQ rows using the CURRENT markup.
 *
 * Deliberately emits the v3 structure rather than resurrecting the v1
 * JS-button accordion, whose script was deleted in 3.0.1. Recovered content
 * therefore looks and behaves like every other FAQ block on the site.
 *
 * Escaping mirrors the v1 renderer: a strict inline allowlist on questions,
 * wp_kses_post() on answers, and a <p> wrapper only when the stored string is
 * not already block-level.
 *
 * @since 3.1.0
 * @param array $items       Output of cis_aafs_normalize_legacy_faqs().
 * @param bool  $collapsible Whether the parent block is in collapsible mode.
 * @return string HTML for the list body.
 */
function cis_aafs_render_legacy_items( $items, $collapsible ) {
	$question_allowed = array(
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
	);

	$out = '';

	foreach ( $items as $item ) {
		$question_html = wp_kses( $item['question'], $question_allowed );

		$answer_parts = array();
		foreach ( $item['paragraphs'] as $para ) {
			$para_html = wp_kses_post( $para );
			if ( '' === $para_html ) {
				continue;
			}
			if ( ! preg_match( '/^\s*<(p|ul|ol|div|blockquote|h[1-6])\b/i', $para_html ) ) {
				$para_html = '<p>' . $para_html . '</p>';
			}
			$answer_parts[] = $para_html;
		}
		$answer_html = implode( "\n", $answer_parts );

		if ( $collapsible ) {
			$out .= sprintf(
				'<details class="cis_accordion__item"><summary class="cis_accordion__question">%1$s</summary><div class="cis_accordion__answer">%2$s</div></details>',
				$question_html,
				$answer_html
			);
		} else {
			$out .= sprintf(
				'<dt class="cis_accordion__question">%1$s</dt><dd class="cis_accordion__answer">%2$s</dd>',
				$question_html,
				$answer_html
			);
		}
	}

	return $out;
}

/**
 * Load translations.
 *
 * @since 1.0.0
 * @return void
 */
function cis_aafs_load_textdomain() {
	load_plugin_textdomain(
		'accessible-accordion-faq-schema',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'cis_aafs_load_textdomain' );

/**
 * Wire editor script translations after the script is registered by
 * register_block_type(). Allows __() calls in index.js to be translated.
 *
 * @since 1.0.0
 * @return void
 */
function cis_aafs_set_script_translations() {
	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations(
			generate_block_asset_handle( 'cis/accessible-accordion-faq', 'editorScript' ),
			'accessible-accordion-faq-schema',
			CIS_AAFS_DIR . 'languages'
		);
	}
}
add_action( 'init', 'cis_aafs_set_script_translations', 20 );

/**
 * GitHub-based update checker.
 *
 * Loaded only if the YahnisElsts plugin-update-checker library is bundled at
 * lib/plugin-update-checker/. This block enables click-to-update from WP admin
 * pulling release ZIP assets from the public GitHub repo.
 *
 * IMPORTANT: This MUST be removed (along with the lib/ directory) before
 * submitting to the WordPress.org plugin directory — wp.org plugins update
 * via wp.org's SVN repo and bundling a third-party update mechanism is
 * disallowed.
 *
 * @since 1.0.0
 */
$cis_aafs_puc_loader = CIS_AAFS_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $cis_aafs_puc_loader ) ) {
	require $cis_aafs_puc_loader;
	$cis_aafs_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/codeink-studios/accessible-accordion-faq-schema/',
		__FILE__,
		'accessible-accordion-faq-schema'
	);
	$cis_aafs_update_checker->setBranch( 'main' );
	$cis_aafs_update_checker->getVcsApi()->enableReleaseAssets();
}
