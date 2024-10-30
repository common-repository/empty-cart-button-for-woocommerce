/**
 * Empty Cart Button for WooCommerce - jQuery Confirm
 *
 * @version 1.2.4
 * @since   1.2.4
 * @author  ProWCPlugins
 * @see     https://craftpip.github.io/jquery-confirm/
 * @todo    [dev] use `jc-attached` instead of `prowc-empty-cart-button-confirm-bind`
 * @todo    [feature] customizable styling (e.g. `theme`) etc.
 */

function prowc_ecb_confirm() {
	jQuery( '.prowc-empty-cart-button-confirm' ).each( function() {
		if ( ! jQuery( this ).hasClass( 'prowc-empty-cart-button-confirm-bind' ) ) {
			jQuery( this ).addClass( 'prowc-empty-cart-button-confirm-bind' );
			jQuery( this ).confirm( {
				title: false,
				content: prowc_ecb_confirm_object.content,
				theme: 'material',
				boxWidth: '300px',
				useBootstrap: false,
			} );
		}
	} );
}

jQuery( document ).ready( function() {
	prowc_ecb_confirm();
	jQuery( document ).ajaxComplete( prowc_ecb_confirm );
} );
