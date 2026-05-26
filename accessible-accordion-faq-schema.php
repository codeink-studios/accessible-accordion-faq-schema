<?php
/**
 * Plugin Name:       Accessible Accordion Block with FAQ Schema
 * Plugin URI:        https://github.com/codeink-studios/accessible-accordion-faq-schema
 * Description:       Gutenberg block for accessible FAQ accordions with optional FAQPage JSON-LD schema. Theme-inheriting, no dependencies, no external services.
 * Version:           3.0.2
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

define( 'CIS_AAFS_VERSION', '3.0.2' );
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

	// Attach the frontend stylesheet to the parent block via the per-block
	// conditional API. wp_enqueue_block_style() guarantees the stylesheet is
	// only emitted on pages where the block actually renders — unlike a
	// "style" entry in block.json, which classic themes can leak globally.
	wp_enqueue_block_style(
		'cis/accessible-accordion-faq',
		array(
			'handle' => 'cis-aafs-style',
			'src'    => CIS_AAFS_URL . 'src/faq-block/style.css',
			'path'   => CIS_AAFS_DIR . 'src/faq-block/style.css',
			'ver'    => CIS_AAFS_VERSION,
		)
	);
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
