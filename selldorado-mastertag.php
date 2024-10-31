<?php
/*
Plugin Name: Selldorado Mastertag
Description: Selldorado's module installs automatically on your wooCommerce shop the technical elements required to launch campaigns on Selldorado. So, you can directly launch your advertising campaigns on Selldorado without technical intervention.
Version:     20180606
Author:      effiliation
Author URI:  http://www.selldorado.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: selldorado-mastertag
Domain Path: /languages
*/

/*
Selldorado Mastertag is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Selldorado Mastertag is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Selldorado Mastertag. If not, see https://www.gnu.org/licenses/gpl-2.0.html
*/


if (!defined('ABSPATH')) {
    exit;
}


/**
 * Check if WooCommerce is active
 **/
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    if (is_ssl()) {
        define("URL_PREFIX", "https");
    } else {
        define("URL_PREFIX", "http");
    }

    function safe_floatval($val)
    {
        return floatval(preg_replace("/[^-0-9\.]/", "", str_replace(",", ".", $val)));
    }


    // i18n
    function selldorado_plugin_load_plugin_textdomain()
    {
        load_plugin_textdomain('selldorado-mastertag', FALSE, basename(dirname(__FILE__)) . '/languages/');
    }

    add_action('plugins_loaded', 'selldorado_plugin_load_plugin_textdomain');


    // Admin menu entry
    add_action('admin_menu', 'selldorado_mastertag_settings_menu');

    function selldorado_mastertag_settings_menu()
    {
        add_options_page('Selldorado Mastertag', 'Selldorado Mastertag', 'manage_options', 'selldorado-mastertag-settings', 'selldorado_mastertag_settings');
    }

    // Admin settings form
    function selldorado_mastertag_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'selldorado-mastertag'));
        }
        include(plugin_dir_path(__FILE__) . "php/selldorado-mastertag-settings.php");
    }

    // Form results handle
    add_action('admin_init', 'selldorado_mastertag_admin_init');

    function selldorado_mastertag_admin_init()
    {
        register_setting('selldorado-mastertag-options', 'selldorado-mastertag-options', 'selldorado_mastertag_options_validate');
        add_settings_section('selldorado_mastertag_main', __('Selldorado Mastertag', 'selldorado-mastertag'), 'selldorado_mastertag_text', 'selldorado-mastertag-options');
        add_settings_field('selldorado-mastertag-id', __('Please input your advertiser id here :', 'selldorado-mastertag'), 'selldorado_mastertag_input_html', 'selldorado-mastertag-options', 'selldorado_mastertag_main');
        add_settings_field('selldorado-datalayer-only', __('Activate dataLayer only ?  :', 'selldorado-mastertag'), 'selldorado_mastertag_datalayer_html', 'selldorado-mastertag-options', 'selldorado_mastertag_main');
    }

    function selldorado_mastertag_text()
    {
        _e("Please insert your advertiser ID in ordrer to activate your Mastertag", 'selldorado-mastertag');
    }

    function selldorado_mastertag_input_html()
    {
        include(plugin_dir_path(__FILE__) . "php/selldorado-mastertag-settings-input.php");
    }

    function selldorado_mastertag_datalayer_html()
    {
        include(plugin_dir_path(__FILE__) . "php/selldorado-mastertag-settings-datalayer.php");
    }

    function selldorado_mastertag_options_validate($input)
    {
        $newinput['mastertag-id'] = trim($input['mastertag-id']);
        $newinput['datalayer-only'] = trim($input['datalayer-only']);
        return $newinput;
    }

    // Mastertag on every page 
    add_action('wp_head', 'selldorado_home_mastertag');
    // Mastertag on login page
    add_action('login_head', 'selldorado_login_mastertag');
    // CPA Tag
    add_action('woocommerce_thankyou', 'selldorado_cpa_tag');

    function selldorado_cpa_tag()
    {
        $advertiser_id = get_option("selldorado-mastertag-options")['mastertag-id'];
        // Advertiser cannot be empty
        if (!empty($advertiser_id)) {
            $user_logged_in = is_user_logged_in();
            $insession = empty($user_logged_in) ? 0 : is_user_logged_in();

            $store_id = get_option("blogname");
            $newcustomer = is_new_customer();

            $order = get_current_order();
            $products = get_products_from_order($order->get_items());
            $id_products = build_id_products($products);
            $products_prices = build_products_prices($products);
            $order_total = safe_floatval($order->order_total) - safe_floatval($order->order_tax);
            $payment = strip_tags($order->payment_method);
            $currency = str_replace('EUR', 'eu', get_woocommerce_currency());
            $voucher = $order->get_used_coupons()[0];

            echo "<img src=\"" . URL_PREFIX . "://track.effiliation.com/servlet/effi.revenue?id=" . $advertiser_id . "&montant=" . $order_total . "&monnaie=" . $currency . "&ref=" . $order->id . "&payment=" . $payment . "&newcustomer=" . $newcustomer . "&voucher=" . $voucher . "\" alt=\"\" width=\"1\" height=\"1\" />";
        }
    }

    function get_customer_total_order()
    {
        $customer_orders = get_posts(array(
            'numberposts' => -1,
            'meta_key' => '_customer_user',
            'meta_value' => get_current_user_id(),
            'post_type' => array('shop_order'),
            'post_status' => array('wc-completed'),
        ));

        $total = 0;
        foreach ($customer_orders as $customer_order) {
            $order = wc_get_order($customer_order);
            $total += $order->get_total();
        }
        return $total;
    }

    function is_new_customer()
    {
        if (!is_user_logged_in()) {
            return "";
        }
        return get_customer_total_order() > 0 ? "0" : "1";
    }

    function get_current_product()
    {
        $post = get_post();
        if (!empty($post)) {
            if ($post->post_type == 'product') {
                $product = new WC_Product($post->ID);
                return $product;
            }
        }
        return "";
    }

    function get_current_order()
    {
        $args = array(
            'post_type' => 'shop_order',
            'post_status' => array_keys(wc_get_order_statuses()),
            'meta_key' => '_customer_user',
            'meta_value' => get_current_user_id(),
            'posts_per_page' => '1',
            'orderby' => 'date'
        );
        $my_query = new WP_Query($args);

        $customer_orders = $my_query->posts;
        $order = new WC_Order();
        foreach ($customer_orders as $customer_order) {
            $order->populate($customer_order);
        }
        return $order;
    }

    function get_current_category_products()
    {
        $args = array('post_type' => 'product', 'product_cat' => $_GET['product_cat']);
        $loop = new WP_Query($args);
        $products = array();
        while ($loop->have_posts()) {
            $loop->the_post();
            $product = get_current_product();
            $products[] = $product;
        }
        return $products;
    }

    function get_current_category_from_product_list($products)
    {
        if (!empty($products)) {
            $categories = get_the_terms($products[0]->ID, 'product_cat');
            foreach ($categories as $category) {
                if ($category->slug == $_GET['product_cat']) {
                    return $category;
                }
            }
        }
        return "";
    }

    function build_id_products($products)
    {
        $id_products = "";
        for ($i = 0, $separator = ""; $i < count($products); $i++, $separator = ',') {
            $id_products .= $separator . get_product_id($products[$i]);
        }
        return $id_products;
    }

    function get_product_id($product)
    {
        $product_sku = $product->get_sku();
        return empty($product_sku) ? $product->post->post_name : $product->get_sku();
    }

    function get_products_from_cart($cart)
    {
        $products = array();
        foreach ($cart as $cartitem) {
            $products[] = $cartitem['data'];
        }
        return $products;
    }

    function get_products_from_order($order)
    {
        $products = array();
        foreach ($order as $orderitem) {
            $products[] = new WC_Product($orderitem['product_id']);
        }
        return $products;
    }

    function build_products_prices($products)
    {
        $products_prices = "";
        for ($i = 0, $separator = ""; $i < count($products); $i++, $separator = ',') {
            $products_prices .= $separator . $products[$i]->get_price();
        }
        return $products_prices;
    }

    function build_form_master_tag($advertiser_id, $insession, $newcustomer, $store_id, $datalayer_only)
    {
        $page = 'form';
        // GTM
        echo "<script> 	var effiDataLayer = {'page': '" . $page . "', 'insession': '" . $insession . "', 'newcustomer': '" . $newcustomer . "', 'storeid':'" . $store_id . "'}; var dataLayer = dataLayer || []; dataLayer.push(effiDataLayer);</script>\n";
        if (!$datalayer_only) {
            echo "<script src=\"" . URL_PREFIX . "://mastertag.effiliation.com/mt" . $advertiser_id . ".js?page=form&insession=" . $insession . "&newcustomer=" . $newcustomer . "&storeid=" . $store_id . "\" async=\"async\"></script>\n";
        }
    }

    function selldorado_login_mastertag()
    {
        $advertiser_id = get_option("selldorado-mastertag-options")['mastertag-id'];
        $datalayer_only = get_option("selldorado-mastertag-options")['datalayer-only'] == 'Yes';
        $is_user_logged_in = is_user_logged_in();
        $insession = empty($is_user_logged_in) ? 0 : is_user_logged_in();
        $store_id = get_option("blogname");
        $newcustomer = is_new_customer();
        build_form_master_tag($advertiser_id, $insession, $newcustomer, $store_id, $datalayer_only);
    }

    function selldorado_home_mastertag()
    {
        $advertiser_id = get_option("selldorado-mastertag-options")['mastertag-id'];
        $datalayer_only = get_option("selldorado-mastertag-options")['datalayer-only'] == 'Yes';
        // Advertiser cannot be empty
        if (!empty($advertiser_id)) {
            $is_user_logged_in = is_user_logged_in();
            $insession = empty($is_user_logged_in) ? 0 : is_user_logged_in();
            $store_id = get_option("blogname");
            $newcustomer = is_new_customer();

            if ((function_exists('is_home') && is_home()) || (function_exists('is_front_page') && is_front_page())) {
                $page = "home";
                // GTM DataLayer
                echo "<script> 	var effiDataLayer = {'page': '" . $page . "', 'insession': '" . $insession . "', 'newcustomer': '" . $newcustomer . "', 'storeid':'" . $store_id . "'}; var dataLayer = dataLayer || []; dataLayer.push(effiDataLayer);</script>\n";
                if (!$datalayer_only) {
                    echo "<script src=\"" . URL_PREFIX . "://mastertag.effiliation.com/mt" . $advertiser_id . ".js?page=home&insession=" . $insession . "&newcustomer=" . $newcustomer . "&storeid=" . $store_id . "\" async=\"async\"></script>\n";
                }
            } else if (function_exists('is_product_category') && is_product_category()) {
                $page = "category";
                $products = get_current_category_products();
                $category = get_current_category_from_product_list($products);
                $id_products = build_id_products($products);
                // GTM DataLayer
                echo "<script> 	var effiDataLayer = {'page': '" . $page . "', 'insession': '" . $insession . "', 'newcustomer': '" . $newcustomer . "', 'storeid':'" . $store_id . "', 'idcat' : '" . $category->slug . "', 'wordingcat' : '" . $category->name . "', 'idp' : '" . $id_products . "'}; var dataLayer = dataLayer || []; dataLayer.push(effiDataLayer);</script>\n";

                if (!$datalayer_only) {
                    echo "<script src=\"" . URL_PREFIX . "://mastertag.effiliation.com/mt" . $advertiser_id . ".js?page=category&insession=" . $insession . "&newcustomer=" . $newcustomer . "&storeid=" . $store_id . "&idcat=" . $category->slug . "&wordingcat=" . $category->name . "&idp=" . $id_products . "\" async=\"async\"></script>\n";
                }
            } else if (function_exists('is_cart') && is_cart()) {
                $page = "addcart";
                global $woocommerce;
                $cart = $woocommerce->cart;
                $products = get_products_from_cart($cart->get_cart());
                $id_products = build_id_products($products);
                $products_prices = build_products_prices($products);
                $cart_total = $cart->cart_contents_total;
                // GTM DataLayer
                echo "<script> 	var effiDataLayer = {'page': '" . $page . "', 'insession': '" . $insession . "', 'newcustomer': '" . $newcustomer . "', 'storeid':'" . $store_id . "', 'idp' : '" . $id_products . "', 'prix' : '" . $products_prices . "', 'montant' : '" . $cart_total . "'}; var dataLayer = dataLayer || []; dataLayer.push(effiDataLayer);</script>\n";

                if (!$datalayer_only) {
                    echo "<script src=\"" . URL_PREFIX . "://mastertag.effiliation.com/mt" . $advertiser_id . ".js?page=addcart&insession=" . $insession . "&newcustomer=" . $newcustomer . "&storeid=" . $store_id . "&idp=" . $id_products . "&prix=" . $products_prices . "&montant=" . $cart_total . "\" async=\"async\"></script>\n";
                }
            } else if (function_exists('is_product') && is_product()) {
                $page = "product";
                $product = get_current_product();
                if (!empty($product)) {
                    $product_id = get_product_id($product);
                    $product_price = $product->get_price();
                }
                // GTM DataLayer
                echo "<script> 	var effiDataLayer = {'page': '" . $page . "', 'insession': '" . $insession . "', 'newcustomer': '" . $newcustomer . "', 'storeid':'" . $store_id . "', 'idp' : '" . $product_id . "', 'prix' : '" . $product_price . "'}; var dataLayer = dataLayer || []; dataLayer.push(effiDataLayer);</script>\n";
                if (!$datalayer_only) {
                    echo "<script src=\"" . URL_PREFIX . "://mastertag.effiliation.com/mt" . $advertiser_id . ".js?page=product&insession=" . $insession . "&newcustomer=" . $newcustomer . "&storeid=" . $store_id . "&idp=" . $product_id . "&prix=" . $product_price . "\" async=\"async\"></script>\n";
                }
            } else if (function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url('order-received')) {
                build_form_master_tag($advertiser_id, $insession, $newcustomer, $store_id, $datalayer_only);
            } else if (function_exists('is_checkout') && is_checkout() && is_wc_endpoint_url('order-received')) {
                $page = "sale";
                $order = get_current_order();

                $products = get_products_from_order($order->get_items());
                $id_products = build_id_products($products);
                $products_prices = build_products_prices($products);
                $order_total = safe_floatval($order->order_total) - safe_floatval($order->order_tax);
                // GTM DataLayer
                echo "<script> 	var effiDataLayer = {'page': '" . $page . "', 'insession': '" . $insession . "', 'newcustomer': '" . $newcustomer . "', 'storeid':'" . $store_id . "', 'idp' : '" . $id_products . "', 'prix' : '" . $products_prices . "', 'montant' : '" . $order_total . "', 'ref' : '" . $order->id . "'}; var dataLayer = dataLayer || []; dataLayer.push(effiDataLayer);</script>\n";
                if (!$datalayer_only) {
                    echo "<script src=\"" . URL_PREFIX . "://mastertag.effiliation.com/mt" . $advertiser_id . ".js?page=sale&insession=" . $insession . "&newcustomer=" . $newcustomer . "&storeid=" . $store_id . "&idp=" . $id_products . "&prix=" . $products_prices . "&montant=" . $order_total . "&ref=" . $order->id . "\" async=\"async\"></script>\n";
                }
            } else {
                $page = 'generic';
                // GTM DataLayer
                echo "<script> 	var effiDataLayer = {'page': '" . $page . "', 'insession': '" . $insession . "', 'newcustomer': '" . $newcustomer . "', 'storeid':'" . $store_id . "'}; var dataLayer = dataLayer || []; dataLayer.push(effiDataLayer);</script>\n";
                if (!$datalayer_only) {
                    echo "<script src=\"" . URL_PREFIX . "://mastertag.effiliation.com/mt" . $advertiser_id . ".js?page=generic&insession=" . $insession . "&newcustomer=" . $newcustomer . "&storeid=" . $store_id . "\" async=\"async\"></script>\n";
                }
            }
        }
    }
}