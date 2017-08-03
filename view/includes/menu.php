<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php
$proText = $purchase->getProText();
?>
<i><h2>Woo Discount Rules <?php echo $proText; ?> <span class="woo-discount-version">v<?php echo WOO_DISCOUNT_VERSION; ?></span></h2></i><hr>
<h3 class="nav-tab-wrapper">
    <a class="nav-tab <?php if ($active == 'pricing-rules') { echo 'nav-tab-active'; } ?>" href="?page=woo_discount_rules&amp;tab=pricing-rules">
        <i class="fa fa-tags" style="font-size: 0.8em;"></i> &nbsp;Price Discount Rules </a>
    <a class="nav-tab <?php if ($active == 'cart-rules') { echo 'nav-tab-active'; } ?>" href="?page=woo_discount_rules&amp;tab=cart-rules">
        <i class="fa fa-shopping-cart" style="font-size: 0.8em;"></i> &nbsp;Cart Discount Rules </a>
    <a class="nav-tab <?php if ($active == 'settings') { echo 'nav-tab-active'; } ?>" href="?page=woo_discount_rules&amp;tab=settings">
        <i class="fa fa-cogs" style="font-size: 0.8em;"></i> &nbsp;Settings </a>
</h3>
