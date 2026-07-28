/**
 * Exercises the real deprecation's isEligible + migrate from index.js by
 * loading it against a stubbed window.wp and capturing the registration.
 */
const fs = require( 'fs' );

const registered = {};

const noop = () => {};
const stubEl = () => ( { __el: true } );

global.window = {
	wp: {
		blocks: {
			registerBlockType: ( name, settings ) => { registered[ name ] = settings; },
			createBlock: ( name, attributes = {}, innerBlocks = [] ) => ( { name, attributes, innerBlocks } ),
		},
		blockEditor: {
			useBlockProps: () => ( {} ),
			InspectorControls: 'InspectorControls',
			InspectorAdvancedControls: 'InspectorAdvancedControls',
			InnerBlocks: Object.assign( function InnerBlocks() {}, { Content: 'Content', ButtonBlockAppender: 'Appender' } ),
			RichText: 'RichText',
		},
		components: { PanelBody: 'PanelBody', SelectControl: 'SelectControl', ToggleControl: 'ToggleControl', TextControl: 'TextControl' },
		element: { createElement: stubEl, Fragment: 'Fragment', useEffect: noop },
		data: { select: () => ( { getBlock: () => null } ) },
		i18n: { __: ( s ) => s },
	},
};

require( require( 'path' ).join( __dirname, '..', 'src', 'faq-block', 'index.js' ) );

const parent = registered[ 'cis/accessible-accordion-faq' ];
const dep = parent.deprecated[ 0 ];

let pass = 0, fail = 0;
function check( label, got, want ) {
	const g = JSON.stringify( got ), w = JSON.stringify( want );
	if ( g === w ) { pass++; console.log( `  PASS  ${ label }` ); }
	else { fail++; console.log( `  FAIL  ${ label }\n        got:  ${ g }\n        want: ${ w }` ); }
}

console.log( '\n--- isEligible: should fire ---' );
check( 'v1 string answer', dep.isEligible( { faqs: [ { question: 'Q', answer: 'A' } ] }, [] ), true );
check( 'v1 array answer', dep.isEligible( { faqs: [ { question: 'Q', answer: [ 'A' ] } ] }, [] ), true );
check( 'question only', dep.isEligible( { faqs: [ { question: 'Q', answer: '' } ] }, [] ), true );

console.log( '\n--- isEligible: must NOT fire (would clobber live content) ---' );
check( 'working v3 block', dep.isEligible( { title: 'FAQ', blockVersion: 3 }, [ { name: 'cis/faq-item' } ] ), false );
check( 'v1 attrs BUT children exist', dep.isEligible( { faqs: [ { question: 'Q', answer: 'A' } ] }, [ { name: 'cis/faq-item' } ] ), false );
check( 'fresh insert, no faqs', dep.isEligible( {}, [] ), false );
check( 'empty faqs array', dep.isEligible( { faqs: [] }, [] ), false );
check( 'faqs present but all blank', dep.isEligible( { faqs: [ { question: '   ', answer: '' } ] }, [] ), false );
check( 'faqs is not an array', dep.isEligible( { faqs: 'nope' }, [] ), false );
check( 'undefined attributes', dep.isEligible( undefined, [] ), false );

console.log( '\n--- migrate: full v1.1.0 block ---' );
const [ attrs, inner ] = dep.migrate( {
	title: 'Frequently Asked Questions',
	titleLevel: 3,
	collapsible: true,
	anchor: 'faq-langley',
	faqs: [
		{ question: 'How far is Langley?', answer: [ 'About 45 minutes.', 'Traffic depending.' ] },
		{ question: 'Parking?', answer: 'Free on site.' },
	],
} );
check( 'title preserved', attrs.title, 'Frequently Asked Questions' );
check( 'titleLevel preserved', attrs.titleLevel, 3 );
check( 'collapsible preserved', attrs.collapsible, true );
check( 'anchor preserved', attrs.anchor, 'faq-langley' );
check( 'schema forced on (v1 always emitted)', attrs.enableSchema, true );
check( 'blockVersion stamped', attrs.blockVersion, 3 );
check( 'faqs attribute not carried forward', attrs.faqs, undefined );
check( 'two faq-items created', inner.length, 2 );
check( 'child block name', inner[ 0 ].name, 'cis/faq-item' );
check( 'question moved to attribute', inner[ 0 ].attributes.question, 'How far is Langley?' );
check( 'two answer paragraphs', inner[ 0 ].innerBlocks.length, 2 );
check( 'paragraph is core/paragraph', inner[ 0 ].innerBlocks[ 0 ].name, 'core/paragraph' );
check( 'paragraph content', inner[ 0 ].innerBlocks[ 0 ].attributes.content, 'About 45 minutes.' );
check( 'second paragraph content', inner[ 0 ].innerBlocks[ 1 ].attributes.content, 'Traffic depending.' );
check( 'string answer -> one paragraph', inner[ 1 ].innerBlocks.length, 1 );

console.log( '\n--- migrate: edge cases ---' );
const [ a2, i2 ] = dep.migrate( { faqs: [
	{ question: 'Kept, answer lost', answer: '' },
	{ question: '', answer: '' },
	'garbage',
	{ question: 'Normal', answer: 'Fine.' },
] } );
check( 'blank + garbage rows dropped, others kept', i2.length, 2 );
check( 'answerless question survives', i2[ 0 ].attributes.question, 'Kept, answer lost' );
check( 'answerless gets empty paragraph to type into', i2[ 0 ].innerBlocks.length, 1 );
check( 'defaults applied when absent', [ a2.title, a2.titleLevel, a2.collapsible, a2.anchor ], [ '', 2, false, '' ] );

const [ a3 ] = dep.migrate( { titleLevel: 99, faqs: [ { question: 'Q', answer: 'A' } ] } );
check( 'out-of-range titleLevel clamped to 2', a3.titleLevel, 2 );

console.log( '\n' + '='.repeat( 46 ) + `\n  ${ pass } passed, ${ fail } failed\n` + '='.repeat( 46 ) );
process.exit( fail > 0 ? 1 : 0 );
