<?php
/**
 * Plugin Name: Gravity Forms 2Checkout Add-On
 * Plugin URI: https://gravityforms.com
 * Description: Integrates Gravity Forms with 2Checkout, enabling end users to purchase goods and services through Gravity Forms.
 * Version: 2.2.0
 * Author: Gravity Forms
 * Author URI: https://gravityforms.com
 * Text Domain: gravityforms2checkout
 * Domain Path: /languages
 *
 * ------------------------------------------------------------------------
 * Copyright 2009 - 2024 rocketgenius
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

defined( 'ABSPATH' ) or die();

define( 'GF_2CHECKOUT_VERSION', '2.2.0' );

// If Gravity Forms is loaded, bootstrap the 2Checkout Add-On.
add_action( 'gform_loaded', array( 'GF_2Checkout_Bootstrap', 'load' ), 5 );

/**
 * Class GF_2Checkout_Bootstrap
 *
 * Handles the loading of the 2Checkout Add-On and registers with the Add-On framework.
 *
 * @since 1.0.0
 */
class GF_2Checkout_Bootstrap {

	/**
	 * If the Payment Add-On Framework exists, 2Checkout Add-On is loaded.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @uses GFAddOn::register()
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-2checkout.php' );

		GFAddOn::register( 'GF_2Checkout' );

	}

}

/**
 * Obtains and returns an instance of the GF_2Checkout class
 *
 * @since  1.0.0
 * @access public
 *
 * @uses GF_2Checkout::get_instance()
 *
 * @return GF_2Checkout
 */
function gf_2checkout() {
	return GF_2Checkout::get_instance();
}
