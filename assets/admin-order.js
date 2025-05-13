jQuery(document).ready(function($) {
    $(document).on('click', '.ckpp-generate-print', function() {
        var btn = $(this);
        var itemId = btn.data('item');
        var orderId = $('#post_ID').val();
        var container = btn.closest('div');
        // Add loading indicator
        var loading = $('<span class="ckpp-print-loading" role="status" aria-live="polite" style="margin-left:1em;">Generating...</span>');
        btn.prop('disabled', true).attr('aria-busy', 'true').after(loading);
        // Remove any previous error
        container.find('.ckpp-print-error').remove();
        $.post(ajaxurl, {
            action: 'ckpp_generate_print_file',
            nonce: CKPPCustomizer.nonce,
            itemId: itemId,
            orderId: orderId
        }, function(resp) {
            loading.remove();
            btn.prop('disabled', false).attr('aria-busy', 'false');
            if (resp.success && resp.data.url) {
                btn.replaceWith('<a href="' + resp.data.url + '" class="button" target="_blank" aria-label="Download print-ready file">Download Print-Ready File</a>');
            } else {
                btn.text('Generate Print-Ready File');
                var err = $('<span class="ckpp-print-error" style="color:#b32d2e;margin-left:1em;" role="alert">Failed to generate print file.</span>');
                btn.after(err);
                btn.focus();
            }
        }).fail(function() {
            loading.remove();
            btn.prop('disabled', false).attr('aria-busy', 'false');
            btn.text('Generate Print-Ready File');
            var err = $('<span class="ckpp-print-error" style="color:#b32d2e;margin-left:1em;" role="alert">Failed to generate print file.</span>');
            btn.after(err);
            btn.focus();
        });
    });
}); 