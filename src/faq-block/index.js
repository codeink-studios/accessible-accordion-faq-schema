/**
 * Accessible FAQ Accordion block.
 *
 * No-build (no JSX) implementation using wp.element.createElement directly.
 * Keeps the plugin install-and-go for end users without a build step.
 *
 * @package CIS_AAFS
 */

( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps     = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var RichText          = wp.blockEditor.RichText;
	var PanelBody         = wp.components.PanelBody;
	var SelectControl     = wp.components.SelectControl;
	var ToggleControl     = wp.components.ToggleControl;
	var Button            = wp.components.Button;
	var el                = wp.element.createElement;
	var Fragment          = wp.element.Fragment;
	var __                = wp.i18n.__;

	registerBlockType( 'cis/accessible-accordion-faq', {
		edit: function ( props ) {
			var attributes    = props.attributes;
			var setAttributes = props.setAttributes;

			var title       = attributes.title || '';
			var titleLevel  = parseInt( attributes.titleLevel, 10 );
			if ( isNaN( titleLevel ) || titleLevel < 2 || titleLevel > 6 ) {
				titleLevel = 2;
			}
			var faqs        = Array.isArray( attributes.faqs ) ? attributes.faqs : [];
			var collapsible = !! attributes.collapsible;

			var blockProps = useBlockProps( {
				className: 'cis_accordion-editor' + ( collapsible ? ' is-collapsible' : '' ),
			} );

			// Always keep at least one (empty) FAQ row in state so the editor is never blank.
			if ( faqs.length === 0 ) {
				faqs = [ { question: '', answer: '' } ];
			}

			// Normalize a FAQ's answer field to an array of paragraph strings.
			// v1.0.x stored answer as a single string; v1.1.0+ stores it as
			// an array of paragraph strings. This handles both.
			function normalizeParagraphs( answer ) {
				if ( Array.isArray( answer ) ) {
					return answer.length > 0 ? answer : [ '' ];
				}
				if ( typeof answer === 'string' ) {
					return [ answer ];
				}
				return [ '' ];
			}

			function updateFaq( index, field, value ) {
				var next = faqs.map( function ( faq, i ) {
					if ( i !== index ) {
						return faq;
					}
					var updated = {};
					updated.question = faq.question || '';
					updated.answer   = normalizeParagraphs( faq.answer );
					updated[ field ] = value;
					return updated;
				} );
				setAttributes( { faqs: next } );
			}

			function updateParagraph( faqIndex, pIndex, value ) {
				var paragraphs = normalizeParagraphs( faqs[ faqIndex ].answer ).slice();
				paragraphs[ pIndex ] = value;
				updateFaq( faqIndex, 'answer', paragraphs );
			}

			function addParagraph( faqIndex ) {
				var paragraphs = normalizeParagraphs( faqs[ faqIndex ].answer ).slice();
				paragraphs.push( '' );
				updateFaq( faqIndex, 'answer', paragraphs );
			}

			function removeParagraph( faqIndex, pIndex ) {
				var paragraphs = normalizeParagraphs( faqs[ faqIndex ].answer ).slice();
				if ( paragraphs.length <= 1 ) {
					return;
				}
				paragraphs.splice( pIndex, 1 );
				updateFaq( faqIndex, 'answer', paragraphs );
			}

			function addFaq() {
				setAttributes( { faqs: faqs.concat( [ { question: '', answer: [ '' ] } ] ) } );
			}

			function removeFaq( index ) {
				if ( faqs.length <= 1 ) {
					setAttributes( { faqs: [ { question: '', answer: [ '' ] } ] } );
					return;
				}
				setAttributes( {
					faqs: faqs.filter( function ( _, i ) {
						return i !== index;
					} ),
				} );
			}

			function moveFaq( index, direction ) {
				var newIndex = index + direction;
				if ( newIndex < 0 || newIndex >= faqs.length ) {
					return;
				}
				var next   = faqs.slice();
				var temp   = next[ index ];
				next[ index ]    = next[ newIndex ];
				next[ newIndex ] = temp;
				setAttributes( { faqs: next } );
			}

			// Sidebar controls.
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
						__( 'FAQPage JSON-LD schema is generated automatically. To customize the anchor link (default: #faq), use the "HTML anchor" field under Advanced.', 'accessible-accordion-faq-schema' )
					)
				)
			);

			// Optional section title.
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

			// FAQ pair rows.
			var faqRows = faqs.map( function ( faq, index ) {
				var paragraphs = normalizeParagraphs( faq.answer );

				var controls = el(
					'div',
					{ className: 'cis_accordion__item-controls' },
					el( Button, {
						icon: 'arrow-up-alt2',
						label: __( 'Move FAQ up', 'accessible-accordion-faq-schema' ),
						onClick: function () { moveFaq( index, -1 ); },
						disabled: 0 === index,
						size: 'small',
					} ),
					el( Button, {
						icon: 'arrow-down-alt2',
						label: __( 'Move FAQ down', 'accessible-accordion-faq-schema' ),
						onClick: function () { moveFaq( index, 1 ); },
						disabled: index === faqs.length - 1,
						size: 'small',
					} ),
					el( Button, {
						icon: 'trash',
						label: __( 'Remove FAQ', 'accessible-accordion-faq-schema' ),
						onClick: function () { removeFaq( index ); },
						isDestructive: true,
						size: 'small',
					} )
				);

				var questionField = el( RichText, {
					tagName: 'div',
					className: 'cis_accordion__question-editor',
					value: faq.question || '',
					onChange: function ( val ) {
						updateFaq( index, 'question', val );
					},
					placeholder: __( 'Question…', 'accessible-accordion-faq-schema' ),
					allowedFormats: [ 'core/bold', 'core/italic' ],
					identifier: 'question-' + index,
				} );

				// One RichText per paragraph. onSplit is a no-op so pressing
				// Enter inside an answer paragraph does NOT split the parent
				// block; users add paragraphs via the explicit button below.
				var paragraphRows = paragraphs.map( function ( paragraph, pIndex ) {
					var pPlaceholder = 0 === pIndex
						? __( 'Answer (use Cmd/Ctrl+K to insert a link)…', 'accessible-accordion-faq-schema' )
						: __( 'Continue answer…', 'accessible-accordion-faq-schema' );

					return el(
						'div',
						{ key: pIndex, className: 'cis_accordion__paragraph-editor-wrap' },
						el( RichText, {
							tagName: 'div',
							className: 'cis_accordion__answer-editor',
							value: paragraph || '',
							onChange: function ( val ) {
								updateParagraph( index, pIndex, val );
							},
							placeholder: pPlaceholder,
							allowedFormats: [ 'core/bold', 'core/italic', 'core/link' ],
							identifier: 'answer-' + index + '-' + pIndex,
							onSplit: function () {},
							onReplace: function () {},
						} ),
						paragraphs.length > 1
							? el( Button, {
								icon: 'no-alt',
								label: __( 'Remove paragraph', 'accessible-accordion-faq-schema' ),
								onClick: function () { removeParagraph( index, pIndex ); },
								size: 'small',
								className: 'cis_accordion__paragraph-remove',
							} )
							: null
					);
				} );

				var addParagraphButton = el(
					Button,
					{
						variant: 'tertiary',
						onClick: function () { addParagraph( index ); },
						size: 'small',
						className: 'cis_accordion__add-paragraph',
					},
					__( '+ Add paragraph', 'accessible-accordion-faq-schema' )
				);

				return el(
					'div',
					{ key: index, className: 'cis_accordion__item-editor' },
					controls,
					questionField,
					paragraphRows,
					addParagraphButton
				);
			} );

			var addButton = el(
				Button,
				{
					variant: 'secondary',
					onClick: addFaq,
					className: 'cis_accordion__add-button',
				},
				__( 'Add FAQ', 'accessible-accordion-faq-schema' )
			);

			return el(
				Fragment,
				null,
				inspector,
				el( 'div', blockProps, titleField, faqRows, addButton )
			);
		},

		// Dynamic render — server-side via render.php.
		save: function () {
			return null;
		},
	} );
}( window.wp ) );
