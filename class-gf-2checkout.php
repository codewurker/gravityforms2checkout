<?php

/**
 * Gravity Forms 2Checkout Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2018, Rocketgenius
 */

defined( 'ABSPATH' ) or die();

// Include the Payment Add-On framework.
GFForms::include_payment_addon_framework();

/**
 * Class GF_2Checkout
 *
 * Primary class to manage the 2Checkout Add-On.
 *
 * @since 1.0
 *
 * @uses GFPaymentAddOn
 */
class GF_2Checkout extends GFPaymentAddOn {

	/**
	 * Version of this add-on which requires reauthentication with the API.
	 *
	 * Anytime updates are made to this class that requires a site to reauthenticate Gravity Forms with 2Checkout, this
	 * constant should be updated to the value of GFForms::$version.
	 *
	 * @since 2.0.2
	 *
	 * @see GFForms::$version
	 */
	const LAST_REAUTHENTICATION_VERSION = '2.0';

	/**
	 * The hashing algorithm used to hash the IPN data and API requests.
	 *
	 * @since 2.2
	 */
	const HASHING_ALGORITHM = 'sha3-256';

	protected $_enable_theme_layer = true;

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @used-by GF_2Checkout::get_instance()
	 *
	 * @var object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the 2Checkout Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @used-by GF_2Checkout::scripts()
	 *
	 * @var string $_version Contains the version, defined from 2checkout.php
	 */
	protected $_version = GF_2CHECKOUT_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '2.2';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityforms2checkout';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityforms2checkout/2checkout.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_url The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms 2Checkout Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var string $_short_title The short title.
	 */
	protected $_short_title = '2Checkout';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @var bool $_enable_rg_autoupgrade true
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines if user will not be able to create feeds for a form until a credit card field has been added.
	 *
	 * @since  1.0
	 * @since  2.0 set to false in favor of the 2Checkout field.
	 * @access protected
	 *
	 * @var bool $_requires_credit_card true.
	 */
	protected $_requires_credit_card = false;

	/**
	 * Defines if callbacks/webhooks/IPN will be enabled and the appropriate database table will be created.
	 *
	 * @since  2.0
	 * @access protected
	 *
	 * @var bool $_supports_callbacks true
	 */
	protected $_supports_callbacks = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_2checkout';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_2checkout';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.4.3
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_2checkout_uninstall';

	/**
	 * Defines the capabilities needed for the 2Checkout Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_2checkout', 'gravityforms_2checkout_uninstall' );

	/**
	 * Contains an instance of the 2Checkout API library, if available.
	 *
	 * @since  1.0
	 * @since  2.0 API contains only one instance for production, it is no longer an array.
	 * @access protected
	 * @var    GF_2Checkout_API $api If available, contains an instance of the 2Checkout API library.
	 */
	protected $api = null;

	/**
	 * Contains the nonce that is used to verify 3DSecure success callback.
	 *
	 * @since 1.3
	 *
	 * @var string
	 */
	protected $success_nonce = null;

	/**
	 * Get an instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GF_2Checkout::$_instance
	 *
	 * @return GF_2Checkout
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Loads the 2Checkout field.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public function pre_init() {

		// For form 3DSecure confirmation redirection, this must be called in `wp`,
		// or confirmation redirect to a page would throw PHP fatal error.
		// Run before calling parent method. We don't want to run anything else before displaying thank you page.
		add_action( 'wp', array( $this, 'maybe_display_thankyou_page_after_3dsecure' ), 5 );

		parent::pre_init();

		require_once 'includes/class-gf-field-2checkout-creditcard.php';
	}


	/**
	 * Handles 3DSecure success redirect by completing the transaction and displaying/processing the form confirmation.
	 *
	 * @since 2.0
	 */
	public function maybe_display_thankyou_page_after_3dsecure() {
		if ( ! $this->is_gravityforms_supported() || ! $this->initialize_api() ) {
			return;
		}

		$entry = $this->get_entry_by_3dsecure_nonce( sanitize_text_field( rgget( 'gf_2checkout_3ds_success' ) ) );
		if ( ! $entry ) {
			return;
		}

		$this->handle_confirmation( $this->process_entry_after_successful_3dsecure( $entry ) );
	}

	/**
	 * Retrieves an entry by looking up for the success nonce that was saved before sending the user to 3DSecure challenge.
	 *
	 * @since 2.0
	 *
	 * @param string $nonce The success token.
	 *
	 * @return false|array The entry if found, or false.
	 */
	private function get_entry_by_3dsecure_nonce( $nonce ) {

		if ( empty( $nonce ) ) {
			return false;
		}

		$entries = GFAPI::get_entries(
			0,
			array(
				'field_filters' => array(
					array(
						'key'   => '3dsecure_success_nonce',
						'value' => wp_hash( $nonce ),
					),
				),
			)
		);

		if ( is_wp_error( $entries ) || ! is_array( $entries ) || count( $entries ) < 1 ) {
			return false;
		}

		return $entries[0];
	}

	/**
	 * Completes processing the entry payment information after successful 3DSecure flow.
	 *
	 * @since 2.0
	 *
	 * @param array $entry Current entry object being processed.
	 *
	 * @return array The entry array after updating its payment information.
	 */
	private function process_entry_after_successful_3dsecure( $entry ) {

		// Only process entry if it is still in pending status.
		// Sometimes IPN is sent and handled before the user is redirected back to the confirmation page, check status to prevent processing the entry twice.
		if ( $entry['payment_status'] !== 'Pending' ) {
			return $entry;
		}

		$order_details = gform_get_meta( $entry['id'], 'order_details' );
		$order_type    = gform_get_meta( $entry['id'], 'order_type' );
		$form          = GFAPI::get_form( $entry['form_id'] );

		if ( $order_type === 'subscription' ) {
			$entry = parent::process_subscription(
				array(
					'subscription' => array(
						'subscription_id' => $order_details['RefNo'],
						'is_success'      => true,
						'amount'          => $order_details['NetPrice'],
					),
				),
				array(),
				array(),
				$form,
				$entry
			);
		} else {
			parent::complete_authorization(
				$entry,
				array(
					'amount'         => $order_details['NetPrice'],
					'transaction_id' => $order_details['RefNo'],
				)
			);
		}

		return $entry;
	}

	/**
	 * Handles displaying/processing entry confirmation after successful 3DSecure flow.
	 *
	 * @since 2.0
	 *
	 * @param array $entry Current entry object being processed.
	 */
	private function handle_confirmation( $entry ) {

		if ( ! class_exists( 'GFFormDisplay' ) ) {
			require_once( GFCommon::get_base_path() . '/form_display.php' );
		}

		$form         = GFAPI::get_form( $entry['form_id'] );
		$confirmation = GFFormDisplay::handle_confirmation( $form, $entry, false );

		if ( is_array( $confirmation ) && isset( $confirmation['redirect'] ) ) {
			header( "Location: {$confirmation['redirect']}" );
			exit;
		}

		GFFormDisplay::$submission[ $entry['form_id'] ] = array(
			'is_confirmation'      => true,
			'confirmation_message' => $confirmation,
			'form'                 => $form,
			'lead'                 => $entry,
		);
	}

