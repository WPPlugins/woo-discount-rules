<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Required Version of WooCommerce to Run.
 */
define('WOO_DISCOUNT_REQUIRED_WOOCOMMERCE_VERSION', '3.5');
/**
 * Plugin Directory.
 */
define('WOO_DISCOUNT_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
/**
 * Plugin Directory URI.
 */
define('WOO_DISCOUNT_URI', untrailingslashit(plugin_dir_url(__FILE__)));
/**
 * Plugin Base Name.
 */
define('WOO_DISCOUNT_PLUGIN_BASENAME', plugin_basename(__FILE__));

if(!function_exists('get_plugin_data')){
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
/**
 * Version of Woo Discount Rules.
 */
$pluginDetails = get_plugin_data(plugin_dir_path(__FILE__).'woo-discount-rules.php');
define('WOO_DISCOUNT_VERSION', $pluginDetails['Version']);

/**
 * check WooCommerce version
 */
if (!function_exists('woo_discount_checkWooCommerceVersion3')) {
    function woo_discount_checkWooCommerceVersion3($version = "3.0")
    {
        // If get_plugins() isn't available, require it
        if ( ! function_exists( 'get_plugins' ) )
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        // Create the plugins folder and file variables
        $plugin_folder = get_plugins( '/' . 'woocommerce' );
        $plugin_file = 'woocommerce.php';

        // If the plugin version number is set, return it
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
            $woocommerce_version = $plugin_folder[$plugin_file]['Version'];

        } else {
            // Otherwise return null
            $woocommerce_version = null;
        }

        if( version_compare( $woocommerce_version, $version, ">=" ) ) {
            return true;
        }
    }
}


$woocommerce_v3 = woo_discount_checkWooCommerceVersion3();
if($woocommerce_v3){
    include_once('includes/pricing-rules-3.php');
    include_once('helper/general-helper-3.php');
    include_once('includes/cart-rules-3.php');
} else {    
    include_once('includes/pricing-rules.php');
    include_once('helper/general-helper.php');
    include_once('includes/cart-rules.php');
}
include_once('includes/discount-base.php');
include_once('helper/purchase.php');
require_once __DIR__ . '/vendor/autoload.php';

// -------------------------- updater -----------------------------------------------------
require plugin_dir_path( __FILE__ ).'/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';

$purchase_helper = new woo_dicount_rules_purchase();
$purchase_helper->init();
$update_url = $purchase_helper->getUpdateURL();

$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    $update_url,
    plugin_dir_path( __FILE__ ).'woo-discount-rules.php',
    'woo-discount-rules'
);

add_action( 'after_plugin_row', array($purchase_helper, 'woodisc_after_plugin_row'),10,3 );

add_action('wp_ajax_forceValidateLicenseKey', array($purchase_helper, 'forceValidateLicenseKey'));

add_action( 'admin_notices', array($purchase_helper, 'errorNoticeInAdminPages'));

// -------------------------- end updater -------------------------------------------------


// --------------------------------------------------GENERAL HOOK-------------------------------------------------------

/** Initiating Plugin */
$discountBase = new woo_dicount_rules_WooDiscountBase();
$pricingRules = new woo_dicount_rules_pricingRules();

// Enqueue Scripts/Styles - in head of admin page
add_action('admin_enqueue_scripts', 'woo_discount_addHeadScript');
// Init in Admin Menu
add_action('admin_menu', array($discountBase, 'adminMenu'));

$postData = \FlycartInput\FInput::getInstance();
// ---------------------------------------------------------------------------------------------------------------------


// --------------------------------------------------WOO DISCOUNT HOOK--------------------------------------------------


// ---------------------------------------------------------------------------------------------------------------------


// ----------------------------------------------------WooCommerce HOOK-------------------------------------------------

// Handling Tight update with wooCommerce Changes.
$empty_add_to_cart = $postData->get('add-to-cart');
$empty_apply_coupon = $postData->get('apply_coupon');
$empty_update_cart = $postData->get('update_cart');
$empty_proceed = $postData->get('proceed');
if ((!empty($empty_add_to_cart) && is_numeric($postData->get('add-to-cart'))) || $postData->get('action', false) == 'woocommerce_add_to_cart') {
//    add_action('woocommerce_add_to_cart', array($discountBase, 'handleDiscount'), 19);
} else if (!empty($empty_apply_coupon) || !empty($empty_update_cart) || !empty($empty_proceed)) {
//    add_action('woocommerce_before_cart_item_quantity_zero', array($discountBase, 'handleDiscount'), 100);
    add_action('woocommerce_after_cart_item_quantity_update', array($discountBase, 'handleDiscount'), 100);

//    add_action('woocommerce_update_cart_action_cart_updated', array($discountBase, 'handleDiscount'));
} else {
    add_action('woocommerce_cart_loaded_from_session', array($discountBase, 'handleDiscount'), 100);
}

