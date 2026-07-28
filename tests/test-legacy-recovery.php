<?php
/**
 * Standalone test of the v1 legacy recovery helpers.
 * Stubs the handful of WP functions they touch, then loads the real file.
 */

define( 'ABSPATH', true );

function plugin_dir_path( $f ) { return dirname( $f ) . '/'; }
function plugin_dir_url( $f )  { return 'https://example.test/wp-content/plugins/x/'; }
function add_action() {}
function register_block_type() {}
function wp_register_style() {}
function wp_style_add_data() {}
function load_plugin_textdomain() {}
function plugin_basename( $f ) { return 'x/x.php'; }
function generate_block_asset_handle() { return 'h'; }
function wp_set_script_translations() {}

// Crude but faithful-enough stand-ins for the escaping layer.
function wp_kses( $s, $allowed ) {
	$tags = implode( '|', array_keys( $allowed ) );
	return preg_replace( '#<(?!/?(' . $tags . ')\b)[^>]*>#i', '', $s );
}
function wp_kses_post( $s ) {
	return preg_replace( '#<\s*(script|iframe|object)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $s );
}

// Load the plugin file, minus the update-checker block at the end (it needs a
// real WordPress to boot). Everything above that point is plain functions.
$cis_aafs_source = file_get_contents( __DIR__ . '/../accessible-accordion-faq-schema.php' );
$cis_aafs_cut    = strpos( $cis_aafs_source, '$cis_aafs_puc_loader' );
if ( false === $cis_aafs_cut ) {
	fwrite( STDERR, "Could not locate the update-checker block to strip.\n" );
	exit( 1 );
}
// eval() is safe here and is the point of the harness: the only input is this
// repo's own plugin file, read from a fixed relative path, with no external or
// user-supplied data anywhere in the chain. Test-only file — never shipped in
// the release ZIP (see .github/workflows/release.yml).
eval( '?>' . substr( $cis_aafs_source, 0, $cis_aafs_cut ) ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- see note above.

$pass = 0; $fail = 0;
function check( $label, $got, $want ) {
	global $pass, $fail;
	if ( $got === $want ) { $pass++; echo "  PASS  $label\n"; }
	else { $fail++; echo "  FAIL  $label\n        got:  " . var_export( $got, true ) . "\n        want: " . var_export( $want, true ) . "\n"; }
}

echo "\n--- normalize: v1.0.x single-string answer ---\n";
$r = cis_aafs_normalize_legacy_faqs( array(
	array( 'question' => 'How far is Langley?', 'answer' => 'About 45 minutes.' ),
) );
check( 'one row recovered', count( $r ), 1 );
check( 'question kept', $r[0]['question'], 'How far is Langley?' );
check( 'answer -> one paragraph', $r[0]['paragraphs'], array( 'About 45 minutes.' ) );

echo "\n--- normalize: v1.1.0+ array answer, multi-paragraph ---\n";
$r = cis_aafs_normalize_legacy_faqs( array(
	array( 'question' => 'Q1', 'answer' => array( 'Para one.', '  ', 'Para two.' ) ),
) );
check( 'blank paragraph dropped', $r[0]['paragraphs'], array( 'Para one.', 'Para two.' ) );

echo "\n--- normalize: rows v1 would not have rendered ---\n";
check( 'no question -> dropped', cis_aafs_normalize_legacy_faqs( array( array( 'question' => '', 'answer' => 'x' ) ) ), array() );
check( 'no answer -> dropped',   cis_aafs_normalize_legacy_faqs( array( array( 'question' => 'Q', 'answer' => '' ) ) ), array() );
check( 'garbage row -> dropped', cis_aafs_normalize_legacy_faqs( array( 'nonsense', 42, null ) ), array() );
check( 'non-array input',        cis_aafs_normalize_legacy_faqs( 'nope' ), array() );
check( 'empty array',            cis_aafs_normalize_legacy_faqs( array() ), array() );

echo "\n--- render: open mode ---\n";
$items = cis_aafs_normalize_legacy_faqs( array(
	array( 'question' => 'Is it <strong>open</strong>?', 'answer' => 'Yes.' ),
) );
$html = cis_aafs_render_legacy_items( $items, false );
check( 'dt/dd emitted', (bool) preg_match( '#<dt class="cis_accordion__question">.*</dt><dd class="cis_accordion__answer"><p>Yes\.</p></dd>#s', $html ), true );
check( 'allowed inline tag survives', strpos( $html, '<strong>open</strong>' ) !== false, true );

echo "\n--- render: collapsible mode ---\n";
$html = cis_aafs_render_legacy_items( $items, true );
check( 'details/summary emitted', (bool) preg_match( '#<details class="cis_accordion__item"><summary class="cis_accordion__question">#', $html ), true );
check( 'no <dt> in collapsible', strpos( $html, '<dt' ) === false, true );

echo "\n--- render: paragraph wrapping ---\n";
$items = cis_aafs_normalize_legacy_faqs( array(
	array( 'question' => 'Q', 'answer' => array( 'Plain text.', '<p>Already wrapped.</p>', '<ul><li>A list</li></ul>' ) ),
) );
$html = cis_aafs_render_legacy_items( $items, false );
check( 'plain text wrapped once', substr_count( $html, '<p>Plain text.</p>' ), 1 );
check( 'pre-wrapped not double-wrapped', strpos( $html, '<p><p>' ) === false, true );
check( 'list left alone', strpos( $html, '<ul><li>A list</li></ul>' ) !== false, true );

echo "\n--- render: script injection in a stored answer ---\n";
$items = cis_aafs_normalize_legacy_faqs( array(
	array( 'question' => 'Q<script>alert(1)</script>', 'answer' => '<script>alert(2)</script>Safe.' ),
) );
$html = cis_aafs_render_legacy_items( $items, false );
check( 'no <script> in output', stripos( $html, '<script' ) === false, true );

echo "\n" . str_repeat( '=', 46 ) . "\n  $pass passed, $fail failed\n" . str_repeat( '=', 46 ) . "\n";
exit( $fail > 0 ? 1 : 0 );
