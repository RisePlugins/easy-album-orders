<?php
/**
 * Stripe integration handler.
 *
 * Handles all Stripe API interactions including Payment Intents,
 * webhook verification, and configuration management.
 *
 * @package Easy_Album_Orders
 * @since   1.1.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Stripe handler class.
 *
 * @since 1.1.0
 */
class EAO_Stripe {

    /**
     * Stripe API version.
     *
     * @since 1.1.0
     * @var string
     */
    const API_VERSION = '2023-10-16';

    /**
     * Settings.
     *
     * @since 1.1.0
     * @var array
     */
    private $settings;

    /**
     * Whether Stripe SDK has been initialized.
     *
     * @since 1.1.0
     * @var bool
     */
    private $initialized = false;

    /**
     * Constructor.
     *
     * @since 1.1.0
     */
    public function __construct() {
        $this->settings = get_option( 'eao_stripe_settings', array() );
    }

    /**
     * Initialize Stripe with API key.
     *
     * Loads the Stripe SDK and sets the API key. Called lazily
     * when Stripe functionality is actually needed.
     *
     * @since 1.1.0
     *
     * @return bool True if initialized successfully, false otherwise.
     */
    private function init_stripe() {
        if ( $this->initialized ) {
            return true;
        }

        if ( ! $this->is_enabled() ) {
            return false;
        }

        // Check if Composer autoload exists.
        $autoload_path = EAO_PLUGIN_DIR . 'vendor/autoload.php';
        if ( ! file_exists( $autoload_path ) ) {
            return false;
        }

        require_once $autoload_path;

        try {
            \Stripe\Stripe::setApiKey( $this->get_secret_key() );
            \Stripe\Stripe::setApiVersion( self::API_VERSION );
            $this->initialized = true;
            return true;
        } catch ( \Exception $e ) {
            return false;
        }
    }

    /**
     * Check if Stripe is enabled and properly configured.
     *
     * @since 1.1.0
     *
     * @return bool True if Stripe is enabled and has required keys.
     */
    public function is_enabled() {
        return ! empty( $this->settings['enabled'] ) && $this->get_secret_key() && $this->get_publishable_key();
    }

    /**
     * Check if in test mode.
     *
     * @since 1.1.0
     *
     * @return bool True if using test mode keys.
     */
    public function is_test_mode() {
        return empty( $this->settings['mode'] ) || 'test' === $this->settings['mode'];
    }

    /**
     * Get the publishable key based on current mode.
     *
     * @since 1.1.0
     *
     * @return string The publishable key or empty string.
     */
    public function get_publishable_key() {
        $key = $this->is_test_mode() ? 'test_publishable_key' : 'live_publishable_key';
        return isset( $this->settings[ $key ] ) ? trim( $this->settings[ $key ] ) : '';
    }

    /**
     * Get the secret key based on current mode.
     *
     * @since 1.1.0
     *
     * @return string The secret key or empty string.
     */
    public function get_secret_key() {
        $key = $this->is_test_mode() ? 'test_secret_key' : 'live_secret_key';
        return isset( $this->settings[ $key ] ) ? trim( $this->settings[ $key ] ) : '';
    }

    /**
     * Get webhook secret.
     *
     * @since 1.1.0
     *
     * @return string The webhook signing secret.
     */
    public function get_webhook_secret() {
        return isset( $this->settings['webhook_secret'] ) ? trim( $this->settings['webhook_secret'] ) : '';
    }

    /**
     * Get statement descriptor.
     *
     * Stripe limits statement descriptors to 22 characters,
     * alphanumeric characters, spaces, and hyphens only.
     *
     * @since 1.1.0
     *
     * @return string The statement descriptor.
     */
    public function get_statement_descriptor() {
        $descriptor = isset( $this->settings['statement_descriptor'] )
            ? $this->settings['statement_descriptor']
            : 'Album Order';

        // Stripe limits: 22 chars, alphanumeric + spaces/hyphens.
        return substr( preg_replace( '/[^a-zA-Z0-9 \-]/', '', $descriptor ), 0, 22 );
    }

