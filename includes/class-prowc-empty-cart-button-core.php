<?php
/**
 * Empty Cart Button for WooCommerce - Core Class
 *
 * @version 1.2.4
 * @since   1.0.0
 * @author  ProWCPlugins
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'ProWC_Empty_Cart_Button_Core' ) ) :

class ProWC_Empty_Cart_Button_Core {

	/**
	 * Constructor.
	 *
	 * @version 1.2.4
	 * @since   1.0.0
	 * @todo    [feature] https://www.w3schools.com/css/css3_buttons.asp
	 * @todo    [feature] Font Awesome, e.g.: `<i class="fas fa-shopping-cart"></i>`
	 */
	function __construct() {
		if ( 'yes' === get_option( 'prowc_empty_cart_button_enabled', 'yes' ) ) {
			// Actions
			add_action( 'init', array( $this, 'empty_cart' ) );
			// Output
			if ( 'disable' != ( $empty_cart_cart_position = apply_filters( 'prowc_empty_cart_button', 'woocommerce_after_cart', 'value_position_cart' ) ) ) {
				add_action( $empty_cart_cart_position, array( $this, 'output_empty_cart_form' ), get_option( 'prowc_empty_cart_position_priority', 10 ) );
			}
			if ( 'disable' != ( $empty_cart_checkout_position = get_option( 'prowc_empty_cart_checkout_position', 'disable' ) ) ) {
				add_action( $empty_cart_checkout_position, array( $this, 'output_empty_cart_form_checkout' ), get_option( 'prowc_empty_cart_checkout_position_priority', 10 ) );
			}
			// Shortcodes
			add_shortcode( 'prowc_empty_cart_button', array( $this, 'get_empty_cart_form_shortcode' ) );
			// Scripts
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	/**
	 * enqueue_scripts.
	 *
	 * @version 1.2.4
	 * @since   1.2.4
	 */
	function enqueue_scripts( $atts ) {
		if ( 'confirm_with_pop_up_box_jquery' == get_option( 'prowc_empty_cart_confirmation', 'no_confirmation' ) ) {
			wp_enqueue_script( 'prowc-empty-cart-button-confirm-lib',
				prowc_empty_cart_button()->plugin_url() . '/includes/lib/jquery-confirm/jquery-confirm.min.js',
				array( 'jquery' ),
				prowc_empty_cart_button()->version,
				true );
			wp_enqueue_style( 'prowc-empty-cart-button-confirm-lib',
				prowc_empty_cart_button()->plugin_url() . '/includes/lib/jquery-confirm/jquery-confirm.min.css',
				array(),
				prowc_empty_cart_button()->version );

			wp_enqueue_script( 'prowc-empty-cart-button-confirm',
				prowc_empty_cart_button()->plugin_url() . '/includes/js/prowc-empty-cart-button-confirm.js',
				array( 'jquery' ),
				prowc_empty_cart_button()->version,
				true );
			wp_localize_script( 'prowc-empty-cart-button-confirm', 'prowc_ecb_confirm_object', array(
					'content' => get_option( 'prowc_empty_cart_confirmation_text', __( 'Are you sure?', 'empty-cart-button-for-woocommerce' ) ),
				) );
		}
	}

	/**
	 * get_empty_cart_form_shortcode.
	 *
	 * @version 1.2.1
	 * @since   1.1.0
	 */
	function get_empty_cart_form_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'html_template' => '<div style="float:right;">%button_form%</div>',
			'html_style'    => '',
			'html_class'    => 'button',
		), $atts, 'prowc_empty_cart_button' );
		return $this->get_empty_cart_form( wp_kses_post($atts['html_template']), esc_attr($atts['html_style']), esc_attr($atts['html_class']), apply_filters( 'prowc_empty_cart_button', 'Empty cart', 'value_text' ) );
	}

	/**
	 * output_empty_cart_form.
	 *
	 * @version 1.2.1
	 * @since   1.1.0
	 */
	public function output_empty_cart_form() {
		echo wp_kses_post(
			$this->get_empty_cart_form(
				get_option('prowc_empty_cart_template', '<div style="float:right;">%button_form%</div>'),
				get_option('prowc_empty_cart_button_style', ''),
				get_option('prowc_empty_cart_button_class', 'button'),
				apply_filters('prowc_empty_cart_button_text', 'Empty cart', 'value_text')
			)
		);
	}

	/**
	 * output_empty_cart_form_checkout.
	 *
	 * @version 1.2.1
	 * @since   1.2.0
	 */
	public function output_empty_cart_form_checkout() {
		echo wp_kses_post(
			$this->get_empty_cart_form(
				get_option('prowc_empty_cart_template_checkout', '<div style="float:right;">%button_form%</div>'),
				get_option('prowc_empty_cart_button_style_checkout', ''),
				get_option('prowc_empty_cart_button_class_checkout', 'button'),
				apply_filters('prowc_empty_cart_button_text', 'Empty cart', 'value_text')
			)
		);
	}

	/**
	 * get_empty_cart_form.
	 *
	 * @version 1.2.4
	 * @since   1.0.0
	 * @todo    [dev] remove deprecated `<form>` completely (and use only `<a>`) (i.e. remove `get_option( 'prowc_empty_cart_button_tag', 'a' )`)
	 * @todo    [dev] (maybe) add `<button>` as optional "type"
	 * @todo    [dev] (maybe) add optional `method="get"` in `<form>`
	 */
	function get_empty_cart_form( $html_template, $html_style, $html_class, $label ) {
		$confirmation_type  = get_option( 'prowc_empty_cart_confirmation', 'no_confirmation' );
		$confirmation_html  = ( 'confirm_with_pop_up_box' == $confirmation_type ) ?
			' onclick="return confirm(\'' . get_option( 'prowc_empty_cart_confirmation_text', __( 'Are you sure?', 'empty-cart-button-for-woocommerce' ) ) . '\')"' : '';
		$confirmation_class = ( 'confirm_with_pop_up_box_jquery' == $confirmation_type ) ?
			'prowc-empty-cart-button-confirm ' : '';
		switch ( get_option( 'prowc_empty_cart_button_tag', 'a' ) ) {
			case 'a':
				$button_form_html = wp_nonce_field( 'prowc_empty_cart_nonce', 'nonce' ) .'<a href="' . add_query_arg( array( 'prowc_empty_cart' => '' ), wc_get_cart_url() ) . '" style="' . $html_style . '"' .
					' class="' . $confirmation_class . $html_class . '" id="prowc_empty_cart"' . $confirmation_html . '>' . $label . '</a>';
				break;
			case 'form':
				$button_form_html = '<form action="" method="post"><input type="submit" style="' . $html_style . '"' .
					' class="' . $confirmation_class . $html_class . '" name="prowc_empty_cart" id="prowc_empty_cart" value="' . $label . '"' . $confirmation_html . '></form>';
				break;
		}
		return str_replace( '%button_form%', $button_form_html, $html_template );
	}

	/**
	 * empty_cart.
	 *
	 * @version 1.2.3
	 * @since   1.0.0
	 */
	function empty_cart() {
		if ( isset( $_REQUEST['prowc_empty_cart'] ) ) {
			WC()->cart->empty_cart();
			if ( 'yes' === apply_filters( 'prowc_empty_cart_button', 'no', 'value_redirection' ) ) {
				if ( wp_redirect( get_option( 'prowc_empty_cart_button_redirect_location', '' ) ) ) {
					exit;
				}
			}
			if ( isset( $_GET['prowc_empty_cart'] ) ) {
				if ( wp_redirect( remove_query_arg( 'prowc_empty_cart' ) ) ) {
					exit;
				}
			}
		}
	}
	
}

endif;

return new ProWC_Empty_Cart_Button_Core();
