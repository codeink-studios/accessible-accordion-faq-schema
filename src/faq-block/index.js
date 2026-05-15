/**
 * Accessible FAQ Accordion — block editor registration.
 *
 * Registers two blocks:
 *   - cis/accessible-accordion-faq (parent) — wrapper, title, schema. Hosts
 *     cis/faq-item children via InnerBlocks.
 *   - cis/faq-item (child) — one Q/A pair. Question is a RichText. Answer
 *     is its own InnerBlocks scoped to text-type core blocks.
 *
 * No-build implementation using wp.element.createElement directly.
 *
 * @package CIS_AAFS
 */

( function ( wp ) {
	'use strict';

	var registerBlockType         = wp.blocks.registerBlockType;
	var useBlockProps             = wp.blockEditor.useBlockProps;
	var InspectorControls         = wp.blockEditor.InspectorControls;
	var InspectorAdvancedControls = wp.blockEditor.InspectorAdvancedControls;
	var InnerBlocks               = wp.blockEditor.InnerBlocks;
	var RichText                  = wp.blockEditor.RichText;
	var PanelBody                 = wp.components.PanelBody;
	var SelectControl             = wp.components.SelectControl;
	var ToggleControl             = wp.components.ToggleControl;
	var TextControl               = wp.components.TextControl;
	var el                        = wp.element.createElement;
	var Fragment                  = wp.element.Fragment;
	var __                        = wp.i18n.__;

	// -----------------------------------------------------------------------
	// Parent block: cis/accessible-accordion-faq
	// -----------------------------------------------------------------------

	var PARENT_ALLOWED  = [ 'cis/faq-item' ];
	var PARENT_TEMPLATE = [ [ 'cis/faq-item' ] ];

	registerBlockType( 'cis/accessible-accordion-faq', {
		edit: function ( props ) {
			var attributes    = props.attributes;
			var setAttributes = props.setAttributes;

			var title       = attributes.title || '';
			var titleLevel  = parseInt( attributes.titleLevel, 10 );
			if ( isNaN( titleLevel ) || titleLevel < 2 || titleLevel > 6 ) {
				titleLevel = 2;
			}
			var collapsible = !! attributes.collapsible;
			var anchor      = ( typeof attributes.anchor === 'string' ) ? attributes.anchor : '';

			var blockProps = useBlockProps( {
				className: 'cis_accordion-editor' + ( collapsible ? ' is-collapsible' : '' ),
			} );

			var inspector = el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __( 'FAQ settings', 'accessible-accordion-faq-schema' ), initialOpen: true },
					el( SelectControl, {
						label: __( 'Title heading level', 'accessible-accordion-faq-schema' ),
						help: __( 'Heading level for the optional section title. Use H2 if this is the main heading on the page, H3 if nested under one.', 'accessible-accordion-faq-schema' ),
						value: String( titleLevel ),
						options: [
							{ label: 'H2', value: '2' },
							{ label: 'H3', value: '3' },
							{ label: 'H4', value: '4' },
							{ label: 'H5', value: '5' },
							{ label: 'H6', value: '6' },
						],
						onChange: function ( val ) {
							var parsed = parseInt( val, 10 );
							if ( parsed >= 2 && parsed <= 6 ) {
								setAttributes( { titleLevel: parsed } );
							}
						},
						__nextHasNoMarginBottom: true,
					} ),
					el( ToggleControl, {
						label: __( 'Collapsible (accordion)', 'accessible-accordion-faq-schema' ),
						help: collapsible
							? __( 'Each answer is hidden until its question is clicked. A small script (~0.5KB) loads on the page.', 'accessible-accordion-faq-schema' )
							: __( 'All answers are visible. No JavaScript is loaded.', 'accessible-accordion-faq-schema' ),
						checked: collapsible,
						onChange: function ( val ) {
							setAttributes( { collapsible: !! val } );
						},
						__nextHasNoMarginBottom: true,
					} )
				),
				el(
					PanelBody,
					{ title: __( 'Schema & linking', 'accessible-accordion-faq-schema' ), initialOpen: false },
					el(
						'p',
						{ style: { margin: 0, fontSize: '12px' } },
						__( 'FAQPage JSON-LD schema is generated automatically. The wrapper id defaults to "faq". Customize via the HTML anchor field in the Advanced panel below.', 'accessible-accordion-faq-schema' )
					)
				)
			);

			// Custom HTML anchor field. We do NOT use supports.anchor here
			// because that maps the anchor to a saved-HTML `id` attribute,
			// which fails for dynamic blocks where save() returns no HTML.
			var advanced = el(
				InspectorAdvancedControls,
				null,
				el( TextControl, {
					label: __( 'HTML anchor', 'accessible-accordion-faq-schema' ),
					help: __( 'Used as the wrapper id, so visitors can link directly to this FAQ section (e.g. yourpage.com/about/#shipping). Leave empty for the default "faq". Letters, numbers, hyphens, and underscores only.', 'accessible-accordion-faq-schema' ),
					value: anchor,
					onChange: function ( val ) {
						// Sanitize input live: keep only chars valid in HTML ids,
						// matching server-side sanitize_html_class() behavior.
						var clean = String( val ).replace( /[^A-Za-z0-9_-]/g, '' );
						setAttributes( { anchor: clean } );
					},
					__nextHasNoMarginBottom: true,
				} )
			);

			var titleField = el( RichText, {
				tagName: 'h' + titleLevel,
				className: 'cis_accordion__title-editor',
				value: title,
				onChange: function ( val ) {
					setAttributes( { title: val } );
				},
				placeholder: __( 'Optional FAQ section title…', 'accessible-accordion-faq-schema' ),
				allowedFormats: [],
				identifier: 'title',
			} );

			var innerBlocks = el( InnerBlocks, {
				allowedBlocks: PARENT_ALLOWED,
				template: PARENT_TEMPLATE,
				templateLock: false,
				orientation: 'vertical',
				renderAppender: InnerBlocks.ButtonBlockAppender,
			} );

			return el(
				Fragment,
				null,
				inspector,
				advanced,
				el( 'div', blockProps, titleField, innerBlocks )
			);
		},

		// Dynamic render — server-side via render.php.
		save: function () {
			return el( InnerBlocks.Content );
		},
	} );

	// -----------------------------------------------------------------------
	// Child block: cis/faq-item
	// -----------------------------------------------------------------------

	var CHILD_ALLOWED  = [
		'core/paragraph',
		'core/list',
		'core/list-item',
		'core/heading',
		'core/quote',
		'core/code',
	];
	var CHILD_TEMPLATE = [
		[ 'core/paragraph', { placeholder: 'Answer (Enter for new paragraph; Cmd/Ctrl+K for a link)…' } ],
	];

	registerBlockType( 'cis/faq-item', {
		edit: function ( props ) {
			var attributes    = props.attributes;
			var setAttributes = props.setAttributes;
			var context       = props.context || {};

			var question      = attributes.question || '';
			var isCollapsible = !! context[ 'cis/accessible-accordion-faq/collapsible' ];

			var blockProps = useBlockProps( {
				className: 'cis_accordion__item-editor' + ( isCollapsible ? ' is-collapsible' : '' ),
			} );

			return el(
				'div',
				blockProps,
				el( RichText, {
					tagName: 'div',
					className: 'cis_accordion__question-editor',
					value: question,
					onChange: function ( val ) {
						setAttributes( { question: val } );
					},
					placeholder: __( 'Question…', 'accessible-accordion-faq-schema' ),
					allowedFormats: [ 'core/bold', 'core/italic' ],
					identifier: 'question',
				} ),
				el(
					'div',
					{ className: 'cis_accordion__answer-editor' },
					el( InnerBlocks, {
						allowedBlocks: CHILD_ALLOWED,
						template: CHILD_TEMPLATE,
						templateLock: false,
					} )
				)
			);
		},

		save: function () {
			return el( InnerBlocks.Content );
		},
	} );
}( window.wp ) );