    /**
     * Create a Payment Intent.
     *
     * Creates a new Stripe Payment Intent for collecting payment.
     * The intent will be confirmed client-side using Stripe.js.
     *
     * @since 1.1.0
     *
     * @param float  $amount          Amount in dollars.
     * @param string $currency        Currency code (default: 'usd').
     * @param array  $metadata        Order metadata to attach to payment.
     * @param string $customer_email  Customer email for receipt.
     * @return array|\WP_Error Payment Intent data or error.
     */
    public function create_payment_intent( $amount, $currency = 'usd', $metadata = array(), $customer_email = '' ) {
        if ( ! $this->init_stripe() ) {
            return new \WP_Error(
                'stripe_disabled',
                __( 'Payment processing is not available. Please contact the site administrator.', 'easy-album-orders' )
            );
        }

        // Validate amount.
        $amount = floatval( $amount );
        if ( $amount <= 0 ) {
            return new \WP_Error(
                'invalid_amount',
                __( 'Invalid payment amount.', 'easy-album-orders' )
            );
        }

        try {
            $params = array(
                'amount'                    => $this->convert_to_cents( $amount ),
                'currency'                  => strtolower( $currency ),
                'metadata'                  => $metadata,
                'statement_descriptor'      => $this->get_statement_descriptor(),
                'automatic_payment_methods' => array(
                    'enabled' => true,
                ),
            );

            // Add receipt email if provided.
            if ( ! empty( $customer_email ) && is_email( $customer_email ) ) {
                $params['receipt_email'] = sanitize_email( $customer_email );
            }

            $intent = \Stripe\PaymentIntent::create( $params );

            return array(
                'id'            => $intent->id,
                'client_secret' => $intent->client_secret,
                'amount'        => $amount,
            );

        } catch ( \Stripe\Exception\CardException $e ) {
            return new \WP_Error( 'card_error', $e->getMessage() );
        } catch ( \Stripe\Exception\RateLimitException $e ) {
            return new \WP_Error( 'rate_limit', __( 'Too many requests. Please try again in a moment.', 'easy-album-orders' ) );
        } catch ( \Stripe\Exception\InvalidRequestException $e ) {
            return new \WP_Error( 'invalid_request', $e->getMessage() );
        } catch ( \Stripe\Exception\AuthenticationException $e ) {
            return new \WP_Error( 'authentication_error', __( 'Payment configuration error. Please contact the site administrator.', 'easy-album-orders' ) );
        } catch ( \Stripe\Exception\ApiConnectionException $e ) {
            return new \WP_Error( 'api_connection', __( 'Network error. Please check your connection and try again.', 'easy-album-orders' ) );
        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new \WP_Error( 'stripe_error', $e->getMessage() );
        }
    }

    /**
     * Retrieve a Payment Intent.
     *
     * @since 1.1.0
     *
     * @param string $payment_intent_id Payment Intent ID (starts with pi_).
     * @return \Stripe\PaymentIntent|\WP_Error The Payment Intent or error.
     */
    public function get_payment_intent( $payment_intent_id ) {
        if ( ! $this->init_stripe() ) {
            return new \WP_Error(
                'stripe_disabled',
                __( 'Payment processing is not available.', 'easy-album-orders' )
            );
        }

        // Validate Payment Intent ID format.
        if ( empty( $payment_intent_id ) || 0 !== strpos( $payment_intent_id, 'pi_' ) ) {
            return new \WP_Error(
                'invalid_payment_intent',
                __( 'Invalid payment reference.', 'easy-album-orders' )
            );
        }

        try {
            return \Stripe\PaymentIntent::retrieve( $payment_intent_id );
        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new \WP_Error( 'stripe_error', $e->getMessage() );
        }
    }

    /**
     * Convert dollars to cents.
     *
     * Stripe requires amounts in the smallest currency unit (cents for USD).
     *
     * @since 1.1.0
     *
     * @param float $amount Amount in dollars.
     * @return int Amount in cents.
     */
    private function convert_to_cents( $amount ) {
        return absint( round( floatval( $amount ) * 100 ) );
    }

