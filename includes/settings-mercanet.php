<?php

return array(
    'enabled' => array(
        'title' => __( 'Enable/Disable', 'woocommerce' ),
        'type' => 'checkbox',
        'label' => __( 'Enable Mercanet Payment', 'woocommerce' ),
        'default' => 'yes'
    ),
    'testmode' => array(
        'title' => __( 'Sandbox', 'woocommerce' ),
        'type' => 'checkbox',
        'label' => __( 'Test mode for Mercanet Payment (no actual payment)', 'woocommerce' ),
        'default' => 'yes'
    ),
    'debug' => array(
        'title' => __( 'Debug mode', 'woocommerce' ),
        'type' => 'checkbox',
        'label' => __( 'Trace payment module in WooCommerce log file', 'woocommerce' ),
        'default' => 'yes'
    ),
    'title' => array(
        'title' => __( 'Title', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
        'default' => __( 'Mercanet Payment', 'woocommerce' ),
        'desc_tip'      => true,
    ),
    'description' => array(
        'title' => __( 'Customer Message', 'woocommerce' ),
        'type' => 'textarea',
        'default' => ''
    ),
    'merchantid' => array(
        'title' => __( 'Merchant ID', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'Identifies your Merchant ID (provided by Mercanet). Will be used to credit your banking account (unless in debug mode).', 'woocommerce' ),
        'default' => '0'
    ),
    'secretkey' => array(
        'title' => __( 'Secret Key', 'woocommerce' ),
        'type' => 'password',
        'description' => __( 'Your secret key -- keep it secret!', 'woocommerce' ),
        'default' => ''
    ),
    'keyversion' => array(
        'title' => __( 'Merchant ID', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'Key version', 'woocommerce' ),
        'default' => '1'
    )
);

?>

