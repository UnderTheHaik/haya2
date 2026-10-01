<?php
/** Plugin Name: Haya2 Local Demo Payments */
defined('ABSPATH') || exit;
add_action('plugins_loaded', function () {
    if (!class_exists('WC_Payment_Gateway')) return;
    class Haya2_Demo_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id='haya2_demo'; $this->method_title='Local demo payment'; $this->method_description='Local simulation only; no payment provider is contacted.'; $this->title='Test payment — no charge'; $this->description='Simulates an approved or declined payment. Do not enter card details.'; $this->enabled='yes'; $this->has_fields=true; $this->supports=['products'];
        }
        public function is_available() { return wp_get_environment_type()==='local' && parent::is_available(); }
        public function payment_fields() { echo '<p>'.esc_html($this->description).'</p><label>Test result <select name="haya2_result"><option value="approved">Approved</option><option value="declined">Declined</option></select></label>'; }
        public function process_payment($order_id) {
            if (wp_get_environment_type()!=='local') return ['result'=>'failure'];
            if (sanitize_text_field(wp_unslash($_POST['haya2_result'] ?? 'approved'))==='declined') { wc_add_notice('Demo payment declined. Choose Approved to retry.','error'); return ['result'=>'failure']; }
            $order=wc_get_order($order_id); $order->payment_complete('LOCAL-DEMO-'.$order_id); $order->add_order_note('Local simulated payment. No funds collected.'); WC()->cart->empty_cart(); return ['result'=>'success','redirect'=>$this->get_return_url($order)];
        }
    }
    add_filter('woocommerce_payment_gateways', function ($gateways) { $gateways[]='Haya2_Demo_Gateway'; return $gateways; });
});
// Local demo must not send emails to addresses entered during testing.
add_filter('pre_wp_mail', function ($value) { return wp_get_environment_type()==='local' ? true : $value; });
// SQLite does not implement WooCommerce's MySQL row-lock reservation query.
// The single-user local demo retains stock validation and reduction on payment.
add_filter('woocommerce_hold_stock_for_checkout', function ($enabled) {
    return wp_get_environment_type()==='local' && defined('DB_ENGINE') && DB_ENGINE==='sqlite' ? false : $enabled;
});
