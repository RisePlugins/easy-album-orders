# Stripe Payment Integration

## Overview

This document outlines the complete implementation plan for integrating Stripe payments into Easy Album Orders. When customers complete their order in the checkout modal, they will be required to pay before the order is finalized.

### Business Model

Easy Album Orders is designed for **individual photographers** who install the plugin on their own WordPress websites. Each photographer:

1. Purchases the Easy Album Orders plugin
2. Installs it on their WordPress site
3. Creates their own Stripe account at [stripe.com](https://stripe.com)
4. Enters their Stripe API keys in the plugin settings
5. Receives payments directly to their Stripe account

**This means:**
- ✅ Each photographer controls their own payments
- ✅ Money goes directly to the photographer (no middleman)
- ✅ Standard Stripe fees apply (typically 2.9% + $0.30 per transaction)
- ✅ No Stripe Connect or platform fees required
- ✅ Simple setup - just API keys

> **Note:** This is NOT a marketplace model. We don't use Stripe Connect because photographers don't share a platform - each has their own independent WordPress installation.

---

## Table of Contents

1. [Integration Approach](#integration-approach)
2. [Prerequisites](#prerequisites)
3. [File Structure](#file-structure)
4. [Implementation Steps](#implementation-steps)
   - [Phase 1: Stripe Configuration](#phase-1-stripe-configuration)
   - [Phase 2: Backend Payment Handler](#phase-2-backend-payment-handler)
   - [Phase 3: Frontend Payment UI](#phase-3-frontend-payment-ui)
   - [Phase 4: Webhook Handler](#phase-4-webhook-handler)
   - [Phase 5: Order Status Integration](#phase-5-order-status-integration)
5. [Security Considerations](#security-considerations)
6. [Testing](#testing)
7. [Error Handling](#error-handling)
8. [Future Enhancements](#future-enhancements)

---

## Integration Approach

We'll use **Stripe Payment Intents API** with **Stripe Elements** for a secure, PCI-compliant payment flow:

1. Customer fills out checkout form in modal
2. Frontend creates a Payment Intent via AJAX
3. Stripe Elements collects card details (never touches our server)
4. Payment is confirmed client-side
5. Webhook confirms payment and updates order status
6. Customer sees confirmation

### Why Payment Intents?

- **SCA Ready**: Supports Strong Customer Authentication (3D Secure)
- **PCI Compliant**: Card details never touch our server
- **Modern**: Stripe's recommended approach for new integrations
- **Flexible**: Supports various payment methods beyond cards

---

## Prerequisites

### Server Requirements (For Plugin Development)

- PHP 7.4+
- SSL certificate (HTTPS required for Stripe)
- WordPress 5.8+

### Stripe Account Setup (For End Users / Photographers)

When photographers install Easy Album Orders, they'll need to set up their own Stripe account. Include these instructions in your plugin documentation or settings page:

#### Step-by-Step Guide for Photographers

1. **Create a Stripe Account**
   - Go to [stripe.com](https://stripe.com) and click "Start now"
   - Enter your email and create a password
   - Verify your email address

2. **Complete Business Verification**
   - Stripe will ask for business details (name, address, tax ID)
   - For sole proprietors, personal information is acceptable
   - This is required to receive payouts

3. **Get Your API Keys**
   - Log into [Stripe Dashboard](https://dashboard.stripe.com)
   - Navigate to **Developers > API Keys**
   - You'll see two types of keys:
     - **Publishable key**: Starts with `pk_test_` or `pk_live_`
     - **Secret key**: Starts with `sk_test_` or `sk_live_`
   - Copy these to your Easy Album Orders settings

4. **Set Up Webhook (Optional but Recommended)**
   - Go to **Developers > Webhooks**
   - Click **Add endpoint**
   - Enter your webhook URL (shown in plugin settings)
   - Select the required events
   - Copy the **Signing secret** to your plugin settings

#### Test Mode vs Live Mode

| Mode | When to Use | API Keys |
|------|-------------|----------|
| **Test** | Setting up, testing payments | `pk_test_...` / `sk_test_...` |
| **Live** | Accepting real payments | `pk_live_...` / `sk_live_...` |

> ⚠️ **Important**: Always test with Test mode first! Use card number `4242 4242 4242 4242` with any future date and any CVC.

---

## File Structure

```
easy-album-orders/
├── includes/
│   ├── core/
│   │   └── class-eao-stripe.php           # Main Stripe integration class
│   ├── public/
│   │   ├── class-eao-ajax-handler.php     # Updated with payment endpoints
│   │   └── class-eao-stripe-webhook.php   # Webhook handler
│   └── admin/
│       └── views/
│           └── album-options-page.php     # Updated with Stripe settings tab
├── assets/
│   ├── js/
│   │   └── public.js                      # Updated checkout flow
│   └── css/
│       └── public.css                     # Stripe Elements styling
└── vendor/
    └── stripe/                            # Stripe PHP SDK (via Composer)
```

---

## Implementation Steps

### Phase 1: Stripe Configuration

#### 1.1 Install Stripe PHP SDK

**Option A: Composer (Recommended)**

Create/update `composer.json` in plugin root:

```json
{
    "require": {
        "stripe/stripe-php": "^10.0"
    }
}
```

Run: `composer install`

**Option B: Manual Installation**

Download the Stripe PHP library and include it in the plugin.

#### 1.2 Add Stripe Settings to Admin

Add a new "Payments" tab to Album Options page with these fields:

| Field | Type | Description |
|-------|------|-------------|
| Enable Stripe | Checkbox | Toggle payment requirement on/off |
| Mode | Select | Test / Live |
| Test Publishable Key | Text | `pk_test_...` |
| Test Secret Key | Password | `sk_test_...` |
| Live Publishable Key | Text | `pk_live_...` |
| Live Secret Key | Password | `sk_live_...` |
| Webhook Secret | Password | `whsec_...` |
| Payment Description | Text | Appears on customer's statement |

**Settings Storage:**

```php
// Option key: eao_stripe_settings
$stripe_settings = array(
    'enabled'              => true,
    'mode'                 => 'test', // 'test' or 'live'
    'test_publishable_key' => 'pk_test_...',
    'test_secret_key'      => 'sk_test_...',
    'live_publishable_key' => 'pk_live_...',
    'live_secret_key'      => 'sk_live_...',
    'webhook_secret'       => 'whsec_...',
    'statement_descriptor' => 'Album Order',
);
```

#### 1.3 Create Stripe Core Class

**File: `includes/core/class-eao-stripe.php`**

```php
<?php
/**
 * Stripe integration handler.
 *
 * @package Easy_Album_Orders
 * @since   1.1.0
 */

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
     * @var string
     */
    const API_VERSION = '2023-10-16';

    /**
     * Settings.
     *
     * @var array
     */
    private $settings;

    /**
     * Constructor.
     *
     * @since 1.1.0
     */
    public function __construct() {
        $this->settings = get_option( 'eao_stripe_settings', array() );
        $this->init_stripe();
    }

    /**
     * Initialize Stripe with API key.
     *
     * @since 1.1.0
     */
    private function init_stripe() {
        if ( ! $this->is_enabled() ) {
            return;
        }

        require_once EAO_PLUGIN_DIR . 'vendor/autoload.php';

        \Stripe\Stripe::setApiKey( $this->get_secret_key() );
        \Stripe\Stripe::setApiVersion( self::API_VERSION );
    }

    /**
     * Check if Stripe is enabled.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_enabled() {
        return ! empty( $this->settings['enabled'] ) && $this->get_secret_key();
    }

    /**
     * Check if in test mode.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_test_mode() {
        return empty( $this->settings['mode'] ) || 'test' === $this->settings['mode'];
    }

    /**
     * Get the publishable key.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function get_publishable_key() {
        $key = $this->is_test_mode() ? 'test_publishable_key' : 'live_publishable_key';
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : '';
    }

    /**
     * Get the secret key.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function get_secret_key() {
        $key = $this->is_test_mode() ? 'test_secret_key' : 'live_secret_key';
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : '';
    }

    /**
     * Get webhook secret.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function get_webhook_secret() {
        return isset( $this->settings['webhook_secret'] ) ? $this->settings['webhook_secret'] : '';
    }

    /**
     * Get statement descriptor.
     *
     * @since 1.1.0
     *
     * @return string
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
     * @since 1.1.0
     *
     * @param float  $amount          Amount in dollars.
     * @param string $currency        Currency code.
     * @param array  $metadata        Order metadata.
     * @param string $customer_email  Customer email for receipt.
     * @return array|\WP_Error Payment Intent data or error.
     */
    public function create_payment_intent( $amount, $currency = 'usd', $metadata = array(), $customer_email = '' ) {
        if ( ! $this->is_enabled() ) {
            return new \WP_Error( 'stripe_disabled', __( 'Payment processing is not enabled.', 'easy-album-orders' ) );
        }

        try {
            $params = array(
                'amount'               => $this->convert_to_cents( $amount ),
                'currency'             => strtolower( $currency ),
                'metadata'             => $metadata,
                'statement_descriptor' => $this->get_statement_descriptor(),
                'automatic_payment_methods' => array(
                    'enabled' => true,
                ),
            );

            // Add receipt email if provided.
            if ( $customer_email ) {
                $params['receipt_email'] = $customer_email;
            }

            $intent = \Stripe\PaymentIntent::create( $params );

            return array(
                'id'            => $intent->id,
                'client_secret' => $intent->client_secret,
                'amount'        => $amount,
            );

        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new \WP_Error( 'stripe_error', $e->getMessage() );
        }
    }

    /**
     * Retrieve a Payment Intent.
     *
     * @since 1.1.0
     *
     * @param string $payment_intent_id Payment Intent ID.
     * @return \Stripe\PaymentIntent|\WP_Error
     */
    public function get_payment_intent( $payment_intent_id ) {
        if ( ! $this->is_enabled() ) {
            return new \WP_Error( 'stripe_disabled', __( 'Payment processing is not enabled.', 'easy-album-orders' ) );
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
     * @since 1.1.0
     *
     * @param string $payload   Raw request body.
     * @param string $signature Stripe-Signature header.
     * @return \Stripe\Event|\WP_Error
     */
    public function verify_webhook( $payload, $signature ) {
        try {
            return \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $this->get_webhook_secret()
            );
        } catch ( \UnexpectedValueException $e ) {
            return new \WP_Error( 'invalid_payload', __( 'Invalid webhook payload.', 'easy-album-orders' ) );
        } catch ( \Stripe\Exception\SignatureVerificationException $e ) {
            return new \WP_Error( 'invalid_signature', __( 'Invalid webhook signature.', 'easy-album-orders' ) );
        }
    }
}
```

---

### Phase 2: Backend Payment Handler

#### 2.1 Add Payment AJAX Endpoints

Update `class-eao-ajax-handler.php` to add these new endpoints:

```php
// In constructor, add:
add_action( 'wp_ajax_eao_create_payment_intent', array( $this, 'create_payment_intent' ) );
add_action( 'wp_ajax_nopriv_eao_create_payment_intent', array( $this, 'create_payment_intent' ) );

add_action( 'wp_ajax_eao_confirm_payment', array( $this, 'confirm_payment' ) );
add_action( 'wp_ajax_nopriv_eao_confirm_payment', array( $this, 'confirm_payment' ) );
```

**New Methods:**

```php
/**
 * Create a Payment Intent for the checkout.
 *
 * @since 1.1.0
 */
public function create_payment_intent() {
    // Verify nonce.
    if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
    }

    $client_album_id = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;
    $cart_token      = isset( $_POST['cart_token'] ) ? sanitize_key( $_POST['cart_token'] ) : '';
    $customer_email  = isset( $_POST['customer_email'] ) ? sanitize_email( $_POST['customer_email'] ) : '';
    $customer_name   = isset( $_POST['customer_name'] ) ? sanitize_text_field( $_POST['customer_name'] ) : '';

    if ( ! $client_album_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid album.', 'easy-album-orders' ) ) );
    }

    // Get cart items.
    $cart_items = EAO_Album_Order::get_cart_items( $client_album_id, $cart_token );

    if ( empty( $cart_items ) ) {
        wp_send_json_error( array( 'message' => __( 'Your cart is empty.', 'easy-album-orders' ) ) );
    }

    // Calculate total.
    $total = 0;
    $order_ids = array();
    foreach ( $cart_items as $item ) {
        $total += EAO_Album_Order::calculate_total( $item->ID );
        $order_ids[] = $item->ID;
    }

    // Validate total.
    if ( $total <= 0 ) {
        // If total is 0 (fully credited), skip payment.
        wp_send_json_success( array(
            'skip_payment' => true,
            'message'      => __( 'No payment required.', 'easy-album-orders' ),
        ) );
    }

    // Initialize Stripe.
    $stripe = new EAO_Stripe();

    if ( ! $stripe->is_enabled() ) {
        // If Stripe is disabled, allow checkout without payment.
        wp_send_json_success( array(
            'skip_payment' => true,
            'message'      => __( 'Payment processing is disabled.', 'easy-album-orders' ),
        ) );
    }

    // Get client album title for description.
    $album_title = get_the_title( $client_album_id );

    // Create Payment Intent.
    $metadata = array(
        'client_album_id' => $client_album_id,
        'cart_token'      => $cart_token,
        'order_ids'       => implode( ',', $order_ids ),
        'customer_name'   => $customer_name,
        'customer_email'  => $customer_email,
    );

    $result = $stripe->create_payment_intent( $total, 'usd', $metadata, $customer_email );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    // Store Payment Intent ID with orders for later verification.
    foreach ( $cart_items as $item ) {
        update_post_meta( $item->ID, '_eao_payment_intent_id', $result['id'] );
    }

    wp_send_json_success( array(
        'client_secret'   => $result['client_secret'],
        'payment_intent'  => $result['id'],
        'publishable_key' => $stripe->get_publishable_key(),
        'amount'          => $total,
    ) );
}

/**
 * Confirm payment was successful and complete checkout.
 *
 * @since 1.1.0
 */
public function confirm_payment() {
    // Verify nonce.
    if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
    }

    $payment_intent_id = isset( $_POST['payment_intent_id'] ) ? sanitize_text_field( $_POST['payment_intent_id'] ) : '';
    $client_album_id   = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;
    $cart_token        = isset( $_POST['cart_token'] ) ? sanitize_key( $_POST['cart_token'] ) : '';

    if ( ! $payment_intent_id || ! $client_album_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid request.', 'easy-album-orders' ) ) );
    }

    // Verify payment with Stripe.
    $stripe = new EAO_Stripe();
    $intent = $stripe->get_payment_intent( $payment_intent_id );

    if ( is_wp_error( $intent ) ) {
        wp_send_json_error( array( 'message' => $intent->get_error_message() ) );
    }

    // Check payment status.
    if ( 'succeeded' !== $intent->status ) {
        wp_send_json_error( array( 
            'message' => __( 'Payment was not successful. Please try again.', 'easy-album-orders' ),
            'status'  => $intent->status,
        ) );
    }

    // Get cart items and verify they match the payment.
    $cart_items = EAO_Album_Order::get_cart_items( $client_album_id, $cart_token );

    if ( empty( $cart_items ) ) {
        wp_send_json_error( array( 'message' => __( 'Cart items not found.', 'easy-album-orders' ) ) );
    }

    // Verify Payment Intent matches.
    foreach ( $cart_items as $item ) {
        $stored_intent = get_post_meta( $item->ID, '_eao_payment_intent_id', true );
        if ( $stored_intent !== $payment_intent_id ) {
            wp_send_json_error( array( 'message' => __( 'Payment verification failed.', 'easy-album-orders' ) ) );
        }
    }

    // Process checkout (update statuses, save customer info, etc.).
    // This is similar to the existing process_checkout but called after payment.
    $customer_name  = isset( $_POST['customer_name'] ) ? sanitize_text_field( $_POST['customer_name'] ) : '';
    $customer_email = isset( $_POST['customer_email'] ) ? sanitize_email( $_POST['customer_email'] ) : '';
    $customer_phone = isset( $_POST['customer_phone'] ) ? EAO_Helpers::sanitize_phone( $_POST['customer_phone'] ) : '';
    $client_notes   = isset( $_POST['client_notes'] ) ? sanitize_textarea_field( $_POST['client_notes'] ) : '';

    $order_ids = array();

    foreach ( $cart_items as $item ) {
        $order_ids[] = $item->ID;

        // Update status to ordered.
        EAO_Album_Order::update_status( $item->ID, EAO_Album_Order::STATUS_ORDERED );

        // Save customer info.
        update_post_meta( $item->ID, '_eao_customer_name', $customer_name );
        update_post_meta( $item->ID, '_eao_customer_email', $customer_email );
        update_post_meta( $item->ID, '_eao_customer_phone', $customer_phone );
        update_post_meta( $item->ID, '_eao_client_notes', $client_notes );
        update_post_meta( $item->ID, '_eao_order_date', current_time( 'mysql' ) );

        // Save payment info.
        update_post_meta( $item->ID, '_eao_payment_status', 'paid' );
        update_post_meta( $item->ID, '_eao_payment_amount', $intent->amount / 100 );
        update_post_meta( $item->ID, '_eao_stripe_charge_id', $intent->latest_charge );
    }

    /**
     * Fires after checkout with payment is completed.
     *
     * @since 1.1.0
     *
     * @param array  $order_ids        Array of order IDs.
     * @param int    $client_album_id  The client album ID.
     * @param string $payment_intent_id Stripe Payment Intent ID.
     */
    do_action( 'eao_order_checkout_complete', $order_ids, $client_album_id );
    do_action( 'eao_payment_complete', $order_ids, $payment_intent_id );

    wp_send_json_success( array(
        'message'      => __( 'Payment successful! Order submitted.', 'easy-album-orders' ),
        'order_count'  => count( $cart_items ),
        'redirect_url' => add_query_arg( 'order_complete', '1', get_permalink( $client_album_id ) ),
    ) );
}
```

#### 2.2 Update Existing process_checkout

Modify `process_checkout()` to check if Stripe is enabled:

```php
public function process_checkout() {
    // Verify nonce.
    if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
    }

    // Check if payment is required.
    $stripe = new EAO_Stripe();
    if ( $stripe->is_enabled() ) {
        // Calculate cart total.
        $client_album_id = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;
        $cart_token      = isset( $_POST['cart_token'] ) ? sanitize_key( $_POST['cart_token'] ) : '';
        $cart_items      = EAO_Album_Order::get_cart_items( $client_album_id, $cart_token );
        
        $total = 0;
        foreach ( $cart_items as $item ) {
            $total += EAO_Album_Order::calculate_total( $item->ID );
        }

        // If total > 0, require payment flow.
        if ( $total > 0 ) {
            wp_send_json_error( array( 
                'message'         => __( 'Payment required.', 'easy-album-orders' ),
                'payment_required' => true,
            ) );
        }
    }

    // Continue with existing checkout logic for free orders...
    // (existing code)
}
```

---

### Phase 3: Frontend Payment UI

#### 3.1 Load Stripe.js

Update `class-eao-public.php` to enqueue Stripe.js:

```php
/**
 * Register the JavaScript for the public-facing side of the site.
 *
 * @since 1.0.0
 */
public function enqueue_scripts() {
    // Only load on client album pages.
    if ( ! $this->is_client_album_page() ) {
        return;
    }

    // Enqueue Stripe.js.
    $stripe = new EAO_Stripe();
    if ( $stripe->is_enabled() ) {
        wp_enqueue_script(
            'stripe-js',
            'https://js.stripe.com/v3/',
            array(),
            null,
            true
        );
    }

    // ... existing script enqueue ...

    // Add Stripe data to localized script.
    $localize_data = array(
        // ... existing data ...
        'stripe' => array(
            'enabled'        => $stripe->is_enabled(),
            'publishableKey' => $stripe->is_enabled() ? $stripe->get_publishable_key() : '',
        ),
    );

    wp_localize_script( $this->plugin_name . '-public', 'eaoPublic', $localize_data );
}
```

#### 3.2 Update Checkout Modal HTML

Update `single-client-album.php` checkout modal:

```php
<!-- Checkout Modal -->
<div class="eao-modal" id="eao-checkout-modal" style="display: none;">
    <div class="eao-modal__backdrop"></div>
    <div class="eao-modal__container">
        <div class="eao-modal__header">
            <h2 class="eao-modal__title"><?php esc_html_e( 'Complete Your Order', 'easy-album-orders' ); ?></h2>
            <button type="button" class="eao-modal__close" id="eao-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="eao-modal__body">
            <p class="eao-modal__intro"><?php esc_html_e( 'Please provide your contact information to complete your order.', 'easy-album-orders' ); ?></p>
            
            <form id="eao-checkout-form">
                <!-- Step 1: Customer Info -->
                <div class="eao-checkout-step" id="eao-step-info">
                    <div class="eao-field">
                        <label for="eao-customer-name" class="eao-field__label"><?php esc_html_e( 'Your Name', 'easy-album-orders' ); ?> <span class="required">*</span></label>
                        <input type="text" id="eao-customer-name" name="customer_name" class="eao-field__input" required>
                    </div>
                    
                    <div class="eao-field">
                        <label for="eao-customer-email" class="eao-field__label"><?php esc_html_e( 'Email Address', 'easy-album-orders' ); ?> <span class="required">*</span></label>
                        <input type="email" id="eao-customer-email" name="customer_email" class="eao-field__input" required>
                        <p class="eao-field__help"><?php esc_html_e( 'We\'ll send your receipt to this email.', 'easy-album-orders' ); ?></p>
                    </div>
                    
                    <div class="eao-field">
                        <label for="eao-customer-phone" class="eao-field__label"><?php esc_html_e( 'Phone Number', 'easy-album-orders' ); ?> <span class="optional">(<?php esc_html_e( 'optional', 'easy-album-orders' ); ?>)</span></label>
                        <input type="tel" id="eao-customer-phone" name="customer_phone" class="eao-field__input">
                    </div>
                    
                    <div class="eao-field">
                        <label for="eao-client-notes" class="eao-field__label"><?php esc_html_e( 'Order Notes', 'easy-album-orders' ); ?> <span class="optional">(<?php esc_html_e( 'optional', 'easy-album-orders' ); ?>)</span></label>
                        <textarea id="eao-client-notes" name="client_notes" class="eao-field__textarea" rows="3"></textarea>
                    </div>
                </div>

                <!-- Step 2: Payment (Stripe Elements) -->
                <div class="eao-checkout-step eao-checkout-step--payment" id="eao-step-payment" style="display: none;">
                    <h3 class="eao-checkout-step__title"><?php esc_html_e( 'Payment Details', 'easy-album-orders' ); ?></h3>
                    
                    <!-- Stripe Elements container -->
                    <div class="eao-stripe-element" id="eao-card-element">
                        <!-- Stripe Card Element will be mounted here -->
                    </div>
                    
                    <!-- Error display -->
                    <div class="eao-stripe-error" id="eao-card-errors" role="alert"></div>
                    
                    <!-- Secure payment badge -->
                    <div class="eao-payment-secure">
                        <span class="dashicons dashicons-lock"></span>
                        <?php esc_html_e( 'Payments are secure and encrypted', 'easy-album-orders' ); ?>
                    </div>
                </div>
            </form>
        </div>
        <div class="eao-modal__footer">
            <div class="eao-modal__total">
                <span><?php esc_html_e( 'Order Total:', 'easy-album-orders' ); ?></span>
                <span class="eao-modal__total-value" id="eao-modal-total">$0.00</span>
            </div>
            <button type="button" class="eao-btn eao-btn--primary eao-btn--full" id="eao-submit-order-btn">
                <span class="eao-btn-text"><?php esc_html_e( 'Continue to Payment', 'easy-album-orders' ); ?></span>
                <span class="eao-spinner" style="display: none;"></span>
            </button>
        </div>
    </div>
</div>
```

#### 3.3 Update JavaScript Checkout Flow

Add Stripe handling to `public.js`:

```javascript
/**
 * Stripe Payment Handler
 */
const EAOStripe = {
    // Stripe instance.
    stripe: null,

    // Elements instance.
    elements: null,

    // Card element.
    cardElement: null,

    // Payment Intent client secret.
    clientSecret: null,

    // Current step: 'info' or 'payment'.
    currentStep: 'info',

    /**
     * Initialize Stripe if enabled.
     */
    init: function() {
        if (!eaoPublic.stripe || !eaoPublic.stripe.enabled || !eaoPublic.stripe.publishableKey) {
            return false;
        }

        this.stripe = Stripe(eaoPublic.stripe.publishableKey);
        this.elements = this.stripe.elements();

        // Create card element with custom styling.
        const style = {
            base: {
                color: '#333',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#dc3545',
                iconColor: '#dc3545'
            }
        };

        this.cardElement = this.elements.create('card', { style: style });

        // Handle real-time validation errors.
        this.cardElement.on('change', function(event) {
            const displayError = document.getElementById('eao-card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        return true;
    },

    /**
     * Mount card element to DOM.
     */
    mountCard: function() {
        const container = document.getElementById('eao-card-element');
        if (container && this.cardElement) {
            this.cardElement.mount('#eao-card-element');
        }
    },

    /**
     * Create Payment Intent.
     *
     * @param {Object} customerData Customer info.
     * @return {Promise}
     */
    createPaymentIntent: function(customerData) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: eaoPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eao_create_payment_intent',
                    nonce: eaoPublic.nonce,
                    client_album_id: eaoPublic.clientAlbumId,
                    cart_token: EAOPublic.getCartToken(),
                    customer_name: customerData.name,
                    customer_email: customerData.email
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.data.message));
                    }
                },
                error: function() {
                    reject(new Error(eaoPublic.i18n?.errorOccurred || 'An error occurred'));
                }
            });
        });
    },

    /**
     * Confirm the payment with Stripe.
     *
     * @param {Object} customerData Customer info.
     * @return {Promise}
     */
    confirmPayment: function(customerData) {
        const self = this;

        return this.stripe.confirmCardPayment(this.clientSecret, {
            payment_method: {
                card: this.cardElement,
                billing_details: {
                    name: customerData.name,
                    email: customerData.email
                }
            }
        }).then(function(result) {
            if (result.error) {
                throw new Error(result.error.message);
            }
            return result.paymentIntent;
        });
    },

    /**
     * Complete the checkout after successful payment.
     *
     * @param {string} paymentIntentId Payment Intent ID.
     * @param {Object} customerData    Customer info.
     * @return {Promise}
     */
    completeCheckout: function(paymentIntentId, customerData) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: eaoPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eao_confirm_payment',
                    nonce: eaoPublic.nonce,
                    payment_intent_id: paymentIntentId,
                    client_album_id: eaoPublic.clientAlbumId,
                    cart_token: EAOPublic.getCartToken(),
                    customer_name: customerData.name,
                    customer_email: customerData.email,
                    customer_phone: customerData.phone,
                    client_notes: customerData.notes
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.data.message));
                    }
                },
                error: function() {
                    reject(new Error(eaoPublic.i18n?.errorOccurred || 'An error occurred'));
                }
            });
        });
    },

    /**
     * Reset for new checkout.
     */
    reset: function() {
        this.clientSecret = null;
        this.currentStep = 'info';
        if (this.cardElement) {
            this.cardElement.clear();
        }
    }
};

// Update EAOPublic.bindCheckout() to integrate Stripe:
bindCheckout: function() {
    const self = this;

    // Initialize Stripe.
    const stripeEnabled = EAOStripe.init();

    // Open checkout modal.
    $('#eao-checkout-btn').on('click', function() {
        self.openCheckoutModal();
        
        // Mount Stripe card element if enabled.
        if (stripeEnabled) {
            setTimeout(function() {
                EAOStripe.mountCard();
            }, 200);
        }
    });

    // Close modal handlers.
    $('#eao-modal-close, .eao-modal__backdrop').on('click', function() {
        self.closeCheckoutModal();
        EAOStripe.reset();
    });

    // Submit order.
    $('#eao-submit-order-btn').on('click', function() {
        if (stripeEnabled) {
            self.handleStripeCheckout();
        } else {
            self.submitCheckout();
        }
    });
},

/**
 * Handle Stripe checkout flow.
 */
handleStripeCheckout: function() {
    const self = this;
    const $btn = $('#eao-submit-order-btn');
    const $btnText = $btn.find('.eao-btn-text');
    const $spinner = $btn.find('.eao-spinner');

    // Get customer data.
    const customerData = {
        name: $('#eao-customer-name').val().trim(),
        email: $('#eao-customer-email').val().trim(),
        phone: $('#eao-customer-phone').val().trim(),
        notes: $('#eao-client-notes').val().trim()
    };

    // Validate customer info.
    if (!customerData.name) {
        self.showMessage('error', eaoPublic.i18n?.enterCustomerName || 'Please enter your name.');
        $('#eao-customer-name').focus();
        return;
    }

    if (!customerData.email || !self.isValidEmail(customerData.email)) {
        self.showMessage('error', eaoPublic.i18n?.invalidEmail || 'Please enter a valid email.');
        $('#eao-customer-email').focus();
        return;
    }

    // Step 1: Show customer info, proceed to payment.
    if (EAOStripe.currentStep === 'info') {
        $btn.prop('disabled', true);
        $btnText.text(eaoPublic.i18n?.processing || 'Processing...');
        $spinner.show();

        // Create Payment Intent.
        EAOStripe.createPaymentIntent(customerData)
            .then(function(data) {
                if (data.skip_payment) {
                    // No payment required, complete checkout.
                    return self.submitCheckout();
                }

                EAOStripe.clientSecret = data.client_secret;
                EAOStripe.currentStep = 'payment';

                // Show payment step.
                $('#eao-step-info').slideUp(200);
                $('#eao-step-payment').slideDown(200);
                $btnText.text(eaoPublic.i18n?.payNow || 'Pay Now');

                $btn.prop('disabled', false);
                $spinner.hide();

                // Focus card element.
                EAOStripe.cardElement.focus();
            })
            .catch(function(error) {
                self.showMessage('error', error.message);
                $btn.prop('disabled', false);
                $btnText.text(eaoPublic.i18n?.continueToPayment || 'Continue to Payment');
                $spinner.hide();
            });

        return;
    }

    // Step 2: Process payment.
    if (EAOStripe.currentStep === 'payment') {
        $btn.prop('disabled', true);
        $btnText.text(eaoPublic.i18n?.processing || 'Processing...');
        $spinner.show();

        EAOStripe.confirmPayment(customerData)
            .then(function(paymentIntent) {
                // Payment successful, complete checkout.
                return EAOStripe.completeCheckout(paymentIntent.id, customerData);
            })
            .then(function(data) {
                // Redirect to confirmation.
                window.location.href = data.redirect_url;
            })
            .catch(function(error) {
                // Show error.
                $('#eao-card-errors').textContent = error.message;
                self.showMessage('error', error.message);
                
                $btn.prop('disabled', false);
                $btnText.text(eaoPublic.i18n?.payNow || 'Pay Now');
                $spinner.hide();
            });
    }
},

/**
 * Simple email validation.
 */
isValidEmail: function(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
```

#### 3.4 Add Stripe Elements CSS

Add to `public.css`:

```css
/* ==========================================================================
   Stripe Payment Elements
   ========================================================================== */

.eao-checkout-step--payment {
    padding-top: 20px;
    border-top: 1px solid #eee;
    margin-top: 20px;
}

.eao-checkout-step__title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
    color: #333;
}

.eao-stripe-element {
    padding: 12px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.eao-stripe-element:focus-within {
    border-color: var(--eao-primary-color, #2d5a27);
    box-shadow: 0 0 0 3px rgba(45, 90, 39, 0.1);
}

.eao-stripe-error {
    color: #dc3545;
    font-size: 13px;
    margin-top: 8px;
    min-height: 20px;
}

.eao-payment-secure {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 12px;
    font-size: 12px;
    color: #666;
}

.eao-payment-secure .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: #28a745;
}

/* Payment processing overlay */
.eao-payment-processing {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.eao-payment-processing__text {
    margin-top: 16px;
    font-weight: 500;
    color: #333;
}
```

---

### Phase 4: Webhook Handler

#### 4.1 Create Webhook Handler Class

**File: `includes/public/class-eao-stripe-webhook.php`**

```php
<?php
/**
 * Stripe Webhook Handler.
 *
 * Handles incoming webhook events from Stripe.
 *
 * @package Easy_Album_Orders
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Stripe Webhook handler class.
 *
 * @since 1.1.0
 */
class EAO_Stripe_Webhook {

    /**
     * Constructor.
     *
     * @since 1.1.0
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_webhook_endpoint' ) );
    }

    /**
     * Register the webhook REST endpoint.
     *
     * @since 1.1.0
     */
    public function register_webhook_endpoint() {
        register_rest_route( 'eao/v1', '/stripe-webhook', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_webhook' ),
            'permission_callback' => '__return_true', // Stripe validates via signature.
        ) );
    }

    /**
     * Handle incoming webhook.
     *
     * Endpoint URL: /wp-json/eao/v1/stripe-webhook
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function handle_webhook( $request ) {
        $payload   = $request->get_body();
        $signature = $request->get_header( 'stripe-signature' );

        if ( ! $signature ) {
            return new \WP_REST_Response( array( 'error' => 'Missing signature' ), 400 );
        }

        $stripe = new EAO_Stripe();
        $event  = $stripe->verify_webhook( $payload, $signature );

        if ( is_wp_error( $event ) ) {
            return new \WP_REST_Response( array( 'error' => $event->get_error_message() ), 400 );
        }

        // Handle the event.
        switch ( $event->type ) {
            case 'payment_intent.succeeded':
                $this->handle_payment_succeeded( $event->data->object );
                break;

            case 'payment_intent.payment_failed':
                $this->handle_payment_failed( $event->data->object );
                break;

            case 'charge.refunded':
                $this->handle_refund( $event->data->object );
                break;

            default:
                // Unhandled event type.
                break;
        }

        return new \WP_REST_Response( array( 'received' => true ), 200 );
    }

    /**
     * Handle successful payment.
     *
     * @since 1.1.0
     *
     * @param object $payment_intent Stripe PaymentIntent object.
     */
    private function handle_payment_succeeded( $payment_intent ) {
        $order_ids = isset( $payment_intent->metadata->order_ids ) 
            ? explode( ',', $payment_intent->metadata->order_ids ) 
            : array();

        foreach ( $order_ids as $order_id ) {
            $order_id = absint( $order_id );
            if ( ! $order_id ) {
                continue;
            }

            // Verify this order belongs to this payment intent.
            $stored_intent = get_post_meta( $order_id, '_eao_payment_intent_id', true );
            if ( $stored_intent !== $payment_intent->id ) {
                continue;
            }

            // Update payment status.
            update_post_meta( $order_id, '_eao_payment_status', 'paid' );
            update_post_meta( $order_id, '_eao_payment_amount', $payment_intent->amount / 100 );
            update_post_meta( $order_id, '_eao_stripe_charge_id', $payment_intent->latest_charge );

            // Ensure order status is "ordered".
            $current_status = EAO_Album_Order::get_order_status( $order_id );
            if ( EAO_Album_Order::STATUS_SUBMITTED === $current_status ) {
                EAO_Album_Order::update_status( $order_id, EAO_Album_Order::STATUS_ORDERED );
            }
        }

        /**
         * Fires after webhook confirms payment success.
         *
         * @since 1.1.0
         *
         * @param array  $order_ids      Array of order IDs.
         * @param object $payment_intent Stripe PaymentIntent object.
         */
        do_action( 'eao_webhook_payment_succeeded', $order_ids, $payment_intent );
    }

    /**
     * Handle failed payment.
     *
     * @since 1.1.0
     *
     * @param object $payment_intent Stripe PaymentIntent object.
     */
    private function handle_payment_failed( $payment_intent ) {
        $order_ids = isset( $payment_intent->metadata->order_ids ) 
            ? explode( ',', $payment_intent->metadata->order_ids ) 
            : array();

        foreach ( $order_ids as $order_id ) {
            $order_id = absint( $order_id );
            if ( ! $order_id ) {
                continue;
            }

            update_post_meta( $order_id, '_eao_payment_status', 'failed' );
            update_post_meta( 
                $order_id, 
                '_eao_payment_error', 
                isset( $payment_intent->last_payment_error->message ) 
                    ? $payment_intent->last_payment_error->message 
                    : 'Unknown error'
            );
        }

        /**
         * Fires after webhook reports payment failure.
         *
         * @since 1.1.0
         *
         * @param array  $order_ids      Array of order IDs.
         * @param object $payment_intent Stripe PaymentIntent object.
         */
        do_action( 'eao_webhook_payment_failed', $order_ids, $payment_intent );
    }

    /**
     * Handle refund.
     *
     * @since 1.1.0
     *
     * @param object $charge Stripe Charge object.
     */
    private function handle_refund( $charge ) {
        // Find orders by charge ID.
        $orders = get_posts( array(
            'post_type'      => 'album_order',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_eao_stripe_charge_id',
                    'value' => $charge->id,
                ),
            ),
        ) );

        foreach ( $orders as $order ) {
            $refund_amount = 0;
            if ( ! empty( $charge->refunds->data ) ) {
                foreach ( $charge->refunds->data as $refund ) {
                    $refund_amount += $refund->amount;
                }
            }

            update_post_meta( $order->ID, '_eao_payment_status', 'refunded' );
            update_post_meta( $order->ID, '_eao_refund_amount', $refund_amount / 100 );
        }

        /**
         * Fires after webhook reports a refund.
         *
         * @since 1.1.0
         *
         * @param array  $orders Order posts.
         * @param object $charge Stripe Charge object.
         */
        do_action( 'eao_webhook_refund', $orders, $charge );
    }
}
```

#### 4.2 Stripe Dashboard Webhook Setup

Configure webhook in Stripe Dashboard:

1. Go to **Developers > Webhooks**
2. Click **Add endpoint**
3. Enter endpoint URL: `https://yoursite.com/wp-json/eao/v1/stripe-webhook`
4. Select events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`
5. Copy the **Signing secret** (starts with `whsec_`)
6. Add to plugin settings

---

### Phase 5: Order Status Integration

#### 5.1 Add Payment Status to Admin

Update `class-eao-album-order-meta.php` to display payment information:

```php
/**
 * Render payment information meta box.
 *
 * @since 1.1.0
 *
 * @param WP_Post $post Post object.
 */
public function render_payment_meta_box( $post ) {
    $payment_status  = get_post_meta( $post->ID, '_eao_payment_status', true );
    $payment_amount  = get_post_meta( $post->ID, '_eao_payment_amount', true );
    $payment_intent  = get_post_meta( $post->ID, '_eao_payment_intent_id', true );
    $charge_id       = get_post_meta( $post->ID, '_eao_stripe_charge_id', true );
    $refund_amount   = get_post_meta( $post->ID, '_eao_refund_amount', true );

    $status_labels = array(
        'paid'     => __( 'Paid', 'easy-album-orders' ),
        'failed'   => __( 'Failed', 'easy-album-orders' ),
        'refunded' => __( 'Refunded', 'easy-album-orders' ),
        'pending'  => __( 'Pending', 'easy-album-orders' ),
    );

    $status_label = isset( $status_labels[ $payment_status ] ) 
        ? $status_labels[ $payment_status ] 
        : __( 'No Payment', 'easy-album-orders' );
    ?>
    <table class="eao-meta-table">
        <tr>
            <th><?php esc_html_e( 'Payment Status', 'easy-album-orders' ); ?></th>
            <td>
                <span class="eao-payment-badge eao-payment-badge--<?php echo esc_attr( $payment_status ?: 'none' ); ?>">
                    <?php echo esc_html( $status_label ); ?>
                </span>
            </td>
        </tr>
        <?php if ( $payment_amount ) : ?>
            <tr>
                <th><?php esc_html_e( 'Amount Paid', 'easy-album-orders' ); ?></th>
                <td><?php echo esc_html( eao_format_price( $payment_amount ) ); ?></td>
            </tr>
        <?php endif; ?>
        <?php if ( $refund_amount ) : ?>
            <tr>
                <th><?php esc_html_e( 'Refunded', 'easy-album-orders' ); ?></th>
                <td><?php echo esc_html( eao_format_price( $refund_amount ) ); ?></td>
            </tr>
        <?php endif; ?>
        <?php if ( $charge_id ) : ?>
            <tr>
                <th><?php esc_html_e( 'Stripe Charge', 'easy-album-orders' ); ?></th>
                <td>
                    <a href="https://dashboard.stripe.com/payments/<?php echo esc_attr( $charge_id ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html( $charge_id ); ?> ↗
                    </a>
                </td>
            </tr>
        <?php endif; ?>
    </table>
    <?php
}
```

#### 5.2 Add Payment Column to Order List

```php
/**
 * Add payment status column.
 *
 * @since 1.1.0
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
public function add_payment_column( $columns ) {
    $new_columns = array();
    
    foreach ( $columns as $key => $label ) {
        $new_columns[ $key ] = $label;
        
        // Add payment column after status.
        if ( 'eao_status' === $key ) {
            $new_columns['eao_payment'] = __( 'Payment', 'easy-album-orders' );
        }
    }
    
    return $new_columns;
}

/**
 * Render payment column content.
 *
 * @since 1.1.0
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
public function render_payment_column( $column, $post_id ) {
    if ( 'eao_payment' !== $column ) {
        return;
    }

    $status = get_post_meta( $post_id, '_eao_payment_status', true );
    $amount = get_post_meta( $post_id, '_eao_payment_amount', true );

    if ( 'paid' === $status ) {
        echo '<span class="eao-payment-badge eao-payment-badge--paid">';
        echo esc_html( eao_format_price( $amount ) );
        echo '</span>';
    } elseif ( 'refunded' === $status ) {
        echo '<span class="eao-payment-badge eao-payment-badge--refunded">';
        esc_html_e( 'Refunded', 'easy-album-orders' );
        echo '</span>';
    } elseif ( 'failed' === $status ) {
        echo '<span class="eao-payment-badge eao-payment-badge--failed">';
        esc_html_e( 'Failed', 'easy-album-orders' );
        echo '</span>';
    } else {
        echo '<span class="eao-payment-badge eao-payment-badge--none">—</span>';
    }
}
```

---

## Security Considerations

### PCI Compliance

- **Never log or store card details** on your server
- Card data goes directly from browser to Stripe via Stripe.js
- Only store Payment Intent IDs and charge references

### API Key Security

- Store secret keys securely in WordPress options
- Never expose secret keys to front-end
- Use environment-specific keys (test vs. live)
- Restrict API key permissions in Stripe Dashboard

### Webhook Security

- Always verify webhook signatures
- Use HTTPS endpoint only
- Implement idempotency (check if event already processed)

### Data Validation

```php
// Always sanitize incoming data.
$amount = absint( $amount );
$email  = sanitize_email( $email );

// Verify Payment Intent belongs to the order.
$stored_intent = get_post_meta( $order_id, '_eao_payment_intent_id', true );
if ( $stored_intent !== $payment_intent_id ) {
    wp_send_json_error( 'Invalid payment' );
}
```

---

## Testing

### Test Mode Setup

1. Use test API keys (Dashboard > Developers > API Keys)
2. Test card numbers:
   - **Successful**: `4242 4242 4242 4242`
   - **Requires Auth**: `4000 0025 0000 3155`
   - **Declined**: `4000 0000 0000 0002`
3. Any future expiry date, any 3-digit CVC

### Testing Checklist

- [ ] Successful payment flow
- [ ] Failed payment handling
- [ ] 3D Secure authentication
- [ ] Webhook delivery
- [ ] Order status updates
- [ ] Email receipts
- [ ] Admin payment display
- [ ] Zero-amount orders (fully credited)
- [ ] Stripe disabled mode

### Stripe CLI for Local Testing

```bash
# Install Stripe CLI
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Forward webhooks to local
stripe listen --forward-to localhost/wp-json/eao/v1/stripe-webhook

# Trigger test events
stripe trigger payment_intent.succeeded
```

---

## Error Handling

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| `card_declined` | Card declined by bank | Ask customer to use different card |
| `expired_card` | Card is expired | Ask customer to update card |
| `incorrect_cvc` | Wrong CVC entered | Have customer re-enter |
| `processing_error` | Issue with Stripe | Retry in a moment |
| `rate_limit` | Too many requests | Implement backoff |

### User-Friendly Messages

```javascript
const errorMessages = {
    'card_declined': 'Your card was declined. Please try a different card.',
    'expired_card': 'Your card has expired. Please use a different card.',
    'incorrect_cvc': 'The security code is incorrect. Please check and try again.',
    'processing_error': 'An error occurred processing your payment. Please try again.',
    'default': 'Something went wrong. Please try again or contact support.'
};
```

---

## Architecture Decision: Direct API Keys vs Stripe Connect

### Current Approach: Direct API Keys ✅

Each photographer enters their own Stripe API keys. This is the right choice because:

| Benefit | Description |
|---------|-------------|
| **Simplicity** | No OAuth flow, just copy/paste API keys |
| **Direct payments** | Money goes directly to photographer |
| **No platform fees** | Only standard Stripe fees (2.9% + $0.30) |
| **Full control** | Photographer manages their own Stripe account |
| **Privacy** | You (plugin seller) never see transaction data |
| **Independence** | Works offline from any central service |

### Alternative: Stripe Connect (Not Recommended for This Use Case)

Stripe Connect would be needed if:
- You wanted to run a **SaaS platform** (one website, many photographers)
- You wanted to **take a commission** from each sale
- Photographers **don't have their own websites**

**Why we don't use Stripe Connect:**
- Adds complexity (OAuth flow required)
- Requires a platform fee or Connect fees
- Creates dependency on your central service
- Unnecessary for standard WordPress plugin model

### If You Ever Want to Add Stripe Connect

If you decide to offer a hosted SaaS version in the future, you would:

1. Register as a Stripe Connect platform
2. Implement OAuth flow for photographers to connect accounts
3. Use `Stripe-Account` header for connected account operations
4. Handle platform fees and payouts

This would be a separate product/service, not part of the WordPress plugin.

---

## Future Enhancements

### Potential Features

1. **Multiple Payment Methods**
   - Apple Pay / Google Pay
   - Bank transfers (ACH)
   - Buy Now, Pay Later (Klarna, Afterpay)

2. **Recurring Payments**
   - Payment plans
   - Subscriptions for volume clients

3. **Refund Management**
   - Admin refund button
   - Partial refunds

4. **Invoice Generation**
   - PDF invoices with Stripe integration
   - Email invoice to customer

5. **Analytics**
   - Revenue reporting
   - Payment method breakdown
   - Failed payment analysis

6. **Multi-Currency**
   - Automatic currency conversion
   - Customer's local currency

---

## Summary Checklist

### Implementation Order

1. [ ] Install Stripe PHP SDK
2. [ ] Create `class-eao-stripe.php`
3. [ ] Add Stripe settings tab to admin
4. [ ] Update AJAX handler with payment endpoints
5. [ ] Create webhook handler
6. [ ] Update checkout modal HTML
7. [ ] Update public.js with Stripe flow
8. [ ] Add payment styles
9. [ ] Add payment meta box to admin
10. [ ] Add payment column to order list
11. [ ] Configure webhook in Stripe Dashboard
12. [ ] Test in test mode
13. [ ] Switch to live mode
14. [ ] Document for users

---

*Document Version: 1.0.0*
*Last Updated: December 2024*

