<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $woocommerce;

/**
 * Class woo_dicount_rules_WooDiscountBase
 */
if (!class_exists('woo_dicount_rules_WooDiscountBase')) {
    class woo_dicount_rules_WooDiscountBase
    {
        /**
         * @var string
         */
        public $default_page = 'pricing-rules';

        /**
         * @var string
         */
        public $default_option = 'woo-discount-config';

        /**
         * @var array
         */
        private $instance = array();

        /**
         * woo_dicount_rules_WooDiscountBase constructor.
         */
        public function __construct()
        {

        }

        /**
         * Singleton Instance maker.
         *
         * @param $name
         * @return bool
         */
        public function getInstance($name)
        {
            if (!isset($this->instance[$name])) {
                if (class_exists($name)) {
                    $this->instance[$name] = new $name;
                    $instance = $this->instance[$name];
                } else {
                    $instance = false;
                }
            } else {
                $instance = $this->instance[$name];
            }
            return $instance;
        }

        /**
         * Managing discount of Price and Cart.
         */
        public function handleDiscount()
        {
            global $woocommerce;

            $price_discount = $this->getInstance('woo_dicount_rules_pricingRules');
            $cart_discount = $this->getInstance('woo_dicount_rules_cartRules');

            $price_discount->analyse($woocommerce);
            $cart_discount->analyse($woocommerce);
        }

        /**
         * For adding script in checkout page
         * */
        public function addScriptInCheckoutPage(){
            $script = '<script type="text/javascript">
                    jQuery( function( $ ) {
                        $( document.body ).on( "blur", "input#billing_email", function() {
                            $("select#billing_country").trigger("change");
                        })
                    });
                </script>';
            echo $script;
        }

        /**
         * WooCommerce hook to change the name of a product.
         *
         * @param $title
         * @return mixed
         */
        public function modifyName($title)
        {
            //
            return $title;
        }

        /**
         * Finally, on triggering the "Thank You" hook by WooCommerce,
         * Overall session data's are stored to the order's meta as "woo_discount_log".
         *
         * @param integer $order_id Order ID.
         */
        public function storeLog($order_id)
        {
            $log['price_discount'] = WC()->session->get('woo_price_discount', array());
            $log['cart_discount'] = WC()->session->get('woo_cart_discount', array());

            add_post_meta($order_id, 'woo_discount_log', json_encode($log));

            // Reset the Coupon Status.
            WC()->session->set('woo_coupon_removed', '');
        }

        /**
         * Create New Menu On WooCommerce.
         */
        public function adminMenu()
        {
            if (!is_admin()) return;

            global $submenu;
            if (isset($submenu['woocommerce'])) {
                add_submenu_page(
                    'woocommerce',
                    'Woo Discount Rules',
                    'Woo Discount Rules',
                    'edit_posts',
                    'woo_discount_rules',
                    array($this, 'viewManager')
                );
            }
        }

        /**
         * Update the Status of the Rule Set.
         */
        public function updateStatus()
        {
            $postData = \FlycartInput\FInput::getInstance();
            $id = $postData->get('id', false);
            if ($id) {
                $status = get_post_meta($id, 'status', false);
                if (isset($status[0])) {
                    $state = ($status[0] == 'publish') ? 'disable' : 'publish';
                    update_post_meta($id, 'status', $state);
                } else {
                    add_post_meta($id, 'status', 'disable');
                    $state = 'disable';
                }
                echo ucfirst($state);
            }
            die();
        }

        /**
         * Remove the Rule Set.
         */
        public function removeRule()
        {
            $postData = \FlycartInput\FInput::getInstance();
            $id = $postData->get('id', false);
            if ($id) {
                try {
                    $id = intval($id);
                    if (!$id) return false;
                    wp_delete_post($id);
                } catch (Exception $e) {
                    //
                }
            }
            die();
        }
//    -------------------------------------- PRICE RULES ---------------------------------------------------------------
        /**
         * Saving the Price Rule.
         *
         * @return bool
         */
        public function savePriceRule()
        {
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            $params = array();
            if (!isset($request['data'])) return false;
            parse_str($request['data'], $params);

            $pricing_rule = $this->getInstance('woo_dicount_rules_pricingRules');
            $pricing_rule->save($params);
            die();
        }

//    -------------------------------------- CART RULES ----------------------------------------------------------------
        /**
         * Saving the Cart Rule.
         *
         * @return bool
         */
        public function saveCartRule()
        {

            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            $params = array();
            if (!isset($request['data'])) return false;
            parse_str($request['data'], $params);
            $this->parseFormWithRules($params, true);
            $pricing_rule = $this->getInstance('woo_dicount_rules_cartRules');
            $pricing_rule->save($params);
            die();
        }

        /**
         * Making the reliable end data to store.
         *
         * @param $cart_rules
         * @param bool $isCartRules
         */
        public function parseFormWithRules(&$cart_rules, $isCartRules = false)
        {
            $cart_rules['discount_rule'] = $this->generateFormData($cart_rules, $isCartRules);
        }

        /**
         * @param $cart_rules
         * @param bool $isCartRules
         * @return array
         */
        public function generateFormData($cart_rules, $isCartRules = false)
        {
            $link = $this->fieldLink();

            $discount_list = array();
            // Here, Eliminating the Cart's rule with duplicates.
            $discount_rule = (isset($cart_rules['discount_rule']) ? $cart_rules['discount_rule'] : array());
            if ($isCartRules) {
                foreach ($discount_rule as $index => $value) {

                    // The Type of Option should get value from it's native index.
                    // $link[$value['type']] will gives the native index of the "type"

                    if (isset($link[$value['type']])) {
                        if (isset($value[$link[$value['type']]])) {
                            $discount_list[$index][$value['type']] = $value[$link[$value['type']]];
                        }
                    } else {
                        $discount_list[$index][$value['type']] = $value['option_value'];
                    }
                }
            }
            return $discount_list;

        }

        /**
         * @return array
         */
        public function fieldLink()
        {
            // TODO: Check Subtotal Link
            return array(
                'products_atleast_one' => 'product_to_apply',
                'products_not_in' => 'product_to_apply',

                'categories_atleast_one' => 'category_to_apply',
                'categories_not_in' => 'category_to_apply',

                'users_in' => 'users_to_apply',
                'roles_in' => 'user_roles_to_apply',
                'shipping_countries_in' => 'countries_to_apply'
            );
        }

        // ----------------------------------------- CART RULES END --------------------------------------------------------


        // -------------------------------------------SETTINGS--------------------------------------------------------------

        /**
         *
         */
        public function saveConfig($licenceValidate = 0)
        {
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            $params = array();
            if (isset($request['data'])) {
                parse_str($request['data'], $params);
            }

            if (is_array($request)) {
                if(isset($params['show_draft']) && $params['show_draft']){
                    $params['show_draft'] = 1;
                } else {
                    $params['show_draft'] = 0;
                }
                foreach ($params as $index => $item) {
//                $params[$index] = woo_dicount_rules_generalHelper::makeString($item);
                    $params[$index] = $item;
                }
                $params = json_encode($params);
            }
//        else {
//            $params = woo_dicount_rules_generalHelper::makeString($params);
//        }

            if (get_option($this->default_option)) {
                update_option($this->default_option, $params);
            } else {
                add_option($this->default_option, $params);
            }
            if(!$licenceValidate)
                die();
        }

        /**
         * @return array
         */
        public function getBaseConfig()
        {
            $option = get_option($this->default_option);
            if (!$option || is_null($option)) {
                return array();
            } else {
                return $option;
            }
        }

        // -------------------------------------------SETTINGS END----------------------------------------------------------

        /**
         * @param $request
         * @return bool
         */
        public function checkSubmission($request)
        {
            if (isset($request['form']) && !empty($request['form'])) {
                $form = sanitize_text_field($request['form']);
                if (strpos($form, '_save') === false) return false;
                // For Saving Form
                $form = str_replace('_save', '', $form);
                // To Verify, the submitted form is in the Registered List or Not
                if (in_array($form, $this->formList())) {
                    if (isset($request['page'])) {
                        switch ($form) {
                            case 'pricing_rules':
                                die(123);
                                $pricing_rule = $this->getInstance('woo_dicount_rules_pricingRules');
                                $pricing_rule->save($request);
                                break;
                            case 'cart_rules':
                                $cart_rules = $this->getInstance('woo_dicount_rules_cartRules');
                                $cart_rules->save($request);
                                break;
                            case 'settings':
                                $this->save($request);
                                break;
                            default:
                                // Invalid Submission.
                                break;
                        }
                    }
                }
            }
        }

        /**
         * @param $option
         */
        public function checkAccess(&$option)
        {
            $postData = \FlycartInput\FInput::getInstance();
            // Handling View
            if ($postData->get('view', false)) {
                $option = $option . '-view';
                // Type : Price or Cart Discounts.
            } elseif ($postData->get('type', false)) {
                if ($postData->get('tab', false)) {
                    if ($postData->get('tab', '') == 'cart-rules') {
                        $option = 'cart-rules-new';
                        if ($postData->get('type', '') == 'view') $option = 'cart-rules-view';
                    }
                } else {
                    $option = $option . '-' . $postData->get('type', '');
                }
            }
        }

        /**
         * @param $request
         */
        public function save($request)
        {
            // Save General Settings of the Plugin.
        }

        /**
         * @return array
         */
        public function formList()
        {
            return array(
                'pricing_rules',
                'cart_rules',
                'settings'
            );
        }

        /**
         *
         */
        public function viewManager()
        {
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();
            $this->checkSubmission($request);

            // Adding Plugin Page Script
            if (function_exists('woo_discount_adminPageScript')) {
                woo_discount_adminPageScript();
            }
            // Loading Instance.
            $generalHelper = $this->getInstance('woo_dicount_rules_generalHelper');
            // Sanity Check.
            if (!$generalHelper) return;
            // Getting Active Tab.
            $tab = $generalHelper->getCurrentTab();

            $path = $this->getPath($tab);

            // Manage Tab.
            $tab = (isset($tab) ? $tab : $this->default_page);
            $html = '';
            // File Check.
            if (file_exists($path)) {
                $data = array();
                $this->fetchData($tab, $data);
                // Processing View.
                $html = $generalHelper->processBaseView($path, $data);
            }
            echo $html;
        }

        /**
         * @param $tab
         * @return mixed
         */
        public function getPath(&$tab)
        {
            $this->checkAccess($tab);
            $pages = $this->adminPages();
            // Default tab.
            $path = $pages[$this->default_page];

            // Comparing Available Tab with Active Tab.
            if (isset($pages[$tab])) {
                $path = $pages[$tab];
            }
            return $path;
        }

        /**
         * @param $type
         * @param $data
         */
        public function fetchData($type, &$data)
        {
            $postData = \FlycartInput\FInput::getInstance();
            $request = $postData->getArray();

            $helper = new woo_dicount_rules_generalHelper();
            $isPro = $helper->checkPluginState();

            switch ($type) {
                // Managing Price Rules View.
                case 'pricing-rules':
                    $pricing_rule = $this->getInstance('woo_dicount_rules_pricingRules');
                    $data = $pricing_rule->getRules();
                    break;
                // Managing Cart Rules View.
                case 'cart-rules':
                    $cart_rule = $this->getInstance('woo_dicount_rules_cartRules');
                    $data = $cart_rule->getRules();
                    break;
                // Managing View of Settings.
                case 'settings':
                    $data = $this->getBaseConfig();
                    break;

                // Managing View of Pricing Rules.
                case 'pricing-rules-new':
                    $data = new stdClass();
                    $data->form = 'pricing_rules_save';
                    if (!$isPro) {
                        $pricing_rule = $this->getInstance('woo_dicount_rules_pricingRules');
                        $data = $pricing_rule->getRules();
                        if (count($data) >= 3) die('You are restricted to process this action.');
                    }
                    break;

                // Managing View of Pricing Rules.
                case 'pricing-rules-view':

                    $view = false;
                    // Handling View
                    if (isset($request['view'])) {
                        $view = $request['view'];
                    }
                    $html = $this->getInstance('woo_dicount_rules_pricingRules');
                    $out = $html->view($type, $view);
                    if (isset($out) && !empty($out)) {
                        $data = $out;
                    }
                    $data->form = 'pricing_rules_save';
                    break;

                // Managing View of Cart Rules.
                case 'cart-rules-view':
                    $view = false;
                    // Handling View
                    if (isset($request['view'])) {
                        $view = $request['view'];
                    } else {

                        if (!$isPro) {
                            $cart_rule = $this->getInstance('woo_dicount_rules_cartRules');
                            $total_record = $cart_rule->getRules(true);
                            if ($total_record >= 3) wp_die('You are restricted to process this action.');
                        }
                    }

                    $html = $this->getInstance('woo_dicount_rules_cartRules');
                    $out = $html->view($type, $view);
                    if (isset($out) && !empty($out)) {
                        $data[] = $out;
                    }
                    break;
                // Managing View of Cart Rules.
                case 'cart-rules-new':
                    if (!$isPro) {
                        $cart_rule = $this->getInstance('woo_dicount_rules_cartRules');
                        $total_record = $cart_rule->getRules(true);
                        if ($total_record >= 3) wp_die('You are restricted to process this action.');
                    }
                    break;

                default:
                    $data = array();

                    break;
            }

        }

        /**
         * @return array
         */
        public function adminPages()
        {
            return array(
                $this->default_page => WOO_DISCOUNT_DIR . '/view/pricing-rules.php',
                'cart-rules' => WOO_DISCOUNT_DIR . '/view/cart-rules.php',
                'settings' => WOO_DISCOUNT_DIR . '/view/settings.php',

                // New Rule also access the same "View" to process
                'pricing-rules-new' => WOO_DISCOUNT_DIR . '/view/view-pricing-rules.php',
                'cart-rules-new' => WOO_DISCOUNT_DIR . '/view/view-cart-rules.php',

                // Edit Rules
                'pricing-rules-view' => WOO_DISCOUNT_DIR . '/view/view-pricing-rules.php',
                'cart-rules-view' => WOO_DISCOUNT_DIR . '/view/view-cart-rules.php'
            );
        }

        /**
         *
         */
        public function getOption()
        {

        }

    }
}