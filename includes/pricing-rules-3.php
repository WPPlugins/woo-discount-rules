<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
include_once(WOO_DISCOUNT_DIR . '/helper/general-helper.php');

/**
 * Class woo_dicount_rules_pricingRules
 */
if (!class_exists('woo_dicount_rules_pricingRules')) {
    class woo_dicount_rules_pricingRules
    {
        /**
         * @var string
         */
        private $option_name = 'woo_discount_price_option';

        /**
         * @var string
         */
        public $post_type = 'woo_discount';

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
         * @var
         */
        public $matched_sets;

        /**
         * @var
         */
        public $baseConfig;

        /**
         * @var
         */
        public $apply_to;

        /**
         * @var string
         */
        public $default_option = 'woo-discount-config';

        public $postData;

        /**
         * woo_dicount_rules_pricingRules constructor.
         */
        public function __construct()
        {
            $this->updateBaseConfig();
            $this->postData = \FlycartInput\FInput::getInstance();
        }

        /**
         * Update the Base config with live.
         */
        public function updateBaseConfig()
        {
            $base = new woo_dicount_rules_WooDiscountBase();
            $base = $base->getBaseConfig();
            if (is_string($base)) $base = json_decode($base, true);
            $this->baseConfig = $base;
            $this->apply_to = (isset($base_config['price_setup']) ? $base_config['price_setup'] : 'all');
        }

        /**
         * Saving the Price Rule Set.
         *
         * @param $request
         * @return bool
         */
        public function save($request)
        {
            //  var_dump($request);
//        die();
//        foreach ($request as $index => $value) {
//            $request[$index] = $value;
//        }

            $id = (isset($request['rule_id']) ? $request['rule_id'] : false);

            $id = intval($id);
            if (!$id && $id != 0) return false;

            $title = (isset($request['rule_name']) ? $request['rule_name'] : 'New');
            $slug = str_replace(' ', '-', strtolower($title));

            // To Lowercase.
            $slug = strtolower($slug);

            // Encoding String with Space.
            $slug = str_replace(' ', '-', $slug);

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
            }

            $form = array(
                'rule_name',
                'rule_descr',
                'rule_method',
                'qty_based_on',
                'date_from',
                'date_to',
                'apply_to',
                'customer',
                'min_qty',
                'max_qty',
                'discount_type',
                'to_discount',
                'status',
                'customer',
                'discount_range',
                'rule_order'
            );

            //----------------------------------------------------------------------------------------------------------
            // Manage Products with it's ID or Category.
            $apply_to = 'all_products';

            if (isset($request['apply_to'])) $apply_to = $request['apply_to'];

            if ($apply_to == 'specific_category') {
                $apply_to = 'category_to_apply';
                if(isset($request['is_cumulative']) && $request['is_cumulative'] == 1){
                    $request['is_cumulative'] = 1;
                } else {
                    $request['is_cumulative'] = 0;
                }
                $form[] = 'is_cumulative';

                if(isset($request['apply_child_categories']) && $request['apply_child_categories'] == 1){
                    $request['apply_child_categories'] = 1;
                } else {
                    $request['apply_child_categories'] = 0;
                }
                $form[] = 'apply_child_categories';

            } elseif ($apply_to == 'specific_products') {
                $apply_to = 'product_to_apply';
            }
            $form[] = $apply_to;

            if (isset($request[$apply_to])) $request[$apply_to] = json_encode($request[$apply_to]);
            //----------------------------------------------------------------------------------------------------------

            // Manage Users.
            $apply_to = 'all';

            if (isset($request['customer'])) $apply_to = $request['customer'];

            if ($apply_to == 'only_given') {
                $apply_to = 'users_to_apply';
            }
            $form[] = $apply_to;

            if (isset($request[$apply_to])) $request[$apply_to] = json_encode($request[$apply_to]);
            //----------------------------------------------------------------------------------------------------------

            // Manage list of Discount Ranges.
            if (isset($request['discount_range'])) {

                foreach ($request['discount_range'] as $index => $value) {
                    $request['discount_range'][$index] = woo_dicount_rules_generalHelper::makeString($value);
                    $request['discount_range'][$index]['title'] = isset($request['rule_name']) ? $request['rule_name'] : '';

                }

                $request['discount_range'] = json_encode($request['discount_range']);
            } else {
                // Reset the Discount Range, if its empty.
                $request['discount_range'] = '';
            }

            $request['status'] = 'publish';

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
         * Load View with Specif post id.
         *
         * @param $option
         * @param integer $id Post ID.
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

        // -------------------------------------------------RULE IMPLEMENTATION---------------------------------------------

        /**
         * To Analyzing the Pricing Rules to Apply the Discount in terms of price.
         */
        public function analyse($woocommerce)
        {
            $this->organizeRules();
            $this->applyRules();
            $this->initAdjustment();
        }

        /**
         * To Organizing the rules to make possible sets.
         */
        public function organizeRules()
        {
            // Loads the Rules to Global.
            $this->getRules();
            // Validate and Re-Assign the Rules.
            $this->filterRules();
        }

        /**
         * To Get Set of Rules.
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
         * To Updating the Log of Implemented Price Discounts.
         *
         * @return bool
         */
        public function makeLog()
        {
            if (is_null($this->matched_sets)) return false;

            $discount_log = array(
                'line_discount' => $this->matched_sets,
            );
            WC()->session->set('woo_price_discount', json_encode($discount_log));
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
         * Filter the Rules with some validations.
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
         * Ordering the Set of Rules.
         *
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
         * Apply the Rules to line items.
         *
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
            $this->generateRuleSets($woocommerce);
            // Sort cart by price ascending

            $cart_contents = $this->sortCartPrice($woocommerce->cart->cart_contents, 'asc');
            foreach ($cart_contents as $index => $item) {
                $this->matchRules($index, $item);
            }
            $this->makeLog();
        }

        /**
         * Generate the Suitable and active rule sets.
         *
         * @param $woocommerce
         * @return bool
         */
        public function generateRuleSets($woocommerce)
        {
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

                // List the type of apply, by Product or by Category.
                if (isset($rule->apply_to)) {
                    // If Rule is processed by Specific Products, then..
                    if ($rule->apply_to == 'specific_products') {
                        if (isset($rule->product_to_apply)) {
                            $rule_sets[$index]['type']['specific_products'] = $this->checkWithProducts($rule, $woocommerce);
                        }
                    } else if ($rule->apply_to == 'specific_category') {
                        if (isset($rule->apply_child_categories) && $rule->apply_child_categories) {
                            $rule_sets[$index]['type']['apply_child_categories'] = 1;
                        } else {
                            $rule_sets[$index]['type']['apply_child_categories'] = 0;
                        }

                        if (isset($rule->category_to_apply)) {
                            $rule_sets[$index]['type']['specific_category'] = $this->checkWithCategory($rule, $woocommerce);
                            if($rule_sets[$index]['type']['apply_child_categories']){
                                $cat = $rule_sets[$index]['type']['specific_category'];
                                $rule_sets[$index]['type']['specific_category'] =  $this->getAllSubCategories($cat);
                            }
                        }
                        if (isset($rule->is_cumulative) && $rule->is_cumulative) {
                            $rule_sets[$index]['type']['is_cumulative'] = 1;
                        } else {
                            $rule_sets[$index]['type']['is_cumulative'] = 0;
                        }
                    } else {
                        $rule_sets[$index]['type'] = 'all';
                    }

                    $rule_sets[$index]['discount'] = 0;
                    if (isset($rule->discount_range)) {
                        if ($rule->discount_range != '') {
                            $rule_sets[$index]['discount'] = $this->getDiscountRangeList($rule);
                        }
                    }

                    // Default setup for all customers.
                    $rule_sets[$index]['allow']['users'] = 'all';
                    // If Rule is processed by Specific Customers, then..
                    if ($rule->customer == 'only_given') {
                        if (isset($rule->users_to_apply)) {
                            $rule_sets[$index]['allow']['users'] = $this->checkWithUsers($rule, $woocommerce);
                        }
                    }
                    $rule_sets[$index]['apply_to'] = $rule->apply_to;
                }

                // If Current Customer is not Allowed to use this discount, then it's going to be removed.
                if ($rule_sets[$index]['allow']['users'] == 'no') {
                    unset($rule_sets[$index]);
                }
            }
            $this->rule_sets = $rule_sets;
        }

        /**
         * Get all sub categories
         * */
        public function getAllSubCategories($cat){
            $category_with_sub_cat = $cat;
            foreach($cat as $c) {
                $args = array('hierarchical' => 1,
                    'show_option_none' => '',
                    'hide_empty' => 0,
                    'parent' => $c,
                    'taxonomy' => 'product_cat');
                $categories = get_categories( $args );
                foreach($categories as $category) {
                    $category_with_sub_cat[] = $category->term_id;
                }
            }
            $category_with_sub_cat = array_unique($category_with_sub_cat);

            return $category_with_sub_cat;
        }

        /**
         * Fetch back the Matched rules.
         *
         * @param $index
         * @param array $item line item.
         */
        public function matchRules($index, $item)
        {
            $applied_rules = array();
            $quantity = (isset($item['quantity']) ? $item['quantity'] : 0);
            $i = 0;
            foreach ($this->rule_sets as $id => $rule) {

                if (isset($rule['type']) && isset($rule['apply_to'])) {

                    // Working with Products and Category.
                    switch ($rule['apply_to']) {

                        case 'specific_products':

                            if ($this->isItemInProductList($rule['type']['specific_products'], $item)) {
                                $applied_rules[$i]['amount'] = $this->getAdjustmentAmount($quantity, $this->array_first($rule['discount']));
                                $applied_rules[$i]['name'] = $rule['name'];
                                $applied_rules[$i]['item'] = $index;
                                $applied_rules[$i]['id'] = $item['product_id'];
                            }

                            break;

                        case 'specific_category':
                            if ($this->isItemInCategoryList($rule['type']['specific_category'], $item)) {
                                if(isset($rule['type']['is_cumulative']) && $rule['type']['is_cumulative']){
                                    $totalQuantityInThisCategory = $this->getProductQuantityInThisCategory($rule['type']['specific_category']);
                                    $quantity = $totalQuantityInThisCategory;
                                }
                                $applied_rules[$i]['amount'] = $this->getAdjustmentAmount($quantity, $this->array_first($rule['discount']));
                                $applied_rules[$i]['name'] = $rule['name'];
                                $applied_rules[$i]['item'] = $index;
                                $applied_rules[$i]['id'] = $item['product_id'];
                            }


                            break;

                        case 'all_products':
                        default:

                            $applied_rules[$i]['amount'] = $this->getAdjustmentAmount($quantity, $this->array_first($rule['discount']));
                            $applied_rules[$i]['name'] = $rule['name'];
                            $applied_rules[$i]['item'] = $index;
                            $applied_rules[$i]['id'] = $item['product_id'];

                            break;
                    }
                }
                $i++;
            }
            $this->matched_sets[$index] = $applied_rules;
        }

        /**
         * Get quantity of products in specific category
         * */
        public function getProductQuantityInThisCategory($category){
            global $woocommerce;
            $quantity = 0;
            if(count($woocommerce->cart->cart_contents)){
                foreach ($woocommerce->cart->cart_contents as $cartItem) {
                    $terms = get_the_terms( $cartItem['product_id'], 'product_cat' );
                    if($terms){
                        $has = 0;
                        foreach ($terms as $term) {
                            if(in_array($term->term_id, $category)){
                                $has = 1;
                            }
                        }
                        if($has){
                            $quantity = $quantity + $cartItem['quantity'];
                        }
                    }
                }
            }
            return $quantity;
        }

        /**
         * Return the First index.
         *
         * @param $array
         * @return mixed
         */
        public function array_first($array)
        {
            if (is_object($array)) $array = (array)$array;
            if (is_array($array)) return $array;
            foreach ($array as $first) {
                return $first;
            }
        }

        /**
         * Return the Adjustment amount.
         *
         * @param $quantity
         * @param $discount_range
         * @return array|bool
         */
        public function getAdjustmentAmount($quantity, $discount_ranges)
        {
            $adjustment = array();
            foreach($discount_ranges as $discount_range) {

                if (!is_array($discount_range) && !is_object($discount_range)) return false;
                $range = is_array($discount_range) ? (object) $discount_range : $discount_range;
                $min = (isset($range->min_qty) ? $range->min_qty : 0);
                $max = (isset($range->max_qty) ? $range->max_qty : false);

                $type = (isset($range->discount_type) ? $range->discount_type : 'price_discount');

                if ($max == false) continue;

                if ((int)$min <= (int)$quantity && (int)$max >= (int)$quantity) {
                    $adjustment[$type] = (isset($range->to_discount) ? $range->to_discount : 0);
                }

            }

            return $adjustment;
        }

        /**
         * Validating the Active user with rule sets.
         *
         * @param $rule
         * @return string
         */
        public function manageUserAccess($rule)
        {
            $allowed = 'no';
            if (!isset($rule->users_to_apply)) return $allowed;

            $users = $rule->users_to_apply;

            if (is_string($users)) $users = json_decode($users, True);

            if (!is_array($users)) return $allowed;

            $user = get_current_user_id();

            if (count(array_intersect($users, array($user))) > 0) {
                $allowed = 'yes';
            }

            return $allowed;
        }

        /**
         * To Check active cart items are in the rules list item.
         *
         * @param $product_list
         * @param $product
         * @return bool
         */
        public function isItemInProductList($product_list, $product)
        {
            if (!isset($product['product_id'])) return false;
            if (!is_array($product_list)) $product_list = (array)$product_list;
            if (count(array_intersect($product_list, array($product['product_id']))) == 1) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * To Check that the items are in specified category.
         *
         * @param $category_list
         * @param $product
         * @return bool
         */
        public function isItemInCategoryList($category_list, $product)
        {

            $helper = new woo_dicount_rules_generalHelper();
            $all_category = $helper->getCategoryList();
            if (!isset($product['product_id'])) return false;
            $product_category = woo_dicount_rules_generalHelper::getCategoryByPost($product);

            $status = false;
            //check any one of category matches
            $matching_cats = array_intersect($product_category, $category_list);
            $result = !empty( $matching_cats );
            if($result){
                $status = true;
            }

            return $status;
        }

        /**
         *
         */
        public function isUserInCustomerList()
        {

        }

        /**
         * Sort cart by price
         *
         * @access public
         * @param array $cart
         * @param string $order
         * @return array
         */
        public function sortCartPrice($cart, $order)
        {
            $cart_sorted = array();

            foreach ($cart as $cart_item_key => $cart_item) {
                $cart_sorted[$cart_item_key] = $cart_item;
            }

            uasort($cart_sorted, array($this, 'sortCartByPrice_' . $order));

            return $cart_sorted;
        }

        /**
         * Sort cart by price uasort collable - ascending
         *
         * @access public
         * @param mixed $first
         * @param mixed $second
         * @return bool
         */
        public function sortCartByPrice_asc($first, $second)
        {
            if (isset($first['data'])) {
                if ($first['data']->get_price() == $second['data']->get_price()) {
                    return 0;
                }
            }
            return ($first['data']->get_price() < $second['data']->get_price()) ? -1 : 1;
        }

        /**
         * Sort cart by price uasort collable - descending
         *
         * @access public
         * @param mixed $first
         * @param mixed $second
         * @return bool
         */
        public function sortCartByPrice_desc($first, $second)
        {
            if (isset($first['data'])) {
                if ($first['data']->get_price() == $second['data']->get_price()) {
                    return 0;
                }
            }
            return ($first['data']->get_price() > $second['data']->get_price()) ? -1 : 1;
        }

        /**
         * Return the List of Products to Apply.
         *
         * @param $woocommerce
         * @param $rule
         * @return array
         */
        public function checkWithProducts($rule, $woocommerce)
        {
            $specific_product_list = array();
            if (is_string($rule->product_to_apply)) {
                $specific_product_list = json_decode($rule->product_to_apply, true);
            }
            return $specific_product_list;
        }

        /**
         * Check with category list.
         *
         * @param $rule
         * @param $woocommerce
         * @return array|mixed
         */
        public function checkWithCategory($rule, $woocommerce)
        {
            $specific_category_list = array();
            if (is_string($rule->category_to_apply)) {
                $specific_category_list = json_decode($rule->category_to_apply, true);
            }
            return $specific_category_list;
        }

        /**
         * Check with User list.
         *
         * @param $rule
         * @param $woocommerce
         * @return array|mixed
         */
        public function checkWithUsers($rule, $woocommerce)
        {
            // Return as , User is allowed to use this discount or not.
            // Working Users.
            return $this->manageUserAccess($rule);
        }

        /**
         * To Return the Discount Ranges.
         *
         * @param $rule
         * @return array|mixed
         */
        public function getDiscountRangeList($rule)
        {
            $discount_range_list = array();
            if (is_string($rule->discount_range)) {
                $discount_range_list = json_decode($rule->discount_range);
            }
            return $discount_range_list;
        }

        /**
         * For Display the price discount of a product.
         */
        public function priceTable()
        {
            global $product;

            $config = $this->baseConfig;
            $show_discount = true;
            // Base Config to Check whether display table or not.
            if (isset($config['show_discount_table'])) {
                if ($config['show_discount_table'] == 'show') {
                    $show_discount = true;
                } else {
                    $show_discount = false;
                }
            }
            // If Only allowed to display, then only its display the table.
            if ($show_discount) {
                $table_data = $this->generateDiscountTableData($product);
                $path = WOO_DISCOUNT_DIR . '/view/template/discount-table.php';
                echo $this->generateTableHtml($table_data, $path);
            }

        }

        /**
         * To generate the Discount table data.
         *
         * @param $product
         * @return array|bool|string
         */
        public function generateDiscountTableData($product)
        {
            $id = (($product->get_id() != 0 && $product->get_id() != null) ? $product->get_id() : 0);
            if ($id == 0) return false;

            $this->organizeRules();

            $discount_range = array();
            if(is_array($this->rules) && count($this->rules) > 0) {
                foreach ($this->rules as $index => $rule) {
                    $status = false;

                    // Check with Active User Filter.
                    if (isset($rule->customer)) {
                        $status = false;
                        if ($rule->customer == 'all') {
                            $status = true;
                        } else {
                            $users = (is_string($rule->users_to_apply) ? json_decode($rule->users_to_apply, true) : array());
                            $user_id = get_current_user_id();
                            if (count(array_intersect($users, array($user_id))) > 0) {
                                $status = true;
                            }
                        }
                    }

                    if ($rule->apply_to == 'specific_products') {

                        // Check with Product Filter.
                        $products_to_apply = json_decode($rule->product_to_apply);

                        if ($rule->product_to_apply == null) $status = true;

                        if ($rule->product_to_apply != null) {
                            $status = false;
                            if (array_intersect($products_to_apply, array($id)) > 0) {
                                $status = true;
                            }
                        }
                    } elseif ($rule->apply_to == 'specific_category') {

                        // Check with Product Category Filter.
                        $category = woo_dicount_rules_generalHelper::getCategoryByPost($id, true);

                        if ($rule->category_to_apply == null) $status = true;

                        if ($rule->category_to_apply != null) {
                            $category_to_apply = json_decode($rule->category_to_apply);
                            if (isset($rule->apply_child_categories) && $rule->apply_child_categories == 1) {
                                $category_to_apply = $this->getAllSubCategories($category_to_apply);
                            }
                            woo_dicount_rules_generalHelper::toInt($category_to_apply);
                            $status = false;
                            if (count(array_intersect($category_to_apply, $category)) > 0) {
                                $status = true;
                            }
                        }

                    } else if ($rule->apply_to == 'all_products') {
                        $status = true;
                    }


                    if ($status) {
                        $discount_range[] = (isset($rule->discount_range) ? json_decode($rule->discount_range) : array());
                    }
                }
            }
            return $discount_range;
        }

        /**
         * To Return the HTML table for show available discount ranges.
         *
         * @param $table_data
         * @param $path
         * @return bool|string
         */
        public function generateTableHtml($table_data, $path)
        {
            ob_start();
            if (!isset($table_data)) return false;
            if (!isset($path) || empty($path) || is_null($path)) return false;
            if (!file_exists($path)) return false;
            $data = $this->getBaseConfig();
            include($path);
            $html = ob_get_contents();
            ob_clean();
            return $html;
        }

        /**
         * Start Implementing the adjustments.
         *
         * @return bool
         */
        public function initAdjustment()
        {
            global $woocommerce;

            // Get settings
            $config = new woo_dicount_rules_WooDiscountBase();
            $config = $config->getBaseConfig();
            if (is_string($config)) $config = json_decode($config, true);
            if(isset($config['price_setup'])){
                $type = $config['price_setup'];
            } else {
                $type = 'all';
            }

            $cart_items = $woocommerce->cart->cart_contents;

            foreach ($cart_items as $cart_item_key => $cart_item) {
                $this->applyAdjustment($cart_item, $cart_item_key, $type);
            }
        }

        /**
         * Start Implement adjustment on individual items in the cart.
         *
         * @param $cart_item
         * @param $cart_item_key
         * @param $type
         * @return bool
         */
        public function applyAdjustment($cart_item, $cart_item_key, $type)
        {
            global $woocommerce;

            // All Sets are Collected properly, just process with that.
            if (!isset($cart_item)) return false;

            // If Product having the rule sets then,
            if (!isset($this->matched_sets[$cart_item_key])) return false;

            $adjustment_set = $this->matched_sets[$cart_item_key];

            $price = $woocommerce->cart->cart_contents[$cart_item_key]['data']->get_price();

            if ($type == 'first') {
                // For Apply the First Rule.
                $discount = $this->getAmount($adjustment_set, $price, 'first');
                $amount = $price - $discount;
                $log = 'Discount | ' . $discount;
                $this->applyDiscount($cart_item_key, $amount, $log);
            } else if ($type == 'biggest') {
                // For Apply the Biggest Discount.
                $discount = $this->getAmount($adjustment_set, $price, 'biggest');
                $amount = $price - $discount;
                $log = 'Discount | ' . $discount;
                $this->applyDiscount($cart_item_key, $amount, $log);
            } else {
                // For Apply All Rules.
                $discount = $this->getAmount($adjustment_set, $price);
                $amount = $price - $discount;
                $log = 'Discount | ' . $discount;
                $this->applyDiscount($cart_item_key, $amount, $log);
            }
        }

        /**
         * To Get Amount based on the Setting that specified.
         *
         * @param $sets
         * @param $price
         * @param string $by
         * @return bool|float|int
         */
        public function getAmount($sets, $price, $by = 'all')
        {
            $discount = 0;
            $overall_discount = 0;

            if (!isset($sets) || empty($sets)) return false;

            if ($price == 0) return $price;

            // For the biggest price, it compares the current product's price.
            if ($by == 'biggest') {
                $discount = $this->getBiggestDiscount($sets, $price);
                return $discount;
            }

            foreach ($sets as $id => $set) {
                // For the First price, it will return the amount after get hit.
                if ($by == 'first') {
                    if (isset($set['amount']['percentage_discount'])) {
                        $discount = ($price / 100) * $set['amount']['percentage_discount'];
                    } else if (isset($set['amount']['price_discount'])) {
                        $discount = $set['amount']['price_discount'];
                    }
                    return $discount;
                } else {
                    // For All, All rules going to apply.
                    if (isset($set['amount']['percentage_discount'])) {
                        $discount = ($price / 100) * $set['amount']['percentage_discount'];
                        // Append all Discounts.
                        $overall_discount = $overall_discount + $discount;
                    } else if (isset($set['amount']['price_discount'])) {
                        $discount = $set['amount']['price_discount'];
                        // Append all Discounts.
                        $overall_discount = $overall_discount + $discount;
                    }
                }
            }
            return $overall_discount;
        }

        /**
         * To Return the Biggest Discount across the available rule sets.
         *
         * @param $discount_list
         * @param $price
         * @return float|int
         */
        public function getBiggestDiscount($discount_list, $price)
        {
            $big = 0;
//            $amount = $price;
            $amount = 0;
            foreach ($discount_list as $id => $discount_item) {
                $amount_type = (isset($discount_item['amount']['percentage_discount']) ? 'percentage_discount' : 'price_discount');
                if ($amount_type == 'percentage_discount') {
                    if (isset($discount_item['amount']['percentage_discount'])) {
                        $amount = (($price / 100) * $discount_item['amount']['percentage_discount']);
                    }
                } else {
                    if (isset($discount_item['amount']['price_discount'])) {
                        $amount = $discount_item['amount']['price_discount'];
                    }
                }

                if ($big < $amount) {
                    $big = $amount;
                }
            }
            return $big;
        }

        /**
         * Finally Apply the Discount to the Cart item by update to WooCommerce Instance.
         *
         * @param $item
         * @param $amount
         * @param $log
         */
        public function applyDiscount($item, $amount, $log)
        {
            global $woocommerce;
            // Make sure item exists in cart
            if (!isset($woocommerce->cart->cart_contents[$item])) {
                return;
            }
            // Log changes
            $woocommerce->cart->cart_contents[$item]['woo_discount'] = array(
                'original_price' => get_option('woocommerce_tax_display_cart') == 'excl' ? wc_get_price_excluding_tax( $woocommerce->cart->cart_contents[$item]['data'] ) : wc_get_price_including_tax( $woocommerce->cart->cart_contents[$item]['data'] ),
                'log' => $log,
            );

            // To handle Woocommerce currency switcher
            global $WOOCS;
            if(isset($WOOCS)){
                if (method_exists($WOOCS, 'get_currencies')){
                    $currencies = $WOOCS->get_currencies();
                    $amount = $amount / $currencies[$WOOCS->current_currency]['rate'];
                }
            }

            // Actually adjust price in cart
//            $woocommerce->cart->cart_contents[$item]['data']->price = $amount;
            $woocommerce->cart->cart_contents[$item]['data']->set_price($amount);

        }

        /**
         * For Show the Actual Discount of a product.
         *
         * @param integer $item_price Actual Price.
         * @param object $cart_item Cart Items.
         * @param string $cart_item_key to identify the item from cart.
         * @return string processed price of a product.
         */
        public function replaceVisiblePricesCart($item_price, $cart_item, $cart_item_key)
        {

            if (!isset($cart_item['woo_discount'])) {
                return $item_price;
            }

            // Get price to display
            $price = get_option('woocommerce_tax_display_cart') == 'excl' ? wc_get_price_excluding_tax($cart_item['data']) : wc_get_price_including_tax($cart_item['data']);

            // Format price to display
            $price_to_display = woo_dicount_rules_generalHelper::wcVersion('2.1') ? wc_price($price) : woocommerce_price($price);
            $original_price_to_display = woo_dicount_rules_generalHelper::wcVersion('2.1') ? wc_price($cart_item['woo_discount']['original_price']) : woocommerce_price($cart_item['woo_discount']['original_price']);

            if ($cart_item['woo_discount']['original_price'] !== $price) {
                $item_price = '<span class="cart_price"><del>' . $original_price_to_display . '</del> <ins>' . $price_to_display . '</ins></span>';
            } else {
                $item_price = $price_to_display;
            }

            return $item_price;
        }
    }
}
