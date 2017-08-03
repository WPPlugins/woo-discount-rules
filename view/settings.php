<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

$active = 'settings';
include_once(WOO_DISCOUNT_DIR . '/view/includes/header.php');
include_once(WOO_DISCOUNT_DIR . '/view/includes/menu.php');

$data = $config;

if (is_string($data)) $data = json_decode($data, true);
?>

<div class="container-fluid">
    <div class="row-fluid">
        <div class="col-md-8">
            <form method="post" id="discount_config">
                <div class="col-md-12" align="right">
                    <input type="submit" id="saveConfig" value="Save Rule" class="button button-primary">
                </div>
                <div class="">
                    <div class="">
                        <h4>General Settings</h4>
                        <hr>
                    </div>
                    <div class="">
                        <div class="row form-group">
                            <div class="col-md-2">
                                <label>
                                    License Key :
                                </label>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="" name="license_key" id="woo-disc-license-key"
                                       value="<?php if (isset($data['license_key'])) echo $data['license_key']; ?>"
                                       placeholder="Your Unique License Key">
                                <input type="button" id="woo-disc-license-check" value="Validate Key" class="button button-info">
                                <?php
                                $verifiedLicense = get_option('woo_discount_rules_verified_key', 0);
                                if (isset($data['license_key']) && $data['license_key'] != '') {
                                    if ($verifiedLicense) {
                                        ?>
                                        <span class="license-success">&#10004;</span>
                                        <?php
                                    } else {
                                        ?>
                                        <div class="license-failed notice-message error inline notice-error notice-alt">
                                            License key seems to be Invalid. Please enter a valid license key
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                                <br>
                                <div id="woo-disc-license-check-msg">
                                    
                                </div>
                            </div>
                        </div>
                        <div class="row form-group">
                            <?php $data['price_setup'] = (isset($data['price_setup']) ? $data['price_setup'] : 'first'); ?>
                            <div class="col-md-2">
                                <label>
                                    Rule Setup for Price:
                                </label>
                            </div>
                            <div class="col-md-6">
                                <select class="selectpicker" name="price_setup">
                                    <option <?php if ($data['price_setup'] == 'first') { ?> selected=selected <?php } ?>
                                        value="first" selected="selected">Apply first matched rule
                                    </option>
                                    <option
                                        value="all" <?php if (!$pro) { ?> disabled <?php }
                                    if ($data['price_setup'] == 'all') { ?> selected=selected <?php } ?>>
                                        <?php if (!$pro) { ?>
                                            Apply all matched rules <b><?php echo $suffix; ?></b>
                                        <?php } else { ?>
                                            Apply all matched rules
                                        <?php } ?>
                                    </option>
                                    <option
                                        value="biggest" <?php if (!$pro) { ?> disabled <?php }
                                    if ($data['price_setup'] == 'biggest') { ?> selected=selected <?php } ?>>
                                        <?php if (!$pro) { ?>
                                            Apply biggest discount <b><?php echo $suffix; ?></b>
                                        <?php } else { ?>
                                            Apply biggest discount
                                        <?php } ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-2">
                                <label>
                                    Discount Title :
                                </label>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="" name="coupon_name"
                                       value="<?php if (isset($data['coupon_name'])) echo $data['coupon_name']; ?>"
                                       placeholder="Discount Coupon Name">
                            </div>
                        </div>
                        <div class="row form-group">
                            <?php $data['show_discount_table'] = (isset($data['show_discount_table']) ? $data['show_discount_table'] : 'show'); ?>
                            <div class="col-md-2">
                                <label>
                                    Discount Table :
                                </label>
                            </div>
                            <div class="col-md-6">
                                <select class="selectpicker" name="show_discount_table">
                                    <option <?php if ($data['show_discount_table'] == 'show') { ?> selected=selected <?php } ?>
                                        value="show">Show
                                    </option>
                                    <option <?php if ($data['show_discount_table'] == 'dont') { ?> selected=selected <?php } ?>
                                        value="dont">Don't Show
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row form-group">
                            <?php $data['show_discount_title_table'] = (isset($data['show_discount_title_table']) ? $data['show_discount_title_table'] : 'show'); ?>
                            <div class="col-md-2">
                                <label>
                                    Show Discount Title on Table :
                                </label>
                            </div>
                            <div class="col-md-6">
                                <select class="selectpicker" name="show_discount_title_table">
                                    <option <?php if ($data['show_discount_title_table'] == 'show') { ?> selected=selected <?php } ?>
                                        value="show">Show
                                    </option>
                                    <option <?php if ($data['show_discount_title_table'] == 'dont') { ?> selected=selected <?php } ?>
                                        value="dont">Don't Show
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row form-group">
                            <?php $data['cart_setup'] = (isset($data['cart_setup']) ? $data['cart_setup'] : 'first'); ?>
                            <div class="col-md-2">
                                <label>
                                    Rule Setup for Cart:
                                </label>
                            </div>
                            <div class="col-md-6">
                                <select class="selectpicker" name="cart_setup">
                                    <option <?php if ($data['cart_setup'] == 'first') { ?> selected=selected <?php } ?>
                                        value="first" selected="selected">Apply first matched rule
                                    </option>
                                    <option
                                        value="all" <?php if (!$pro) { ?> disabled <?php }
                                    if ($data['cart_setup'] == 'all') { ?> selected=selected <?php } ?>>
                                        <?php if (!$pro) { ?>
                                            Apply all matched rules <b><?php echo $suffix; ?></b>
                                        <?php } else { ?>
                                            Apply all matched rules
                                        <?php } ?>
                                    </option>
                                    <option
                                        value="biggest" <?php if (!$pro) { ?> disabled <?php }
                                    if ($data['cart_setup'] == 'biggest') { ?> selected=selected <?php } ?>>
                                        <?php if (!$pro) { ?>
                                            Apply biggest discount <b><?php echo $suffix; ?></b>
                                        <?php } else { ?>
                                            Apply biggest discount
                                        <?php } ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-2">
                                <label>
                                    Draft
                                </label>
                            </div>
                            <div class="col-md-6">
                                <?php
                                $checked = 0;
                                if (isset($data['show_draft']) && $data['show_draft'] == 1){
                                    $checked = 1;
                                } ?>
                                <input type="checkbox" class="" id="show_draft_1" name="show_draft"
                                       value="1" <?php if($checked){ echo 'checked'; } ?>> <label class="checkbox_label" for="show_draft_1">Exclude Draft products in product select box.</label>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="ajax_path" value="<?php echo admin_url('admin-ajax.php') ?>">
        </div>
        <div class="col-md-1"></div>
        <!-- Sidebar -->
        <?php include_once(__DIR__ . '/template/sidebar.php'); ?>
        <!-- Sidebar END -->
        </form>
    </div>
</div>