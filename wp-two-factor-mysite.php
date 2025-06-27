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



// Admin Menu 
function wtfm_add_menu() {
     add_menu_page(
        'WP Two Factor Mysite',
        'Two Factor',
        'manage_options',
        'two_fact_mysite',
        'wtfm_two_callback',
        'dashicons-admin-network',
        50
     );
}
add_action('admin_menu', 'wtfm_add_menu');


function wtfm_two_callback() {
    ?>
    <div class="wrap">
        <h2>Two Factor Authentication Settings</h2>
        <form action="options.php" method="post">
            <?php
            settings_fields('wtfm_settings_group');
            do_settings_sections('wtfm_settings');
            submit_button('Save Changes');

            ?>
        </form>
    </div>
    <?php

    
}


// Send OTP after login and redirect to verify page
add_action('wp_login', 'wtfm_otp_login', 10, 2);
function wtfm_otp_login($user_login, $user) {
    wp_clear_auth_cookie(); // Force logout before OTP verify
    $otp = rand(100000, 999999);
    set_transient('otp_' . $user->ID, $otp, 300); // store OTP for 5 minutes

    $sent = wp_mail(
        $user->user_email,
        'Your OTP Code',
        'Your OTP is: <strong>' . $otp . '</strong>',
        ['Content-Type: text/html; charset=UTF-8']
    );

    if (!$sent) {
        error_log('OTP email failed to send to ' . $user->user_email);
    }

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
                delete_transient('otp_' . $user_id); // remove OTP after use
                wp_set_auth_cookie($user_id);
                wp_redirect(home_url('/'));
                exit;
            } else {
                echo "<p style='color:red; text-align:center;'>Invalid OTP!</p>";
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
        <style>
            body {
                background-color: #F5F5FA;
                font-family: Arial, sans-serif;
            }
            .otp_form {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .sub_otp_form {
                background-color: #fff;
                padding: 30px;
                width: 300px;
                border-radius: 10px;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
                text-align: center;
            }
            .sub_otp_form label {
                font-size: 20px;
                display: block;
                margin-bottom: 10px;
            }
            .sub_otp_form input[type="number"],
            .sub_otp_form input[type="submit"] {
                width: 90%;
                padding: 10px;
                margin: 10px 0;
                border-radius: 5px;
                border: 1px solid #ccc;
            }
            .sub_otp_form input[type="submit"] {
                background-color: #0073aa;
                color: white;
                border: none;
                font-weight: bold;
                cursor: pointer;
            }
            .sub_otp_form input[type="submit"]:hover {
                background-color: #005177;
            }
            .back-link {
                margin-top: 10px;
                display: block;
                font-size: 14px;
                text-decoration: none;
                color: #0073aa;
            }
        </style>
        <div class="otp_form">
            <div class="sub_otp_form">
                <form method="post">
                    <label for="otp">Enter OTP</label>
                    <input type="number" id="otp" name="otp" placeholder="Enter code" required />
                    <input type="submit" name="verify_otp" value="Verify" />
                </form>
                <a class="back-link" href="' . wp_login_url() . '">← Back to login</a>
            </div>
        </div>
        </body>
        </html>';
        exit;
    }
});
