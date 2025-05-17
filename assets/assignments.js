jQuery(function($) {
    const root = $('#ckpp-assignments-root');
    if (!root.length) return;
    const nonce = root.data('nonce');
    let paged = 1;
    let allProducts = [];
    let designs = [];
    let has_more = false;
    let searchTerm = '';

    function renderAssignments() {
        let filtered = allProducts.filter(prod =>
            prod.title.toLowerCase().includes(searchTerm.toLowerCase())
        );
        let html = `
            <div class="ckpp-assignments-searchbar">
                <input type="text" id="ckpp-assignments-search" placeholder="Search products..." autocomplete="off">
            </div>
            <div class="ckpp-assignments-list">
                <div class="ckpp-assignments-list-header">
                    <span class="ckpp-list-thumb"></span>
                    <span class="ckpp-list-title">Product</span>
                    <span class="ckpp-list-design">Design</span>
                </div>
        `;
        if (filtered.length === 0) {
            html += '<div class="ckpp-assignments-list-empty">No products found.</div>';
        } else {
            filtered.forEach(prod => {
                html += `<div class="ckpp-assignments-list-row">
                    <span class="ckpp-list-thumb">
                        ${prod.thumbnail ? `<img src="${prod.thumbnail}" alt="" />` : '<div class="ckpp-thumb-placeholder"></div>'}
                    </span>
                    <span class="ckpp-list-title">${prod.title}</span>
                    <span class="ckpp-list-design">
                        <select data-product="${prod.id}" class="ckpp-assignment-select">
                            <option value="">— None —</option>
                            ${designs.map(d => `<option value="${d.id}"${d.id == prod.design_id ? ' selected' : ''}>${d.title}</option>`).join('')}
                        </select>
                    </span>
                </div>`;
            });
        }
        html += '</div>';
        html += '<div class="ckpp-assignments-pagination">';
        if (paged > 1) html += '<button id="ckpp-prev-page" class="button">Previous</button> ';
        if (has_more) html += '<button id="ckpp-next-page" class="button">Next</button>';
        html += '</div>';
        root.html(html);
        // Set the search box value and focus after rendering
        const searchInput = document.getElementById('ckpp-assignments-search');
        if (searchInput) {
            searchInput.value = searchTerm;
            searchInput.focus();
            // Move caret to end
            const val = searchInput.value;
            searchInput.setSelectionRange(val.length, val.length);
        }
    }

    function loadAssignments() {
        root.html('<p>Loading...</p>');
        $.get(CKPPAssignments.ajaxUrl, { action: 'ckpp_get_assignments', nonce, paged }, function(resp) {
            if (!resp.success) { root.html('<div class="error">'+resp.data.message+'</div>'); return; }
            allProducts = resp.data.products;
            designs = resp.data.designs;
            has_more = resp.data.has_more;
            renderAssignments();
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
    root.on('input', '#ckpp-assignments-search', function() {
        searchTerm = $(this).val();
        renderAssignments();
    });
    loadAssignments();
});

// Add modern CSS for list view and native select
if (!document.getElementById('ckpp-assignments-style')) {
    const style = document.createElement('style');
    style.id = 'ckpp-assignments-style';
    style.textContent = `
    .ckpp-assignments-searchbar {
        margin: 1.5em 0 0.5em 0;
        text-align: right;
    }
    #ckpp-assignments-search {
        padding: 0.5em 1em;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 1em;
        width: 260px;
        max-width: 100%;
        background: #fafbfc;
        transition: border 0.2s;
    }
    #ckpp-assignments-search:focus {
        border-color: #fec610;
        outline: none;
        box-shadow: 0 0 0 2px #fec61033;
    }
    .ckpp-assignments-list {
        margin: 1.5em 0 0 0;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        overflow: hidden;
    }
    .ckpp-assignments-list-header, .ckpp-assignments-list-row {
        display: grid;
        grid-template-columns: 56px 1fr 220px;
        align-items: center;
        gap: 1em;
        padding: 0.7em 1.2em;
    }
    .ckpp-assignments-list-header {
        background: #fafbfc;
        font-weight: 700;
        color: #444;
        font-size: 1.05em;
        border-bottom: 1px solid #eee;
    }
    .ckpp-assignments-list-row {
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.15s;
    }
    .ckpp-assignments-list-row:last-child {
        border-bottom: none;
    }
    .ckpp-assignments-list-row:hover {
        background: #fffbe6;
    }
    .ckpp-list-thumb {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .ckpp-list-thumb img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 6px;
        background: #f3f3f3;
    }
    .ckpp-thumb-placeholder {
        width: 40px;
        height: 40px;
        background: #e0e0e0;
        border-radius: 6px;
    }
    .ckpp-list-title {
        font-weight: 500;
        color: #222;
        font-size: 1em;
        word-break: break-word;
    }
    .ckpp-list-design {
        text-align: right;
    }
    .ckpp-assignment-select {
        width: 200px;
        max-width: 100%;
        padding: 0.4em 0.6em;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 1em;
        background: #fafbfc;
        transition: border 0.2s;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        margin: 0;
    }
    .ckpp-assignment-select:focus {
        border-color: #fec610;
        outline: none;
        box-shadow: 0 0 0 2px #fec61033;
    }
    .ckpp-assignments-list-empty {
        padding: 2em;
        text-align: center;
        color: #888;
        font-size: 1.1em;
    }
    .ckpp-assignments-pagination {
        margin: 2em 0 1em 0;
        text-align: center;
    }
    .ckpp-assignments-pagination .button {
        background: #fec610;
        color: #222;
        border: none;
        border-radius: 6px;
        padding: 0.5em 1.2em;
        font-weight: 600;
        margin: 0 0.5em;
        cursor: pointer;
        transition: background 0.2s;
    }
    .ckpp-assignments-pagination .button:hover {
        background: #ffd84d;
    }
    `;
    document.head.appendChild(style);
} 