<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

$active = 'pricing-rules';
include_once(WOO_DISCOUNT_DIR . '/view/includes/header.php');
include_once(WOO_DISCOUNT_DIR . '/view/includes/sub-menu.php');

$config = (isset($config)) ? $config : '{}';
$rule_id = 0;
$form = '';

$status = 'publish';

if (is_string($config)) {
    $data = json_decode($config);
} elseif (is_object($config)) {
    if (isset($config->form)) {
        $form = $config->form;
    }
}
// Dummy Object.
$obj = new stdClass();

$data = (isset($config[0]) ? $config[0] : array());
$rule_id = (isset($data->ID)) ? $data->ID : 0;

$discounts = array();
$discount_rules = array();
if (isset($data->discount_rule)) {
    $discount_rules = (is_string($data->discount_rule) ? json_decode($data->discount_rule, true) : array('' => ''));
}

foreach ($discount_rules as $index => $rule) {
    foreach ($rule as $id => $value) {
        $discounts[$id] = $value;
    }
}
$discount_rules = $discounts;
if (empty($discount_rules)) {
    $discount_rules = array(0 => '');
    $type = 'subtotal_least';
}

?>
    <div class="container-fluid">
        <form id="form_cart_rule">
            <div class="row-fluid">
                <div class="col-md-9">
                    <div class="col-md-12" align="right">
                        <input type="submit" id="saveCartRule" value="Save Rule" class="button button-primary">
                        <a href="?page=woo_discount_rules&tab=cart-rules" class="button button-secondary">Cancel</a>
                    </div>
                    <?php if ($rule_id == 0) { ?>
                        <div class="col-md-12"><h2>New Cart Rule</h2></div>
                    <?php } else { ?>
                        <div class="col-md-12"><h2>Edit Cart Rule
                                | <?php echo(isset($data->rule_name) ? $data->rule_name : ''); ?></h2></div>
                    <?php } ?>
                    <div class="col-md-12" id="general_block"><h4 class="text text-muted"> General</h4>
                        <hr>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3"><label> Order : <i
                                            class="text-muted glyphicon glyphicon-exclamation-sign"
                                            title="The Simple Ranking concept to said, which one is going to execute first and so on."></i></label>
                                </div>
                                <div class="col-md-6"><input type="number" class="rule_order"
                                                             id="rule_order"
                                                             name="rule_order"
                                                             value="<?php echo(isset($data->rule_order) ? $data->rule_order : ''); ?>"
                                                             placeholder="ex. 1">
                                    <code>WARNING: More than one rule should not have same priority. </code>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3"><label> Rule Name <i
                                            class="text-muted glyphicon glyphicon-exclamation-sign"
                                            title="Rule Desctriptions."></i></label></div>
                                <div class="col-md-6"><input type="text" class="form-control rule_descr"
                                                             id="rule_name"
                                                             name="rule_name"
                                                             value="<?php echo(isset($data->rule_name) ? $data->rule_name : ''); ?>"
                                                             placeholder="ex. Standard Rule."></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3"><label> Rule Description <i
                                            class="text-muted glyphicon glyphicon-exclamation-sign"
                                            title="Rule Desctriptions."></i></label></div>
                                <div class="col-md-6"><input type="text" class="form-control rule_descr"
                                                             name="rule_descr"
                                                             value="<?php echo(isset($data->rule_descr) ? $data->rule_descr : ''); ?>"
                                                             id="rule_descr"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-3"><label> Validity <i
                                            class="text-muted glyphicon glyphicon-exclamation-sign"
                                            title="Period of Rule Active."></i></label></div>
                                <div class="col-md-6">
                                    <div class="form-inline"><input type="text"
                                                                    name="date_from"
                                                                    class="form-control datepicker"
                                                                    value="<?php echo(isset($data->date_from) ? $data->date_from : ''); ?>"
                                                                    placeholder="From">
                                        <input type="text" name="date_to"
                                               class="form-control datepicker"
                                               value="<?php echo(isset($data->date_to) ? $data->date_to : ''); ?>"
                                               placeholder="To"></div>
                                </div>
                            </div>
                        </div>
                        <div align="right">
                            <input type="button" class="button button-primary restriction_tab" value="Next">
                        </div>
                    </div>

                    <div class="col-md-12" id="restriction_block"><h4 class="text text-muted"> Cart Conditions </h4>
                        <a href=javascript:void(0) id="add_cart_rule" class="button button-primary"><i
                                class="glyphicon glyphicon-plus"></i>
                            Add Condition</a>
                        <hr>
                        <div class="form-group">
                            <div id="cart_rules_list">
                                <?php
                                $i = 0;
                                foreach ($discount_rules as $rule_type => $rule) {

                                    if (!empty($discount_rules)) {
                                        if (!isset($discount_rules[0])) {
                                            $type = $rule_type;
                                        }
                                    }

                                    // Dummy Entry for One Rule at starting.
                                    // Note : Must having at least one rule on starting.
                                    $rule = (!is_null($rule) ? $rule : [0 => '1']);
                                    ?>
                                    <div class="cart_rules_list row">
                                        <div class="col-md-3 form-group">
                                            <label>
                                                Type
                                                <select class="form-control cart_rule_type"
                                                        id="cart_condition_type_<?php echo $i; ?>"
                                                        name="discount_rule[<?php echo $i; ?>][type]">
                                                    <optgroup label="Cart Subtotal">
                                                        <option
                                                            value="subtotal_least"<?php if ($type == 'subtotal_least') { ?> selected=selected <?php } ?>>
                                                            Subtotal at least
                                                        </option>
                                                        <option
                                                            value="subtotal_less"<?php if ($type == 'subtotal_less') { ?> selected=selected <?php } ?>>
                                                            Subtotal less than
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="Cart Item Count">
                                                        <option
                                                            value="item_count_least"<?php if ($type == 'item_count_least') { ?> selected=selected <?php } ?>>
                                                            Count of cart items at least
                                                        </option>
                                                        <option
                                                            value="item_count_less"<?php if ($type == 'item_count_less') { ?> selected=selected <?php } ?>>
                                                            Count of cart items less than
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="Quantity Sum">
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="quantity_least" <?php
                                                            }
                                                            if ($type == 'quantity_least') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                Sum of item quantities at least
                                                                <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                Sum of item quantities at least
                                                            <?php } ?>
                                                        </option>

                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="quantity_less" <?php
                                                            }
                                                            if ($type == 'quantity_less') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                Sum of item quantities less than
                                                                <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                Sum of item quantities less than
                                                            <?php } ?>
                                                        </option>
                                                    </optgroup>
                                                    <!--                                                <optgroup label="Products In Cart">-->
                                                    <!---->
                                                    <!--                                                    <!-- At least one of these should present in the cart to apply. -->

                                                    <!--                                                    <option-->
                                                    <!--                                                        value="products_atleast_one"-->
                                                    <?php //if ($type == 'products_atleast_one') { ?><!-- selected=selected -->
                                                    <?php //} ?><!-->
                                                    <!--                                                        At least one product in-->
                                                    <!--                                                        cart-->
                                                    <!--                                                    </option>-->
                                                    <!--                                                    <!-- These products should not present in the cart to apply. -->

                                                    <!--                                                    <option-->
                                                    <!--                                                        value="products_not_in"-->
                                                    <?php //if ($type == 'products_not_in') { ?><!-- selected=selected -->
                                                    <?php //} ?><!-->
                                                    <!--                                                        None of selected products in-->
                                                    <!--                                                        cart-->
                                                    <!--                                                    </option>-->
                                                    <!--                                                </optgroup>-->
