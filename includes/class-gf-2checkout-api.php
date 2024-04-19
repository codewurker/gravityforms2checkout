<?php

defined( 'ABSPATH' ) or die();

/**
 * Gravity Forms 2Checkout API library.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2018, Rocketgenius
 */
class GF_2Checkout_API {

	/**
	 * 2Checkout API mode.
	 *
	 * @since  1.0
	 * @var    string
	 * @access protected
	 */
	protected $api_mode = 'production';

	/**
	 * Base 2Checkout Production API URL.
	 *
	 * @since  1.0
	 * @var    string
	 * @access protected
	 */
	protected $api_url = 'https://api.2checkout.com/rpc/6.0/';

	/**
	 * 2Checkout Secret Key.
	 *
	 * @since  2.0
	 * @var    string
	 * @access protected
	 */
	protected $secret_key = '';

	/**
	 * 2Checkout Merchant Code.
	 *
	 * @since  2.0
	 * @var    string
	 * @access protected
	 */
	protected $merchant_code = '';

	/**
	 * Authentication session_id.
	 *
	 * @since 2.0
	 *
	 * @var string
	 */
	protected $session_id = '';

	/**
	 * Base 2Checkout Sandbox API URL.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 * @var    string
	 * @access protected
	 */
	protected $api_sandbox_url = 'https://sandbox.2checkout.com/';

	/**
	 * 2Checkout Password.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 * @var    string
	 * @access protected
	 */
	protected $password = '';

	/**
	 * 2Checkout Private Key.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 * @var    string
	 * @access protected
	 */
	protected $private_key = '';

	/**
	 * 2Checkout Seller ID.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 * @var    string
	 * @access protected
	 */
	protected $seller_id = '';

	/**
	 * 2Checkout Username.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 * @var    string
	 * @access protected
	 */
	protected $username = '';

	/**
	 * Initialize 2Checkout API library.
	 *
	 * @since  1.0
	 * @since  2.0  Use merchant code & secret instead of seller id, username, password and keys.
	 * @access public
	 *
	 * @param string $api_mode        API mode.
	 * @param string $merchant_code   2Checkout Merchant Code.
	 * @param string $secret_key      2Checkout Private Key.
	 */
	public function __construct( $api_mode, $merchant_code, $secret_key ) {
		$this->api_mode      = $api_mode;
		$this->merchant_code = $merchant_code;
		$this->secret_key    = $secret_key;
	}

	/**
	 * Creates an order.
	 *
	 * @since 2.0
	 *
	 * @param Order $order_object Object containing payment and order data.
	 *
	 * @return array|WP_Error Order result or error.
	 */
	public function create_order( $order_object ) {
		return $this->api_request( 'placeOrder', array( $order_object ) );
	}

	/**
	 * Retrieves order details.
	 *
	 * @since 2.0
	 *
	 * @param string $reference_number Order reference number.
	 *
	 * @return array|WP_Error Order details or error.
	 */
	public function get_order( $reference_number ) {
		return $this->api_request( 'getOrder', array( $reference_number ) );
	}

	/**
	 * Retrieves subscription details.
	 *
	 * @since 2.0
	 *
	 * @param string $reference_number Subscription reference number.
	 *
	 * @return array|WP_Error Subscription details or error.
	 */
	public function get_subscription( $reference_number ) {
		return $this->api_request( 'getSubscription', array( $reference_number ) );
	}

	/**
	 * Generates a session id for authenticating API requests.
	 *
	 * @since 2.0
	 *
	 * @return string The generated session id or an empty string if it failed to create one.
	 */
	public function generate_session_id() {
		$date       = gmdate( 'Y-m-d H:i:s' );
		$hash       = hash_hmac( \GF_2Checkout::HASHING_ALGORITHM, strlen( $this->merchant_code ) . $this->merchant_code . strlen( $date ) . $date, $this->secret_key );
		$session_id = $this->api_request( 'login', array( $this->merchant_code, $date, $hash ) );

		if ( is_wp_error( $session_id ) ) {
			gf_2checkout()->log_error( 'Unable to generate session_id: ' . $session_id->get_error_message() );
			return '';
		}

		return $session_id;
	}


