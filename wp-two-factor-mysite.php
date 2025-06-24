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
        <style>
        body{
            background-color: #F5F5FA;
        }
        .otp_form{
          margin: 0;
          padding: 0;
          display: flex;
          justify-content: center;
          align-items: center;
          height: 60vh;
        }
        .sub_otp_form{
            background-color: #fff;
            width: 300px;
            padding: 30px;
            height: 180px;
            align-items: center;
            justify-content: center;
            display: flex;
            text-align: center;
            border-radius: 10px;
            box-shadow: 5px 5px 10px #ccc;
        }
        .sub_otp_form form label{
            font-size: 25px;
            font-weight: 400;
        }
        .sub_otp_form form input[type="number"]{
            width: 200px;
            padding: 10px;
            border: 1px solid #ddd;
            box-shadow: 1px 1px 10px #ccc;
            border-radius: 5px;
        }
        .sub_otp_form form input[type="submit"]{
            padding: 10px 25px;
            border: none;
            background: blue;
            color: #fff;
            border-radius: 5px;
            transition: 0.5s;
            font-size: 16px;
            font-weight: bold;
        }
        .sub_otp_form form input[type="submit"]:hover{
            background-color: #000;
        }
           
        
        </style>
        <div class="otp_form">
          <div class="sub_otp_form">
            <form method="post">
              <label for="otp">Enter OTP:</label><br><br>
              <input type="number" id="otp" name="otp" placeholder="Enter code" required /><br><br>
              <input type="submit" name="verify_otp" value="Verify" />
            </form>
           </div>
        </div>
        </body>
        </html>';
        exit;
    }
});