<!--                                                                                                    <optgroup label="Categories In Cart">-->
                                                                                                        <!-- At least one of these should present in the cart to apply. -->

<!--                                                                                                        <option-->
<!--                                                            --><?php //if (!$pro) { ?><!-- disabled --><?php //} else { ?><!-- value="categories_atleast_one" --><?php
//                                                            } ?>
<!--                                                    --><?php //if ($type == 'categories_atleast_one') { ?><!-- selected="selected"-->
<!--                                                    --><?php //} ?><!-->
<!--                                                                                                            At least one category in-->
<!--                                                                                                            cart-->
<!--                                                                                                        </option>-->
                                                                                                        <!-- These Category should not present in the cart to apply.
                                                                                                        <option
                                                                                                            value="categories_not_in"
                                                    <?php //if ($type == 'categories_not_in') { ?><!-- selected=selected -->
                                                    <?php //} ?>
                                                    <!--                                                        None of selected categories in-->
                                                    <!--                                                        cart-->
                                                    <!--                                                    </option>-->
<!--                                                                                                    </optgroup>-->
                                                    <optgroup label="Customer Details (must be logged in)">
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="users_in" <?php
                                                            }
                                                            if ($type == 'users_in') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                User in list <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                User in list
                                                            <?php } ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="roles_in" <?php
                                                            }
                                                            if ($type == 'roles_in') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                User role in list <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                User role in list
                                                            <?php } ?>
                                                        </option>
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="shipping_countries_in" <?php
                                                            }
                                                            if ($type == 'shipping_countries_in') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                Shipping country in list <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                Shipping country in list
                                                            <?php } ?>
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="Customer Email Domain (Eg: edu)">
                                                        <option
                                                            <?php if (!$pro) { ?> disabled <?php } else { ?> value="customer_email_tld" <?php
                                                            }
                                                            if ($type == 'customer_email_tld') { ?> selected=selected <?php } ?>>
                                                            <?php if (!$pro) { ?>
                                                                Email ends with <b><?php echo $suffix; ?></b>
                                                            <?php } else { ?>
                                                                Email ends with
                                                            <?php } ?>
                                                        </option>
                                                    </optgroup>
                                                </select>
                                            </label>
                                        </div>
                                        <div class="col-md-3 form-group">
                                            <label> Value
                                                <?php
                                                $users_list = array();
                                                $class = 'style="display:none"';
                                                $hit = false;
                                                if ($type == 'users_in') {
                                                    $users_list = $discount_rules[$type];
                                                    $class = 'style="display:block"';
                                                    $hit = true;
                                                }
                                                //
                                                ?>
                                                <div id="user_div_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <select class="user_list selectpicker"
                                                            id="cart_user_list_<?php echo $i; ?>" multiple
                                                            name="discount_rule[<?php echo $i; ?>][users_to_apply][]">
                                                        <?php foreach ($users as $index => $user) { ?>
                                                            <option
                                                                value="<?php echo $index; ?>"<?php if (in_array($index, $users_list)) { ?> selected=selected <?php } ?>><?php echo $user; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <?php
                                                $products_list = array();
                                                $class = 'style="display:none"';
                                                if ($type == 'products_atleast_one' || $type == 'products_not_in') {
                                                    $products_list = $discount_rules[$type];;
                                                    $class = 'style="display:block"';
                                                    $hit = true;
                                                }

                                                ?>
                                                <div id="product_div_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <select class="product_list selectpicker"
                                                            id="cart_product_list_<?php echo $i; ?>"
                                                            multiple
                                                            name="discount_rule[<?php echo $i; ?>][product_to_apply][]">
                                                        <?php foreach ($products as $index => $product) { ?>
                                                            <option
                                                                value="<?php echo $index; ?>" <?php if (in_array($index, $products_list)) { ?> selected=selected <?php } ?>><?php echo $product; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <?php
                                                $category_list = array();
                                                $class = 'style="display:none"';
                                                if ($type == 'categories_atleast_one' || $type == 'categories_not_in') {
                                                    $category_list = $discount_rules[$type];;

                                                    $class = 'style="display:block"';
                                                    $hit = true;
                                                }

                                                ?>
                                                <div id="category_div_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <select class="category_list selectpicker"
                                                            id="cart_category_list_<?php echo $i; ?>"
                                                            multiple
                                                            name="discount_rule[<?php echo $i; ?>][category_to_apply][]">
                                                        <?php foreach ($category as $index => $cat) { ?>
                                                            <option
                                                                value="<?php echo $index; ?>"<?php if (in_array($index, $category_list)) { ?> selected=selected <?php } ?>><?php echo $cat; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <?php
                                                $roles_list = array();
                                                $class = 'style="display:none"';
                                                if ($type == 'roles_in') {
                                                    $roles_list = $discount_rules[$type];;
                                                    $class = 'style="display:block"';
                                                    $hit = true;
                                                }

                                                ?>
                                                <div id="roles_div_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <select class="roles_list selectpicker"
                                                            id="cart_roles_list_<?php echo $i; ?>" multiple
                                                            name="discount_rule[<?php echo $i; ?>][user_roles_to_apply][]">
                                                        <?php foreach ($userRoles as $index => $user) { ?>
                                                            <option
                                                                value="<?php echo $index; ?>"<?php if (in_array($index, $roles_list)) { ?> selected=selected <?php } ?>><?php echo $user; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <?php
                                                $countries_list = array();
                                                $class = 'style="display:none"';
                                                if ($type == 'shipping_countries_in') {
                                                    $countries_list = $discount_rules[$type];;
                                                    $class = 'style="display:block"';
                                                    $hit = true;
                                                }

                                                ?>
                                                <div id="countries_div_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <select class="country_list selectpicker"
                                                            data-live-search="true"
                                                            id="cart_countries_list_<?php echo $i; ?>"
                                                            multiple
                                                            name="discount_rule[<?php echo $i; ?>][countries_to_apply][]">
                                                        <?php foreach ($countries as $index => $country) { ?>
                                                            <option
                                                                value="<?php echo $index; ?>"<?php if (in_array($index, $countries_list)) { ?> selected=selected <?php } ?>><?php echo $country; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <?php
                                                if ($hit) {
                                                    $class = 'style="display:none"';
                                                } else {
                                                    $class = 'style="display:block"';
                                                }
                                                ?>
                                                <div id="general_<?php echo $i; ?>" <?php echo $class; ?>>
                                                    <input type="text"
                                                           value="<?php echo(isset($discount_rules[$type]) && !is_array($discount_rules[$type]) ? $discount_rules[$type] : ''); ?>"
                                                           name="discount_rule[<?php echo $i; ?>][option_value]">
                                                </div>
                                            </label>
                                        </div>
                                        <div class="col-md-1"><label> Action <a href=javascript:void(0)
                                                                                class="button button-secondary remove_cart_rule">Remove</a>
                                            </label></div>
                                    </div>
                                    <?php
                                    $i++;
                                }
                                ?>
                            </div>
                        </div>
                        <div align="right">
                            <input type="button" class="button button-secondary general_tab" value="Previous">
                            <input type="button" class="button button-primary discount_tab" value="Next">
                        </div>
                    </div>

                    <!-- TODO: Implement ForEach Concept -->
                    <div class="col-md-12" id="discount_block"><h4 class="text text-muted"> Discount</h4>
                        <?php
                        $discount_type = 'percentage_discount';
                        $to_discount = 0;
                        if (isset($data)) {
                            if (isset($data->discount_type)) {
                                $discount_type = $data->discount_type;
                            }
                            if (isset($data->to_discount)) {
                                $to_discount = $data->to_discount;
                            }
                        }
                        ?>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label> Discount Type :
                                        <select class="form-control" name="discount_type">
                                            <option
                                                value="percentage_discount" <?php if ($discount_type == 'percentage_discount') { ?> selected=selected <?php } ?>>
                                                Percentage Discount
                                            </option>
                                            <option
                                                <?php if (!$pro) { ?> disabled <?php } else { ?> value="price_discount" <?php }
                                                if ($discount_type == 'price_discount') { ?> selected=selected <?php } ?>>
                                                <?php if (!$pro) { ?>
                                                    Price Discount <b><?php echo $suffix; ?></b>
                                                <?php } else { ?>
                                                    Price Discount

                                                <?php } ?>
                                            </option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label> value :
                                        <input type="text" name="to_discount" class="form-control"
                                               value="<?php echo $to_discount; ?>">
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div align="right">
                            <input type="button" class="button button-secondary restriction_tab" value="Previous">
                        </div>
                    </div>
                </div>
            </div>
            <!-- Sidebar -->
            <?php include_once(__DIR__ . '/template/sidebar.php'); ?>
            <!-- Sidebar END -->
            <input type="hidden" name="rule_id" id="rule_id" value="<?php echo $rule_id; ?>">
            <input type="hidden" name="form" value="<?php echo $form; ?>">
            <input type="hidden" id="ajax_path" value="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" id="admin_path" value="<?php echo admin_url('admin.php?page=woo_discount_rules'); ?>">
            <input type="hidden" id="pro_suffix" value="<?php echo $suffix; ?>">
            <input type="hidden" id="is_pro" value="<?php echo $pro; ?>">
            <!--            <input type="hidden" name="status" value="--><?php //echo $status; ?><!--">-->
        </form>
    </div>

<?php include_once(WOO_DISCOUNT_DIR . '/view/includes/footer.php'); ?>