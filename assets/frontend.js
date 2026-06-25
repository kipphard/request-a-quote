/* Angebotsanfrage – Frontend JS */
( function () {
	'use strict';

	var data = window.agaData || {};

	/**
	 * AJAX-Hilfsfunktion.
	 *
	 * @param {string}   ajaxAction AJAX-Action-Name.
	 * @param {number}   productId  Produkt-ID.
	 * @param {number}   qty        Menge (nur für add relevant).
	 * @param {Function} callback   Callback: function(success, responseData).
	 */
	function doAjax( ajaxAction, productId, qty, callback ) {
		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', data.ajaxUrl, true );
		xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
		xhr.onreadystatechange = function () {
			if ( xhr.readyState !== 4 ) {
				return;
			}
			try {
				var result = JSON.parse( xhr.responseText );
				callback( result.success === true, result.data || {} );
			} catch ( e ) {
				callback( false, {} );
			}
		};
		var params = 'action=' + encodeURIComponent( ajaxAction )
			+ '&nonce=' + encodeURIComponent( data.nonce )
			+ '&product_id=' + encodeURIComponent( productId )
			+ '&qty=' + encodeURIComponent( qty );
		xhr.send( params );
	}

	/**
	 * "Angebot anfragen"-Buttons initialisieren.
	 */
	function initAddButtons() {
		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.aga-add-to-quote' );
			if ( ! btn ) {
				return;
			}
			e.preventDefault();

			var productId = parseInt( btn.getAttribute( 'data-product-id' ), 10 );
			var qty       = parseInt( btn.getAttribute( 'data-qty' ) || '1', 10 );

			if ( ! productId ) {
				return;
			}

			btn.disabled = true;

			doAjax( 'aga_add_to_quote', productId, qty, function ( success, result ) {
				btn.disabled = false;
				if ( success ) {
					btn.textContent = data.i18n ? data.i18n.added : '';
					setTimeout( function () {
						btn.textContent = btn.getAttribute( 'data-original-label' ) || btn.textContent;
					}, 2000 );
					updateCountBadge( result.count );
				} else {
					alert( data.i18n ? data.i18n.error : 'Fehler' );
				}
			} );

			if ( ! btn.getAttribute( 'data-original-label' ) ) {
				btn.setAttribute( 'data-original-label', btn.textContent );
			}
		} );
	}

	/**
	 * „Entfernen"-Buttons in der Angebotsliste initialisieren.
	 */
	function initRemoveButtons() {
		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.aga-remove-from-quote' );
			if ( ! btn ) {
				return;
			}
			e.preventDefault();

			var productId = parseInt( btn.getAttribute( 'data-product-id' ), 10 );
			if ( ! productId ) {
				return;
			}

			btn.disabled = true;

			doAjax( 'aga_remove_from_quote', productId, 0, function ( success, result ) {
				if ( success ) {
					var row = btn.closest( 'tr' );
					if ( row ) {
						row.parentNode.removeChild( row );
					}
					updateCountBadge( result.count );
					// Wenn Liste leer → Formular ausblenden.
					var tbody = document.querySelector( '.aga-list-table tbody' );
					if ( tbody && tbody.children.length === 0 ) {
						var form = document.querySelector( '.aga-quote-form' );
						if ( form ) {
							form.style.display = 'none';
						}
						var table = document.querySelector( '.aga-list-table' );
						if ( table ) {
							table.style.display = 'none';
						}
						var emptyMsg = document.createElement( 'p' );
						emptyMsg.className = 'aga-empty';
						emptyMsg.textContent = data.i18n ? data.i18n.removed : '';
						var listWrap = document.querySelector( '.aga-quote-list' );
						if ( listWrap ) {
							listWrap.appendChild( emptyMsg );
						}
					}
				} else {
					btn.disabled = false;
					alert( data.i18n ? data.i18n.error : 'Fehler' );
				}
			} );
		} );
	}

	/**
	 * Zähler-Badge im Header oder Mini-Cart aktualisieren (falls vorhanden).
	 *
	 * @param {number|undefined} count Neue Anzahl.
	 */
	function updateCountBadge( count ) {
		if ( count === undefined ) {
			return;
		}
		var badges = document.querySelectorAll( '.aga-count-badge' );
		badges.forEach( function ( badge ) {
			badge.textContent = count;
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		initAddButtons();
		initRemoveButtons();
	} );
}() );
