<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<div class="col-md-3">
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="col-md-12">
                <div id="" align="right">
                    <div class="woo-side-button" class="hide-on-click">
                        <span id="sidebar_text">Hide</span>
                        <span id="sidebar_icon" class="dashicons dashicons-arrow-left"></span>
                    </div>
                </div>
                <div class="woo-side-panel">
                    <?php
                    echo woo_dicount_rules_generalHelper::getSideBarContent();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>