	/**
	 * Initialize the frontend hooks.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GF_2Checkout::register_init_scripts()
	 * @uses GF_2Checkout::populate_credit_card_last_four()
	 * @uses GFPaymentAddOn::init()
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'gform_field_content', array( $this, 'add_2checkout_token' ), 10, 5 );
		add_filter( 'gform_register_init_scripts', array( $this, 'register_init_scripts' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'add_upgrade_connection_notice' ) );
		add_action( 'admin_notices', array( $this, 'maybe_display_ipn_hash_upgrade_notice' ) );

		// Supports frontend feeds.
		$this->_supports_frontend_feeds = true;

		parent::init();
	}

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @since  1.0
	 * @since  2.0 Use 2pay.js library instead of deprecated 2co.js
	 * @access public
	 *
	 * @uses   GF_2Checkout::frontend_script_callback()
	 * @uses   GFAddOn::get_base_url()
	 * @uses   GFAddOn::get_short_title()
	 * @uses   GFAddOn::get_version()
	 * @uses   GFCommon::get_base_url()
	 * @uses   GFPaymentAddOn::scripts()
	 *
	 * @return array
	 */
	public function scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$scripts = array(
			array(
				'handle'  => '2pay.js',
				'src'     => 'https://2pay-js.2checkout.com/v1/2pay.js',
				'version' => $this->get_version(),
				'deps'    => array(),
			),
			array(
				'handle'    => 'gform_2checkout_frontend',
				'src'       => $this->get_base_url() . "/js/frontend{$min}.js",
				'version'   => $this->get_version(),
				'deps'      => array( 'jquery', '2pay.js', 'wp-a11y' ),
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'frontend_script_callback' ),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );

	}

	/***
	 * Return the styles that need to be enqueued.
	 *
	 * @since  2.0
	 *
	 * @return array Returns an array of styles and when to enqueue them
	 */
	public function styles() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$styles = array(
			array(
				'handle'    => 'gforms_2checkout_frontend',
				'src'       => $this->get_base_url() . "/css/frontend{$min}.css",
				'version'   => $this->_version,
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'frontend_script_callback' ),
				),
			),
			array(
				'handle'    => 'gforms_2checkout_plugin_settings',
				'src'       => $this->get_base_url() . "/css/plugin_settings{$min}.css",
				'version'   => $this->_version,
				'in_footer' => false,
				'enqueue'   => array(
					array(
						'admin_page' => array( 'form_settings', 'form_editor' ),
					),
				),
			),
		);

		return array_merge( parent::styles(), $styles );

	}

	/**
	 * Check if the form has an active 2Checkout feed and a credit card field.
	 *
	 * @since  1.0
	 * @since  2.0 Check if 2Checkout field exists instead of default credit card field.
	 * @access public
	 *
	 * @used-by GF_2Checkout::scripts()
	 * @uses    GFFeedAddOn::has_feed()
	 * @uses    GFPaymentAddOn::has_credit_card_field()
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool
	 */
	public function frontend_script_callback( $form ) {

		return ! is_admin() && $form && $this->has_feed( $form['id'] ) && $this->has_2checkout_card_field( $form );

	}

	/**
	 * Add required 2Checkout token hidden input in case of a multi-page form.
	 *
	 * @since  2.0
	 *
	 * @param string  $content The field content to be filtered.
	 * @param object  $field   The field that this input tag applies to.
	 * @param string  $value   The default/initial value that the field should be pre-populated with.
	 * @param integer $lead_id When executed from the entry detail screen, $lead_id will be populated with the Entry ID.
	 * @param integer $form_id The current Form ID.
	 *
	 * @return string $content HTML formatted content.
	 */
	public function add_2checkout_token( $content, $field, $value, $lead_id, $form_id ) {

		// If this form does not have a 2Checkout feed or if this is not a 2Checkout field, return field content.
		if ( ! $this->has_feed( $form_id ) || $field->get_input_type() !== '2checkout_creditcard' ) {
			return $content;
		}

		// Populate 2Checkout token to hidden fields if it exists.
		$token = sanitize_text_field( rgpost( '2checkout_response' ) );
		if ( $token ) {
			$content .= '<input type="hidden" name="2checkout_response" value="' . esc_attr( $token ) . '" />';
		}

		return $content;

	}


	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Prepare plugin settings fields.
	 *
	 * @since  1.0
	 * @since  2.0 Use merchant code & secret and deprecate sandbox credentials.
	 * @access public
	 *
	 * @return array
	 * @uses   GF_2Checkout::initialize_api()
	 */
	public function plugin_settings_fields() {
		$tooltip =
			sprintf(
				// Translators: 1. Open anchor tag 2. close anchor tag.
				esc_html__( 'Enter your 2Checkout API credentials below. For more information about these settings, check out the %1$sGravity Forms documentation.%2$s', 'gravityforms2checkout' ),
				'<a href="https://docs.gravityforms.com/setting-up-the-2checkout-add-on/" target="_blank" title="Setting up the 2Checkout Add-on">',
				'</a>'
		);

		return array(
			array(
				'title'  => esc_html__( 'API Mode', 'gravityforms2checkout' ),
				'fields' => array(
					array(
						'name'          => 'apiMode',
						'label'         => esc_html__( 'API Mode', 'gravityforms2checkout' ),
						'type'          => 'radio',
						'required'      => true,
						'horizontal'    => true,
						'default_value' => 'sandbox',
						'choices'       => array(
							array(
								'label' => esc_html__( 'Production', 'gravityforms2checkout' ),
								'value' => 'production',
							),
							array(
								'label' => esc_html__( 'Sandbox', 'gravityforms2checkout' ),
								'value' => 'sandbox',
							),
						),
					),
				),
			),
			array(
				'title'   => esc_html__( 'API Credentials', 'gravityforms2checkout' ),
				'tooltip' => $tooltip,
				'fields'  => array(
					array(
						'name'              => 'merchant_code',
						'label'             => esc_html__( 'Merchant Code', 'gravityforms2checkout' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'validate_production_credentials' ),
						'required'          => true,
					),
					array(
						'name'              => 'secret_key',
						'label'             => esc_html__( 'Secret Key', 'gravityforms2checkout' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'validate_production_credentials' ),
						'required'          => true,
					),
					array(
						'name'  => 'publishable_key',
						'label' => esc_html__( 'Publishable Key', 'gravityforms2checkout' ),
						'type'  => 'text',
						'class' => 'medium',
					),
					array(
						'name'  => 'private_key',
						'label' => esc_html__( 'Private Key', 'gravityforms2checkout' ),
						'type'  => 'text',
						'class' => 'medium',
					),
					array(
						'name'        => 'IPN_enabled',
						'label'       => esc_html__( 'IPN Configured?', 'gravityforms2checkout' ),
						'type'        => 'checkbox',
						'horizontal'  => true,
						'required'    => true,
						'description' => $this->get_ipn_section_description(),
						'choices'     => array(
							array(
								'label' => esc_html__( 'I have enabled the Gravity Forms IPN URL in my 2Checkout account.', 'gravityforms2checkout' ),
								'value' => 1,
								'name'  => 'IPN_enabled',
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 1.7
	 *
	 * @return string
	 */
	public function get_menu_icon() {

		return file_get_contents( $this->get_base_path() . '/images/menu-icon.svg' );

	}

	/**
	 * Validate production API credentials.
	 *
	 * @since      1.0
	 * @access  public
	 *
	 * @used-by GF_2Checkout::plugin_settings_fields()
	 * @uses    GF_2Checkout::initialize_api()
	 *
	 * @return bool|null
	 */
	public function validate_production_credentials() {

		// Capture API response.
		$api_response = $this->initialize_api( 'production' );

		return is_a( $api_response, 'GF_2Checkout_API' ) ? true : $api_response;

	}

	/**
	 * Validate sandbox API credentials.
	 *
	 * @since      1.0
	 * @deprecated 2.0 No longer used by internal code.
	 * @access  public
	 *
	 * @used-by GF_2Checkout::plugin_settings_fields()
	 * @uses    GF_2Checkout::initialize_api()
	 *
	 * @return bool|null
	 */
	public function validate_sandbox_credentials() {

		// Capture API response.
		$api_response = $this->initialize_api( 'sandbox' );

		return is_a( $api_response, 'GF_2Checkout_API' ) ? true : $api_response;

	}


	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Set feed creation control.
	 *
	 * @since  1.0
	 * @since  2.0 Check if a 2Checkout field exists.
	 * @access public
	 *
	 * @uses   GF_2Checkout::initialize_api()
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return is_a( $this->initialize_api(), 'GF_2Checkout_API' ) && $this->has_2checkout_card_field();

	}

	/**
	 * Get the require 2Checkout field message.
	 *
	 * @since 2.0
	 *
	 * @return false|string
	 */
	public function feed_list_message() {
		$form = $this->get_current_form();

		// If settings are not yet configured, display default message.
		if ( ! is_a( $this->initialize_api(), 'GF_2Checkout_API' ) ) {
			return GFFeedAddOn::feed_list_message();
		}

		// If form doesn't have a 2Checkout field, display require message.
		if ( ! $this->has_2checkout_card_field( $form ) ) {
			return $this->requires_2checkout_card_message();
		}

		return GFFeedAddOn::feed_list_message();
	}

	/**
	 * Display require 2Checkout field message.
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	public function requires_2checkout_card_message() {
		$url = add_query_arg(
			array(
				'view'    => null,
				'subview' => null,
			)
		);

		return sprintf( esc_html__( "You must add a 2Checkout field to your form before creating a feed. Let's go %1\$sadd one%2\$s!", 'gravityforms2checkout' ), "<a href='" . esc_url( $url ) . "'>", '</a>' );
	}

	/**
	 * Setup fields for feed settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses   GFAddOn::remove_field()
	 * @uses   GFFeedAddOn::feed_settings_fields()
	 *
	 * @return array $settings
	 */
	public function feed_settings_fields() {

		// Get feed settings fields.
		$settings = parent::feed_settings_fields();

		// Add subscription name field.
		$subscription_name_field = array(
			'name'    => 'subscription_name',
			'label'   => esc_html__( 'Subscription Name', 'gravityforms2checkout' ),
			'type'    => 'text',
			'class'   => 'medium',
			'tooltip' => '<h6>' . esc_html__( 'Subscription Name', 'gravityforms2checkout' ) . '</h6>' . esc_html__( 'Enter a name for the subscription. It will be displayed on the 2Checkout dashboard.', 'gravityforms2checkout' ),
		);
		$settings                = $this->add_field_before( 'recurringAmount', $subscription_name_field, $settings );

		// Remove trial field.
		$settings = $this->remove_field( 'trial', $settings );

		return $settings;

	}

	/**
	 * Prepare a list of needed billing information fields.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function billing_info_fields() {

		$fields = array(
			array(
				'name'     => 'email',
				'label'    => esc_html__( 'Email', 'gravityforms2checkout' ),
				'required' => true,
			),
			array(
				'name'     => 'address',
				'label'    => esc_html__( 'Address', 'gravityforms2checkout' ),
				'required' => true,
			),
			array(
				'name'     => 'address2',
				'label'    => esc_html__( 'Address 2', 'gravityforms2checkout' ),
				'required' => false,
			),
			array(
				'name'     => 'city',
				'label'    => esc_html__( 'City', 'gravityforms2checkout' ),
				'required' => true,
			),
			array(
				'name'     => 'state',
				'label'    => esc_html__( 'State', 'gravityforms2checkout' ),
				'required' => true,
			),
			array(
				'name'     => 'zip',
				'label'    => esc_html__( 'Zip', 'gravityforms2checkout' ),
				'required' => true,
			),
			array(
				'name'     => 'country',
				'label'    => esc_html__( 'Country', 'gravityforms2checkout' ),
				'required' => true,
			),
			array(
				'name'     => 'phone',
				'label'    => esc_html__( 'Phone Number', 'gravityforms2checkout' ),
				'required' => true,
			),
		);

		return $fields;

	}

	/**
	 * Define the choices available in the billing cycle drop downs.
	 *
	 * @since   1.0
	 * @access  public
	 *
	 * @used-by GFPaymentAddOn::settings_billing_cycle()
	 *
	 * @return array
	 */
	public function supported_billing_intervals() {
		return array(
			'week'  => array(
				'label' => esc_html__( 'week(s)', 'gravityforms2checkout' ),
				'min'   => 1,
				'max'   => 12,
			),
			'month' => array(
				'label' => esc_html__( 'month(s)', 'gravityforms2checkout' ),
				'min'   => 1,
				'max'   => 12,
			),
			'year'  => array(
				'label' => esc_html__( 'year(s)', 'gravityforms2checkout' ),
				'min'   => 1,
				'max'   => 1,
			),
		);
	}

	/**
	 * Define the option choices available.
	 *
	 * @since   1.0
	 * @access  public
	 *
	 * @used-by GFPaymentAddOn::other_settings_fields()
	 *
	 * @return array
	 */
	public function option_choices() {

		return array();

	}


	// # FRONTEND ------------------------------------------------------------------------------------------------------

	/**
	 * Register 2Checkout script when displaying form.
	 *
	 * @since   1.0
	 * @access  public
	 *
	 * @param array $form         Form object.
	 * @param array $field_values Current field values. Not used.
	 * @param bool  $is_ajax      If form is being submitted via AJAX.
	 *
	 * @used-by GF_2Checkout::init()
	 * @uses    GFAddOn::get_plugin_settings()
	 * @uses    GFFeedAddOn::has_feed()
	 * @uses    GFFormDisplay::add_init_script()
	 * @uses    GFFormDisplay::ON_PAGE_RENDER
	 * @uses    GFPaymentAddOn::get_credit_card_field()
	 */
	public function register_init_scripts( $form, $field_values, $is_ajax ) {

		// Get 2Checkout field.
		$cc_field = $this->get_2checkout_card_field( $form );

		// If form does not have a 2Checkout feed and does not have a credit card field, exit.
		if ( ! $this->has_feed( $form['id'] ) || ! $cc_field || ! $this->initialize_api() ) {
			return;
		}

		// Get plugin settings.
		$settings = $this->get_plugin_settings();

		// Prepare 2Checkout Javascript arguments.
		$args = array(
			'apiMode'      => $settings['apiMode'],
			'formId'       => $form['id'],
			'ccFieldId'    => $cc_field->id,
			'ccPage'       => $cc_field->pageNumber,
			'isAjax'       => $is_ajax,
			'merchantCode' => $settings['merchant_code'],
			'secretKey'    => $settings['secret_key'],
		);

		// get all 2Checkout feeds.
		$feeds = $this->get_feeds_by_slug( $this->_slug, $form['id'] );

		foreach ( $feeds as $feed ) {
			if ( rgar( $feed, 'is_active' ) === '0' ) {
				continue;
			}

			// Get feed settings to pass them to JS object.
			$feed_settings = array(
				'feedId' => $feed['id'],
			);

			if ( rgars( $feed, 'meta/transactionType' ) === 'product' ) {
				$feed_settings['paymentAmount'] = rgars( $feed, 'meta/paymentAmount' );
			}

			$args['feeds'][ $feed['id'] ] = $feed_settings;
		}

		// Initialize 2Checkout script.
		$script = 'new GF2Checkout( ' . json_encode( $args ) . ' );';

		// Add 2Checkout script to form scripts.
		GFFormDisplay::add_init_script( $form['id'], '2checkout', GFFormDisplay::ON_PAGE_RENDER, $script );

	}

	/**
	 * Gets the payment validation result.
	 *
	 * @since  2.0
	 *
	 * @param array $validation_result Contains the form validation results.
	 * @param array $authorization_result Contains the form authorization results.
	 *
	 * @return array The validation result for the credit card field.
	 */
	public function get_validation_result( $validation_result, $authorization_result ) {
		if ( empty( $authorization_result['error_message'] ) ) {
			return $validation_result;
		}

		$credit_card_page = 0;
		foreach ( $validation_result['form']['fields'] as &$field ) {
			if ( $field->type === '2checkout_creditcard' ) {
				$field->failed_validation  = true;
				$field->validation_message = $authorization_result['error_message'];
				$credit_card_page          = $field->pageNumber;
				break;
			}
		}

		$validation_result['credit_card_page'] = $credit_card_page;
		$validation_result['is_valid']         = false;

		return $validation_result;
	}


	// # TRANSACTIONS --------------------------------------------------------------------------------------------------

	/**
	 * Initialize authorizing the transaction for the product & services type feed or return the 2co.js error.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $feed            The Feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The Form object currently being processed.
	 * @param array $entry           The Entry object currently being processed.
	 *
	 * @uses   GF_2Checkout::authorize_product()
	 * @uses   GF_2Checkout::get_2checkout_js_error()
	 * @uses   GF_2Checkout::initialize_api()
	 * @uses   GFPaymentAddOn::authorization_error()
	 *
	 * @return array
	 */
	public function authorize( $feed, $submission_data, $form, $entry ) {

		// Initialize API.
		if ( ! is_a( $this->initialize_api(), 'GF_2Checkout_API' ) ) {
			return $this->authorization_error( esc_html__( 'Unable to initialize API.', 'gravityforms2checkout' ) );
		}

		$validation_errors = $this->validate_submission( $submission_data, $form, $entry, $feed );

		// If there were validation errors, return them.
		if ( is_array( $validation_errors ) ) {
			return $validation_errors;
		}

		// Authorize product.
		return $this->authorize_product( $feed, $submission_data, $form, $entry );

	}

	/**
	 * Create the 2Checkout sale authorization and return any authorization errors which occur.
	 *
	 * @since  1.0
	 * @since  2.0 Use API version 6.
	 * @access public
	 *
	 * @param array $feed            The Feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The Form object currently being processed.
	 * @param array $entry           The Entry object currently being processed.
	 *
	 * @used-by GF_2Checkout::authorize()
	 * @uses    GFAddOn::get_field_map_fields()
	 * @uses    GFAddOn::get_field_value()
	 * @uses    GFAddOn::log_debug()
	 * @uses    GFAddOn::log_error()
	 * @uses    GF_2Checkout::prepare_sale()
	 * @uses    GF_2Checkout::validate_customer_info()
	 * @uses    GF_2Checkout_API::create_sale()
	 * @uses    GFPaymentAddOn::authorization_error()
	 *
	 * @return array
	 */
	public function authorize_product( $feed, $submission_data, $form, $entry ) {

		// Create order object.
		$order_object = $this->create_order_object( $submission_data, $form, $entry, $feed );
		$this->log_debug( __METHOD__ . '(): Order to be created; ' . print_r( $order_object, true ) );

		// Create & validate order.
		$order                   = $this->api->create_order( $order_object );
		$order_validation_errors = $this->validate_order_details( $order );
		if ( is_array( $order_validation_errors ) ) {
			return $order_validation_errors;
		}

		$this->log_debug( __METHOD__ . '(): Order created Successfully;  ' . print_r( $order, true ) );

		// Prepare authorization response.
		return array(
			'is_authorized'   => true,
			'transaction_id'  => $order['RefNo'],
			'payment_details' => $order['PaymentDetails'],
		);
	}

	/**
	 * Capture the 2Checkout charge which was authorized during validation.
	 *
	 * @since  1.0
	 * @since  2.0 Use API version 6.
	 * @access public
	 *
	 * @param array $auth            Contains the result of the authorize() function.
	 * @param array $feed            The Feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The Form object currently being processed.
	 * @param array $entry           The Entry object currently being processed.
	 *
	 * @uses   GF_2Checkout::initialize_api()
	 * @uses   GF_2Checkout_API::detail_sale()
	 * @uses   GFPaymentAddOn::authorization_error()
	 *
	 * @return array
	 */
	public function capture( $auth, $feed, $submission_data, $form, $entry ) {

		// Initialize API.
		if ( ! is_a( $this->initialize_api(), 'GF_2Checkout_API' ) ) {
			return $this->authorization_error( esc_html__( 'Unable to initialize API.', 'gravityforms2checkout' ) );
		}

		// Validate order was successfully authorized.
		if ( ! $auth['is_authorized'] || empty( $auth['transaction_id'] ) ) {
			return array(
				'is_success'    => false,
				'error_message' => $auth['error_message'],
			);
		}

		// Get order details.
		$order_details = $this->api->get_order( $auth['transaction_id'] );
		if ( is_wp_error( $order_details ) ) {
			$this->log_error( __METHOD__ . '(): Could not retrieve order; ' . $order_details->get_error_message() . ' (' . $order_details->get_error_code() . ')' );
			return array(
				'is_success'    => false,
				'error_message' => $order_details->get_error_message(),
				'orderDetails'  => array(),
			);
		}

		// Store order details for later use.
		$this->log_debug( 'Retrieved order: ' . print_r( $order_details, true ) );
		gform_update_meta( $entry['id'], '2checkout_payment_details', $auth['payment_details'] );
		gform_update_meta( $entry['id'], 'order_details', $order_details );
		gform_update_meta( $entry['id'], 'order_type', 'product' );

		// If order is completed already, return payment, otherwise return empty array to complete authorization.
		if ( $order_details['Status'] === 'AUTHRECEIVED' || $order_details['Status'] === 'PENDING' ) {
			return array();
		} elseif ( $order_details['Status'] === 'COMPLETE' ) {
			return array(
				'is_success'     => true,
				'transaction_id' => $auth['transaction_id'],
				'amount'         => $order_details['NetPrice'],
				'payment_method' => $order_details['PaymentDetails']['Type'],
				'orderDetails'   => $order_details,
			);
		}

		// If status is not pending or completed, then order is cancelled.
		return array(
			'is_success'    => false,
			'error_message' => __( 'Order was cancelled', 'gravityforms2checkout' ),
		);
	}

	/**
	 * Complete authorization (mark entry as pending and create note) for the pending orders.
	 *
	 * @since 2.0
	 *
	 * @param array $entry  Entry data.
	 * @param array $action Authorization data.
	 *
	 * @return bool
	 */
	public function complete_authorization( &$entry, $action ) {
		$order_details = gform_get_meta( $entry['id'], 'order_details' );
		if ( empty( $order_details ) ) {
			return false;
		}

		$this->update_entry_credit_card_details( $entry, $order_details );
		$this->set_pending_payment_status( $entry, $order_details );
		$this->maybe_redirect_to_3dsecure( $entry );

		$action['amount']         = $order_details['NetPrice'];
		$action['transaction_id'] = $order_details['RefNo'];

		return parent::complete_authorization( $entry, $action );
	}

	/**
	 * Subscribe the user to a 2Checkout recurring sale.
	 *
	 * @since  1.0
	 * @since  2.0 Use API version 6.
	 *
	 * @param array $feed            The Feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The Form object currently being processed.
	 * @param array $entry           The Entry object currently being processed.
	 *
	 * @return array
	 */
	public function subscribe( $feed, $submission_data, $form, $entry ) {

		// Initialize API.
		if ( ! is_a( $this->initialize_api(), 'GF_2Checkout_API' ) ) {
			return $this->authorization_error( esc_html__( 'Unable to initialize API.', 'gravityforms2checkout' ) );
		}

		// If there were validation errors, return them.
		$validation_errors = $this->validate_submission( $submission_data, $form, $entry, $feed );
		if ( is_array( $validation_errors ) ) {
			return $validation_errors;
		}

		// Create order object.
		$order_object = $this->create_order_object( $submission_data, $form, $entry, $feed );
		$this->log_debug( __METHOD__ . '(): Order to be created; ' . print_r( $order_object, true ) );

		// Create & validate order.
		$order                   = $this->api->create_order( $order_object );
		$order_validation_errors = $this->validate_order_details( $order );
		if ( is_array( $order_validation_errors ) ) {
			return $order_validation_errors;
		}

		$this->log_debug( __METHOD__ . '(): Subscription Order created; ' . print_r( $order, true ) );

		// Prepare authorization response.
		return array(
			'is_success'      => true,
			'subscription_id' => $order['RefNo'],
			'amount'          => $submission_data['payment_amount'],
			'payment_method'  => $order['PaymentDetails']['Type'],
			'payment_details' => $order['PaymentDetails'],
			'order_details'   => $order,
		);

	}

	/**
	 * Process subscription.
	 *
	 * @since  2.0
	 *
	 * @param array $authorization   Contains the result of the subscribe() function.
	 * @param array $feed            The feed object currently being processed.
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The form object currently being processed.
	 * @param array $entry           The entry object currently being processed.
	 *
	 * @return array The entry object.
	 */
	public function process_subscription( $authorization, $feed, $submission_data, $form, $entry ) {

		// 2Checkout IPN always return a subscription event even if it was a product, mark this as a subscription entry on our side to be able to distinguish when processing IPN.
		gform_update_meta( $entry['id'], 'order_type', 'subscription' );

		// Get order From 2Checkout.
		$order_details = $this->api->get_order( $authorization['subscription']['subscription_id'] );

		if ( ! is_wp_error( $order_details ) ) {
			// Save order details for later use.
			$this->log_debug( 'Retrieved subscription order:' . print_r( $order_details, true ) );
			gform_update_meta( $entry['id'], '2checkout_payment_details', $authorization['subscription']['payment_details'] );
			gform_update_meta( $entry['id'], 'order_details', $order_details );

			$this->update_entry_credit_card_details( $entry, $order_details );
			$this->set_pending_payment_status( $entry, $order_details );
			$this->maybe_redirect_to_3dsecure( $entry );
		} else {
			$this->log_error( __METHOD__ . '(): Could not retrieve order; ' . $order_details->get_error_message() . ' (' . $order_details->get_error_code() . ')' );
			$authorization['subscription']['is_success']    = false;
			$authorization['subscription']['error_message'] = esc_html__( 'Could not retrieve subscription from 2Checkout.', 'gravityforms2checkout' );
		}

		return parent::process_subscription( $authorization, $feed, $submission_data, $form, $entry );
	}


	/**
	 * Updates entry values for 2Checkout field with CC details returned from API.
	 *
	 * @since 2.0
	 *
	 * @param array $entry          Current entry object being processed.
	 * @param array $order_details  Order details returned from 2Checkout API.
	 *
	 * @return void
	 */
	private function update_entry_credit_card_details( &$entry, $order_details ) {
		$form  = GFAPI::get_form( $entry['form_id'] );
		$field = $this->get_2checkout_card_field( $form );
		// Update entry with credit card data.
		$entry[ $field['id'] . '.1' ] = empty( $order_details['PaymentDetails']['PaymentMethod']['LastDigits'] ) ? '' : 'XXXX XXXXX XXXXX ' . $order_details['PaymentDetails']['PaymentMethod']['LastDigits'];
		$entry[ $field['id'] . '.4' ] = empty( $order_details['PaymentDetails']['PaymentMethod']['CardType'] ) ? '' : $order_details['PaymentDetails']['PaymentMethod']['CardType'];
		GFAPI::update_entry( $entry );
	}

	/**
	 * Check if the returned order array contains a request to start the 3DSecure flow, redirect the user to the url if so.
	 *
	 * @since 2.0
	 *
	 * @param array $entry Current entry being processed.
	 */
	private function maybe_redirect_to_3dsecure( $entry ) {
		$payment_details = gform_get_meta( $entry['id'], '2checkout_payment_details' );
		$this->log_debug( '--Returned Payment Details---' );
		$this->log_debug( print_r( $payment_details, true ) );

		if ( ! is_array( $payment_details ) || empty( $payment_details['PaymentMethod']['Authorize3DS']['Href'] ) ) {
			return;
		}

		gform_update_meta( $entry['id'], '3dsecure_success_nonce', wp_hash( $this->get_success_nonce() ) );

		$redirect_url = add_query_arg(
			rgar( $payment_details['PaymentMethod']['Authorize3DS'], 'Params', array() ),
			$payment_details['PaymentMethod']['Authorize3DS']['Href']
		);
		$this->log_debug( '3DS Redirect URL: ' . $redirect_url );

		header( 'location: ' . $redirect_url );
		exit();
	}

	/**
	 * Sets the entry payment status as pending.
	 *
	 * @since 2.0
	 *
	 * @param array $entry         Current entry object being processed.
	 * @param array $order_detail  Order details array returned from 2Checkout API.
	 */
	private function set_pending_payment_status( $entry, $order_detail ) {
		GFAPI::update_entry_property( $entry['id'], 'payment_status', 'Pending' );
		GFAPI::update_entry_property( $entry['id'], 'payment_method', '2Checkout' );
		GFAPI::update_entry_property( $entry['id'], 'transaction_id', $order_detail['RefNo'] );
	}

	// # FORM SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Add supported notification events.
	 *
	 * @since 2.0
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return array|false The supported notification events. False if feed cannot be found within $form.
	 */
	public function supported_notification_events( $form ) {

		// If this form does not have a 2Checkout feed, return false.
		if ( ! $this->has_feed( $form['id'] ) ) {
			return false;
		}

		// Return 2Checkout notification events.
		return array(
			'complete_payment'          => esc_html__( 'Payment Completed', 'gravityforms2checkout' ),
			'refund_payment'            => esc_html__( 'Payment Refunded', 'gravityforms2checkout' ),
			'fail_payment'              => esc_html__( 'Payment Failed', 'gravityforms2checkout' ),
			'create_subscription'       => esc_html__( 'Subscription Created', 'gravityforms2checkout' ),
			'add_subscription_payment'  => esc_html__( 'Subscription Payment Added', 'gravityforms2checkout' ),
			'fail_subscription_payment' => esc_html__( 'Subscription Payment Failed', 'gravityforms2checkout' ),
		);

	}

	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Initializes 2Checkout API if credentials are valid.
	 *
	 * @since  1.0
	 * @since  2.0    Validate credentials by trying to generate a session id.
	 * @since  2.0    api_mode is always set to production as it does not affect initializing the API.
	 * @access public
	 *
	 * @param string $api_mode 2Checkout API mode (production or sandbox).
	 *
	 * @uses   GFAddOn::get_current_settings()
	 * @uses   GFAddOn::get_plugin_settings()
	 * @uses   GFAddOn::get_slug()
	 * @uses   GFAddOn::is_plugin_settings()
	 * @uses   GFAddOn::log_debug()
	 * @uses   GFAddOn::log_error()
	 * @uses   GF_2Checkout_API::detail_company_info()
	 *
	 * @return GF_2Checkout_API|false An API wrapper instance or false.
	 */
	public function initialize_api( $api_mode = '' ) {

		// Get the plugin settings.
		$settings = $this->is_plugin_settings( $this->get_slug() ) ? $this->get_current_settings() : $this->get_plugin_settings();

		// If API is already initialized, return.
		if ( ! is_null( $this->api ) ) {
			return $this->api;
		}

		// Load the API library.
		if ( ! class_exists( 'GF_2Checkout_API' ) ) {
			require_once( 'includes/class-gf-2checkout-api.php' );
		}

		// If API credentials are empty, return.
		if ( ! rgars( $settings, 'merchant_code' ) || ! rgars( $settings, 'secret_key' ) || ! rgars( $settings, 'IPN_enabled' ) ) {
			return false;
		}



		// Initialize a new 2Checkout API object.
		$twocheckout = new GF_2Checkout_API( $api_mode, $settings['merchant_code'], $settings['secret_key'] );

		if ( ! empty( $twocheckout->generate_session_id() ) ) {
			// Assign API instance to class.
			$this->api = $twocheckout;

			// Remove upgrade notice now that the settings have been set.
			if ( get_option( 'gf_2checkout_upgrade_notice' ) ) {
				update_option( 'gf_2checkout_upgrade_notice', false );
			}

			// Update the reauthentication version.
			$settings['reauth_version'] = self::LAST_REAUTHENTICATION_VERSION;
			$this->update_plugin_settings( $settings );

			return $this->api;
		}

		// Log that authentication test failed.
		$this->log_error( __METHOD__ . '(): API credentials are invalid' );

		return false;

	}

	/**
	 * Response from 2co.js is posted to the server as '2checkout_response'.
	 *
	 * @since   1.0
	 * @access  public
	 *
	 * @used-by GF_2Checkout::add_2checkout_inputs()
	 * @uses    GFAddOn::maybe_decode_json()
	 *
	 * @return array|null
	 */
	public function get_2checkout_js_response() {

		// Get response.
		$response = rgpost( '2checkout_response' );

		return $this->maybe_decode_json( $response );

	}

	/**
	 * Validate customer information.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $sale      The Sale object currently being processed.
	 * @param array $form      The Form object currently being processed.
	 * @param array $field_map Billing Information field map.
	 *
	 * @uses   GF_2Checkout::validate_billing_address()
	 * @uses   GFFormsModel::get_field()
	 * @uses   GFPaymentAddOn::authorization_error()
	 *
	 * @return array|bool
	 */
	public function validate_customer_info( $sale = array(), $form = array(), $field_map = array() ) {

		// Validate name.
		if ( ! rgars( $sale, 'billingAddr/name' ) ) {
			return $this->authorization_error( esc_html__( "You must provide the cardholder's name.", 'gravityforms2checkout' ) );
		}

		// Validate email address.
		if ( ! rgars( $sale, 'billingAddr/email' ) ) {
			return $this->authorization_error( esc_html__( 'You must provide your email address.', 'gravityforms2checkout' ) );
		}

		// Validate phone number.
		if ( ! rgars( $sale, 'billingAddr/phoneNumber' ) ) {
			return $this->authorization_error( esc_html__( 'You must provide your phone number.', 'gravityforms2checkout' ) );
		}

		// Validate billing address.
		if ( ! $this->validate_billing_address( $sale['billingAddr'], GFFormsModel::get_field( $form, $field_map['country'] ) ) ) {
			return $this->authorization_error( esc_html__( 'You must provide a valid billing address.', 'gravityforms2checkout' ) );
		}

		return true;

	}

	/**
	 * Checks if current form has a 2checkout field.
	 *
	 * @since 2.0
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool
	 */
	public function has_2checkout_card_field( $form = null ) {
		return false !== $this->get_2checkout_card_field( $form );
	}

	/**
	 * Retrieves 2checkout field from current form.
	 *
	 * @since 2.0
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool|GF_Field_2Checkout_CreditCard he 2Checkout field object, if found. Otherwise, false.
	 */
	public function get_2checkout_card_field( $form = null ) {

		if ( is_null( $form ) ) {
			$form = $this->get_current_form();
		}

		$fields = GFAPI::get_fields_by_type( $form, array( '2checkout_creditcard' ) );

		return empty( $fields ) ? false : $fields[0];
	}

	/**
	 * Validates submitted data contains required values to create a 2Checkout sale.
	 *
	 * @since 2.0
	 *
	 * @param array $submission_data      The submitted data.
	 * @param array $form                 The Form object currently being processed.
	 * @param array $entry                The Entry object currently being processed.
	 * @param array $feed                 The Feed object currently being processed.
	 *
	 * @return bool|array Authorization error array or true if data is valid.
	 */
	public function validate_submission( $submission_data, $form, $entry, $feed ) {

		// Validate name.
		$ccField = $this->get_2checkout_card_field( $form );
		$name    = empty( $entry[ $ccField->id . '.5' ] ) ? array() : explode( ' ', $entry[ $ccField->id . '.5' ] );
		if ( count( $name ) < 2 ) {
			return $this->authorization_error( esc_html__( "You must provide the cardholder's full name.", 'gravityforms2checkout' ) );
		}

		// Get field mapping information and validate submission data.
		$field_map = $this->get_field_map_fields( $feed, 'billingInformation' );

		// Validate Address.
		if ( empty( $this->get_field_value( $form, $entry, $field_map['address'] ) )
			|| empty( $this->get_field_value( $form, $entry, $field_map['city'] ) )
			|| empty( $this->get_field_value( $form, $entry, $field_map['state'] ) )
			|| empty( $this->get_field_value( $form, $entry, $field_map['zip'] ) )
			|| empty( $this->get_field_value( $form, $entry, $field_map['country'] ) )
		) {
			return $this->authorization_error( esc_html__( 'You must provide a valid billing address.', 'gravityforms2checkout' ) );
		}

		// Validate email address.
		if ( empty( $this->get_field_value( $form, $entry, $field_map['email'] ) ) ) {
			return $this->authorization_error( esc_html__( 'You must provide your email address.', 'gravityforms2checkout' ) );
		}

		// Validate phone number.
		if ( empty( $this->get_field_value( $form, $entry, $field_map['phone'] ) ) ) {
			return $this->authorization_error( esc_html__( 'You must provide your phone number.', 'gravityforms2checkout' ) );
		}

		return true;
	}


	/**
	 * Creates a 2Checkout Order object.
	 *
	 * @since 2.0
	 *
	 * @param array $submission_data   The customer and transaction data.
	 *                                 or recurring billing information if this is a subscription order.
	 * @param array $form              The Form object currently being processed.
	 * @param array $entry             The Entry object currently being processed.
	 * @param array $feed              The Feed object currently being processed.
	 *
	 * @return Object Created order object.
	 */
	public function create_order_object( $submission_data, $form, $entry, $feed ) {

		// Create order object.
		$order            = new stdClass();
		$order->Currency  = GFCommon::get_currency();
		$order->LocalTime = gmdate( 'Y-m-d H:i:s' );
		$order->Items     = array();

		// Add line items.
		if ( $feed['meta']['transactionType'] != 'subscription' ) {
			$order = $this->prepare_single_payment( $order, $submission_data, $feed );
		} else {
			$order = $this->prepare_subscription( $order, $submission_data, $feed );
		}

		// Add Name and billing information.
		$order = $this->prepare_billing( $order, $submission_data, $entry, $feed, $form );

		// Add payment details.
		$order = $this->prepare_payment_details( $order, $feed );

		return $order;

	}

	/**
	 * Validates order object was created successfully.
	 *
	 * @since 2.0
	 *
	 * @param object|WP_Error $order Order object returned from API or WP_Error.
	 *
	 * @return array|bool Authorization error array or true.
	 */
	public function validate_order_details( $order ) {

		if ( is_wp_error( $order ) ) {

			$this->log_error( __METHOD__ . '(): Could not create order; ' . $order->get_error_message() . ' (' . $order->get_error_code() . ')' );
			return $this->authorization_error( $order->get_error_message() );

		} elseif ( ! empty( $order['Errors'] ) && is_array( $order['Errors'] ) ) {

			$error = array_pop( $order['Errors'] );
			$this->log_error( __METHOD__ . '(): Order created with an error; ' . $error );
			return $this->authorization_error( $error );

		}

		return true;
	}

	/**
	 * Adds line items and discounts to single payment order object.
	 *
	 * @since 2.0
	 *
	 * @param object $order           2Checkout order object.
	 * @param array  $submission_data Submitted form data.
	 * @param array  $feed            Current feed object being processed.
	 *
	 * @return object 2Checkout Order object.
	 */
	private function prepare_single_payment( $order, $submission_data, $feed ) {

		// Add line items.
		foreach ( $submission_data['line_items'] as $line_item ) {
			$item                = new stdClass();
			$item->Code          = null;
			$item->Quantity      = $line_item['quantity'];
			$item->PurchaseType  = 'PRODUCT';
			$item->Tangible      = false;
			$item->IsDynamic     = true;
			$item->Price         = new stdClass();
			$item->Price->Amount = $line_item['unit_price'];
			$item->Price->Type   = 'CUSTOM';
			$item->Name          = $line_item['name'];
			$item->Description   = $line_item['description'];
			$order->Items[]      = $item;
		}

		// Add discounts.
		if ( is_array( $submission_data['discounts'] ) ) {
			foreach ( $submission_data['discounts'] as $discount ) {
				$discount_item                = new stdClass();
				$discount_item->Name          = $discount['name'];
				$discount_item->PurchaseType  = 'COUPON';
				$discount_item->IsDynamic     = true;
				$discount_item->Quantity      = (int) $discount['quantity'];
				$discount_item->Price         = new stdClass();
				$discount_item->Price->Amount = (float) ( $discount['unit_price'] * -1 );
				$order->Items[]               = $discount_item;
			}
		}

		return $order;
	}

	/**
	 * Adds subscription details to subscription order object.
	 *
	 * @since 2.0
	 *
	 * @param object $order           2Checkout order object.
	 * @param array  $submission_data Submitted form data.
	 * @param array  $feed            Current feed object being processed.
	 *
	 * @return object 2Checkout Order object.
	 */
	private function prepare_subscription( $order, $submission_data, $feed ) {

		// Add subscription details.
		$item                                = new stdClass();
		$item->Name                          = empty( rgars( $feed, 'meta/subscription_name' ) ) ? rgars( $feed, 'meta/feedName' ) : rgars( $feed, 'meta/subscription_name' );
		$item->Code                          = null;
		$item->Quantity                      = 1;
		$item->PurchaseType                  = 'PRODUCT';
		$item->Tangible                      = false;
		$item->IsDynamic                     = true;
		$item->Price                         = new stdClass();
		$item->Price->Amount                 = ( (float) $submission_data['payment_amount'] );
		$item->Price->Type                   = 'CUSTOM';
		$item->RecurringOptions              = new stdClass();
		$item->RecurringOptions->CycleLength = (int) $feed['meta']['billingCycle_length'];
		$item->RecurringOptions->CycleUnit   = ucwords( $feed['meta']['billingCycle_unit'] );
		$item->RecurringOptions->CycleAmount = (float) $submission_data['payment_amount'];

		if ( $feed['meta']['recurringTimes'] ) {
			$item->RecurringOptions->ContractLength = (int) $feed['meta']['recurringTimes'];
			$item->RecurringOptions->ContractUnit   = ucwords( $feed['meta']['billingCycle_unit'] );
		} else {
			$item->RecurringOptions->ContractLength = 1;
			$item->RecurringOptions->ContractUnit   = 'FOREVER';
		}

		$order->Items[] = $item;

		// Add setup fee as a new order item if enabled.
		if ( rgar( $feed['meta'], 'setupFee_enabled' ) ) {
			$setup_item                = new stdClass();
			$setup_item->Code          = null;
			$setup_item->Quantity      = 1;
			$setup_item->PurchaseType  = 'TAX';
			$setup_item->Tangible      = false;
			$setup_item->IsDynamic     = true;
			$setup_item->Price         = new stdClass();
			$setup_item->Price->Amount = $submission_data['setup_fee'];
			$setup_item->Price->Type   = 'CUSTOM';
			$setup_item->Name          = esc_html__( 'Setup fee', 'gravityforms2checkout' );
			$order->Items[]            = $setup_item;
		}

		return $order;
	}

	/**
	 * Adds billing and delivery information to order object.
	 *
	 * @since 2.0
	 *
	 * @param object $order           2Checkout order object.
	 * @param array  $submission_data Submitted form data.
	 * @param array  $entry           The Entry object currently being processed.
	 * @param array  $feed            Current feed object being processed.
	 * @param array  $form            Current form object being processed.
	 *
	 * @return object 2Checkout Order object.
	 */
	private function prepare_billing( $order, $submission_data, $entry, $feed, $form ) {

		// Get field mapping information and validate submission data.
		$field_map = $this->get_field_map_fields( $feed, 'billingInformation' );
		$ccField   = $this->get_2checkout_card_field( $form );
		$name      = explode( ' ', $entry[ $ccField->id . '.5' ] );

		// Add billing information.
		$order->BillingDetails              = new stdClass();
		$order->BillingDetails->Address1    = $this->get_field_value( $form, $entry, $field_map['address'] );
		$order->BillingDetails->Address2    = $this->get_field_value( $form, $entry, $field_map['address2'] );
		$order->BillingDetails->City        = $this->get_field_value( $form, $entry, $field_map['city'] );
		$order->BillingDetails->State       = $this->get_field_value( $form, $entry, $field_map['state'] );
		$order->BillingDetails->CountryCode = GFCommon::get_country_code( $this->get_field_value( $form, $entry, $field_map['country'] ) );
		$order->BillingDetails->Phone       = $this->get_field_value( $form, $entry, $field_map['phone'] );
		$order->BillingDetails->Email       = $this->get_field_value( $form, $entry, $field_map['email'] );
		$order->BillingDetails->FirstName   = $name[0];
		$order->BillingDetails->LastName    = $name[1];
		$order->BillingDetails->Zip         = $this->get_field_value( $form, $entry, $field_map['zip'] );

		// Add delivery information ( same as billing ).
		$order->DeliveryDetails = $order->BillingDetails;

		return $order;
	}

	/**
	 * Adds payment information to order object.
	 *
	 * @since 2.0
	 *
	 * @param object $order 2Checkout order object.
	 * @param array  $feed  Current feed object being processed.
	 *
	 * @return object 2Checkout Order object.
	 */
	private function prepare_payment_details( $order, $feed ) {

		$settings = $this->get_plugin_settings();
		$api_mode = $settings['apiMode'];
		$form     = $this->get_current_form();

		// Add payment details token.
		$order->PaymentDetails                                    = new stdClass();
		$order->PaymentDetails->Type                              = $api_mode === 'sandbox' ? 'TEST' : 'EES_TOKEN_PAYMENT';
		$order->PaymentDetails->Currency                          = GFCommon::get_currency();
		$order->PaymentDetails->PaymentMethod                     = new stdClass();
		$order->PaymentDetails->PaymentMethod->EesToken           = $this->get_2checkout_js_response();
		$order->PaymentDetails->PaymentMethod->Vendor3DSReturnURL = $this->get_success_url( rgar( $form, 'id' ), rgar( $feed, 'id' ) );
		$order->PaymentDetails->PaymentMethod->Vendor3DSCancelURL = $this->get_cancel_url( rgar( $form, 'id' ), rgar( $feed, 'id' ) );

		if ( $feed['meta']['transactionType'] === 'subscription' ) {
			$order->PaymentDetails->PaymentMethod->RecurringEnabled = true;
		}

		return $order;
	}

	/**
	 * Define the markup to be displayed for the IPN section description.
	 *
	 * @since 2.0
	 *
	 * @return string HTML formatted IPN description.
	 */
	public function get_ipn_section_description() {
		ob_start();
		?>
			<p><a href="javascript:void(0);"
			onclick="tb_show('IPN Instructions', '#TB_inline?width=500&inlineId=ipn-instructions', '');" onkeypress="tb_show('IPN Instructions', '#TB_inline?width=500&inlineId=ipn-instructions', '');"><?php esc_html_e( 'View Instructions', 'gravityforms2checkout' ); ?></a></p>

		<div id="ipn-instructions" style="display:none;">
			<ol class="ipn-instructions">
				<li>
					<?php esc_html_e( 'Click the following link and log in to access your 2Checkout IPN management page:', 'gravityforms2checkout' ); ?>
					<br/>
					<a href="https://secure.avangate.com/cpanel/index.php" target="_blank">https://secure.avangate.com/cpanel/index.php</a>
				</li>
				<li><?php esc_html_e( 'Navigate to Dashboard → Integrations → Webhooks and API.', 'gravityforms2checkout' ); ?></li>
				<li><?php esc_html_e( 'Click on the IPN Settings tab.', 'gravityforms2checkout' ); ?></li>
				<li>
					<?php esc_html_e( 'Click on the Add IPN URL button and add the following IPN URL.', 'gravityforms2checkout' ); ?>
					<code><?php echo esc_url( home_url( '/', 'https' ) . '?callback=' . $this->_slug ); ?></code>
				</li>
				<li><?php esc_html_e( 'Select SHA3 from the list of available hashing algorithms.', 'gravityforms2checkout' ); ?></li>
				<li><?php esc_html_e( 'Click Add IPN button.', 'gravityforms2checkout' ); ?></li>
				<li><?php esc_html_e( 'Scroll down to the triggers section and make sure Completed orders, Cancelled orders and Reversed and refund orders are checked.', 'gravityforms2checkout' ); ?></li>
				<li><?php esc_html_e( 'Scroll down to Response tags and click select all.', 'gravityforms2checkout' ); ?></li>
				<li><?php esc_html_e( 'Update the settings.', 'gravityforms2checkout' ); ?></li>
			</ol>

		</div>

		<?php
		return ob_get_clean();
	}


	/**
	 * Get success URL for 3DSecure flow.
	 *
	 * @since 2.0
	 *
	 * @param int $form_id Form ID.
	 * @param int $feed_id Feed ID.
	 *
	 * @return string
	 */
	private function get_success_url( $form_id, $feed_id ) {
		/**
		 * Filters 2Checkout success URL, which is the URL that users will be sent to after completing 3DSecure confirmation successfully.
		 *
		 * @since 2.0
		 *
		 * @param string $url     The URL to be filtered.
		 * @param int    $form_id The ID of the form being submitted.
		 * @param int    $feed_id The ID of the feed being processed.
		 */
		return apply_filters(
			'gform_2checkout_success_url',
			add_query_arg( 'gf_2checkout_3ds_success', $this->get_success_nonce(), $this->get_page_url() ),
			$form_id,
			$feed_id
		);
	}

	/**
	 * Get cancel URL for 2Checkout 3DSecure flow.
	 *
	 * @since 2.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return string
	 */
	private function get_cancel_url( $form_id ) {
		/**
		 * Filters 2Checkout cancel URL, which is the URL that users will be sent to after cancelling/failing 3DSecure confirmation.
		 *
		 * @since 2.0
		 *
		 * @param string $url     The URL to be filtered.
		 * @param int    $form_id The ID of the form being submitted.
		 */
		return apply_filters( 'gform_2checkout_cancel_url', $this->get_page_url(), $form_id );
	}

	/**
	 * Build the URL of the current page.
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	private function get_page_url() {
		$page_url = GFCommon::is_ssl() ? 'https://' : 'http://';

		/**
		 * Set the 2Checkout URL port if it's not 80.
		 *
		 * @since 2.0
		 *
		 * @param string Default server port.
		 */
		$server_port = apply_filters( 'gform_2checkout_url_port', $_SERVER['SERVER_PORT'] );

		if ( $server_port != '80' && $server_port != '443' ) {
			$page_url .= $_SERVER['SERVER_NAME'] . ':' . $server_port . $_SERVER['REQUEST_URI'];
		} else {
			$page_url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}

		return $page_url;
	}

	/**
	 * Generates a cryptographic token that is used later to verify the redirected user after successfully passing 3DS challenge.
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	private function get_success_nonce() {
		if ( ! $this->success_nonce ) {
			$this->success_nonce = wp_generate_password( 12, false );
		}

		return $this->success_nonce;
	}

	/**
	 * Adds a flag that connection settings needs to be updated, this flag will be used later to decide if an admin notice should be dispayed or not.
	 *
	 * @since 2.0
	 *
	 * @param string $previous_version The version of the already installed  add-on before upgrade.
	 */
	public function upgrade( $previous_version ) {
		if ( ! empty( $previous_version ) && version_compare( $previous_version, '2.0-beta-1', '<' ) && ! $this->initialize_api() ) {
			update_option( 'gf_2checkout_upgrade_notice', true );
		}
	}

	/**
	 * Displays an admin notice to inform the user about required settings changes if add-on legacy API version is installed.
	 *
	 * @since 2.0
	 */
	public function add_upgrade_connection_notice() {
		if ( ! $this->requires_api_reauthentication() ) {
			return;
		}

		$message = sprintf(
			/* translators: 1: open <a> tag, 2: close <a> tag */
			esc_html__( 'Connection requirements for 2Checkout have changed with this latest release. %1$sPlease update your plugin settings%2$s to reconnect your site to 2Checkout.', 'gravityforms2checkout' ),
			'<a href="' . esc_url( $this->get_plugin_settings_url() ) . '">',
			'</a>'
		);

		echo sprintf( '<div class="notice notice-error gf-notice"><p>%s</p></div>', $message );
	}

	/**
	 * Checks if a message should be displayed to remind users to update their IPN hash algorithm.
	 *
	 * @since 2.2
	 *
	 * @return void
	 */
	public function maybe_display_ipn_hash_upgrade_notice() {

		// If add-on is a fresh install, means probably there are no IPN created yet, no need to show anything, since the new IPN will be created with the new hash algorithm.
		if ( $this->initialize_api() === false || empty( $this->get_plugin_settings() ) ) {
			return;
		}

		$ipn_received_hash_algorithm = get_option( 'gf_2checkout_ipn_received_hash_algorithm' );
		if ( empty( $ipn_received_hash_algorithm ) || $ipn_received_hash_algorithm === self::HASHING_ALGORITHM ) {
			return;
		}

		$message = sprintf(
			/* translators: 1.notice about failed calls, 2.open anchor tag, 3.close anchor tag */
			esc_html__( 'To ensure uninterrupted payment processing through your Gravity Forms 2Checkout Add-On, please update your 2Checkout IPN settings to the new SHA3 algorithm. Full details and steps are available at %1$1s2Checkout API Migration Guide%2$2s.', 'gravityforms2checkout' ),
			'<a href="https://verifone.cloud/docs/2checkout/API-Integration/01Start-using-the-2Checkout-API/2Checkout-API-general-information/Migration_guide_SHA2_SHA3/Webhooks_upgrade_to_the_SHA_algorithm" target="_blank">',
			'</a>'
		);

		\GFCommon::add_dismissible_message( $message, 'gravityforms2checkout_hashing_algo_upgrade', 'error', $this->_capabilities_form_settings, true, 'site-wide' );

	}

	/**
	 * Check whether this add-on needs to be reauthenticated with the 2Checkout API.
	 *
	 * @since 2.0.2
	 *
	 * @return bool
	 */
	private function requires_api_reauthentication() {
		$settings = $this->get_plugin_settings();

		return ! empty( $settings ) && version_compare( rgar( $settings, 'reauth_version' ), self::LAST_REAUTHENTICATION_VERSION, '<' );
	}

	// # IPN Notifications  --------------------------------------------------------------------------------------------


	/**
	 * If the 2Checkout IPN belongs to a valid entry process the raw response into a standard Gravity Forms $action.
	 *
	 * @since 2.0
	 *
	 * @return array|bool Return a valid GF $action or bool if the webhook can't be processed or no action needs to be done.
	 */
	public function callback() {
		if ( ! $this->is_gravityforms_supported() ) {
			return false;
		}

		// Log all request data.
		$this->log_debug( '--- Start logging IPN request data --- ' );
		$this->log_debug( print_r( $_REQUEST, true ) );
		// Maybe there is a difference.
		$this->log_debug( '--- Start logging input stream data --- ' );
		$request_body = trim( file_get_contents( 'php://input' ) );
		$this->log_debug( print_r( $request_body, true ) );

		// Hash will be unset later so cache it here.
		$hash3_256 = sanitize_text_field( rgar( $_POST, 'SIGNATURE_SHA3_256' ) );
		$hash      = sanitize_text_field( rgar( $_POST, 'HASH' ) );

		$order_reference_number = sanitize_text_field( $_POST['REFNO'] );
		$ipn_date               = sanitize_text_field( $_POST['IPN_DATE'] );
		$order_total            = sanitize_text_field( $_POST['IPN_TOTALGENERAL'] );
		$order_status           = sanitize_text_field( $_POST['ORDERSTATUS'] );

		if ( ! $this->verify_ipn() ) {
			$this->log_error( __METHOD__ . '(): Invalid IPN HASH, so it was not created by Gravity Forms. Aborting.' );
			return false;
		}

		$entry_id = $this->get_entry_by_transaction_id( $order_reference_number );
		if ( ! $entry_id ) {
			$this->log_error( __METHOD__ . '(): Could not find entry. Aborting.' );
			return false;
		}

		$this->output_ipn_read_receipt( $_POST );

		$entry  = GFAPI::get_entry( $entry_id );
		$action = array(
			'id'             => empty( $hash3_256 ) ? $hash : $hash3_256,
			'entry_id'       => $entry_id,
			'transaction_id' => $order_reference_number,
			'amount'         => $order_total,
		);

		// Prevent already processed entries from being processed again.
		if ( gform_get_meta( $entry_id, 'IPN_' . $order_status . '_PROCESSED' ) === '1' ) {
			$this->log_debug( 'IPN ALREADY PROCESSED, ABORTING' );
			return false;
		} else {
			$this->log_debug( __METHOD__ . '(): IPN request received. Starting to process => ' . print_r( $_POST, true ) );
		}

		// Mark entry as processed for this particular event.
		gform_update_meta( $entry_id, 'IPN_' . $order_status . '_PROCESSED', '1' );

		// If this is a subscription event.
		$order_type = gform_get_meta( $entry_id, 'order_type' );
		if ( $order_type === 'subscription' ) {
			$action['subscription_id'] = $order_reference_number;
			return $this->process_subscription_ipn( $entry, $action );
		}

		return $this->process_product_ipn( $entry, $action );
	}

	/**
	 * Processes a subscription IPN.
	 *
	 * @since 2.0
	 *
	 * @param array $entry  The current entry object being processed.
	 * @param array $action The action array.
	 *
	 * @return bool|array Action array or boolean if no need to process an action.
	 */
	private function process_subscription_ipn( &$entry, $action ) {
		$order_status = sanitize_text_field( $_POST['ORDERSTATUS'] );

		switch ( $order_status ) {
			case 'COMPLETE':
				$action['type'] = 'add_subscription_payment';
				break;
			case 'INVALID':
			case 'SUSPECT':
			case 'CANCELED':
				$action['type'] = 'fail_subscription_payment';
				break;
			case 'REFUND':
			case 'REVERSED':
				$action['type'] = 'refund_payment';
				break;
			default:
				return false;
		}

		return $action;
	}

	/**
	 * Processes a product IPN.
	 *
	 * @since 2.0
	 *
	 * @param array $entry  The current entry object being processed.
	 * @param array $action The action array.
	 *
	 * @return bool|array Action array or boolean if no need to process an action.
	 */
	private function process_product_ipn( &$entry, $action ) {
		$order_status = sanitize_text_field( $_POST['ORDERSTATUS'] );
		switch ( $order_status ) {
			case 'COMPLETE':
				$action['type'] = 'complete_payment';
				break;
			case 'INVALID':
			case 'SUSPECT':
				$action['type'] = 'fail_payment';
				break;
			case 'CANCELED':
				$action['type'] = 'void_authorization';
				break;
			case 'REFUND':
			case 'REVERSED':
				$action['type'] = 'refund_payment';
				break;
			default:
				return false;
		}

		return $action;
	}

	/**
	 * Verifies that the IPN notification is a valid IPN request generated from 2Checkout.
	 *
	 * @since 2.0
	 *
	 * @return bool
	 */
	private function verify_ipn() {
		$hash3_256 = sanitize_text_field( $_POST['SIGNATURE_SHA3_256'] );
		$hash      = sanitize_text_field( $_POST['HASH'] );
		// Unset all signature values as they are not used in generating the string that is hashed.
		unset( $_POST['SIGNATURE_SHA3_256'] );
		unset( $_POST['SIGNATURE_SHA2_256'] );
		unset( $_POST['HASH'] );

		$string = '';
		foreach ( $_POST as $key => $value ) {
			$string .= $this->expand_array( (array) $value );
		}


		$this->log_debug( 'Hash string: ' . $string );
		$algorithm = ! empty( $hash3_256 ) ? self::HASHING_ALGORITHM : 'md5';
		update_option( 'gf_2checkout_ipn_received_hash_algorithm', $algorithm );
		$signature = hash_hmac( $algorithm, $string, $this->get_plugin_setting( 'secret_key' ) );
		$this->log_debug( 'Signature: ' . $signature );

		$received_signature = $hash3_256;
		// For backwards compatibility.
		if ( $algorithm === 'md5' ) {
			$received_signature = $hash;
		} else {
			\GFCommon::remove_dismissible_message( 'gravityforms2checkout_hashing_algo_upgrade' );
		}

		return $signature === $received_signature;
	}

	/**
	 * Converts IPN request array into a string formatted to generate the IPN signature.
	 *
	 * @since 2.0
	 *
	 *  @param array $array An array to be converted into a string.
	 *
	 * @return string
	 */
	private function expand_array( $array ) {
		$retval = '';
		foreach ( $array as $i => $value ) {
			if ( is_array( $value ) ) {
				$retval .= $this->expand_array( $value );
			} else {
				$size    = strlen( $value );
				$retval .= $size . $value;
			}
		}

		return $retval;
	}

	/**
	 * Output IPN read receipt string.
	 *
	 * @since 2.0
	 *
	 * @param array $ipn_request The IPN request body.
	 */
	private function output_ipn_read_receipt( $ipn_request ) {
		$base_string = $this->expand_array(
			array(
				$ipn_request['IPN_PID'][0],
				$ipn_request['IPN_PNAME'][0],
				$ipn_request['IPN_DATE'],
				$ipn_request['IPN_DATE'],
			)
		);

		$this->log_debug( 'Read receipt base string: ' . $base_string );
		$hash = hash_hmac( self::HASHING_ALGORITHM, $base_string, $this->get_plugin_setting( 'secret_key' ) );

		http_response_code( 200 );
		echo '<sig algo="' . self::HASHING_ALGORITHM . '" date="' . $ipn_request['IPN_DATE'] . '">' . $hash . '</sig>' . PHP_EOL;
	}

	// # Deprecated METHODS --------------------------------------------------------------------------------------------

	/**
	 * Add required 2Checkout inputs to form.
	 *
	 * @since      1.0
	 * @deprecated 2.0 No longer used by internal code.
	 * @access  public
	 *
	 * @param string $content  The field content to be filtered.
	 * @param object $field    The field that this input tag applies to.
	 * @param string $value    The default/initial value that the field should be pre-populated with.
	 * @param int    $entry_id When executed from the entry detail screen, $entry_id will be populated with the Entry ID.
	 * @param int    $form_id  The current Form ID.
	 *
	 * @used-by GF_2Checkout::init()
	 * @uses    GFFeedAddOn::has_feed()
	 * @uses    GF_2Checkout::get_2checkout_js_response()
	 *
	 * @return string
	 */
	public function add_2checkout_inputs( $content, $field, $value, $entry_id, $form_id ) {

		// If this form does not have a 2Checkout feed or if this is not a credit card field, return field content.
		if ( ! $this->has_feed( $form_id ) || 'creditcard' !== $field->get_input_type() ) {
			return $content;
		}

		// If a 2Checkout response exists, populate it to a hidden field.
		if ( $this->get_2checkout_js_response() ) {
			$content .= "<input type='hidden' name='2checkout_response' id='gf_2checkout_response' value='" . rgpost( 'gf_2checkout_response' ) . "' />";
		}

		// Remove name attribute from credit card field inputs for security.
		// Removes: name='input_2.1', name='input_2.2[]', name='input_2.3' where 2 is the credit card field id.
		$content = preg_replace( "/name='input_{$field->id}.[1|2|3](\[])?'/", '', $content );

		return $content;

	}

	/**
	 * Check if a 2co.js error has been returned and then return the appropriate message.
	 *
	 * @since   1.0
	 * @deprecated 2.0 No longer used.
	 * @access  public
	 *
	 * @used-by GF_2Checkout::authorize()
	 * @uses    GF_2Checkout::get_2checkout_js_response()
	 *
	 * @return bool|string
	 */
	public function get_2checkout_js_error() {

		// Get 2co.js response.
		$response = $this->get_2checkout_js_response();

		// If an error message is provided, return error message.
		if ( rgar( $response, 'errorMsg' ) ) {
			return $response['errorMsg'];
		}

		return false;

	}

	/**
	 * Populate the $_POST with the last four digits of the card number.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 *
	 * @access public
	 *
	 * @param array $form Form object.
	 *
	 * @used-by GF_2Checkout::init()
	 * @uses    GF_2Checkout::get_2checkout_js_error()
	 * @uses    GF_2Checkout::get_2checkout_js_response()
	 * @uses    GFPaymentAddOn::$is_payment_gateway
	 * @uses    GFPaymentAddOn::get_credit_card_field()
	 */
	public function populate_credit_card_last_four( $form ) {

		if ( ! $this->is_payment_gateway ) {
			return;
		}

		// If response was an error, exit.
		if ( $this->get_2checkout_js_error() ) {
			return;
		}

		// Get the credit card field.
		$cc_field = $this->get_credit_card_field( $form );

		// Get the 2Checkout response.
		$response = $this->get_2checkout_js_response();

		$_POST[ 'input_' . $cc_field->id . '_1' ] = 'XXXXXXXXXXXX' . substr( $response['response']['paymentMethod']['cardNum'], -4 );

	}


	/**
	 * Prepare initial sale parameters.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 *
	 * @access public
	 *
	 * @param array $submission_data The customer and transaction data.
	 * @param array $form            The Form object currently being processed.
	 * @param array $entry           The Entry object currently being processed.
	 * @param array $field_map       Billing Information field map.
	 *
	 * @uses   GFAddOn::get_field_value()
	 * @uses   GF_2Checkout::get_2checkout_js_response()
	 * @uses   GFCommon::get_currency()
	 *
	 * @return array
	 */
	public function prepare_sale( $submission_data, $form, $entry, $field_map ) {

		// Get 2co.js response.
		$response = $this->get_2checkout_js_response();

		return array(
			'token'           => $response['response']['token']['token'],
			'merchantOrderId' => uniqid(),
			'currency'        => GFCommon::get_currency(),
			'lineItems'       => array(),
			'billingAddr'     => array(
				'name'        => rgar( $submission_data, 'card_name' ),
				'addrLine1'   => $this->get_field_value( $form, $entry, $field_map['address'] ),
				'addrLine2'   => $this->get_field_value( $form, $entry, $field_map['address2'] ),
				'city'        => $this->get_field_value( $form, $entry, $field_map['city'] ),
				'state'       => $this->get_field_value( $form, $entry, $field_map['state'] ),
				'zipCode'     => $this->get_field_value( $form, $entry, $field_map['zip'] ),
				'country'     => $this->get_field_value( $form, $entry, $field_map['country'] ),
				'email'       => $this->get_field_value( $form, $entry, $field_map['email'] ),
				'phoneNumber' => $this->get_field_value( $form, $entry, $field_map['phone'] ),
			),
		);

	}

	/**
	 * Validate billing address.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 *
	 * @access public
	 *
	 * @param array  $address Billing address.
	 * @param object $field   Address field.
	 *
	 * @uses   GF_Field_Address::get_country_code()
	 *
	 * @return bool
	 */
	public function validate_billing_address( $address, $field ) {

		// If address line 1, city or country are missing, return false.
		if ( ! rgar( $address, 'addrLine1' ) || ! rgar( $address, 'city' ) || ! rgar( $address, 'country' ) ) {
			return false;
		}

		// Prepare list of countries requiring state and zip code.
		$state_zip_required = array( 'AR', 'AU', 'BG', 'CA', 'CN', 'CY', 'EG', 'ES', 'FR', 'GB', 'ID', 'IN', 'IT', 'JP', 'MX', 'MY', 'NL', 'PA', 'PH', 'PL', 'RO', 'RU', 'RS', 'SE', 'SG', 'TH', 'TR', 'US', 'ZA' );

		// Get country code.
		$country_code = $field->get_country_code( $address['country'] );

		// If state or zip code is missing, return false.
		if ( in_array( $country_code, $state_zip_required ) && ( ! rgar( $address, 'state' ) || ! rgar( $address, 'zipCode' ) ) ) {
			return false;
		}

		return true;

	}

    // When working to handle theme styles / styling, this is where it should be handled.
	public function theme_layer_third_party_styles( $form_id, $settings, $block_settings ) {
		return array();
	}
}
