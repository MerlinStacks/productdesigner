jQuery(function($) {
    const root = $('#ckpp-assignments-root');
    if (!root.length) return;
    const nonce = root.data('nonce');
    let paged = 1;
    function loadAssignments() {
        root.html('<p>Loading...</p>');
        $.get(CKPPAssignments.ajaxUrl, { action: 'ckpp_get_assignments', nonce, paged }, function(resp) {
            if (!resp.success) { root.html('<div class="error">'+resp.data.message+'</div>'); return; }
            const { products, designs, has_more } = resp.data;
            let html = '<table class="widefat"><thead><tr><th>Product</th><th>Design</th></tr></thead><tbody>';
            products.forEach(prod => {
                html += `<tr>
                    <td>
                        ${prod.thumbnail ? `<img src="${prod.thumbnail}" style="width:40px;height:40px;object-fit:cover;margin-right:8px;vertical-align:middle;" alt="">` : ''}
                        ${prod.title}
                    </td>
                    <td>
                        <select data-product="${prod.id}">
                            <option value="">— None —</option>
                            ${designs.map(d => `<option value="${d.id}"${d.id == prod.design_id ? ' selected' : ''}>${d.title}</option>`).join('')}
                        </select>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            html += '<div style="margin-top:1em;">';
            if (paged > 1) html += '<button id="ckpp-prev-page">Previous</button> ';
            if (has_more) html += '<button id="ckpp-next-page">Next</button>';
            html += '</div>';
            root.html(html);
        });
    }
    root.on('change', 'select[data-product]', function() {
        const product_id = $(this).data('product');
        const design_id = $(this).val();
        $.post(CKPPAssignments.ajaxUrl, {
            action: 'ckpp_save_assignment',
            nonce,
            product_id,
            design_id
        }, function(resp) {
            if (resp.success) {
                // Optionally show a success message
            } else {
                alert(resp.data.message || 'Error saving assignment');
            }
        });
    });
    root.on('click', '#ckpp-prev-page', function() { paged--; loadAssignments(); });
    root.on('click', '#ckpp-next-page', function() { paged++; loadAssignments(); });
    loadAssignments();
}); 