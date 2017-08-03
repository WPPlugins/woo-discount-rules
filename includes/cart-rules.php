<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
include_once(WOO_DISCOUNT_DIR . '/helper/general-helper.php');
include_once(WOO_DISCOUNT_DIR . '/includes/discount-base.php');

/**
 * Class woo_dicount_rules_cartRules
 */
if (!class_exists('woo_dicount_rules_cartRules')) {
    class woo_dicount_rules_cartRules
    {
        /**
         * @var string
         */
        private $option_name = 'woo_discount_cart_option';

        /**
         * @var string
         */
        public $post_type = 'woo_discount_cart';

        /**
         * @var bool
         */
        public $discount_applied = false;

        /**
         * @var
         */
        private $rules;

        /**
         * @var
         */
        public $rule_sets;

        /**
         * @var array
         */
        public $cart_items;

        /**
         * @var
         */
        public $sub_total;

        /**
         * @var int
         */
        public $discount_total = 0;

        /**
         * @var array
         */
        public $coupon_list;

        /**
         * @var string
         */
        public $coupon_code;

        /**
         * @var
         */
        public $matched_sets;

        public $postData;

        /**
         * woo_dicount_rules_cartRules constructor.
         */
        public function __construct()
        {
            global $woocommerce;

            $this->postData = \FlycartInput\FInput::getInstance();
            $this->cart_items = (isset($woocommerce->cart->cart_contents) ? $woocommerce->cart->cart_contents : array());
            $this->calculateCartSubtotal();
            $this->coupon_list = (isset($woocommerce->cart->applied_coupons) ? $woocommerce->cart->applied_coupons : array());

            // Check for Remove Coupon Request.
            if (!is_null($this->postData->get('remove_coupon', null))) $this->removeWoocommerceCoupon($this->postData->get('remove_coupon'));

            // Update Coupon Code
            $this->coupon_code = strtolower($this->getCouponCode());


        }

        /**
         * Save Cart Configs.
         *
         * @param array $request bulk request data.
         * @return bool
         */
        public function save($request)
        {
            foreach ($request as $index => $value) {
                if ($index !== 'discount_rule') {
//                $request[$index] = generalHelper::makeString($value);
                    $request[$index] = woo_dicount_rules_generalHelper::makeString($value);
                }
            }

            $id = (isset($request['rule_id']) ? $request['rule_id'] : false);

            $id = intval($id);
            if (!$id && $id != 0) return false;

            $title = (isset($request['rule_name']) ? $request['rule_name'] : 'New');
            $slug = str_replace(' ', '-', strtolower($title));

            // To Lowercase.
            $slug = strtolower($slug);

            // Encoding String with Space.
            $slug = str_replace(' ', '-', $slug);

            $form = array(
                'rule_name',
                'rule_descr',
                'date_from',
                'date_to',
                'apply_to',
                'discount_type',
                'to_discount',
                'discount_rule',
                'rule_order',
                'status'
            );

            if ($id) {
                $post = array(
                    'ID' => $id,
                    'post_title' => $title,
                    'post_name' => $slug,
                    'post_content' => 'New Rule',
                    'post_type' => $this->post_type,
                    'post_status' => 'publish'
                );
                wp_update_post($post);
            } else {
                $post = array(
                    'post_title' => $title,
                    'post_name' => $slug,
                    'post_content' => 'New Rule',
                    'post_type' => $this->post_type,
                    'post_status' => 'publish'
                );
                $id = wp_insert_post($post);
                $request['status'] = 'publish';
            }

            foreach ($request['discount_rule'] as $index => $value) {
                $request['discount_rule'][$index] = woo_dicount_rules_generalHelper::makeString($value);
            }

            if (isset($request['discount_rule'])) $request['discount_rule'] = json_encode($request['discount_rule']);

            if (is_null($id) || !isset($id)) return false;

            foreach ($request as $index => $value) {
                if (in_array($index, $form)) {
                    if (get_post_meta($id, $index)) {
                        update_post_meta($id, $index, $value);
                    } else {
                        add_post_meta($id, $index, $value);
                    }
                }
            }
        }

        /**
         * Load View Data.
         *
         * @param $option
         * @param integer $id to load post.
         * @return string mixed response.
         */
        public function view($option, $id)
        {
            $id = intval($id);
            if (!$id) return false;

            $post = get_post($id, 'OBJECT');
            if (isset($post)) {
                if (isset($post->ID)) {
                    $post->meta = get_post_meta($post->ID);
                }
            }
            return $post;
        }

        /**
         * List of Checklist.
         */
        public function checkPoint()
        {
            // Apply rules with products.
            // NOT YET USED.
            if ($this->discount_applied) return true;
        }

        /**
         * Load List of Rules.
         *
         * @return mixed
         */
        public function getRules($onlyCount = false)
        {
            $posts = get_posts(array('post_type' => $this->post_type));
            if ($onlyCount) return count($posts);
            if (isset($posts) && count($posts) > 0) {
                foreach ($posts as $index => $item) {
                    $posts[$index]->meta = get_post_meta($posts[$index]->ID);
                }

                $this->rules = $posts;
            }
            return $posts;
        }

        /**
         * To Analyzing the Pricing Rules to Apply the Discount in terms of price.
         */
        public function analyse($woocommerce)
        {
            global $woocommerce;
            // Re-arranging the Rules.
            $this->organizeRules();
            // Apply Group of Rules.
            $this->applyRules();
            // Get Overall Discounts.
            $this->getDiscountAmount();
            // Add a Coupon Virtually (Temporary access).
            if ($this->discount_total != 0) {
                add_filter('woocommerce_get_shop_coupon_data', array($this, 'addVirtualCoupon'), 10, 2);
                add_action('woocommerce_after_calculate_totals', array($this, 'applyFakeCoupons'));
            }
        }

        /**
         *
         */
        public function appliedCoupons()
        {

        }


        /**
         * To Make record of discount changes.
         *
         * @return bool
         */
        public function makeLog()
        {
            if (is_null($this->coupon_code) || empty($this->coupon_code)) return false;

            $discount_log = array(
                'coupon_name' => $this->coupon_code,
                'discount' => $this->discount_total,
            );
            WC()->session->set('woo_cart_discount', json_encode($discount_log));
        }

        /**
         * Virtually add Coupon to apply the Discount.
         *
         * @param array $unknown_param
         * @param string $old_coupon_code Existing Coupon
         * @return array|bool
         */
        public function addVirtualCoupon($unknown_param, $old_coupon_code)
        {
            $coupon_code = $this->coupon_code;
            // Getting Coupon Remove status from Session.
            $is_removed = WC()->session->get('woo_coupon_removed');
            // If Both are same, then it won't added.
            if ($coupon_code == $is_removed) return false;

            if ($old_coupon_code == $coupon_code) {
                if ($this->postData->get('remove_coupon', false) == $coupon_code) return false;
                $this->makeLog();
                $coupon = array(
                    'id' => 321123 . rand(2, 9),
                    'type' => 'fixed_cart',
                    'amount' => $this->discount_total,
                    'individual_use' => 'no',
                    'product_ids' => array(),
                    'exclude_product_ids' => array(),
                    'usage_limit' => '',
                    'usage_limit_per_user' => '',
                    'limit_usage_to_x_items' => '',
                    'usage_count' => '',
                    'expiry_date' => '',
                    'apply_before_tax' => 'yes',
                    'free_shipping' => 'no',
                    'product_categories' => array(),
                    'exclude_product_categories' => array(),
                    'exclude_sale_items' => 'no',
                    'minimum_amount' => '',
                    'maximum_amount' => '',
                    'customer_email' => '',
                );

                return $coupon;
            }
        }

        /**
         * To Get the Coupon code that already specified.
         *
         * @return string
         */
        public function getCouponCode()
        {
            $config = new woo_dicount_rules_WooDiscountBase();
            $config = $config->getBaseConfig();

            if (is_string($config)) $config = json_decode($config, true);

            // Pre-Defined alternative Coupon Code.
            $coupon = 'Discount';

            // Verify and overwrite the Coupon Code.
            if (isset($config['coupon_name']) && $config['coupon_name'] != '') $coupon = $config['coupon_name'];
            return $coupon;
        }

        /**
         * Apply fake coupon to cart
         *
         * @access public
         * @return void
         */
        public function applyFakeCoupons()
        {
            global $woocommerce;

            // 'newyear' is a temporary coupon for validation.
            $coupon_code = apply_filters('woocommerce_coupon_code', $this->coupon_code);
            // Getting New Instance with the Coupon Code.
            $the_coupon = new WC_Coupon($coupon_code);
            // Validating the Coupon as Valid and discount status.
            if ($the_coupon->is_valid() && !$woocommerce->cart->has_discount($coupon_code)) {

                // Do not apply coupon with individual use coupon already applied
                if ($woocommerce->cart->applied_coupons) {
                    foreach ($woocommerce->cart->applied_coupons as $code) {
                        $coupon = new WC_Coupon($code);

                        if ($coupon->individual_use == 'yes') {
                            return false;
                        }
                    }
                }

                // Add coupon
                $woocommerce->cart->applied_coupons[] = $coupon_code;
                do_action('woocommerce_applied_coupon', $coupon_code);

                return true;
            }
        }

        /**
         * Simply remove or reset the virtual coupon by set "empty" as value
         * to "Woo's" session "woo_coupon_removed".
         *
         * @param $coupon
         */
        public function removeWoocommerceCoupon($coupon)
        {
            WC()->session->set('woo_coupon_removed', $coupon);
        }

        /**
         *
         */
        public function removeCartDiscount()
        {
            global $woocommerce;

            // Iterate over applied coupons and check each of them
            foreach ($woocommerce->cart->applied_coupons as $code) {

                // Check if coupon code matches our fake coupon code
                if ($this->getCouponCode() === $code) {

                    // Get coupon
                    $coupon = new WC_Coupon($code);

                    // Remove coupon if it no longer exists
                    if (!$coupon->is_valid()) {

                        // Remove the coupon
                        add_filter('woocommerce_coupons_enabled', array($this, 'woocommerceEnableCoupons'));
                        $this->remove_woocommerce_coupon($code);
                        remove_filter('woocommerce_coupons_enabled', array($this, 'woocommerceEnableCoupons'));
                    }
                }
            }
        }

        /**
         * @return string
         */
        public function woocommerceEnableCoupons()
        {
            return 'true';
        }

        /**
         *
         */
        public function organizeRules()
        {
            // Loads the Rules to Global.
            $this->getRules();
            // Validate and Re-Assign the Rules.
            $this->filterRules();
        }

        /**
         * @return bool
         */
        public function applyRules()
        {
            global $woocommerce;
            // If there is no rules, then return false.
            if (!isset($this->rules)) return false;

            // Check point having list of checklist to apply.
            if ($this->checkPoint()) return false;

            // To Generate Valid Rule sets.
            $this->generateRuleSets();
        }

        /**
         *
         */
        public function filterRules()
        {
            $rules = $this->rules;

            if (is_null($rules) || !isset($rules)) return false;
            // Start with empty set.
            $rule_set = array();
            foreach ($rules as $index => $rule) {
                $status = (isset($rule->status) ? $rule->status : false);

                // To Check as Plugin Active - InActive.
                if ($status == 'publish') {
                    $date_from = (isset($rule->date_from) ? strtotime($rule->date_from) : false);
                    $date_to = (isset($rule->date_to) ? strtotime($rule->date_to) : false);
                    $today = strtotime(date('m/d/Y'));

                    // Validating Rule with Date of Expiry.
                    if (($date_from <= $today) && (($date_to == '') || ($date_to >= $today))) {
                        // Validating the Rule with its Order ID.
                        if (isset($rule->rule_order)) {
                            // If Order ID is '-', then this rule not going to implement.
                            if ($rule->rule_order !== '-') {
                                $rule_set[] = $rule;
                            }
                        }
                    }
                }
            }
            $this->rules = $rule_set;

            // To Order the Rules, based on its order ID.
            $this->orderRules();
        }

        /**
         * @return bool
         */
        public function orderRules()
        {
            if (empty($this->rules)) return false;

            $ordered_rules = array();

            // Make associative array with Order ID.
            foreach ($this->rules as $index => $rule) {
                if (isset($rule->rule_order)) {
                    if ($rule->rule_order != '') {
                        $ordered_rules[$rule->rule_order] = $rule;
                    }
                }
            }
            // Order the Rules with it's priority.
            ksort($ordered_rules);

            $this->rules = $ordered_rules;
        }

        /**
         * @return bool
         */
        public function generateRuleSets()
        {
            global $woocommerce;
            $rule_sets = array();

            if (!isset($this->rules)) return false;

            // Loop the Rules set to collect matched rules.
            foreach ($this->rules as $index => $rule) {
                // General Rule Info.
                $rule_sets[$index]['discount_type'] = 'price_discount';
                $rule_sets[$index]['name'] = (isset($rule->rule_name) ? $rule->rule_name : 'Rule_' . $index);
                $rule_sets[$index]['descr'] = (isset($rule->rule_descr) ? $rule->rule_descr : '');
                $rule_sets[$index]['method'] = (isset($rule->rule_method) ? $rule->rule_method : 'qty_based');
                $rule_sets[$index]['qty_based_on'] = (isset($rule->qty_based_on) ? $rule->qty_based_on : 'each_product');
                $rule_sets[$index]['date_from'] = (isset($rule->date_from) ? $rule->date_from : false);
                $rule_sets[$index]['date_to'] = (isset($rule->date_to) ? $rule->date_to : false);
                $rule_sets[$index]['discount_rule'] = (isset($rule->discount_rule) ? $rule->discount_rule : false);
                $rule_sets[$index]['discount_type'] = (isset($rule->discount_type) ? $rule->discount_type : false);
                $rule_sets[$index]['to_discount'] = (isset($rule->to_discount) ? $rule->to_discount : false);
                $rule_sets[$index]['enabled'] = $this->validateCart($rule_sets[$index]['discount_rule']);
            }
            $this->rule_sets = $rule_sets;
        }

        /**
         * Get Overall discount amount across allover the rules that available.
         *
         * @return integer Total Discount Amount.
         */
        public function getDiscountAmount()
        {
            $discount = 0;
            $discounts = array();
            if (!isset($this->rule_sets)) return false;

            // Get settings
            $config = new woo_dicount_rules_WooDiscountBase();
            $config = $config->getBaseConfig();
            if (is_string($config)) $config = json_decode($config, true);
            if(isset($config['cart_setup'])){
                $cart_setup = $config['cart_setup'];
            } else {
                $cart_setup = 'all';
            }

            if(count($this->rule_sets)){
                if(in_array($cart_setup, array('first', 'all'))){
                    if($cart_setup == 'first'){
                        // Processing the Totals.
                        foreach ($this->rule_sets as $index => $rule) {
                            if ($rule['enabled'] == true) {
                                $discounts['name'][$index] = $rule['name'];
                                $discounts['type'][$index] = $rule['discount_type'];
                                if ($rule['discount_type'] == 'price_discount') {
                                    // Getting the Flat Rate of Discount.
                                    $discounts['to_discount'][$index] = $this->calculateDiscount($this->sub_total, array('type' => 'price', 'value' => $rule['to_discount']));
                                } else {
                                    // Getting the Percentage level of Discount.
                                    $discounts['to_discount'][$index] = $this->calculateDiscount($this->sub_total, array('type' => 'percentage', 'value' => $rule['to_discount']));
                                }
                                // Sum of Available discount list.
                                $discount += $discounts['to_discount'][$index];
                                // Update the status of the status of the discount rule.
                                $discounts['is_enabled'][$index] = $rule['enabled'];
                                break;
                            }
                        }
                    } else {
                        // Processing the Totals.
                        foreach ($this->rule_sets as $index => $rule) {
                            if ($rule['enabled'] == true) {
                                $discounts['name'][$index] = $rule['name'];
                                $discounts['type'][$index] = $rule['discount_type'];
                                if ($rule['discount_type'] == 'price_discount') {
                                    // Getting the Flat Rate of Discount.
                                    $discounts['to_discount'][$index] = $this->calculateDiscount($this->sub_total, array('type' => 'price', 'value' => $rule['to_discount']));
                                } else {
                                    // Getting the Percentage level of Discount.
                                    $discounts['to_discount'][$index] = $this->calculateDiscount($this->sub_total, array('type' => 'percentage', 'value' => $rule['to_discount']));
                                }
                                // Sum of Available discount list.
                                $discount += $discounts['to_discount'][$index];
                                // Update the status of the status of the discount rule.
                                $discounts['is_enabled'][$index] = $rule['enabled'];
                            }
                        }
                    }
                } else if($cart_setup == 'biggest'){
                    $biggestDiscount = 0;
                    // Processing the Totals.
                    foreach ($this->rule_sets as $index => $rule) {
                        if ($rule['enabled'] == true) {
                            if ($rule['discount_type'] == 'price_discount') {
                                // Getting the Flat Rate of Discount.
                                $newDiscount = $this->calculateDiscount($this->sub_total, array('type' => 'price', 'value' => $rule['to_discount']));
                            } else {
                                // Getting the Percentage level of Discount.
                                $newDiscount = $this->calculateDiscount($this->sub_total, array('type' => 'percentage', 'value' => $rule['to_discount']));
                            }

                            if($newDiscount > $biggestDiscount){
                                $biggestDiscount = $newDiscount;
                                $discounts['name'][1] = $rule['name'];
                                $discounts['type'][1] = $rule['discount_type'];
                                $discounts['to_discount'][1] = $newDiscount;
                                $discount = $newDiscount;
                                // Update the status of the status of the discount rule.
                                $discounts['is_enabled'][1] = $rule['enabled'];
                            }
                        }
                    }
                }
            }

            $this->discount_total = $discount;
            return $discounts;
        }

        /**
         * Comparing the Rules with the each line item to check
         * and return as, matched or not.
         *
         * @param array $rules
         * @return bool true|false
         */
        public function validateCart($rules)
        {
            $this->calculateCartSubtotal();
            $rules = (is_string($rules) ? json_decode($rules, true) : array());
            // Simple array helper to re-arrange the structure.
            woo_dicount_rules_generalHelper::reArrangeArray($rules);
            foreach ($rules as $index => $rule) {
                // Validating the Rules one by one.
                if ($this->applyRule($index, $rule) == false) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Applying bunch amount of rules with the line item.
         *
         * @param string $index Index of the Rule
         * @param array $rule array of rule info.
         * @return bool true|false as matched or not.
         */
        public function applyRule($index, $rule)
        {
            switch ($index) {

                // Cart Subtotal.
                case 'subtotal_least':
                    if ($this->sub_total < $rule) {
                        return false;
                    }
                    return true;
                    break;
                case 'subtotal_less':
                    if ($this->sub_total >= $rule) {
                        return false;
                    }
                    return true;
                    break;

                // Cart Item Count.
                case 'item_count_least':
                    if (count($this->cart_items) < $rule) {
                        return false;
                    }
                    return true;
                    break;
                case 'item_count_less':
                    if (count($this->cart_items) >= $rule) {
                        return false;
                    }
                    return true;
                    break;

                // Quantity Count.
                case 'quantity_least':
                    if ($this->cartItemQtyTotal() < $rule) {
                        return false;
                    }
                    return true;
                    break;
                case 'quantity_less':
                    if ($this->cartItemQtyTotal() >= $rule) {
                        return false;
                    }
                    return true;
                    break;

                // Logged In Users.
                case 'users_in':
                    if (get_current_user_id() == 0 || !in_array(get_current_user_id(), $rule)) {
                        return false;
                    }
                    return true;
                    break;
                case 'shipping_countries_in':
//                    $user_meta = get_user_meta(get_current_user_id());
                    $shippingCountry = WC()->customer->get_shipping_country();
//                    if (!$user_meta || !isset($user_meta['shipping_country']) || empty($user_meta['shipping_country']) || !in_array($user_meta['shipping_country'][0], $rule)) {
                    if (empty($shippingCountry) || !in_array($shippingCountry, $rule)) {
                        return false;
                    }
                    return true;
                    break;
                case 'roles_in':
                    if (get_current_user_id() == 0 || count(array_intersect(woo_dicount_rules_generalHelper::getCurrentUserRoles(), $rule)) == 0) {
                        return false;
                    }
                    return true;
                    break;
                case 'customer_email_tld':
                    $rule = explode(',', $rule);
                    foreach($rule as $key => $r){
                        $rule[$key] = trim($r);
                        $rule[$key] = trim($rule[$key], '.');
                    }
                    $postData = $this->postData->get('post_data', '', 'raw');
                    $postDataArray = array();
                    if($postData != ''){
                        parse_str($postData, $postDataArray);
                    }
                    $postBillingEmail = $this->postData->get('billing_email', '', 'raw');
                    if($postBillingEmail != ''){
                        $postDataArray['billing_email'] = $postBillingEmail;
                    }
                    if(!get_current_user_id()){
                        $order_id = $this->postData->get('order-received', 0);
                        if($order_id){
                            $order = new WC_Order( $order_id);
                            $postDataArray['billing_email'] = $order->billing_email;
                        }
                    }
                    if(isset($postDataArray['billing_email']) && $postDataArray['billing_email'] != ''){
                        $user_email = $postDataArray['billing_email'];
                        if(get_current_user_id()){
                            update_user_meta(get_current_user_id(), 'billing_email', $user_email);
                        }
                        $tld = $this->getTLDFromEmail($user_email);
                        if(in_array($tld, $rule)){
                            return true;
                        }
                    } else if(get_current_user_id()){
                        $user_email = get_user_meta( get_current_user_id(), 'billing_email', true );
                        if($user_email != '' && !empty($user_email)){
                            $tld = $this->getTLDFromEmail($user_email);
                            if(in_array($tld, $rule)){
                                return true;
                            }
                        } else {
                            $user_details = get_userdata( get_current_user_id() );
                            if(isset($user_details->data->user_email) && $user_details->data->user_email != ''){
                                $user_email = $user_details->data->user_email;
                                $tld = $this->getTLDFromEmail($user_email);
                                if(in_array($tld, $rule)){
                                    return true;
                                }
                            }
                        }
                    }
                    return false;
                    break;
                /*case 'categories_atleast_one':
                    if(count($rule)){
                        $w_categories = $this->getCartProductCaregories();
                        if(count($w_categories)){
                            foreach ($rule as $cat){
                                if(in_array($cat, $w_categories)){
                                    return true;
                                }
                            }
                        }
                    }
                    return false;
                    break;*/

            }

        }

        /**
         * Get tld from email
         * */
        protected function getTLDFromEmail($email){
            $emailArray = explode('@', $email);
            if(isset($emailArray[1])){
                $emailDomainArray = explode('.', $emailArray[1]);
                if(count($emailDomainArray)>1){
                    unset($emailDomainArray[0]);
                }
                return implode('.', $emailDomainArray);
            }
            return $emailArray[0];
        }

        /**
         * To get product categories
         * */
        /*public function getCartProductCaregories(){
            global $woocommerce;
            $product_cat_id = array();
            if(count($woocommerce->cart->cart_contents)){
                foreach ($woocommerce->cart->cart_contents as $cartItem) {
                    $terms = get_the_terms( $cartItem['product_id'], 'product_cat' );
                    if($terms)
                    foreach ($terms as $term) {
                        $product_cat_id[] = $term->term_id;
                    }
                }
            }
            return $product_cat_id;
        }*/

        /**
         * Get cart total amount
         *
         * @access public
         * @return float
         */
        public function calculateCartSubtotal()
        {
            $cart_subtotal = 0;
            // Iterate over all cart items and
            foreach ($this->cart_items as $cart_item_key => $cart_item) {
                $quantity = (isset($cart_item['quantity']) && $cart_item['quantity']) ? $cart_item['quantity'] : 1;
                $cart_subtotal += $cart_item['data']->price * $quantity;
            }

            $this->sub_total = (float)$cart_subtotal;
        }

        /**
         * To Sum the Cart Item's Qty.
         *
         * @return int Total Qty of Cart.
         */
        public function cartItemQtyTotal()
        {
            global $woocommerce;
            $cart_items = $woocommerce->cart->cart_contents;
            $total_quantity = 0;

            foreach ($cart_items as $cart_item) {
                $current_quantity = (isset($cart_item['quantity']) && $cart_item['quantity']) ? $cart_item['quantity'] : 1;
                $total_quantity += $current_quantity;
            }
            return $total_quantity;
        }

        /**
         * Overall Discount Calculation based on Percentage or Flat.
         *
         * @param integer $sub_total Subtotal of the Cart.
         * @param integer $adjustment percentage or discount of adjustment.
         * @return integer Final Discount Amount.
         */
        public function calculateDiscount($sub_total, $adjustment)
        {
            $sub_total = ($sub_total < 0) ? 0 : $sub_total;

            $discount = 0;

            if ($adjustment['type'] == 'percentage') {
                $discount = $sub_total * ($adjustment['value'] / 100);
            } else if ($adjustment['type'] == 'price') {
                $discount = $adjustment['value'];
            }

            return ($discount <= 0) ? 0 : $discount;
        }

    }
}