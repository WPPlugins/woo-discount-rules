<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!isset($table_data) || empty($table_data)) return false;
$base_config = (is_string($data)) ? json_decode($data, true) : (is_array($data) ? $data : array());
?>
<table>
    <thead>
    <tr>
        <?php if (isset($base_config['show_discount_title_table'])) {
            if ($base_config['show_discount_title_table'] == 'show') {
                ?>
                <td>Name</td>
            <?php }
        } ?>
        <td>Range</td>
        <td>Discount</td>
    </tr>
    </thead>
    <tbody>
    <?php
    $have_discount = false;
    $table = $table_data;
    foreach ($table as $index => $item) {
        if ($item) {
            foreach ($item as $id => $value) {

                $title = isset($value->title) ? $value->title : '';
                $min = isset($value->min_qty) ? $value->min_qty : 0;
                $max = isset($value->max_qty) ? $value->max_qty : 0;
                $discount_type = isset($value->discount_type) ? $value->discount_type : 0;
                $to_discount = isset($value->to_discount) ? $value->to_discount : 0;

                ?>
                <tr>
                    <?php if (isset($base_config['show_discount_title_table'])) {
                        if ($base_config['show_discount_title_table'] == 'show') {
                            ?>
                            <td><?php echo $title; ?></td>
                        <?php }
                    } ?>
                    <td><?php echo $min . ' - ' . $max; ?></td>
                    <?php if ($discount_type == 'percentage_discount') { ?>
                        <td><?php echo $to_discount . ' %'; ?></td>
                    <?php } else { ?>
                        <td><?php echo wc_price($to_discount); ?></td>
                    <?php } ?>
                </tr>
            <?php }
            $have_discount = true;
        }
    }
    if (!$have_discount) {
        echo '<tr><td colspan="2">No Active Discounts.</td></tr>';
    }
    ?>
    </tbody>
</table>