	/**
	 * Sends an API request.
	 *
	 * @since 2.0
	 *
	 * @param string $method Request method.
	 * @param array  $params Request parameters.
	 *
	 *
	 * @return WP_Error|array Response array or a WP_Error object that contains response error details.
	 */
	public function api_request( $method, $params ) {
		//Add hash algorithm to params as it is required for all requests.
		$params[] = \GF_2Checkout::HASHING_ALGORITHM;
		// If this is not a login request, add session_id.
		if ( $method !== 'login' ) {
			$session_id = $this->generate_session_id();
			if ( false === $session_id ) {
				return new WP_Error( '1', 'Invalid API credentials.', $response );
			}
			array_unshift( $params, $this->generate_session_id() );
		}

		// Build request object.
		$request_object          = new stdClass();
		$request_object->jsonrpc = '2.0';
		$request_object->method  = $method;
		$request_object->params  = $params;
		$request_object->id      = wp_rand();

		// Build request arguments.
		$args = array(
			'method'  => 'POST',
			'timeout' => 120,
			'body'    => wp_json_encode( $request_object ), // @codingStandardsIgnoreLine
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
		);

		// Execute request.
		$result = wp_remote_request( $this->api_url, $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = wp_remote_retrieve_body( $result );
		$response = gf_2checkout()->maybe_decode_json( $response );

		if ( ! isset( $response['error'] ) && ! empty( $response['result'] ) ) {
			return $response['result'];
		}

		return $this->get_wp_error_from_response( $response );
	}

	/**
	 * Extracts error code and message from API response.
	 *
	 * @since 2.0
	 *
	 * @param array $response API request response.
	 *
	 * @return WP_Error
	 */
	private function get_wp_error_from_response( $response ) {
		if ( ! empty( $response['error'] ) && is_array( $response['error'] ) ) {

			$error_data = explode( ':', $response['error']['message'] );
			if ( count( $error_data ) < 2 ) {
				$error_code    = 'generic';
				$error_message = $error_data[0];
			} else {
				$error_code    = $error_data[0];
				$error_message = $error_data[1];
			}

			return new WP_Error( $error_code, $error_message, $response );
		}

		return new WP_Error( 'generic', esc_html__( 'API request failed', 'gravityforms2checkout' ), $response );
	}


	// # Deprecated methods ///

	/**
	 * Get company information details for current account.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 * @access public
	 *
	 * @uses   GF_2Checkout_API::make_request()
	 *
	 * @return array
	 * @throws Exception If response is an error.
	 */
	public function detail_company_info() {

		return $this->make_request( 'api/acct/detail_company_info', array(), 'GET', 'vendor_company_info' );

	}

	/**
	 * Create sale.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 * @access public
	 *
	 * @param array $sale Sale parameters.
	 *
	 * @uses   GF_2Checkout_API::make_request()
	 *
	 * @return array
	 * @throws Exception If response is an error.
	 */
	public function create_sale( $sale = array() ) {

		// Prepare base sale parameters.
		$sale_base = array(
			'sellerId'   => $this->seller_id,
			'privateKey' => $this->private_key,
		);

		// Merge sales parameters.
		$sale = array_merge( $sale_base, $sale );

		return $this->make_request( 'checkout/api/1/' . $this->seller_id . '/rs/authService', $sale, 'POST', 'response' );

	}

	/**
	 * Get details for a sale.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 * @access public
	 *
	 * @param string $sale_id Sale ID or order number.
	 *
	 * @uses   GF_2Checkout_API::make_request()
	 *
	 * @return array
	 * @throws Exception If response is an error.
	 */
	public function detail_sale( $sale_id = '' ) {

		return $this->make_request( 'api/sales/detail_sale', array( 'sale_id' => $sale_id ), 'GET', 'sale' );

	}

	/**
	 * Make API request.
	 *
	 * @since  1.0
	 * @deprecated 2.0
	 * @access private
	 *
	 * @param string $action     Request action.
	 * @param array  $options    Request options.
	 * @param string $method     HTTP method. Defaults to GET.
	 * @param string $return_key Array key from response to return. Defaults to null (return full response).
	 *
	 * @return array
	 * @throws Exception
	 */
	private function make_request( $action, $options = array(), $method = 'GET', $return_key = null ) {

		// Build request URL.
		$request_url = ( 'sandbox' === $this->api_mode ? $this->api_sandbox_url : $this->api_url ) . $action;

		// Add query parameters.
		if ( 'GET' === $method && ! empty( $options ) ) {
			$request_url = add_query_arg( $options, $request_url );
		}

		// Build request headers.
		$headers = array(
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
			'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password ),
		);

		// Build request arguments.
		$args = array(
			'body'    => 'GET' !== $method ? json_encode( $options ) : null,
			'headers' => $headers,
			'method'  => $method,
			'timeout' => 120,
		);

		// Execute request.
		$result = wp_remote_request( $request_url, $args );

		// If response is an error, throw an Exception.
		if ( is_wp_error( $result ) ) {
			throw new Exception( $result->get_error_message() );
		}

		// Decode response.
		$response = wp_remote_retrieve_body( $result );
		$response = gf_2checkout()->maybe_decode_json( $response );

		// If an exception is set, throw Exception.
		if ( rgar( $response, 'exception' ) ) {
			throw new Exception( $response['exception']['errorMsg'], $response['exception']['errorCode'] );
		}

		// If an error is set, throw Exception.
		if ( rgar( $response, 'code' ) && rgar( $response, 'message' ) ) {
			throw new Exception( $response['code'], $response['message'] );
		}

		if ( $error_message = rgars( $response, 'errors/0/message' ) ) {
			throw new Exception( $error_message );
		}

		// If a return key is defined and array item exists, return it.
		if ( ! rgblank( $return_key ) && rgar( $response, $return_key ) ) {
			return $response[ $return_key ];
		}

		return $response;

	}

}
