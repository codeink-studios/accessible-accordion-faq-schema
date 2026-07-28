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
	var createBlock               = wp.blocks.createBlock;
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
	var useEffect                 = wp.element.useEffect;
	var select                    = wp.data.select;
	var __                        = wp.i18n.__;

	// Current block schema version. Bumped when attribute defaults change in
	// ways that would silently regress existing posts. See migration logic in
	// the parent block's edit() and parent render.php.
	var BLOCK_VERSION = 3;

	// -----------------------------------------------------------------------
	// Parent block: cis/accessible-accordion-faq
	// -----------------------------------------------------------------------

	var PARENT_ALLOWED  = [ 'cis/faq-item' ];
	var PARENT_TEMPLATE = [ [ 'cis/faq-item' ] ];

	// -----------------------------------------------------------------------
	// Legacy v1.x rescue migration
	//
	// v1.x stored the entire FAQ in ONE self-closing block: every Q/A pair
	// lived in a `faqs` array attribute and there were no child blocks at all.
	// v2.0.0 restructured to parent + cis/faq-item InnerBlocks children and
	// shipped no deprecation, so from v2 onward a v1 block matched nothing:
	// render.php walks innerBlocks, finds none, and returns ''. The FAQ
	// vanished from the front end while the data sat untouched in
	// post_content — and was destroyed for good the first time an editor
	// opened the post and saved it, because `faqs` is no longer a declared
	// attribute and undeclared attributes are dropped on re-serialization.
	//
	// save() returns null across the whole v1 line, and InnerBlocks.Content
	// over zero children also serializes to nothing, so these blocks parse as
	// VALID against the current save. A normal deprecation would never fire.
	// isEligible is the documented opt-in that lets a still-valid block
	// migrate anyway; it receives the RAW attributes from the block comment,
	// so `faqs` is visible here even though the current schema has no such key.
	// -----------------------------------------------------------------------

	var LEGACY_V1_ATTRIBUTES = {
		title:       { type: 'string',  default: '' },
		titleLevel:  { type: 'number',  default: 2 },
		faqs:        { type: 'array',   default: [] },
		collapsible: { type: 'boolean', default: false },
		anchor:      { type: 'string',  default: '' },
	};

	/**
	 * Normalize a v1 answer into an array of paragraph strings. v1.0.x stored
	 * a single string; v1.1.0+ stored an array of strings. Both shapes appear
	 * in the wild — the v1 render.php accepted either.
	 *
	 * @param {*} answer Raw legacy answer value.
	 * @return {Array} Trimmed, non-empty paragraph strings.
	 */
	function legacyAnswerParagraphs( answer ) {
		var out = [];
		if ( Array.isArray( answer ) ) {
			answer.forEach( function ( para ) {
				if ( typeof para === 'string' && para.trim() !== '' ) {
					out.push( para.trim() );
				}
			} );
		} else if ( typeof answer === 'string' && answer.trim() !== '' ) {
			out.push( answer.trim() );
		}
		return out;
	}

	/**
	 * True only for a genuine un-migrated v1 block: legacy `faqs` data present,
	 * carrying at least one real question or answer, and no child blocks yet.
	 * Deliberately strict — a false positive here would clobber a working v2/v3
	 * block's children.
	 *
	 * @param {Object} attributes  Raw parsed block-comment attributes.
	 * @param {Array}  innerBlocks Currently parsed inner blocks.
	 * @return {boolean} Whether the legacy migration should run.
	 */
	function hasLegacyFaqData( attributes, innerBlocks ) {
		if ( innerBlocks && innerBlocks.length > 0 ) {
			return false;
		}
		var faqs = attributes && attributes.faqs;
		if ( ! Array.isArray( faqs ) || faqs.length === 0 ) {
			return false;
		}
		return faqs.some( function ( faq ) {
			if ( ! faq || typeof faq !== 'object' ) {
				return false;
			}
			var question = typeof faq.question === 'string' ? faq.question.trim() : '';
			return question !== '' || legacyAnswerParagraphs( faq.answer ).length > 0;
		} );
	}

	registerBlockType( 'cis/accessible-accordion-faq', {
		edit: function ( props ) {
			var attributes    = props.attributes;
			var setAttributes = props.setAttributes;
			var clientId      = props.clientId;

			var title       = attributes.title || '';
			var titleLevel  = parseInt( attributes.titleLevel, 10 );
			if ( isNaN( titleLevel ) || titleLevel < 2 || titleLevel > 6 ) {
				titleLevel = 2;
			}
			var collapsible      = !! attributes.collapsible;
			var enableSchema     = !! attributes.enableSchema;
			var blockVersion     = parseInt( attributes.blockVersion, 10 ) || 0;
			var anchor           = ( typeof attributes.anchor === 'string' ) ? attributes.anchor : '';

			// One-time migration: write blockVersion + preserve legacy schema
			// behavior the first time a block is loaded under v3.x. A legacy
			// v2.x block is detected by having at least one inner cis/faq-item
			// whose `question` attribute is non-empty (a fresh insert has only
			// the template-stub child with an empty question).
			useEffect( function () {
				if ( blockVersion >= BLOCK_VERSION ) {
					return;
				}
				var blockData = select( 'core/block-editor' ).getBlock( clientId );
				var innerBlocks = ( blockData && blockData.innerBlocks ) || [];
				var hasLegacyContent = innerBlocks.some( function ( item ) {
					var q = item && item.attributes && item.attributes.question;
					return typeof q === 'string' && q.trim() !== '';
				} );
				if ( hasLegacyContent ) {
					// v2.x block opened in v3 editor — preserve schema-on.
					setAttributes( {
						blockVersion: BLOCK_VERSION,
						enableSchema: true,
					} );
				} else {
					// Fresh v3 insert — apply v3 defaults.
					setAttributes( {
						blockVersion: BLOCK_VERSION,
						enableSchema: false,
					} );
				}
			}, [] );

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
							? __( 'Each answer is hidden until its question is clicked. Uses the browser’s native <details> element. No JavaScript loaded.', 'accessible-accordion-faq-schema' )
							: __( 'All answers are visible. No JavaScript is loaded.', 'accessible-accordion-faq-schema' ),
						checked: collapsible,
						onChange: function ( val ) {
							setAttributes( { collapsible: !! val } );
						},
						__nextHasNoMarginBottom: true,
					} ),
					el( ToggleControl, {
						label: __( 'Enable FAQ schema (JSON-LD)', 'accessible-accordion-faq-schema' ),
						help: enableSchema
							? __( 'A FAQPage JSON-LD block will be added to this page. Use only when this FAQ block is the primary content of the page, or to give AI search engines structured Q/A signal.', 'accessible-accordion-faq-schema' )
							: __( 'Off by default. Google removed FAQ rich results in May 2026, but the markup remains valid structured data and is still consumed by Bing and AI search surfaces. Turn on when this FAQ is the primary content of the page.', 'accessible-accordion-faq-schema' ),
						checked: enableSchema,
						onChange: function ( val ) {
							setAttributes( { enableSchema: !! val } );
						},
						__nextHasNoMarginBottom: true,
					} )
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

		deprecated: [
			{
				attributes: LEGACY_V1_ATTRIBUTES,

				// Matches the v1 line, which was fully dynamic.
				save: function () {
					return null;
				},

				isEligible: function ( attributes, innerBlocks ) {
					return hasLegacyFaqData( attributes, innerBlocks );
				},

				migrate: function ( attributes ) {
					var items = [];

					( attributes.faqs || [] ).forEach( function ( faq ) {
						if ( ! faq || typeof faq !== 'object' ) {
							return;
						}

						var question   = typeof faq.question === 'string' ? faq.question.trim() : '';
						var paragraphs = legacyAnswerParagraphs( faq.answer );

						// Nothing recoverable in this row — drop it rather than
						// leaving an empty item behind.
						if ( question === '' && paragraphs.length === 0 ) {
							return;
						}

						// Keep a question whose answer went missing: an empty
						// paragraph gives the editor something to type into,
						// and losing the question would be worse.
						if ( paragraphs.length === 0 ) {
							paragraphs = [ '' ];
						}

						items.push( createBlock(
							'cis/faq-item',
							{ question: question },
							paragraphs.map( function ( para ) {
								return createBlock( 'core/paragraph', { content: para } );
							} )
						) );
					} );

					var level = parseInt( attributes.titleLevel, 10 );
					if ( isNaN( level ) || level < 2 || level > 6 ) {
						level = 2;
					}

					return [
						{
							title:        typeof attributes.title === 'string' ? attributes.title : '',
							titleLevel:   level,
							collapsible:  !! attributes.collapsible,
							anchor:       typeof attributes.anchor === 'string' ? attributes.anchor : '',
							// v1 had no schema toggle — it always emitted
							// FAQPage JSON-LD. Preserve that, matching the
							// v2.x legacy gate in render.php.
							enableSchema: true,
							blockVersion: BLOCK_VERSION,
						},
						items,
					];
				},
			},
		],
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
