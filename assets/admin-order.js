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

// Drag & Drop for CKPP Admin Uploads
(function() {
  function setupDropzone(dropzoneId, inputId) {
    var dropzone = document.getElementById(dropzoneId);
    var input = document.getElementById(inputId);
    var filenameSpan = dropzone ? dropzone.querySelector('.ckpp-upload-filename') : null;
    var labelSpan = dropzone ? dropzone.querySelector('.ckpp-upload-label') : null;
    if (!dropzone || !input) return;
    function showFileName() {
      if (input.files && input.files.length && filenameSpan) {
        console.log('File selected:', input.files[0].name);
        filenameSpan.textContent = input.files[0].name;
        filenameSpan.style.display = 'block';
        dropzone.classList.add('has-file');
        if (labelSpan) labelSpan.style.display = 'none';
      } else if (filenameSpan) {
        console.log('No file selected');
        filenameSpan.textContent = '';
        filenameSpan.style.display = 'none';
        dropzone.classList.remove('has-file');
        if (labelSpan) labelSpan.style.display = '';
      }
    }
    dropzone.addEventListener('click', function(e) {
      input.click();
    });
    dropzone.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        input.click();
      }
    });
    dropzone.addEventListener('dragover', function(e) {
      e.preventDefault();
      dropzone.classList.add('dragover');
    });
    dropzone.addEventListener('dragleave', function(e) {
      dropzone.classList.remove('dragover');
    });
    dropzone.addEventListener('drop', function(e) {
      e.preventDefault();
      dropzone.classList.remove('dragover');
      if (e.dataTransfer.files && e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        var event = new Event('change', { bubbles: true });
        input.dispatchEvent(event);
      }
    });
    input.addEventListener('change', showFileName);
    showFileName();
  }
  document.addEventListener('DOMContentLoaded', function() {
    setupDropzone('ckpp-font-dropzone', 'ckpp_font_file');
    setupDropzone('ckpp-clipart-dropzone', 'ckpp_clipart_file');
  });
})(); 