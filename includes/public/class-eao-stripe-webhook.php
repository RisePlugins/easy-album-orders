<?php
/**
 * Stripe Webhook Handler.
 *
 * Handles incoming webhook events from Stripe for payment
 * confirmations, failures, and refunds.
 *
 * @package Easy_Album_Orders
 * @since   1.1.0
 */

// If not WordPress, exit.
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
		register_rest_route(
			'eao/v1',
			'/stripe-webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true', // Stripe validates via signature.
			)
		);
	}

	/**
	 * Handle incoming webhook.
	 *
	 * Endpoint URL: /wp-json/eao/v1/stripe-webhook
	 *
	 * @since 1.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function handle_webhook( $request ) {
		$payload   = $request->get_body();
		$signature = $request->get_header( 'stripe-signature' );

		// Validate signature is present.
		if ( ! $signature ) {
			$this->log_webhook_error( 'Missing Stripe signature header' );
			return new WP_REST_Response( array( 'error' => 'Missing signature' ), 400 );
		}

		// Initialize Stripe and verify webhook.
		$stripe = new EAO_Stripe();
		$event  = $stripe->verify_webhook( $payload, $signature );

		if ( is_wp_error( $event ) ) {
			$this->log_webhook_error( 'Signature verification failed: ' . $event->get_error_message() );
			return new WP_REST_Response( array( 'error' => $event->get_error_message() ), 400 );
		}

		// Log the event type.
		$this->log_webhook_event( $event->type, $event->id );

		// Handle the event based on type.
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
				// Unhandled event type - still return 200 to acknowledge receipt.
				break;
		}

		return new WP_REST_Response( array( 'received' => true ), 200 );
	}

	/**
	 * Handle successful payment.
	 *
	 * Called when Stripe confirms the payment was successful.
	 * Updates order payment status and metadata.
	 *
	 * @since 1.1.0
	 *
	 * @param object $payment_intent Stripe PaymentIntent object.
	 */
	private function handle_payment_succeeded( $payment_intent ) {
		$order_ids = $this->get_order_ids_from_intent( $payment_intent );

		if ( empty( $order_ids ) ) {
			$this->log_webhook_error( 'No order IDs found in payment_intent.succeeded: ' . $payment_intent->id );
			return;
		}

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

			// Check if already processed (idempotency).
			$current_payment_status = get_post_meta( $order_id, '_eao_payment_status', true );
			if ( 'paid' === $current_payment_status ) {
				continue; // Already processed.
			}

			// Update payment status.
			update_post_meta( $order_id, '_eao_payment_status', 'paid' );
			update_post_meta( $order_id, '_eao_payment_amount', $payment_intent->amount / 100 );
			update_post_meta( $order_id, '_eao_stripe_charge_id', $payment_intent->latest_charge );
			update_post_meta( $order_id, '_eao_payment_date', current_time( 'mysql' ) );

			// Ensure order status is "ordered" (in case frontend callback failed).
			$current_status = EAO_Album_Order::get_order_status( $order_id );
			if ( EAO_Album_Order::STATUS_SUBMITTED === $current_status ) {
				EAO_Album_Order::update_status( $order_id, EAO_Album_Order::STATUS_ORDERED );

				// Save customer info from metadata if not already saved.
				$this->maybe_save_customer_info( $order_id, $payment_intent );
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
	 * Called when Stripe reports the payment failed.
	 * Updates order payment status with error details.
	 *
	 * @since 1.1.0
	 *
	 * @param object $payment_intent Stripe PaymentIntent object.
	 */
	private function handle_payment_failed( $payment_intent ) {
		$order_ids = $this->get_order_ids_from_intent( $payment_intent );

		if ( empty( $order_ids ) ) {
			return;
		}

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

			// Get error message.
			$error_message = isset( $payment_intent->last_payment_error->message )
				? $payment_intent->last_payment_error->message
				: __( 'Unknown payment error', 'easy-album-orders' );

			// Update payment status.
			update_post_meta( $order_id, '_eao_payment_status', 'failed' );
			update_post_meta( $order_id, '_eao_payment_error', $error_message );
			update_post_meta( $order_id, '_eao_payment_failed_date', current_time( 'mysql' ) );
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
	 * Called when Stripe processes a refund for a charge.
	 * Updates order payment status to reflect the refund.
	 *
	 * @since 1.1.0
	 *
	 * @param object $charge Stripe Charge object.
	 */
	private function handle_refund( $charge ) {
		// Find orders by charge ID.
		$orders = get_posts(
			array(
				'post_type'      => 'album_order',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'   => '_eao_stripe_charge_id',
						'value' => $charge->id,
					),
				),
			)
		);

		if ( empty( $orders ) ) {
			$this->log_webhook_error( 'No orders found for charge.refunded: ' . $charge->id );
			return;
		}

		foreach ( $orders as $order ) {
			// Calculate total refund amount.
			$refund_amount = 0;
			if ( ! empty( $charge->refunds->data ) ) {
				foreach ( $charge->refunds->data as $refund ) {
					$refund_amount += $refund->amount;
				}
			}

			// Determine if fully refunded or partially refunded.
			$original_amount = $charge->amount;
			$is_full_refund  = ( $refund_amount >= $original_amount );

			// Update payment status.
			if ( $is_full_refund ) {
				update_post_meta( $order->ID, '_eao_payment_status', 'refunded' );
			} else {
				update_post_meta( $order->ID, '_eao_payment_status', 'partial_refund' );
			}

			update_post_meta( $order->ID, '_eao_refund_amount', $refund_amount / 100 );
			update_post_meta( $order->ID, '_eao_refund_date', current_time( 'mysql' ) );
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

	/**
	 * Extract order IDs from Payment Intent metadata.
	 *
	 * @since 1.1.0
	 *
	 * @param object $payment_intent Stripe PaymentIntent object.
	 * @return array Array of order IDs.
	 */
	private function get_order_ids_from_intent( $payment_intent ) {
		if ( ! isset( $payment_intent->metadata->order_ids ) || empty( $payment_intent->metadata->order_ids ) ) {
			return array();
		}

		return array_filter( array_map( 'absint', explode( ',', $payment_intent->metadata->order_ids ) ) );
	}

	/**
	 * Maybe save customer info from Payment Intent metadata.
	 *
	 * This is a fallback in case the frontend callback didn't complete.
	 *
	 * @since 1.1.0
	 *
	 * @param int    $order_id       Order ID.
	 * @param object $payment_intent Stripe PaymentIntent object.
	 */
	private function maybe_save_customer_info( $order_id, $payment_intent ) {
		// Only save if customer info isn't already set.
		$existing_name = get_post_meta( $order_id, '_eao_customer_name', true );
		if ( ! empty( $existing_name ) ) {
			return;
		}

		// Get customer info from metadata.
		$metadata = $payment_intent->metadata;

		if ( ! empty( $metadata->customer_name ) ) {
			update_post_meta( $order_id, '_eao_customer_name', sanitize_text_field( $metadata->customer_name ) );
		}

		if ( ! empty( $metadata->customer_email ) ) {
			update_post_meta( $order_id, '_eao_customer_email', sanitize_email( $metadata->customer_email ) );
		}

		// Set order date if not already set.
		$existing_date = get_post_meta( $order_id, '_eao_order_date', true );
		if ( empty( $existing_date ) ) {
			update_post_meta( $order_id, '_eao_order_date', current_time( 'mysql' ) );
		}

		// Trigger order completion hook if not already triggered.
		$client_album_id = get_post_meta( $order_id, '_eao_client_album_id', true );
		if ( $client_album_id ) {
			/**
			 * Fires after webhook completes an order (fallback).
			 *
			 * @since 1.1.0
			 *
			 * @param array $order_ids       Array containing the order ID.
			 * @param int   $client_album_id The client album ID.
			 */
			do_action( 'eao_order_checkout_complete', array( $order_id ), $client_album_id );
		}
	}

	/**
	 * Log webhook event for debugging.
	 *
	 * @since 1.1.0
	 *
	 * @param string $event_type Event type.
	 * @param string $event_id   Event ID.
	 */
	private function log_webhook_event( $event_type, $event_id ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[EAO Stripe Webhook] Event: %s, ID: %s', $event_type, $event_id ) );
		}
	}

	/**
	 * Log webhook error for debugging.
	 *
	 * @since 1.1.0
	 *
	 * @param string $message Error message.
	 */
	private function log_webhook_error( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[EAO Stripe Webhook] Error: %s', $message ) );
		}
	}
}

