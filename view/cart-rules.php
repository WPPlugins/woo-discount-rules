<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

$active = 'cart-rules';
include_once(WOO_DISCOUNT_DIR . '/view/includes/header.php');
include_once(WOO_DISCOUNT_DIR . '/view/includes/menu.php');

$config = (isset($config)) ? $config : '{}';

$data = array();
$rule_list = $config;

//for ( WC()->cart->get_coupons as $code => $coupon){
global $woocommerce;
//    var_dump($woocommerce);
//   if ( ! $valid ){
//       WC()->cart->remove_coupon( $code );
//   }
//}
?>
    <div class="" id="cart_rule">
        <div class="row-fluid">
            <div class="col-md-8">
                <h4>Cart Rules</h4>
                <hr>
                <form method="post" action="?page=woo_discount_rules">
                    <div class="row">
                        <div class="col-md-4" id="add_new_rule_div">
                            <?php if (isset($rule_list)) {
                                if (count($rule_list) >= 3 && !$pro) { ?>
                                    <a href=javascript:void(0)
                                       class="button button-secondary">You Reach Max. Rule Limit
                                    </a>
                                <?php } else {
                                    ?>
                                    <a href="?page=woo_discount_rules&tab=cart-rules&type=new"
                                       id="add_new_rule"
                                       class="button button-primary">Add New Rule
                                    </a>
                                    <?php
                                }
                            }

                            ?>

                        </div>
                        <div class="col-md-12">
                            <code>NOTE: Order Should not be empty ('-').If it's empty('-'), then it won't be
                                implemented.</code>
                        </div>
                    </div>
                    <div class="row">
                        <div class="">
                            <div class=""></div>
                            <div class="">
                                <table class="wp-list-table widefat fixed striped posts">
                                    <thead>
                                    <tr>
                                        <td>Name</td>
                                        <td>Start Date</td>
                                        <td>Expired On</td>
                                        <td>Order</td>
                                        <td>Action</td>
                                    </tr>
                                    </thead>
                                    <tbody id="cart_rule">
                                    <?php
                                    $i = 1;
                                    if (is_array($rule_list)) {
                                        if (count($rule_list) > 0) {
                                            foreach ($rule_list as $index => $rule) {
                                                if (!$pro && $i > 3) continue;
                                                $meta = $rule->meta;
                                                $status = isset($meta['status'][0]) ? $meta['status'][0] : 'disable';
                                                $class = 'button button-secondary';

                                                if ($status == 'publish') {
                                                    $class = 'button button-primary';
                                                    $value = 'Disable';
                                                } else {
                                                    $class = 'button button-secondary';
                                                    $value = 'Enable';
                                                }
                                                ?>

                                                <tr>
                                                    <td><?php echo(isset($meta['rule_name'][0]) ? $meta['rule_name'][0] : '-') ?></td>
                                                    <td><?php echo(isset($rule->date_from) ? $rule->date_from : '-') ?></td>
                                                    <td><?php echo(isset($rule->date_to) ? $rule->date_to : '-') ?></td>
                                                    <td><?php echo((isset($rule->rule_order) && ($rule->rule_order != '')) ? $rule->rule_order : ' - ') ?></td>
                                                    <td>
                                                        <a class="button button-primary"
                                                           href="?page=woo_discount_rules&tab=cart-rules&view=<?php echo $rule->ID ?>">
                                                            Edit
                                                        </a>
                                                        <a class="<?php echo $class; ?> cart_manage_status"
                                                           id="state_<?php echo $rule->ID ?>"><?php echo $value; ?>
                                                        </a>
                                                        <a class="button button-secondary cart_delete_rule"
                                                           id="delete_<?php echo $rule->ID ?>">Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                                $i++;
                                            }
                                        }
                                    }
                                    ?>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td>Name</td>
                                        <td>Start Date</td>
                                        <td>Expired On</td>
                                        <td>Order</td>
                                        <td>Action</td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <hr>

                    <input type="hidden" name="form" value="cart_rules">
                    <input type="hidden" id="ajax_path" value="<?php echo admin_url('admin-ajax.php') ?>">
                </form>
            </div>
            <div class="col-md-1"></div>
            <!-- Sidebar -->
            <?php include_once(__DIR__ . '/template/sidebar.php'); ?>
            <!-- Sidebar END -->
        </div>
    </div>
    <div class="clear"></div>
<?php include_once(WOO_DISCOUNT_DIR . '/view/includes/footer.php'); ?>