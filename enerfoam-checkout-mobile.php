<?php
/**
 * Plugin Name: Enerfoam Checkout Mobile
 * Description: Require a plausible mobile number for Iranian checkout orders in Classic and WooCommerce Store API, independently of email or Google sign-in.
 * Version: 0.3.1
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH')) { exit; }
function efm_digits($s) { return str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'],['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'],(string)$s); }
function efm_canon($s) {
  $s=preg_replace('/[\s.()\x{200c}\x{200e}\x{200f}-]+/u','',efm_digits($s));
  if (!is_string($s)) return '';
  if (preg_match('/^09\d{9}$/D',$s)) return '+98'.substr($s,1);
  if (preg_match('/^00989\d{9}$/D',$s)) return '+'.substr($s,2);
  if (preg_match('/^989\d{9}$/D',$s)) return '+'.$s;
  return $s;
}
function efm_valid($s,$country='IR') {
  $s=efm_canon($s);
  if (preg_match('/^\+989\d{9}$/D',$s)) return true;
  return strtoupper((string)$country)!=='IR' && (bool)preg_match('/^\+[1-9]\d{7,14}$/D',$s);
}
function efm_message(){return 'برای ثبت سفارش شماره موبایل معتبر وارد کنید؛ پس از ورود با گوگل نیز شماره موبایل الزامی است.';}
add_filter('woocommerce_checkout_fields',function($f){
  if(isset($f['billing']['billing_phone'])) {
    $f['billing']['billing_phone']['required']=true;
    $f['billing']['billing_phone']['label']='شماره موبایل';
    $f['billing']['billing_phone']['type']='tel';
    $f['billing']['billing_phone']['autocomplete']='tel';
  }
  return $f;
},100);
add_filter('woocommerce_checkout_posted_data',function($d){if(isset($d['billing_phone'])) $d['billing_phone']=efm_canon($d['billing_phone']); return $d;},80);
add_action('woocommerce_after_checkout_validation',function($d,$errors){
  if(!efm_valid($d['billing_phone']??'',$d['billing_country']??'IR')) $errors->add('efm_mobile',efm_message());
},10,2);
add_action('woocommerce_store_api_checkout_update_order_from_request',function($order,$request){
  if(!($request instanceof WP_REST_Request) || strtoupper($request->get_method())!=='POST') return;
  $a=$request->get_param('billing_address');$a=is_array($a)?$a:[];
  if(!efm_valid($a['phone']??'',$a['country']??'IR')) throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException('enerfoam_mobile_required',efm_message(),400);
  if(method_exists($order,'set_billing_phone')) $order->set_billing_phone(efm_canon($a['phone']));
},10,2);
register_activation_hook(__FILE__,function(){
 foreach(['woocommerce_myaccount_page_id'=>1293,'woocommerce_cart_page_id'=>1307,'woocommerce_checkout_page_id'=>1308] as $key=>$id) {
   if(get_post_status($id)==='publish') update_option($key,$id);
 }
 update_option('woocommerce_checkout_phone_field','required');
});
