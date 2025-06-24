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
    set_transient('otp_' . $user->ID, $otp, 300); // store OTP for 5 minutes

    wp_mail(
        $user->user_email,
        'Your OTP Code',
        'Your OTP is: ' . $otp
    );

    wp_redirect(home_url('/otp-verify?user_id=' . $user->ID));
    exit;
}

// Show OTP form and handle verification
add_action('template_redirect', function() {
    if (isset($_GET['user_id']) && strpos($_SERVER['REQUEST_URI'], 'otp-verify') !== false) {

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
            $user_id = intval($_GET['user_id']);
            $otp_input = sanitize_text_field($_POST['otp']);
            $stored_otp = get_transient('otp_' . $user_id);

            if ($stored_otp && $stored_otp == $otp_input) {
                wp_set_auth_cookie($user_id);
                wp_redirect(home_url('/'));
                exit;
            } else {
                echo "<p style='color:red;'>Invalid OTP!</p>";
            }
        }

        // Show OTP form
        echo '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Enter OTP</title>
        </head>
        <body>
        <div class="otp_form">
            <form action="" method="post">
                <label>Enter OTP:</label>
                <input type="text" name="otp" required />
                <input type="submit" name="verify_otp" value="Verify" />
            </form>
            </div>
        </body>
        </html>';
        exit;
    }
});
