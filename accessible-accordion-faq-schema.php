<?php
/**
 * Plugin Name:       Accessible Accordion Block with FAQ Schema
 * Plugin URI:        https://github.com/codeink-studios/accessible-accordion-faq-schema
 * Description:       Gutenberg block for accessible FAQ accordions with FAQPage JSON-LD schema. Theme-inheriting, no dependencies, no external services.
 * Version:           1.1.0
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

define( 'CIS_AAFS_VERSION', '1.1.0' );
define( 'CIS_AAFS_FILE', __FILE__ );
define( 'CIS_AAFS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CIS_AAFS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register block + frontend script.
 *
 * Frontend script is registered (not enqueued) so render.php can enqueue it
 * only when at least one block instance uses the collapsible option. Pages
 * with non-collapsible FAQ blocks ship zero JavaScript.
 *
 * @since 1.0.0
 * @return void
 */
function cis_aafs_register() {
	register_block_type( CIS_AAFS_DIR . 'src/faq-block' );

	wp_register_script(
		'cis-aafs-toggle',
		CIS_AAFS_URL . 'src/faq-block/faq-toggle.js',
		array(),
		CIS_AAFS_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
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
