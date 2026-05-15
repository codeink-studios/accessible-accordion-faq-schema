/**
 * Accessible FAQ Accordion — frontend toggle.
 *
 * Vanilla, dependency-free. ~0.5 KB minified equivalent. Loads only when at
 * least one collapsible block instance is on the page (enqueued from
 * render.php, not declared as block.json viewScript).
 *
 * @package CIS_AAFS
 */

( function () {
	'use strict';

	/**
	 * Wire one trigger button.
	 *
	 * @param {HTMLButtonElement} button Trigger button.
	 */
	function bindTrigger( button ) {
		// Idempotent: do not bind the same button twice (cheap guard for
		// pages that re-run init after async DOM insertion).
		if ( button.dataset.cisAafsBound === '1' ) {
			return;
		}
		button.dataset.cisAafsBound = '1';

		button.addEventListener( 'click', function () {
			var expanded = 'true' === button.getAttribute( 'aria-expanded' );
			var answerId = button.getAttribute( 'aria-controls' );
			if ( ! answerId ) {
				return;
			}
			var answer = document.getElementById( answerId );
			if ( ! answer ) {
				return;
			}
			button.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
			if ( expanded ) {
				answer.setAttribute( 'hidden', '' );
			} else {
				answer.removeAttribute( 'hidden' );
			}
		} );
	}

	function init() {
		var triggers = document.querySelectorAll(
			'.cis_accordion--collapsible .cis_accordion__trigger'
		);
		for ( var i = 0; i < triggers.length; i++ ) {
			bindTrigger( triggers[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
