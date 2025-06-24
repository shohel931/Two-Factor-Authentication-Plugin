<?php 

/*
* Plugin Name: WP Two Factor Mysite
* Plugin URI: https://wordpress.org/plugins/wp-two-factor-mysite
* Description: Adds email-based OTP login verification.
* Version: 1.0.0
* Requires at least: 5.2
* Requires PHP: 7.2
* Author: Shohel Rana
* Author URI: https://shohelrana.top
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

add_action('wp_login', 'wtfm_otp_login', 10, 2);

function wtfm_otp_login($user_login, $user) {
    $otp = rand(100000, 999999);
    set_transient('otp_' . $user->ID, $otp, 300);

    wp_mail(
        $user->user_email,
        'Your OTP Code',
        'Your OTP is' . $otp
    );

    wp_redirect(home_url('/otp-verify?user_id=' . $user->ID));
    exit;
}








?>