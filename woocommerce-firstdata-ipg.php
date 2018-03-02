<?php
/**
 * Plugin Name: WooCommerce FirstData IPG Payment Gateway
 * Plugin URI: https://www.zimoo354.mx/
 * Description: This plugin allows the user to pay with Credit/Debit card
 * through FirstData IPG
 * Author: Carlos Isaias Ruiz lara
 * Author URI: https://www.zimoo354.mx/
 * Version: 0.1
 * Text Domain: wc-firstdata-ipg
 *
 * Copyright: (c) 2017-2018 zimoo354 and WooCommerce
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WP-Gateway-FirstData-IPG
 * @author    zimoo354
 * @category  Admin
 * @copyright Copyright: (c) 2017-2018 zimoo354 and WooCommerce
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * 
 */

defined( 'ABSPATH' ) or exit;


// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + FirstData IPG gateway
 */
function wc_firstdata_ipg_add_to_gateways( $gateways ) {
	$gateways[] = 'WP_Gateway_FirstData_IPG';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_firstdata_ipg_add_to_gateways' );




/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_firstdata_ipg_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=firstdata_ipg' ) . '">' . __( 'Configure', 'wc-firstdata-ipg' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_firstdata_ipg_plugin_links' );


/**
 * FirstData IPG Gateway
 *
 * Provides access to FirstData IPG Gateway from Woocommerce
 *
 * @class 		WP_Gateway_FirstData_IPG
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		SkyVerge
 */
add_action( 'plugins_loaded', 'wc_firstdata_ipg_init', 11 );

function wc_firstdata_ipg_init() {

	class WP_Gateway_FirstData_IPG extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {

			$this->id                 = 'firstdata_ipg';
			$this->has_fields         = true;
			$this->method_title       = __( 'FirstData IPG', 'wc-firstdata-ipg' );
			$this->method_description = __( 'This plugin allows the user to pay with Credit/Debit card. Orders are marked as "on-hold" when received.', 'wc-firstdata-ipg' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );

			$this->store_id  = $this->get_option( 'store_id' );
			$this->sharedsecret  = $this->get_option( 'shared_secret' );
			$this->timezone = $this->get_option( 'timezone' );
			$this->currency = $this->get_option( 'currency' );
			$this->is_sandbox = $this->get_option( 'is_sandbox' );


			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'payment_form' ) );

			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

			// Admin Panel
		    // add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );
		}



		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {

			$this->form_fields = apply_filters( 'wc_firstdata_ipg_form_fields', array(

				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'wc-firstdata-ipg' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable FirstData IPG Gateway', 'wc-firstdata-ipg' ),
					'default' => 'yes'
				),
				
				'is_sandbox' => array(
					'title'   => __( 'Is Sandbox', 'wc-firstdata-ipg' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Sandbox Mode', 'wc-firstdata-ipg' ),
					'default' => 'yes'
				),
				
				'title' => array(
					'title'       => __( 'Title', 'wc-firstdata-ipg' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-firstdata-ipg' ),
					'default'     => __( 'FirstData IPG Payment', 'wc-firstdata-ipg' ),
					'desc_tip'    => true,
				),
				
				'description' => array(
					'title'       => __( 'Description', 'wc-firstdata-ipg' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-firstdata-ipg' ),
					'default'     => __( 'You will be able to pay in the next step', 'wc-firstdata-ipg' ),
					'desc_tip'    => true,
				),
				
				'instructions' => array(
					'title'       => __( 'Instructions', 'wc-firstdata-ipg' ),
					'type'        => 'textarea',
					'description' => __( 'Press "PAY" button and you will be redirected to the FirstData IPG page', 'wc-firstdata-ipg' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'store_id' => array(
					'title'       => __( 'Store ID', 'wc-firstdata-ipg' ),
					'type'        => 'text',
					'description' => __( 'FirstData will give you this info', 'wc-firstdata-ipg' ),
					'default'     => __( '3910010', 'wc-firstdata-ipg' ),
					'desc_tip'    => true,
				),
				'shared_secret' => array(
					'title'       => __( 'Shared Secret', 'wc-firstdata-ipg' ),
					'type'        => 'text',
					'description' => __( 'FirstData will give you this info', 'wc-firstdata-ipg' ),
					'default'     => __( 'sharedsecret', 'wc-firstdata-ipg' ),
					'desc_tip'    => true,
				),
				'timezone' => array(
					'title'       => __( 'TimeZone', 'wc-firstdata-ipg' ),
					'type'        => 'text',
					'description' => __( 'Set your time zone' ),
					'default'     => __( 'America/Mexico_City', 'wc-firstdata-ipg' ),
					'desc_tip'    => true,
				),
				'timezone' => array(
					'title'       => __( 'TimeZone', 'wc-firstdata-ipg' ),
					'type'        => 'text',
					'description' => __( 'Set your time zone' ),
					'default'     => __( 'America/Mexico_City', 'wc-firstdata-ipg' ),
					'desc_tip'    => true,
				),
				'currency' => array(
					'title'       => __( 'Currency Code', 'wc-firstdata-ipg' ),
					'type'        => 'text',
					'description' => __( 'Set your currency code' ),
					'default'     => __( '484', 'wc-firstdata-ipg' ),
					'desc_tip'    => true,
				),


			) );
		}



		/**
		 * Output for the order received page.
		 */

		public function payment_form($order_id) {
			// Getting the order
			$order = wc_get_order( $order_id );

			if ($this->is_sandbox)
				$ipg_endpoint = "https://test.ipg-online.com/connect/gateway/processing";
			else
				$ipg_endpoint = "https://ipg-online.com/connect/gateway/processing";

			
			if (isset($_GET['ipg_stat'])) {
				if ($_GET['ipg_stat'] == 1) {
					$order->update_status('processing', 'IPG Success');
					$mailer = WC()->mailer();
					$mails = $mailer->get_emails();
					if ( ! empty( $mails ) ) {
					    foreach ( $mails as $mail ) {
					        if ( $mail->id == 'customer_completed_order' ) {
					           $mail->trigger( $order->id );
					        }
					     }
					}
				} else {
					$order->update_status('pending', 'IPG Failed');
				}
			}


			if ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) :
				?>
				<h1>Muchas gracias por tu pago! Tu orden está siendo procesada</h1>
				<?php 
			else:

				$ipg_return_url = get_permalink( wc_get_page_id( 'checkout' ) ) . 'order-received/' . $order_id . '/?key=' . $order->order_key . '&ipg_stat=';
				
				// Extracting the total amount to pay in the format that FirstData requires
				$amount = number_format($order->get_total(), 2, '.', '');

				$FirstData_form_fields = array(
					// Functional Fields
					'ponumber'			 => $order_id,
					'txntype'            => "sale",
					'timezone'           => $this->timezone,
					'txndatetime'        => $this->getDateTime(),
					'hash_algorithm'     => "SHA256",
					'hash'               => $this->createHash( $amount, $this->currency ),
					'storename'          => $this->store_id,
					'mode'               => 'payonly',
					'chargetotal'        => $amount,
					'currency'           => $this->currency,
					'responseSuccessURL' => $ipg_return_url . '1',
					'responseFailURL'    => $ipg_return_url . '0',

					// Billing Fields
					
					// Shipping Fields
					
				);

				?>
				<form method="post" action="<?php echo $ipg_endpoint ?>">
					<?php foreach ($FirstData_form_fields as $key => $value): ?>
						<input type="hidden" name="<?php echo $key ?>" value="<?php echo $value ?>">
					<?php endforeach ?>

					<input type="submit" value="PAGAR">
				</form>
				<?php
			endif;
		}

		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}


		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {

			$order = wc_get_order( $order_id );
			
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting offline payment', 'wc-firstdata-ipg' ) );
			
			// Reduce stock levels
			$order->reduce_order_stock();
			
			// Remove cart
			WC()->cart->empty_cart();
			
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
			);
		}

		/**
		 * Get Current date/time in the required format
		 *
		 * @param string $timeZone
		 * @return string
		 */
		public function getDateTime(  ) {
			date_default_timezone_set($this->timezone);
			$dateTime = date("Y:m:d-H:i:s");
			return $dateTime;
		}


		public function createHash($chargetotal, $currency) {

			$storeId = $this->store_id;

			$sharedSecret = $this->sharedsecret;

			$stringToHash = $storeId . $this->getDateTime() . $chargetotal . $currency .
			$sharedSecret;
			$ascii = bin2hex($stringToHash);
			return hash("sha256", $ascii);
		}

  } // end \WP_Gateway_FirstData_IPG class
}
