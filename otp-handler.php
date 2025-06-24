<?php 

add_action('template_redirect' function() {
    if (isset($_GET['user_id']) && isset($_POST['verify_otp'])) {
        $user_id = intval($_GET['user_id']);
        $otp_input = sanitize_taxt_field($_POST['otp']);
        $stored_otp = get_transient('otp_' . $user_id);
        if ($stored_otp && $stored_otp == $otp_input) {
            wp_set_auth_cookie($user_id);
            wp_redirect(home_url('/'));
            exit;
        }
        else
    }
})








?>