// Manually Update Line Item Name.
add_filter('woocommerce_cart_item_name', array($discountBase, 'modifyName'));

// Remove Filter to make the previous one as last filter.
remove_filter('woocommerce_cart_item_name', 'filter_woocommerce_cart_item_name', 10, 3);

// Alter the Display Price HTML.
add_filter('woocommerce_cart_item_price', array($pricingRules, 'replaceVisiblePricesCart'), 100, 3);
// Older Version support this hook.
add_filter('woocommerce_cart_item_price_html', array($pricingRules, 'replaceVisiblePricesCart'), 100, 3);

// Pricing Table of Individual Product.
add_filter('woocommerce_before_add_to_cart_form', array($pricingRules, 'priceTable'));

// Updating Log After Creating Order
add_action('woocommerce_thankyou', array($discountBase, 'storeLog'));
// ---------------------------------------------------------------------------------------------------------------------

// --------------------------------------------------AJAX REQUEST-------------------------------------------------------

add_action('wp_ajax_savePriceRule', array($discountBase, 'savePriceRule'));
add_action('wp_ajax_saveCartRule', array($discountBase, 'saveCartRule'));
add_action('wp_ajax_saveConfig', array($discountBase, 'saveConfig'));

add_action('wp_ajax_UpdateStatus', array($discountBase, 'updateStatus'));
add_action('wp_ajax_RemoveRule', array($discountBase, 'removeRule'));

add_action( 'woocommerce_after_checkout_form', array($discountBase, 'addScriptInCheckoutPage'));

// ---------------------------------------------------------------------------------------------------------------------

// --------------------------------------------------GENERAL FUNCTIONS--------------------------------------------------

/**
 * To Append Script Wordpress.
 */
if (!function_exists('woo_discount_addHeadScript')) {
    function woo_discount_addHeadScript()
    {
        //
    }
}

/**
 * Adding Admin Page Script.
 */
if (!function_exists('woo_discount_adminPageScript')) {
    function woo_discount_adminPageScript()
    {
        $status = false;
        $postData = \FlycartInput\FInput::getInstance();
        // Plugin scripts should run only in plugin page.
        if (is_admin()) {
            if ($postData->get('page', false) == 'woo_discount_rules') {
                $status = true;
            }
            // By Default, the landing page also can use this script.
        } elseif (!is_admin()) {
            //  $status = true;
        }

        if ($status) {
            wp_register_style('woo_discount_style', WOO_DISCOUNT_URI . '/assets/css/style.css');
            wp_enqueue_style('woo_discount_style');

            wp_register_style('woo_discount_style_custom', WOO_DISCOUNT_URI . '/assets/css/custom.css');
            wp_enqueue_style('woo_discount_style_custom');

            wp_register_style('woo_discount_style_tab', WOO_DISCOUNT_URI . '/assets/css/tabbablePanel.css');
            wp_enqueue_style('woo_discount_style_tab');

            // For Implementing Select Picker Library.
            wp_register_style('woo_discount_style_select', WOO_DISCOUNT_URI . '/assets/css/bootstrap.select.min.css');
            wp_enqueue_style('woo_discount_style_select');

            wp_enqueue_script('woo_discount_script_select', WOO_DISCOUNT_URI . '/assets/js/bootstrap.select.min.js');


            // -------------------------------------------------------------------------------------------------------------

            wp_register_style('woo_discount_bootstrap', WOO_DISCOUNT_URI . '/assets/css/bootstrap.min.css');
            wp_enqueue_style('woo_discount_bootstrap');

            wp_register_script('woo_discount_jquery_ui_js_2', WOO_DISCOUNT_URI . '/assets/js/bootstrap.min.js');
            wp_enqueue_script('woo_discount_jquery_ui_js_2');

            wp_register_style('woo_discount_jquery_ui_css', WOO_DISCOUNT_URI . '/assets/css/jquery-ui.css');
            wp_enqueue_style('woo_discount_jquery_ui_css');

            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-datepicker');

//        wp_register_style('woo_discount_select2_css', WOO_DISCOUNT_URI . '/assets/css/select2.min.css');
//        wp_enqueue_style('woo_discount_select2_css');
//
//        wp_register_script('woo_discount_select2_js', WOO_DISCOUNT_URI . '/assets/js/select2.min.js');
//        wp_enqueue_script('woo_discount_select2_js');

            wp_enqueue_script('woo_discount_script', WOO_DISCOUNT_URI . '/assets/js/app.js');

        }
    }
}

// ---------------------------------------------------------------------------------------------------------------------