jQuery(document).ready(function ($) {

    $('input#billing_email').on('blur', function (e) {
        const email = $(e.target).val();
        $.post(blueodin_properties.ajax_url, {
                _ajax_nonce: blueodin_properties.nonce,
                action: "blueodin_capture_email",
                email: email
            }
        );
    });

});