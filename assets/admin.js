/* Angebotsanfrage – Admin JS */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		// Kategorie-Auswahl ein-/ausblenden je nach „Anwenden auf"-Wahl.
		var radios  = document.querySelectorAll( 'input[name="apply_to"]' );
		var catRow  = document.getElementById( 'aga-categories-row' );

		if ( ! radios.length || ! catRow ) {
			return;
		}

		function syncCatRow() {
			var selected = document.querySelector( 'input[name="apply_to"]:checked' );
			if ( selected && selected.value === 'categories' ) {
				catRow.style.display = '';
			} else {
				catRow.style.display = 'none';
			}
		}

		radios.forEach( function ( radio ) {
			radio.addEventListener( 'change', syncCatRow );
		} );

		syncCatRow();
	} );
}() );
