<?php
/**
 * Plugin Name:       Accessible Accordion Block with FAQ Schema
 * Plugin URI:        https://github.com/codeink-studios/accessible-accordion-faq-schema
 * Description:       Gutenberg block for accessible FAQ accordions with optional FAQPage JSON-LD schema. Theme-inheriting, no dependencies, no external services.
 * Version:           3.0.3
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

define( 'CIS_AAFS_VERSION', '3.0.3' );
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
