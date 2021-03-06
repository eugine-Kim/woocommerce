<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WooCommerce Payment Gateway class
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class 		WC_Payment_Gateway
 * @extends		WC_Settings_API
 * @version		2.1.0
 * @package		WooCommerce/Abstracts
 * @category	Abstract Class
 * @author 		WooThemes
 */
abstract class WC_Payment_Gateway extends WC_Settings_API {

	/** @var string Payment method ID. */
	var $id;

	/** @var string Payment method title. */
	var $title;

	/** @var string Chosen payment method id. */
	var $chosen;

	/** @var bool True if the gateway shows fields on the checkout. */
	var $has_fields;

	/** @var array Array of countries this gateway is allowed for. */
	var $countries;

	/** @var string Available for all counties or specific. */
	var $availability;

	/** @var string 'yes' if the method is enabled. */
	var $enabled;

	/** @var string Icon for the gateway. */
	var $icon;

	/** @var string Description for the gateway. */
	var $description;

	/** @var array Array of supported features such as 'default_credit_card_form' */
	var $supports		= array( 'products' );

	/**
	 * Get the return url (thank you page)
	 *
	 * @access public
	 * @param string $order (default: '')
	 * @return string
	 */
	public function get_return_url( $order = '' ) {
		if ( $order )
			$return_url = $order->get_checkout_order_received_url();
		else
			$return_url = woocommerce_get_endpoint_url( 'order-received', '', get_permalink( woocommerce_get_page_id( 'checkout' ) ) );

		if ( is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' )
			$return_url = str_replace( 'http:', 'https:', $return_url );

		return apply_filters( 'woocommerce_get_return_url', $return_url );
	}

	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @access public
	 * @return bool
	 */
	public function is_available() {
		if ( $this->enabled == "yes" )
			return true;
	}

	/**
	 * has_fields function.
	 *
	 * @access public
	 * @return bool
	 */
	public function has_fields() {
		return $this->has_fields ? true : false;
	}

	/**
	 * Return the gateways title
	 *
	 * @access public
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_gateway_title', $this->title, $this->id );
	}

	/**
	 * Return the gateways description
	 *
	 * @access public
	 * @return string
	 */
	public function get_description() {
		return apply_filters( 'woocommerce_gateway_description', $this->description, $this->id );
	}

	/**
	 * get_icon function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_icon() {
		global $woocommerce;

		$icon = $this->icon ? '<img src="' . WC_HTTPS::force_https_url( $this->icon ) . '" alt="' . $this->get_title() . '" />' : '';

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Set As Current Gateway.
	 *
	 * Set this as the current gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function set_current() {
		$this->chosen = true;
	}

	/**
	 * Process Payment
	 *
	 * Process the payment. Override this in your gateway.
	 *
	 * @param int $order_id
	 * @access public
	 * @return void
	 */
	public function process_payment( $order_id ) {}

	/**
	 * Validate Frontend Fields
	 *
	 * Validate payment fields on the frontend.
	 *
	 * @access public
	 * @return bool
	 */
	public function validate_fields() { return true; }

    /**
     * If There are no payment fields show the description if set.
     * Override this in your gateway if you have some.
     *
     * @access public
     * @return void
     */
    public function payment_fields() {
        if ( $description = $this->get_description() )
        	echo wpautop( wptexturize( $description ) );
        if ( $this->supports( 'default_credit_card_form' ) )
        	$this->credit_card_form();
    }

	/**
	 * Check if a gateway supports a given feature.
	 *
	 * Gateways should override this to declare support (or lack of support) for a feature.
	 * For backward compatibility, gateways support 'products' by default, but nothing else.
	 *
	 * @access public
	 * @param $feature string The name of a feature to test support for.
	 * @return bool True if the gateway supports the feature, false otherwise.
	 * @since 1.5.7
	 */
	public function supports( $feature ) {
		return apply_filters( 'woocommerce_payment_gateway_supports', in_array( $feature, $this->supports ) ? true : false, $feature, $this );
	}

	/**
	 * Core credit card form which gateways can used if needed.
	 *
	 * @param  array $args
	 */
	public function credit_card_form( $args = array(), $fields = array() ) {
		wp_enqueue_script( 'wc-credit-card-form' );

		$default_args = array(
			'fields_have_names' => true, // Some gateways like stripe don't need names as the form is tokenized
		);

		$args = wp_parse_args( $args, apply_filters( 'woocommerce_credit_card_form_args', $default_args, $this->id ) );

		$default_fields = array(
			'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . $this->id . '-card-number">' . __( "Card Number", 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . $this->id . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . ( $args['fields_have_names'] ? $this->id . '-card-number' : '' ) . '" />
			</p>',
			'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . $this->id . '-card-expiry">' . __( "Expiry (MM/YY)", 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . $this->id . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="MM / YY" name="' . ( $args['fields_have_names'] ? $this->id . '-card-expiry' : '' ) . '" />
			</p>',
			'card-cvc-field' => '<p class="form-row form-row-last">
				<label for="' . $this->id . '-card-cvc">' . __( "Card Code", 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . $this->id . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="CVC" name="' . ( $args['fields_have_names'] ? $this->id . '-card-cvc' : '' ) . '" />
			</p>'
		);

		$fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
		?>
		<fieldset id="<?php echo $this->id; ?>-cc-form">
			<?php do_action( 'woocommerce_credit_card_form_before', $this->id ); ?>
			<?php echo $fields['card-number-field']; ?>
			<?php echo $fields['card-expiry-field']; ?>
			<?php echo $fields['card-cvc-field']; ?>
			<?php do_action( 'woocommerce_credit_card_form_before', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php
	}
}
