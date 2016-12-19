<?php
/*
Plugin Name: WooCommerce Mercanet POST Payment Gateway
Depends: WooCommerce
Plugin URI: https://github.com/skiane
Description: Mercanet (POST) Payment gateway for woocommerce, provided as is, no support
Version: 1.0
Author: Stephane Fritsch
Author URI: https://github.com/skiane
*/
// XXX LOGO
/**
 * Mercanet Standard Payment Gateway.
 *
 * Provides a Mercanet Standard Payment Gateway.
 *
 * @class 		WC_Gateway_Mercanet
 * @extends		WC_Payment_Gateway
 * @version		1.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Stephane Fritsch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'init_mercanet_gateway_class' );
add_filter( 'woocommerce_payment_gateways', 'add_mercanet_gateway_class' );

function init_mercanet_gateway_class() {

/**
 * WC_Gateway_Mercanet Class.
 */
class WC_Gateway_Mercanet extends WC_Payment_Gateway {

	/** @var bool Whether or not logging is enabled */
	public static $log_enabled = false;

	/** @var WC_Logger Logger instance */
    // XXX log not working
	public static $log = true;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'mercanet';
		$this->has_fields         = false;
		$this->order_button_text  = __( 'Order', 'woocommerce' );
		$this->method_title       = __( 'Mercanet', 'woocommerce' );
		$this->method_description = sprintf( __( 'Woocommerce payment gateway for Mercanet POST.'));
		$this->supports           = array(
			'products',
			//'refunds'
		);

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();


// XXX Which one ?
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'receipt_page'));
// XXX Which one ?
        add_action('woocommerce_api_' .strtolower( get_class( $this ) ), array( $this, 'check_mercanet_response' ) );
        add_action('init', array(&$this, 'check_mercanet_response'));


		// Define user set variables.
		$this->title          = $this->get_option( 'title' );
		$this->description    = $this->get_option( 'description' );
		$this->testmode       = 'yes' === $this->get_option( 'testmode', 'no' );
		$this->debug          = 'yes' === $this->get_option( 'debug', 'no' );
		$this->identity_token = $this->get_option( 'identity_token' );

        if ( $this->testmode ) {
            // These setting can be used to test the payment workflow. Not for real !
            $this->url = "https://payment-webinit.simu.mercanet.bnpparibas.net/paymentInit";
            $this->merchantId = '002001000000001';
            $this->secretKey = '002001000000001_KEY1';
            $this->keyVersion = 1;
        } else {
            $this->url = "https://payment-webinit.mercanet.bnpparibas.net/paymentInit";
            $this->merchantId     = $this->get_option( 'merchantid' );
            $this->secretKey = $this->get_option( 'secretkey' );
            $this->keyVersion = $this->get_option( 'keyversion' );
        }

		self::$log_enabled    = $this->debug;

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = 'no';
		} else {
            // XXX additional tests ???
			if ( $this->identity_token ) {
			}
		}

	}
	/**
	 * Logging method.
	 * @param string $message
	 */
	public static function log( $message ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->add( 'mercanet', $message );
		}
	}

	/**
	 * Get gateway icon.
	 * @return string
	 */
	public function get_icon() {
		$icon_html = '';
		$icon      = (array) $this->get_icon_image( WC()->countries->get_base_country() );

		foreach ( $icon as $i ) {
			$icon_html .= '<img src="' . esc_attr( $i ) . '" alt="' . esc_attr__( 'Mercanet Acceptance Mark', 'woocommerce' ) . '" />';
		}

        $icon_html = '';

		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}

	/**
	 * Get the link for an icon based on country.
	 * @param  string $country
	 * @return string
	 */
	protected function get_icon_url( $country ) {
		return 'https://www.mercanet.com/' . strtolower( $country ) . '/cgi-bin/webscr?cmd=xpt/Marketing/general/WIMercanet-outside';
	}

	/**
	 * Get Mercanet images for a country.
	 * @param  string $country
	 * @return array of image URLs
	 */
	protected function get_icon_image( $country ) {
        $icon = 'https://mercanet-bo.bnpparibas.net/imgs/logo_hp_bnp.gif';
		//return apply_filters( 'woocommerce_mercanet_icon', $icon );
        return $icon;
	}

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_mercanet_supported_currencies', array( 'EUR', ) ) );
	}

	/**
	 * Admin Panel Options.
	 * - Options for bits like 'title' and availability on a country-by-country basis.
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		if ( $this->is_valid_for_use() ) {
			parent::admin_options();
		} else {
			?>
			<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'Mercanet does not support your store currency.', 'woocommerce' ); ?></p></div>
			<?php
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include( 'includes/settings-mercanet.php' );
	}

	/**
	 * Get the transaction URL.
	 * @param  WC_Order $order
	 * @return string
	 */
	public function get_transaction_url( $order ) {
		if ( $this->testmode ) {
            $this->view_transaction_url = "https://payment-webinit.simu.mercanet.bnpparibas.net/paymentInit";
		} else {
            $this->view_transaction_url = "https://payment-webinit.mercanet.bnpparibas.net/paymentInit";
		}
		return parent::get_transaction_url( $order );
	}

    function getSign($data) {
            return hash('sha256', $data.$this->secretKey);
    }

    public function receipt_page( $order_id ){
        if ( 0 < $order_id ) {
            $order = wc_get_order( $order_id );
            $amount = $order->get_total()*100;
        }
        if ($order->get_status() == "pending") {
            $returnUrl_base = WC()->api_request_url( 'WC_Gateway_Mercanet' );
            $returnUrl = add_query_arg( 'wc-api', get_class( $this ), $returnUrl_base );
            $automaticResponseUrl = add_query_arg( 'source', 'auto', $returnUrl );
            $returnUrl = $_SERVER['SCRIPT_URI'].$_SERVER['REQUEST_URI'];

            $merchantId = $this->merchantId;
            $keyVersion = $this->keyVersion;
            $transactionRef = "cernuschi". $order_id;
            $data = "amount=$amount|currencyCode=978|merchantId=$merchantId|normalReturnUrl=$returnUrl|automaticResponseUrl=$automaticResponseUrl|transactionReference=$transactionRef|keyVersion=$keyVersion";
            $sign = self::getSign($data);
    ?>
<div class="box" style="height: 100px; width: 400px; margin-top:50px; ">
<div style=" margin:30px; ">
    <img style="height: 50px;" src="<?php echo self::get_icon_image(); ?>">
        <p style="color: white;">Pour finaliser votre paiement, cliquez sur le bouton ci-dessous afin d'être redirigé vers le site sécurisé Mercanet (Service de BNP Paribas).<p>
    <form method="post" action="<?php echo($this->url); ?>">
    <input type="hidden" name="Data" value="<?php echo($data); ?>">
    <input type="hidden" name="InterfaceVersion" value="HP_2.9">
    <input type="hidden" name="Seal" value="<?php echo($sign); ?>">
        <input type="submit" value=">> Paiement par carte bancaire <<">
      </form>
</div>
</div>
    <?
        } else {
            // XXX Affichage commande validée
        }
	}

	/**
	 * Process the payment and return the result.
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
        log("Process Payment : order_id = $order_id");
        $order          = wc_get_order( $order_id );
return array(
                 'result'   => 'success',
                 'redirect' => $this->get_return_url( $order )
             );
	}

	/**
	 * Can the order be refunded via Mercanet?
	 * @param  WC_Order $order
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		return $order && $order->get_transaction_id();
	}

	/**
	 * Process a refund if supported.
	 * @param  int    $order_id
	 * @param  float  $amount
	 * @param  string $reason
	 * @return bool True or false based on success, or a WP_Error object
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $this->can_refund_order( $order ) ) {
			$this->log( 'Refund Failed: No transaction ID' );
			return new WP_Error( 'error', __( 'Refund Failed: No transaction ID', 'woocommerce' ) );
		}

	}

    public function check_mercanet_response(){
        $ok = false;
        if (!isset($_POST['Data'])) {
            return;
        }
        if (!isset($_POST['Seal'])) {
            return;
        }
        $response = explode('|',$_POST['Data']);
        $sign = $_POST['Seal'];
        if ($sign != self::getSign($_POST['Data'])) {
            // Seal does not match
            return;
        }
        $parsedResponse = Array();
        foreach ($response as $v) {
            list($k, $v) = explode('=',$v);
            $parsedResponse[ $k ] = $v;
        }
        if (!isset($parsedResponse['transactionReference'])) {
            // No order number
            return;
        }
        ob_flush();
        $prefix = "cernuschi";
        $str = $parsedResponse['transactionReference'];
        if (substr($str, 0, strlen($prefix)) == $prefix) {
            $order_id = substr($str, strlen($prefix));
        } else {
            return;
        }
        $order = new WC_Order( $order_id );
        global $woocommerce;
        switch ($parsedResponse['responseCode']) {
            case 0:
                if (isset($_GET['source']) && $order->get_status() == "pending") {
                    $order -> payment_complete();
                    $notes = 'Paiement Mercanet OK';
                    $order -> add_order_note($notes);
                    $woocommerce -> cart -> empty_cart();
                    $ok = true;
                } elseif (!isset($_GET['source']) && $order->get_status() == "processing") {
                    echo 'Retour boutique normal<p>';
                    ob_flush();
                    $ok = true;
                } else {
                    $notes = 'XXX paiement Mercanet '.$order->get_status();
                    $order -> add_order_note($notes);
                }
                break;
            case 17:
                $notes = "Mercanet: Paiement abandonné par l'utilisateur";
                $order -> add_order_note($notes);
                break;
            default:
                $notes = 'XXX paiement Mercanet ERROR';
                $order -> add_order_note($notes);
                break;
        }
        if ($ok) {
            echo "Success<p>";
            exit;
        } else {
/* XXX DEBUG
            echo '<p>';
            var_dump($_POST);
            echo '<p>';
            var_dump($this);
            echo '<p>';
            var_dump($parsedResponse);
*/
            ob_flush();
        }
    }
}
}

function add_mercanet_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_Mercanet';
    return $methods;
}