    /**
     * Verify webhook signature.
     *
     * Validates that a webhook request came from Stripe using
     * the webhook signing secret.
     *
     * @since 1.1.0
     *
     * @param string $payload   Raw request body.
     * @param string $signature Stripe-Signature header value.
     * @return \Stripe\Event|\WP_Error The verified event or error.
     */
    public function verify_webhook( $payload, $signature ) {
        if ( ! $this->init_stripe() ) {
            return new \WP_Error(
                'stripe_disabled',
                __( 'Stripe is not configured.', 'easy-album-orders' )
            );
        }

        $webhook_secret = $this->get_webhook_secret();
        if ( empty( $webhook_secret ) ) {
            return new \WP_Error(
                'missing_webhook_secret',
                __( 'Webhook secret is not configured.', 'easy-album-orders' )
            );
        }

        try {
            return \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $webhook_secret
            );
        } catch ( \UnexpectedValueException $e ) {
            return new \WP_Error(
                'invalid_payload',
                __( 'Invalid webhook payload.', 'easy-album-orders' )
            );
        } catch ( \Stripe\Exception\SignatureVerificationException $e ) {
            return new \WP_Error(
                'invalid_signature',
                __( 'Invalid webhook signature.', 'easy-album-orders' )
            );
        }
    }

    /**
     * Get webhook endpoint URL.
     *
     * Returns the URL that should be configured in Stripe Dashboard
     * for webhook events.
     *
     * @since 1.1.0
     *
     * @return string The webhook endpoint URL.
     */
    public static function get_webhook_url() {
        return rest_url( 'eao/v1/stripe-webhook' );
    }

    /**
     * Validate API keys format.
     *
     * Checks if the provided API keys have the correct format.
     *
     * @since 1.1.0
     *
     * @param string $key  The API key to validate.
     * @param string $type The key type: 'publishable' or 'secret'.
     * @param string $mode The mode: 'test' or 'live'.
     * @return bool True if valid format, false otherwise.
     */
    public static function validate_key_format( $key, $type, $mode ) {
        if ( empty( $key ) ) {
            return true; // Empty is allowed (not configured).
        }

        $key = trim( $key );

        // Determine expected prefix.
        $prefix = '';
        if ( 'publishable' === $type ) {
            $prefix = 'test' === $mode ? 'pk_test_' : 'pk_live_';
        } elseif ( 'secret' === $type ) {
            $prefix = 'test' === $mode ? 'sk_test_' : 'sk_live_';
        }

        return 0 === strpos( $key, $prefix );
    }

    /**
     * Get current settings.
     *
     * @since 1.1.0
     *
     * @return array Current Stripe settings.
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Refresh settings from database.
     *
     * Call this after settings have been updated to ensure
     * the instance uses the latest values.
     *
     * @since 1.1.0
     */
    public function refresh_settings() {
        $this->settings    = get_option( 'eao_stripe_settings', array() );
        $this->initialized = false;
    }

    /**
     * Create a refund for a payment.
     *
     * Refunds a charge, either fully or partially.
     *
     * @since 1.2.0
     *
     * @param string $charge_id Stripe Charge ID (starts with ch_).
     * @param float  $amount    Optional. Amount to refund in dollars. If null, full refund.
     * @param string $reason    Optional. Reason for refund: 'duplicate', 'fraudulent', or 'requested_by_customer'.
     * @return array|\WP_Error Refund data or error.
     */
    public function create_refund( $charge_id, $amount = null, $reason = 'requested_by_customer' ) {
        if ( ! $this->init_stripe() ) {
            return new \WP_Error(
                'stripe_disabled',
                __( 'Payment processing is not available.', 'easy-album-orders' )
            );
        }

        // Validate Charge ID format.
        if ( empty( $charge_id ) || 0 !== strpos( $charge_id, 'ch_' ) ) {
            return new \WP_Error(
                'invalid_charge',
                __( 'Invalid payment reference.', 'easy-album-orders' )
            );
        }

        // Validate reason.
        $valid_reasons = array( 'duplicate', 'fraudulent', 'requested_by_customer' );
        if ( ! in_array( $reason, $valid_reasons, true ) ) {
            $reason = 'requested_by_customer';
        }

        try {
            $params = array(
                'charge' => $charge_id,
                'reason' => $reason,
            );

            // If amount specified, add it (partial refund).
            if ( null !== $amount ) {
                $amount = floatval( $amount );
                if ( $amount <= 0 ) {
                    return new \WP_Error(
                        'invalid_amount',
                        __( 'Invalid refund amount.', 'easy-album-orders' )
                    );
                }
                $params['amount'] = $this->convert_to_cents( $amount );
            }

            $refund = \Stripe\Refund::create( $params );

            return array(
                'id'     => $refund->id,
                'amount' => $refund->amount / 100,
                'status' => $refund->status,
            );

        } catch ( \Stripe\Exception\CardException $e ) {
            return new \WP_Error( 'card_error', $e->getMessage() );
        } catch ( \Stripe\Exception\InvalidRequestException $e ) {
            // Common errors: charge already refunded, charge too old, etc.
            return new \WP_Error( 'invalid_request', $e->getMessage() );
        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new \WP_Error( 'stripe_error', $e->getMessage() );
        }
    }

    /**
     * Get a charge to check refund status.
     *
     * @since 1.2.0
     *
     * @param string $charge_id Stripe Charge ID.
     * @return \Stripe\Charge|\WP_Error The charge or error.
     */
    public function get_charge( $charge_id ) {
        if ( ! $this->init_stripe() ) {
            return new \WP_Error(
                'stripe_disabled',
                __( 'Payment processing is not available.', 'easy-album-orders' )
            );
        }

        if ( empty( $charge_id ) || 0 !== strpos( $charge_id, 'ch_' ) ) {
            return new \WP_Error(
                'invalid_charge',
                __( 'Invalid payment reference.', 'easy-album-orders' )
            );
        }

        try {
            return \Stripe\Charge::retrieve( $charge_id );
        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new \WP_Error( 'stripe_error', $e->getMessage() );
        }
    }
}

