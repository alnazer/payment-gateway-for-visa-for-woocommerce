<?php
/*
Plugin Name: Payment Gateway for VISA
Plugin URI: https://github.com/alnazer/payment-gateway-for-visa-for-woocommerce
Description: This add-on offers you to expand your customer base with the ability to pay by Visa 
Author: alnazer 
Version: 2.0.0
Author URI: http://github.com/alnazer
*Text Domain: wc_visa
* Domain Path: /languages
*/
/**
 * @package wc VISA
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action('plugins_loaded', 'woocommerce_wc_visa_init', 0);
function woocommerce_wc_visa_init(){

	if ( !class_exists( 'WC_Payment_Gateway' ) ) {return;}
	/**
	 *  VISA Gateway.
	 *
	 * Provides a VISA Payment Gateway.
	 *
	 * @class       WC_Gateway_VISA
	 * @extends     WC_Payment_Gateway
	 * @version     5.0.0
	 * @package     WooCommerce/Classes/Payment
	 */
	class WC_Gateway_VISA extends WC_Payment_Gateway {
        protected $commission;
        protected $paymentid;
        protected $rand;
        protected $currency;
        protected $complete_order_status;
        protected $configArray = array();
        protected $resultIndicator = "";
        protected $sessionVersion = "";
        protected $checkoutUrl     = "";
        protected $cancelUrl      = "";
        protected $errorUrl       = "";
        protected $responceUrl    = "" ;
        protected $version = 66;
        protected $language;
        protected $gatewayUrl;
        protected $is_test;
        public $account_details;
        public $interaction_options;
        public $interaction;
        public $visa_type_options;
        public $merchantId;
        public $apiusername;
        public $password;
        public $type;
        public $visa_account_slug = "alnazer_visa_accounts";
		public function __construct()
		{
			$this->setup_properties();
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();


			$this->rand = time ().rand(500,1000);
            $this->interaction_options = [
                "PURCHASE"=> __('PURCHASE', 'wc_visa'),
                "AUTHORIZE"=> __('AUTHORIZE', 'wc_visa'),
                "NONE"=> __('NONE', 'wc_visa'),
                "VERIFY"=> __('VERIFY', 'wc_visa'),
            ];

            $this->visa_type_options = ["local"=> __('Local', 'wc_visa'),
                "global"=> __('Global-GCC', 'wc_visa'),
            ];
            $this->visa_is_test_options = [ "no"=> __('Disable', 'wc_visa'),"yes"=> __('Enable', 'wc_visa')];

			$this->title                = $this->get_option( 'title' );
			$this->language             = $this->get_option( 'language' );
			$this->description          = $this->get_option( 'description' );
            $this->merchantId           = $this->get_option( 'merchantId' );
            $this->apiusername          = $this->get_option( 'apiusername' );
            $this->password             = $this->get_option( 'password' );
            $this->interaction          = $this->get_option('interaction');

			$this->is_test =  $this->get_option( 'is_test' );
			$this->setGatewayUrl();
			$this->setConfigArray();
			// Version number of the API being used for your integration
			// this is the default value if it isn't being specified in process.php
			$this->configArray["version"]   = $this->version;
			$this->paymentid                = 'DN'.$this->rand;
			$this->currency                 =  get_option('woocommerce_currency');
			$this->complete_order_status    = $this->get_option('complete_order_status');
			$this->logo                     = $this->get_option('logo');
			$this->commission    = $this->get_option( "commission" );
            $this->account_details = get_option(
                $this->visa_account_slug,
                array(
                    array(
                        'visa_active' => $this->get_option( 'visa_active' ),
                        'visa_title'   => $this->get_option( 'visa_title' ),
                        'visa_type'   => $this->get_option( 'visa_type' ),
                        'visa_interaction' => $this->get_option( 'visa_interaction' ),
                        'visa_commission'      => $this->get_option( 'visa_commission' ),
                        'visa_merchantid'      => $this->get_option( 'visa_merchantid' ),
                        'visa_password'           => $this->get_option( 'visa_password' ),
                        'is_test'  => $this->get_option( 'visa_is_test' ),
                    ),
                )
            );

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_filter('woocommerce_available_payment_gateways', [$this,'visa_conditional_payment_gateways'], 10, 1);


			add_filter('woocommerce_thankyou_order_received_text', [$this,'visa_woo_change_order_received_text'] );
			add_filter( 'woocommerce_endpoint_order-received_title', [$this,'visa_thank_you_title']);

			// change payment title
			add_filter( "woocommerce_gateway_title", [ $this, 'visa_woocommerce_gateway_title' ], 26, 2 );
			add_filter( "woocommerce_gateway_description", [ $this, 'visa_woocommerce_gateway_description' ], 26, 2 );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_account_details' ) );
            $this->intiOldData();
			$this->setApiUrl();
		}
        protected function setup_properties() {
            $this->id                 = 'wc_visa';
            $this->icon               =  plugins_url( 'images/visa-logo.png' , __FILE__ );
            $this->method_title       = $this->title;
            $this->method_description = __( 'Easy and fast and secure', 'wc_visa' );
            $this->has_fields         = false;
        }
		public function init_form_fields(){
			$this->form_fields = array(
				'enabled' => array(
					'title' => __( 'Enable/Disable', 'woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Enable VISA Payment', 'woocommerce' ),
					'default' => 'yes'
				),
				/*'is_test' => array(
					'title'       => 'Test mode',
					'label'       => 'Enable Test Mode',
					'type'        => 'checkbox',
					'description' => __("Place the payment gateway in test mode using test. only this user roles [Shop manager,Administrator] can test payment","wc_visa"),
					'default'     => 'no',
					'desc_tip'    => false,
				),*/
				'logo' => array(
					'title' => __('logo', 'wc_visa'),
					'type' => 'text',
					'description' => __('Company Logo.'),
					'default'=>plugins_url( 'images/logo.png' , __FILE__ )
				),
				'title' => array(
					'title' => __('Title:', 'wc_visa'),
					'type'=> 'text',
					'description' => __('This controls the title which the user sees during checkout.', 'wc_visa'),
					'default' => __('VISA', 'wc_visa')
				),
				'description' => array(
					'title' => __('Description:', 'wc_visa'),
					'type' => 'textarea',
					'description' => __('This controls the description which the user sees during checkout.', 'wc_visa'),
					'default' => __('Pay securely by Credit card through VISA.', 'wc_visa')
				),
				'language'=> array(
					'title' => __('Language:', 'wc_visa'),
					'type'=> 'select',
					'default' => strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)),
					'options'=>$this->getLangList()
				),
				'gatewayarea'=> array(
					'title' => __('Area:', 'wc_visa'),
					'type'=> 'select',
					//'description' => __('This controls the title which the user sees during checkout.', 'wc_visa'),
					'default' => 'asia',
					'options'=>[
						"americas"=> __('Americas', 'wc_visa'),
						"europe"=> __('Europe', 'wc_visa'),
						"asia"=> __('Asia', 'wc_visa'),
						"other"=> __('Other', 'wc_visa'),
					]
				),
				/*'interaction'=>
					array(
						'title' => __("Interaction:", 'wc_visa'),
						'type'=> 'select',
						'description' => __('Indicates the operation that you wish to perform during the Hosted Checkout interaction', 'wc_visa'),
						'default' => 'AUTHORIZE',
						'options'=>[
							"AUTHORIZE"=> __('AUTHORIZE', 'wc_visa'),
							"NONE"=> __('NONE', 'wc_visa'),
							"PURCHASE"=> __('PURCHASE', 'wc_visa'),
							"VERIFY"=> __('VERIFY', 'wc_visa'),
						]
					),*/
				'complete_order_status'=> array(
					'title' => __('Complete Order Status', 'wc_visa'),
					'description' => __('The status to which the request is transferred upon successful payment by Visa', 'wc_visa'),
					'type'=> 'select',
					'default' => "completed",
					'options'=>$this->getOrderStatusList()
				),
				/*'commission'   => [
					'title'             => __( 'Payment commission', 'wc_visa' ),
					'type'              => 'number',
					'custom_attributes' => array( 'step' => 0.100, 'min' => 0 ),
					'description'       => __( 'Charge percent (%) the transfer commission to the customer. If you want to bear it, leave a zero value', 'wc_visa' ),
					'default'           => 0,
					'desc_tip'          => false,
				],
				'merchantId' => array(
					'title' => __('merchantId', 'wc_visa'),
					'type' => 'text',
					'description' => __('Given by VISA for your Merchant Account.'),
					//'default'=>'pay'
				),

				'apiusername' => array(
					'title' => __('apiusername', 'wc_visa'),
					'type' => 'text',
					'description' => __('Given by VISA for your Merchant Account.'),
				),
				'password' => array(
					'title' => __('password', 'wc_visa'),
					'type' => 'password',
					'description' => __('Given by VISA for your Merchant Account.'),
				),*/
                'account_details'=> array(
                    'title' => __('Account Details', 'wc_visa'),
                    "type" => "alnazer_visa_account_details"
                )
			);
		}
		/**
		 * define VISA gateway url
		 * you must call before calling  setConfigArray
		 */
        protected function setGatewayUrl()
		{
			// Base URL of the Payment Gateway. Do not include the version.
			//https://ap-gateway.mastercard.com/api/nvp
			switch ($this->get_option( 'gatewayarea' )) {
				case 'americas':
					$this->gatewayUrl =    "https://na-gateway.mastercard.com/api/nvp";
					break;
                case 'other':
                case 'europe':
					$this->gatewayUrl =    "https://eu-gateway.mastercard.com/api/nvp";
					break;
                case 'asia':
                default:
					$this->gatewayUrl = "https://ap-gateway.mastercard.com/api/nvp";
					break;
			}
		}
		/**
		 * set Config for VISA payment
		 */
        protected function setConfigArray()
		{
			if(isset($_REQUEST['resultIndicator']) && !empty(esc_attr($_REQUEST['resultIndicator']))){
				$this->resultIndicator = sanitize_text_field($_REQUEST ['resultIndicator']);
			}

			if(isset($_REQUEST ['sessionVersion']) && !empty(esc_attr($_REQUEST ['sessionVersion']))){
				$this->sessionVersion = sanitize_text_field($_REQUEST ['sessionVersion']);
			}

			$this->configArray["certificateVerifyPeer"] = FALSE;
			// possible values:
			// 0 = do not check/verify hostname
			// 1 = check for existence of hostname in certificate
			// 2 = verify request hostname matches certificate hostname
			$this->configArray["certificateVerifyHost"] = 0;
			$this->configArray["gatewayUrl"] = $this->gatewayUrl;
			$this->checkoutUrl = str_replace("api/nvp","static/checkout/checkout.min.js",$this->gatewayUrl);
			if($this->is_test == "yes")
			{
				// Merchant ID supplied by your payments provider
				$this->configArray["merchantId"] = "TESTMPGS_TEST";
				// API username in the format below where Merchant ID is the same as above
				$this->configArray["apiUsername"] = "merchant.".$this->configArray["merchantId"];
				// API password which can be configured in Merchant Administration
				$this->configArray["password"] = "c543aae3f27bd1e0b3db7cdb8b246a57";
			}
			else
			{
				// Merchant ID supplied by your payments provider
				$this->configArray["merchantId"] = $this->merchantId;

				// API username in the format below where Merchant ID is the same as above
				$this->configArray["apiUsername"] = "merchant.".$this->configArray["merchantId"];

				// API password which can be configured in Merchant Administration
				$this->configArray["password"] = $this->password;
			}



			// The debug setting controls displaying the raw content of the request and
			// response for a transaction.
			// In production you should ensure this is set to FALSE as to not display/use
			// this debugging information
			$this->configArray["debug"] = FALSE;

		}
		/**
		 * set Api url
		 * like erro.cancel.responce ... ect
		 */
        protected function setApiUrl()
		{
			$this->redirectUrl    = "redirect_".strtolower(__CLASS__);
			$this->cancelUrl    = "cancel_".strtolower(__CLASS__);
			$this->errorUrl     = "error_".strtolower(__CLASS__);
			$this->responceUrl  = "responce_".strtolower(__CLASS__);
			add_action( 'woocommerce_api_'.$this->redirectUrl, array( $this, 'form' ) );
			add_action( 'woocommerce_api_'.$this->cancelUrl, array( $this, 'visa_cancel' ) );
			add_action( 'woocommerce_api_'.$this->errorUrl, array( $this, 'visa_error' ) );
			add_action( 'woocommerce_api_'.$this->responceUrl, array( $this, 'visa_responce' ) );
		}

		/**
		 * add colored order status in received page
		 * @param $str
		 * @return string
		 */
		public function visa_woo_change_order_received_text($str) {
			global  $id;
			$order = $this->get_order_in_recived_page($id,true);
			$order_status = $order->get_status();
			return  sprintf("%s <b><span style=\"color:%s\">%s</span></b>.",__("Thank you. Your order has been","wc_visa"),$this->get_status_color($order_status),__(ucfirst($order_status),"woocommerce"));
		}

        /**
         * Generate account details html.
         *
         * @return string
         */
        public function generate_alnazer_visa_account_details_html() {

            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc"><?php esc_html_e( 'Account details:', 'woocommerce' ); ?></th>
                <td class="forminp" id="<?php echo $this->visa_account_slug ?>">
                    <div class="wc_input_table_wrapper">
                        <table class="widefat wc_input_table sortable" cellspacing="0">
                            <thead>
                            <tr>
                                <th class="sort">&nbsp;</th>

                                <th><?php esc_html_e( 'title', 'wc_visa' ); ?></th>
                                <th style="width:10px"><?php esc_html_e( 'active', 'wc_visa' ); ?></th>
                                <th style="width:15px"><?php esc_html_e( 'Test mode', 'wc_visa' ); ?></th>
                                <th><?php esc_html_e( 'type', 'wc_visa' ); ?></th>
                                <th><?php esc_html_e( 'Interaction', 'wc_visa' ); ?></th>
                                <th><?php esc_html_e( 'Payment commission', 'wc_visa' ); ?> %</th>
                                <th><?php esc_html_e( 'merchantId', 'wc_visa' ); ?></th>
                                <th><?php esc_html_e( 'password', 'wc_visa' ); ?></th>
                            </tr>
                            </thead>
                            <tbody class="accounts">
                            <?php
                            $i = -1;
                            if ( $this->account_details ) {
                                foreach ( $this->account_details as $account ) {
                                    $i++;
                                    $checked = (isset($account['visa_active']) && $account['visa_active'] == "true") ? "checked" : "";
                                    echo '<tr class="account">
										<td class="sort"></td>
										<td><input type="text" value="' . esc_attr( wp_unslash( $account['visa_title'] ) ) . '" name="visa_title[' . esc_attr( $i ) . ']" /></td>
										<td style="text-align:center" ><input type="checkbox" value="true" '.$checked. ' name="visa_active[' . esc_attr( $i ) . ']" /></td>
										<td style="text-align:center">'.$this->selected("visa_is_test[".esc_attr( $i )."]",$this->visa_is_test_options,esc_attr( wp_unslash( $account['visa_is_test'] ?? "" ) )).'</td>
										<td>'.$this->selected("visa_type[".esc_attr( $i )."]",$this->visa_type_options,esc_attr( wp_unslash( $account['visa_type'] ?? "" ) )).'</td>
										
										<td>'.$this->selected("visa_interaction[".esc_attr( $i )."]",$this->interaction_options,esc_attr( wp_unslash( $account['visa_interaction'] ) )).'</td>
										<td><input type="number" step="0.1" min="0" value="' . esc_attr( wp_unslash( $account['visa_commission'] ) ) . '" name="visa_commission[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $account['visa_merchantid'] ) . '" name="visa_merchantid[' . esc_attr( $i ) . ']" /></td>
										<td><input type="password" value="' . esc_attr( $account['visa_password'] ) . '" name="visa_password[' . esc_attr( $i ) . ']" /></td>
									</tr>';
                                }
                            }
                            $select = $this->selected("visa_type[' + size + ']", $this->visa_type_options);
                            $select_is_test = $this->selected("visa_is_test[' + size + ']", $this->visa_is_test_options);
                            $select_interaction = $this->selected("visa_interaction[' + size + ']", $this->interaction_options);

                            ?>
                            </tbody>
                            <tfoot>
                            <tr>
                                <th colspan="7"><a href="#" class="add button"><?php esc_html_e( '+ Add account', 'wc_visa' ); ?></a> <a href="#" class="remove_rows button"><?php esc_html_e( 'Remove selected account(s)', 'wc_visa' ); ?></a></th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                    <script type="text/javascript">
                        jQuery(function() {
                            jQuery('#<?php  echo $this->visa_account_slug ; ?>').on( 'click', 'a.add', function(){

                                var size = jQuery('#<?php  echo $this->visa_account_slug ; ?>').find('tbody .account').length;

                                jQuery('<tr class="account">\
									<td class="sort"></td>\
									<td><input type="text" name="visa_title[' + size + ']" /></td>\
									<td><input style="text-align:center" type="checkbox" value="true" checked  name="visa_active[' + size + ']" /></td>\
									<td><?php echo $select_is_test; ?></td>\
									<td><?php echo $select; ?></td>\

									<td><?php echo $select_interaction ?></td>\
									<td><input type="number" step="0.1" min="0" name="visa_commission[' + size + ']" /></td>\
									<td><input type="text" name="visa_merchantid[' + size + ']" /></td>\
									<td><input type="password" name="visa_password[' + size + ']" /></td>\
								</tr>').appendTo('#<?php  echo $this->visa_account_slug ; ?> table tbody');

                                return false;
                            });
                        });
                    </script>
                </td>
            </tr>
            <?php
            return ob_get_clean();

        }

        /**
         * Save account details table.
         */
        public function save_account_details() {

            $accounts = array();

            // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification already handled in WC_Admin_Settings::save()
            if ( isset( $_POST['visa_title'] ) && isset( $_POST['visa_type'] ) && isset( $_POST['visa_interaction'] )
                 && isset( $_POST['visa_commission'] ) && isset( $_POST['visa_merchantid'] ) && isset( $_POST['visa_password'] ) ) {

                $visa_titles   = wc_clean( wp_unslash( $_POST['visa_title'] ) );
                $visa_types      = wc_clean( wp_unslash( $_POST['visa_type'] ) );
                $visa_interactions      = wc_clean( wp_unslash( $_POST['visa_interaction'] ) );
                $visa_commissions           = wc_clean( wp_unslash( $_POST['visa_commission'] ) );
                $visa_merchantids            = wc_clean( wp_unslash( $_POST['visa_merchantid'] ) );
                $visa_passwords            = wc_clean( wp_unslash( $_POST['visa_password'] ) );
                $visa_actives            = wc_clean( wp_unslash( $_POST['visa_active'] ?? [] ) );
                $visa_is_tests            = wc_clean( wp_unslash( $_POST['visa_is_test'] ?? [] ) );

                foreach ( $visa_titles as $i => $name ) {
                    if ( ! isset( $visa_titles[ $i ] ) ) {
                        continue;
                    }
                    $accounts[] = array(
                        'visa_active'   => $visa_actives[ $i ] ?? "false",
                        'visa_title'   => $visa_titles[ $i ],
                        'visa_type'   => $visa_types[ $i ] ?? "local",
                        'visa_interaction' => $visa_interactions[ $i ],
                        'visa_commission'      => $visa_commissions[ $i ],
                        'visa_merchantid'           => $visa_merchantids[ $i ],
                        'visa_password'            => $visa_passwords[ $i ],
                        'visa_is_test'            => $visa_is_tests[ $i ],

                    );
                }
            }
            // phpcs:enable
            do_action( 'woocommerce_update_option', array( 'id' => $this->visa_account_slug ) );
            update_option( $this->visa_account_slug, $accounts );

        }

		/**
		 * add colored order status in received page
		 * @param $old_title
		 * @return string
		 */
		public function visa_thank_you_title( $old_title){
			global  $id;
			$order_status = $this->get_order_in_recived_page($id);

			if ( isset ( $order_status ) ) {
				return  sprintf( "%s , <b><span style=\"color:%s\">%s</span></b>",__('Order',"wc_visa"),$this->get_status_color($order_status), esc_html( __(ucfirst($order_status),"woocommerce")) );
			}
			return $old_title;
		}
		/**
		 * set status color
		 * @param $status
		 * @return string
		 */
        protected function get_status_color($status){
			switch ($status){
				case "pending":
					return "#0470fb";
				case "processing":
					return "#fbbd04";
				case "on-hold":
					return "#04c1fb";
				case "completed":
					return "green";
				default:
					return "#fb0404";
			}
		}
		/**
		 * get order details in received page
		 * @param $page_id
		 * @param bool $return_order
		 * @return bool|string|WC_Order
		 */
        protected function get_order_in_recived_page($page_id,$return_order= false){
			global $wp;
			if ( is_order_received_page() && get_the_ID() === $page_id ) {
				$order_id  = apply_filters( 'woocommerce_thankyou_order_id', absint( $wp->query_vars['order-received'] ) );
				$order_key = apply_filters( 'woocommerce_thankyou_order_key', empty( $_GET['key'] ) ? '' : wc_clean( $_GET['key'] ) );
				if ( $order_id > 0 ) {
					$order = new WC_Order( $order_id );

					if ( $order->get_order_key() != $order_key ) {
						$order = false;
					}
					if($return_order){
						return $order;
					}
					return $order->get_status();
				}
			}
			return false;
		}
		/**
		 * get order id by get or session saved
		 */
        protected function getOrderId()
		{
			if(!empty($_GET) && array_key_exists('order_id',$_GET) && !empty($_GET['order_id']) && intval($_GET['order_id'])){
				return intval($_GET['order_id']);
			}
			if(!empty($this->getSession('visa_order_id')))
			{
				return $this->getSession('visa_order_id');
			}
			return 0;
		}
		/**
		 * get order id by get or session saved
		 */
        protected function getOrderHash()
		{
			if(!empty($_GET) && array_key_exists('order_hash',$_GET) && !empty($_GET['order_hash']) && intval($_GET['order_hash'])){
				return $_GET['order_hash'];
			}
			if(!empty($this->getSession('order_hash')))
			{
				return $this->getSession('order_hash');
			}
			return 0;
		}

		/** get ammount
		 * @param $order
		 * @return float|int
		 */
        protected function getTotalAmount($order){
			return $order->get_total()+$this->getCommotionsValue($order);
		}
        protected function getCommotionsValue($order){
			if($this->commission > 0){
				return ($order->get_total() * (float) $this->commission)/100;
			}
			return 0;
		}
		/**
		 * hide gateways in test mode
		 * @param $available_gateways
		 * @return mixed
		 */
		public  function  visa_conditional_payment_gateways($available_gateways){

			if(is_admin()){
				return $available_gateways;
			}
			if($this->is_test == "yes"){
				$wp_get_current_user = wp_get_current_user();
				if(isset($wp_get_current_user)){
					if(!in_array("shop_manager",$wp_get_current_user->roles) && !in_array("administrator",$wp_get_current_user->roles)){
						unset($available_gateways[$this->id]);
					}
				}
			}
			return $available_gateways;
		}

		/**
		 * @param $title
		 * @param $gateway_id
		 *
		 * @return mixed|string
		 */
		public function visa_woocommerce_gateway_title( $title, $gateway_id ) {
			if ( ! is_admin() ) {
				if ( $this->is_test == "yes" && $this->id == $gateway_id ) {
					$title = sprintf( "%s <span style='color: red'>%s</span>", $title, __( "Test Mode", "wc_visa" ) );
				}
			}

			return $title;
		}

		/**
		 * @param $description
		 * @param $gateway_id
		 *
		 * @return mixed|string
		 */
		public function visa_woocommerce_gateway_description( $description, $gateway_id ) {
			if ( ! is_admin() ) {
				if ( $this->commission > 0 && $this->id == $gateway_id ) {
					$description = sprintf( "%s  <b>%s</b> <br/> %s", __( "(+) transfer fee", "wc_visa" ), $this->commission."%", $description );
				}
			}

			return $description;
		}
        protected function visa_request($operation="INITIATE_CHECKOUT",$body = [])
		{
			$merchantObj = new Alnazer_WC_Gateway_VISA_Merchant($this->configArray);
			$parserObj = new Alnazer_WC_Gateway_VISA_Parser($merchantObj); 
			$requestUrl = $parserObj->FormRequestUrl($merchantObj);
			
			$body = array_merge($body,["apiOperation"=> $operation,'merchant' => $this->merchantId]);
			$request = $parserObj->ParseRequest($merchantObj, $body);
			$response = $parserObj->SendTransaction($merchantObj,$request);

			return	$this->parse_from_nvp($response);
		}
		/**
		 * process_payment
		 */
		function process_payment($order_id){
			global $woocommerce;
			require_once plugin_dir_path(__FILE__)."configuration.php" ;
			require_once  plugin_dir_path(__FILE__)."connection.php" ;
			$order = new WC_Order( $order_id );
			if(!isset($_SESSION))
			{
				session_start();
			}
			$visa_order_id = $order_id."-".$this->rand;
            /*$this->dd([
                $this->interaction,
                $this->merchantId,
                $this->apiusername,
                $this->type,
                $this->title,
                $this->commission
            ]);*/
			$body = array(
				"order.id"=>$visa_order_id,
				"order.amount"=> $this->getTotalAmount($order),
				"order.currency"=>$this->currency,
				"order.description"=> $this->getOrderDescription($visa_order_id, $order),
				"interaction.operation"=> $this->interaction,//VERIFY,PURCHASE,NONE,AUTHORIZE
			);
			
			$parsed_array = $this->visa_request("INITIATE_CHECKOUT",$body);
		 
			if($parsed_array['result'] == "SUCCESS"){
				$this->setSession('session.id',$parsed_array['session.id']);
				$this->setSession('session.version',$parsed_array['session.version']);
				$this->setSession('successIndicator',$parsed_array['successIndicator']);
				$this->setSession('merchant',$parsed_array['merchant']);
				$this->setSession('visa_payment_id',$this->paymentid);
				$this->setSession('order_hash',$visa_order_id);
				$this->setSession('visa_order_id',$order_id);

				$_note=  sprintf("VISA session.id : %s <br/>",$parsed_array['session.id']);
				$_note.= sprintf("VISA session.version : %s <br/>",$parsed_array['session.version']);
				$_note.= sprintf("VISA  successIndicator : %s <br/>",$parsed_array['successIndicator']);
				$_note.= sprintf("VISA  merchant : %s <br/>",$parsed_array['merchant']);
				$order->add_order_note($_note);
				// Reduce stock levels.
				do_action('wc_visa_before_process_payment_redirect',[$order,$this->paymentid]);
				return array (
					'result'   => 'success',
					'redirect' => get_site_url(). '?wc-api='.$this->redirectUrl.'&order_id='.$order_id.'&order_hash='.$visa_order_id,
					'PAYEMENT_ID' => $order_id
				);
			}else{
				do_action('wc_visa_on_process_payment_error',[$order,$this->paymentid]);
				$msg['message'] = __('Payment error:','wc_visa').__($parsed_array['error.explanation'],'wc_visa');
				$msg['class'] = 'error';
				$order->add_order_note($msg['message']);
				$this->set_order_notif($msg);
				return;
			}
		}

		/**
		 * create order description to visa
		 */
        protected function getOrderDescription($order_id, $order){
			$orderDesc = __('Order num','wc_visa')." : $order_id - ".__('Total','wc_visa')." : ".$order->get_total()." ".get_option('woocommerce_currency');
			if($this->commission > 0){
				$orderDesc.=" (+) Commotions ".$this->getCommotionsValue($order);
			}
			return $orderDesc;
		}

		/**
		 * make request payment
		 * return html code or error
		 */
		public function form()
		{
			header('Content-Type: text/html; charset=utf-8');
			$msg['message'] = __('Error happened','wc_visa');
			$msg['class'] = 'error';
			if(!isset($_SESSION))
			{
				session_start();
			}
			$order_id = $this->getOrderId();
			$visa_order_id = $this->getOrderHash();
			/*echo "<pre>";
			$this->dd([
				$visa_order_id,
				$_REQUEST
			]);
			*/
			$order = new WC_Order( $order_id );
			if($order_id && $order){

				$template = file_get_contents(plugin_dir_path(__FILE__)."redirect-page.html");

				$repalce = [
					"{{logo}}" => str_replace("http://","https://",$this->logo),
					"{{get_site_url}}" => get_site_url(),
					"{{merchantId}}" => $this->configArray["merchantId"],
					"{{total}}" => $this->getTotalAmount($order),
					"{{currency}}" => $this->currency,
					"{{product_name}}" => $this->getOrderDescription($order_id, $order),
					"{{order_id}}" => $order_id,
					"{{visa_order_id}}" => $visa_order_id,
					"{{reference}}" => $this->paymentid,//$this->getSession('visa_payment_id'),
					'{{blogname}}' => get_option('blogname'),
					"{{session_id}}"=>  $this->getSession('session.id'),
					"{{checkoutUrl}}" => $this->checkoutUrl,
					'{{language}}' => $this->language,
					'{{operation}}' => $this->interaction,
					'{{order_date}}' => date("Y-m-d"),
					'{{cancel_link}}' => $this->cancelUrl,
					'{{error_link}}' => $this->errorUrl,
					'{{responce_link}}' => $this->responceUrl,
				];

				echo $template = str_replace(array_keys($repalce) , array_values($repalce) , $template);
				exit();
			}else{
				$msg['message'] = __('order not found','wc_visa');
				$msg['class'] = 'error';
				$this->set_order_notif($msg);
				$redirect = $this->get_return_url( $order );
				if ( wp_redirect($redirect) ) { exit; }
			}
			exit();
		}
		public function visa_error()
		{

			$status = 'Error';
			//$order_id = $this->getSession('visa_order_id');
			$order_id = $this->getOrderId();
			$order = new WC_Order( $order_id );
			if($order){
				$order->update_status ( 'failed', __ ( 'failed Payment VISA', 'wc_visa' ) );
				$msg['message'] = sanitize_text_field($_REQUEST['explanation']);
				$msg['class'] = 'error';
				$this->set_order_notif($msg);
				$order->add_order_note(__( 'failed Payment VISA :'.$msg['message'], ));
			}
			else
			{
				$msg['message'] = __('order not found','wc_visa');
				$msg['class'] = 'error';
				$this->set_order_notif($msg);
			}
			$redirect = $this->get_return_url( $order );
			if ( wp_redirect($redirect) ) { exit; }
		}
		public function visa_cancel()
		{

			$order_id = $this->getOrderId();
			$order = new WC_Order( $order_id );
			if($order){
				$order->update_status( 'cancelled', __( 'Unpaid order cancelled - time limit reached.', 'wc_visa' ) );
			}
			else
			{
				$msg['message'] = __('order not found','wc_visa');
				$msg['class'] = 'error';
				$this->set_order_notif($msg);
			}
			$redirect = $this->get_return_url( $order );
			if ( wp_redirect($redirect) ) { exit; }
		}
		public function visa_responce()
		{
			global $woocommerce;
			$order_id = $this->getOrderId();
			$visa_order_id = $this->getOrderHash();
			$order = new WC_Order( $order_id );
			if($order){
				$orderID = $visa_order_id;
				require_once plugin_dir_path(__FILE__)."configuration.php" ;
				require_once  plugin_dir_path(__FILE__)."connection.php" ;

                $parsed_array = $this->visa_request("RETRIEVE_ORDER",["order.id"=>$orderID]);
				$resultIndicator = sanitize_text_field($_GET['resultIndicator']) ?? 0;
				
				if((empty($parsed_array) && $this->getSession('successIndicator') === $resultIndicator) || !empty($parsed_array) && $parsed_array['result'] == 'SUCCESS' || in_array($parsed_array['status'],["AUTHENTICATED","CAPTURED","AUTHORIZED"])  ){
					wc_reduce_stock_levels( $order_id );

					$order->update_status( $this->complete_order_status);
					if($this->complete_order_status == 'completed'){
						$order->payment_complete();
					}

					$woocommerce->cart->empty_cart();
				}elseif(!empty($parsed_array) && $parsed_array['result'] == 'ERROR'){
					$order->update_status ( 'failed', __ ( 'failed Payment VISA', 'wc_visa' ) );
				}
				/*$this->dd([
					$this->getSession('successIndicator'),
					$resultIndicator,
					$visa_order_id,
					$_REQUEST,
					$parsed_array
				]);*/
				$_note = "";
				if(isset($parsed_array['invoiceNumber']) && !empty($parsed_array['invoiceNumber'])){
					$_note .=  sprintf("VISA invoiceNumber : %s <br/>",$parsed_array['invoiceNumber']);
				}
				if(isset($parsed_array['result']) && !empty($parsed_array['result'])){
					$_note.= sprintf("VISA result : %s <br/>",$parsed_array['result']);
				}
				if(isset($parsed_array['status']) && !empty($parsed_array['status'])){
					$_note.= sprintf("VISA status : %s <br/>",$parsed_array['status']);
				}
				if(isset($parsed_array['merchant']) && !empty($parsed_array['merchant'])){
					$_note.= sprintf("VISA  merchant : %s <br/>",$parsed_array['merchant']);
				}
				$_note.= sprintf("VISA  successIndicator : %s <br/>",$resultIndicator);
				
				$order->add_order_note($_note);
			}
			else
			{
				$msg['message'] = __('order not found','wc_visa');
				$msg['class'] = 'error';
				$this->set_order_notif($msg);
			}
			// if not have order recevied page
			// redirect to order page
			$redirect = $this->get_return_url( $order );
			if( is_wc_endpoint_url( 'order-received' ) )
			{
				$redirect = $order->get_checkout_order_received_url();
			}

			if ( wp_redirect($redirect) ) { exit; }
		}
        protected function set_order_notif($msg)
		{
			global $woocommerce;
			if ( function_exists( 'wc_add_notice' ) )
			{
				wc_add_notice( $msg['message'], $msg['class'] );
			}
			else
			{
				if($msg['class']=='success'){
					$woocommerce->add_message( $msg['message']);
				}else{
					$woocommerce->add_error( $msg['message'] );
				}
				$woocommerce->set_messages();
			}
		}
		/**
		 * Admin Panel Options
		 * - Options for bits like 'title', 'description', 'alias'
		 **/
		public function admin_options(){
			echo '<h3>'.$this->title.'</h3>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}
        protected function setSession($key='visa_order_id',$value){
			if(!isset($_SESSION))
			{
				session_start();
			}
			$_SESSION[$key] = sanitize_text_field($value);
		}
        protected function getSession($key='visa_order_id'){
			if(!isset($_SESSION))
			{
				session_start();
			}
			if(isset($_SESSION[$key]))
			{
				return sanitize_text_field($_SESSION[$key]);
			}
			return null;

		}
        protected function parse_from_nvp($string){
			$array=array();
			$pairArray = array();
			$param = array();
			if (strlen($string) != 0) {
				$pairArray = explode("&", $string);
				foreach ($pairArray as $pair) {
					$param = explode("=", $pair);
					$array[urldecode($param[0])] = urldecode($param[1]);
				}
			}
			return $array;
		}

		//Takes an associative array (in the format -> [key1 => val1, key2 => val2, ...]) and builds a return string

		//inserting '=' and '&' to divide name value pairs (in the format -> "key1=val1&key2=val2&key3=val3...")

        protected function parse_to_nvp($array){
			$string = '';
			foreach($array as $key => $val){
				$string .= urlencode($key) . "=" . urlencode($val) . "&";
			}
			$string = substr($string,0, -1);
			return $string;
		}

        protected function getRandomString($length) {

			$salt = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9));
			$maxIndex = count($salt) - 1;
			$result = '';
			for ($i = 0; $i < $length; $i++) {
				$index = mt_rand(0, $maxIndex);
				$result .= $salt[$index];
			}
			return $result;

		}
		protected function dd(...$data){
			echo "<pre>";
			print_r($data);
			echo "</pre>";
			die;
		}
		private function getLangList(){
			return array(
				'ab'=> 'Abkhazian',
				'aa'=> 'Afar',
				'af'=> 'Afrikaans',
				'ak'=> 'Akan',
				'sq'=> 'Albanian',
				'am'=> 'Amharic',
				'ar'=> 'Arabic',
				'an'=> 'Aragonese',
				'hy'=> 'Armenian',
				'as'=> 'Assamese',
				'av'=> 'Avaric',
				'ae'=> 'Avestan',
				'ay'=> 'Aymara',
				'az'=> 'Azerbaijani',
				'bm'=> 'Bambara',
				'ba'=> 'Bashkir',
				'eu'=> 'Basque',
				'be'=> 'Belarusian',
				'bn'=> 'Bengali',
				'bh'=> 'Bihari languages',
				'bi'=> 'Bislama',
				'bs'=> 'Bosnian',
				'br'=> 'Breton',
				'bg'=> 'Bulgarian',
				'my'=> 'Burmese',
				'ca'=> 'Catalan, Valcodeian',
				'km'=> 'Central Khmer',
				'ch'=> 'Chamorro',
				'ce'=> 'Chechen',
				'ny'=> 'Chichewa, Chewa, Nyanja',
				'zh'=> 'Chinese',
				'cu'=> 'Church Slavonic, Old Bulgarian, Old Church Slavonic',
				'cv'=> 'Chuvash',
				'kw'=> 'Cornish',
				'co'=> 'Corsican',
				'cr'=> 'Cree',
				'hr'=> 'Croatian',
				'cs'=> 'Czech',
				'da'=> 'Danish',
				'dv'=> 'Divehi, Dhivehi, Maldivian',
				'nl'=> 'Dutch, Flemish',
				'dz'=> 'Dzongkha',
				'en'=> 'English',
				'eo'=> 'Esperanto',
				'et'=> 'Estonian',
				'ee'=> 'Ewe',
				'fo'=> 'Faroese',
				'fj'=> 'Fijian',
				'fi'=> 'Finnish',
				'fr'=> 'Frcodeh',
				'ff'=> 'Fulah',
				'gd'=> 'Gaelic, Scottish Gaelic',
				'gl'=> 'Galician',
				'lg'=> 'Ganda',
				'ka'=> 'Georgian',
				'de'=> 'German',
				'ki'=> 'Gikuyu, Kikuyu',
				'el'=> 'Greek (Modern)',
				'kl'=> 'Greenlandic, Kalaallisut',
				'gn'=> 'Guarani',
				'gu'=> 'Gujarati',
				'ht'=> 'Haitian, Haitian Creole',
				'ha'=> 'Hausa',
				'he'=> 'Hebrew',
				'hz'=> 'Herero',
				'hi'=> 'Hindi',
				'ho'=> 'Hiri Motu',
				'hu'=> 'Hungarian',
				'is'=> 'Icelandic',
				'io'=> 'Ido',
				'ig'=> 'Igbo',
				'id'=> 'Indonesian',
				'ia'=> 'Interlingua (International Auxiliary Language Association)',
				'ie'=> 'Interlingue',
				'iu'=> 'Inuktitut',
				'ik'=> 'Inupiaq',
				'ga'=> 'Irish',
				'it'=> 'Italian',
				'ja'=> 'Japanese',
				'jv'=> 'Javanese',
				'kn'=> 'Kannada',
				'kr'=> 'Kanuri',
				'ks'=> 'Kashmiri',
				'kk'=> 'Kazakh',
				'rw'=> 'Kinyarwanda',
				'kv'=> 'Komi',
				'kg'=> 'Kongo',
				'ko'=> 'Korean',
				'kj'=> 'Kwanyama, Kuanyama',
				'ku'=> 'Kurdish',
				'ky'=> 'Kyrgyz',
				'lo'=> 'Lao',
				'la'=> 'Latin',
				'lv'=> 'Latvian',
				'lb'=> 'Letzeburgesch, Luxembourgish',
				'li'=> 'Limburgish, Limburgan, Limburger',
				'ln'=> 'Lingala',
				'lt'=> 'Lithuanian',
				'lu'=> 'Luba-Katanga',
				'mk'=> 'Macedonian',
				'mg'=> 'Malagasy',
				'ms'=> 'Malay',
				'ml'=> 'Malayalam',
				'mt'=> 'Maltese',
				'gv'=> 'Manx',
				'mi'=> 'Maori',
				'mr'=> 'Marathi',
				'mh'=> 'Marshallese',
				'ro'=> 'Moldovan, Moldavian, Romanian',
				'mn'=> 'Mongolian',
				'na'=> 'Nauru',
				'nv'=> 'Navajo, Navaho',
				'nd'=> 'Northern Ndebele',
				'ng'=> 'Ndonga',
				'ne'=> 'Nepali',
				'se'=> 'Northern Sami',
				'no'=> 'Norwegian',
				'nb'=> 'Norwegian Bokmål',
				'nn'=> 'Norwegian Nynorsk',
				'ii'=> 'Nuosu, Sichuan Yi',
				'oc'=> 'Occitan (post 1500)',
				'oj'=> 'Ojibwa',
				'or'=> 'Oriya',
				'om'=> 'Oromo',
				'os'=> 'Ossetian, Ossetic',
				'pi'=> 'Pali',
				'pa'=> 'Panjabi, Punjabi',
				'ps'=> 'Pashto, Pushto',
				'fa'=> 'Persian',
				'pl'=> 'Polish',
				'pt'=> 'Portuguese',
				'qu'=> 'Quechua',
				'rm'=> 'Romansh',
				'rn'=> 'Rundi',
				'ru'=> 'Russian',
				'sm'=> 'Samoan',
				'sg'=> 'Sango',
				'sa'=> 'Sanskrit',
				'sc'=> 'Sardinian',
				'sr'=> 'Serbian',
				'sn'=> 'Shona',
				'sd'=> 'Sindhi',
				'si'=> 'Sinhala, Sinhalese',
				'sk'=> 'Slovak',
				'sl'=> 'Slovenian',
				'so'=> 'Somali',
				'st'=> 'Sotho, Southern',
				'nr'=> 'South Ndebele',
				'es'=> 'Spanish, Castilian',
				'su'=> 'Sundanese',
				'sw'=> 'Swahili',
				'ss'=> 'Swati',
				'sv'=> 'Swedish',
				'tl'=> 'Tagalog',
				'ty'=> 'Tahitian',
				'tg'=> 'Tajik',
				'ta'=> 'Tamil',
				'tt'=> 'Tatar',
				'te'=> 'Telugu',
				'th'=> 'Thai',
				'bo'=> 'Tibetan',
				'ti'=> 'Tigrinya',
				'to'=> 'Tonga (Tonga Islands)',
				'ts'=> 'Tsonga',
				'tn'=> 'Tswana',
				'tr'=> 'Turkish',
				'tk'=> 'Turkmen',
				'tw'=> 'Twi',
				'ug'=> 'Uighur, Uyghur',
				'uk'=> 'Ukrainian',
				'ur'=> 'Urdu',
				'uz'=> 'Uzbek',
				've'=> 'Venda',
				'vi'=> 'Vietnamese',
				'vo'=> 'Volap_k',
				'wa'=> 'Walloon',
				'cy'=> 'Welsh',
				'fy'=> 'Western Frisian',
				'wo'=> 'Wolof',
				'xh'=> 'Xhosa',
				'yi'=> 'Yiddish',
				'yo'=> 'Yoruba',
				'za'=> 'Zhuang, Chuang',
				'zu'=> 'Zulu'
			);
		}
		/**
		 *
		 */
        protected function getOrderStatusList(){
			$list = wc_get_order_statuses();

			$array = [];
			if($list){
				foreach ($list as $key => $value) {
					$_key = str_replace("wc-","",$key);
					if(in_array($_key,['processing','completed'])){
						$array[$_key] = $value;
					}
				}
				return $array;
			}
			return [];

		}

        protected function selected($name, array $data, string $esc_attr = null)
        {
            $html = sprintf('<select name="%s" class="select">',$name);
            if($data){
                foreach ($data as $key => $value){
                    $selected = (!empty($esc_attr) && $esc_attr === $key) ? "selected" : "";
                    $html.= sprintf('<option value="%s" %s >%s</option>',$key,$selected,$value);
                }
            }
            $html.="</select>";
            return $html;
        }

        private function intiOldData()
        {
            if(empty(get_option($this->visa_account_slug)) && !empty($this->merchantId) && !empty($this->password)){
                add_option($this->visa_account_slug,[
                    [
                        'visa_active' => "true",
                        'visa_title'   => $this->title,
                        'visa_type'   => "local",
                        'visa_interaction' => $this->interaction,
                        'visa_commission'      => $this->commission ?? 0,
                        'visa_merchantid'      => $this->merchantId,
                        'visa_password'           => $this->password,
                        'visa_is_test'  => $this->get_option( 'is_test'),
                    ]
                ]);
            }
        }

    }
    class WC_Alnazer_Gateway_VISA_NEW extends WC_Gateway_VISA{
        /**
         * Setup general properties for the gateway.
         */
        public function __construct($config= array())
        {
            parent::__construct();
            $this->init($config);
            $this->setConfigArray();
        }

        protected function init($config = []) {
            $this->icon               =  plugins_url( 'images/visa-logo.png' , __FILE__ );
            $this->method_description = __( 'Easy and fast and secure', 'wc_visa' );
            $this->has_fields         = false;
            if($config && count($config) > 0){
                foreach ($config as $key => $item) {
                    $this->{$key} = $item;
                }
            }
        }
    }

	/**
	 * Add the Gateway to WooCommerce
	 **/
	function woocommerce_add_wc_visa_gateway($methods) {
        $page = sanitize_text_field($_GET['page'] ?? null);
        $tab = sanitize_text_field($_GET['tab'] ?? null);
        if(is_admin() &&   $page === "wc-settings" && $tab === "checkout"){
            $methods[] = 'WC_Gateway_VISA';
        }else{
            $alnazer_visa_account_list = get_option("alnazer_visa_accounts");
            if(isset($alnazer_visa_account_list) && is_array($alnazer_visa_account_list) && count($alnazer_visa_account_list) > 0){
                foreach ($alnazer_visa_account_list as $key => $item) {
                    if(!is_admin() && $item['visa_active'] !== "true"){
                        continue;
                    }
                    $config = [
                        "id" =>  "wc_visa_$key",
                        "title" =>  $item["visa_title"],
                        "method_title" =>  $item["visa_title"],
                        "type" =>  $item["visa_type"],
                        "interaction" =>  $item["visa_interaction"],
                        "commission" =>  $item["visa_commission"],
                        "merchantId" =>  $item["visa_merchantid"],
                        "apiusername" => "merchant.".$item["visa_merchantid"],
                        "password" =>  $item["visa_password"],
                        'is_test' => $item['visa_is_test']

                    ];
                    $methods[] = new WC_Alnazer_Gateway_VISA_NEW($config);
                }
            }
        }

		return $methods;
	}
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_wc_visa_gateway' );
}

