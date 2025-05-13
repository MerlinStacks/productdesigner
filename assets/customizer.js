// assets/customizer.js
(function($) {
    let config = null;
    let modal = null;
    let lastFocused = null;
    $(document).ready(function() {
        const btn = document.getElementById('ckpp-personalize-btn');
        if (!btn) return;
        btn.addEventListener('click', function() {
            lastFocused = document.activeElement;
            openCustomizer();
        });
    });
    function openCustomizer() {
        modal = document.getElementById('ckpp-customizer-modal');
        modal.innerHTML = '<div id="ckpp-customizer-overlay" tabindex="-1" aria-modal="true" role="dialog" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;">' +
            '<div id="ckpp-customizer-window" style="background:#fff;padding:2em;max-width:700px;width:90vw;max-height:90vh;overflow:auto;position:relative;" role="document">' +
            '<button id="ckpp-customizer-close" aria-label="' + CKPPCustomizer.closeLabel + '" style="position:absolute;top:1em;right:1em;">&times;</button>' +
            '<h2 id="ckpp-customizer-title">' + CKPPCustomizer.title + '</h2>' +
            '<div id="ckpp-customizer-loading" style="display:none;text-align:center;margin:2em 0;">' + CKPPCustomizer.loading + '</div>' +
            '<form id="ckpp-customizer-form" aria-labelledby="ckpp-customizer-title"></form>' +
            '<div id="ckpp-customizer-preview" style="margin-top:2em;"></div>' +
            '<button id="ckpp-customizer-apply" class="button button-primary" style="margin-top:2em;">' + CKPPCustomizer.applyLabel + '</button>' +
            '<div id="ckpp-customizer-error" style="color:#b32d2e;margin-top:1em;display:none;"></div>' +
            '</div></div>';
        modal.style.display = 'block';
        trapFocus(document.getElementById('ckpp-customizer-overlay'));
        fetchConfig();
        document.getElementById('ckpp-customizer-close').onclick = closeCustomizer;
        document.getElementById('ckpp-customizer-overlay').onkeydown = function(e) {
            if (e.key === 'Escape') closeCustomizer();
        };
        setTimeout(function() { document.getElementById('ckpp-customizer-window').focus(); }, 100);
    }
    function closeCustomizer() {
        modal.style.display = 'none';
        if (lastFocused) lastFocused.focus();
    }
    function fetchConfig() {
        showLoading(true);
        $.get(CKPPCustomizer.ajaxUrl, {
            action: 'ckpp_get_product_config',
            nonce: CKPPCustomizer.nonce,
            productId: CKPPCustomizer.productId
        }, function(resp) {
            showLoading(false);
            if (resp.success && resp.data.config) {
                try {
                    config = JSON.parse(resp.data.config);
                    if (!config || !config.objects) throw new Error('Empty config');
                } catch(e) {
                    config = null;
                    showError('No personalization design/config found for this product.');
                    return;
                }
                renderControls();
                renderPreview();
            } else {
                showError('No personalization design/config found for this product.');
            }
        }).fail(function() {
            showLoading(false);
            showError('Failed to load personalization options.');
        });
    }
    function renderControls() {
        const form = document.getElementById('ckpp-customizer-form');
        if (!config || !config.objects) {
            form.innerHTML = '<em>' + CKPPCustomizer.noOptions + '</em>';
            return;
        }
        let html = '';
        config.objects.forEach(function(obj, idx) {
            if (obj.type === 'i-text') {
                html += '<label for="ckpp-input-text-' + idx + '">' + CKPPCustomizer.textLabel.replace('%d', idx+1) + '</label> ' +
                    '<input type="text" id="ckpp-input-text-' + idx + '" name="text_' + idx + '" value="' + (obj.text || '') + '" aria-required="true" /><br/>';
            }
            if (obj.placeholderType === 'dropdown' && obj.options) {
                html += '<label for="ckpp-input-dropdown-' + idx + '">' + (obj.label || 'Dropdown') + '</label> ';
                html += '<select id="ckpp-input-dropdown-' + idx + '" name="dropdown_' + idx + '">';
                obj.options.forEach(function(opt) {
                    html += '<option value="' + opt + '">' + opt + '</option>';
                });
                html += '</select><br/>';
            }
            if (obj.placeholderType === 'swatch' && obj.swatches) {
                html += '<fieldset><legend>' + (obj.label || 'Color Swatch') + '</legend>';
                obj.swatches.forEach(function(color, cidx) {
                    html += '<label style="margin-right:1em;">';
                    html += '<input type="radio" name="swatch_' + idx + '" value="' + color + '" aria-label="' + color + '" />';
                    html += '<span style="display:inline-block;width:24px;height:24px;background:' + color + ';border:1px solid #ccc;margin-left:0.5em;"></span>';
                    html += '</label>';
                });
                html += '</fieldset>';
            }
            if (obj.placeholderType === 'image') {
                html += '<label for="ckpp-input-image-' + idx + '">' + (obj.label || 'Image Upload') + '</label> ';
                html += '<input type="file" id="ckpp-input-image-' + idx + '" name="image_' + idx + '" accept="image/*" /><br/>';
                html += '<img id="ckpp-image-preview-' + idx + '" src="" alt="Image preview" style="display:none;max-width:120px;max-height:80px;margin-top:0.5em;" />';
            }
        });
        form.innerHTML = html;
        form.oninput = renderPreview;
        form.onchange = function(e) {
            // Image preview logic
            config.objects.forEach(function(obj, idx) {
                if (obj.placeholderType === 'image') {
                    const fileInput = form['image_' + idx];
                    const previewImg = document.getElementById('ckpp-image-preview-' + idx);
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            previewImg.src = ev.target.result;
                            previewImg.style.display = 'block';
                            previewImg.setAttribute('aria-label', 'Image preview');
                        };
                        reader.readAsDataURL(fileInput.files[0]);
                    } else {
                        previewImg.src = '';
                        previewImg.style.display = 'none';
                    }
                }
            });
            renderPreview();
        };
        document.getElementById('ckpp-customizer-apply').onclick = function(e) {
            e.preventDefault();
            savePersonalization();
        };
    }
    function renderPreview() {
        const previewDiv = document.getElementById('ckpp-customizer-preview');
        if (!config || !config.objects) {
            previewDiv.innerHTML = '';
            return;
        }
        // Use Fabric.js for live preview
        if (!window.fabric) {
            const fabricScript = document.createElement('script');
            fabricScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.2.4/fabric.min.js';
            fabricScript.onload = renderPreview;
            document.body.appendChild(fabricScript);
            return;
        }
        previewDiv.innerHTML = '<canvas id="ckpp-preview-canvas" width="500" height="400" style="border:1px solid #ccc;"></canvas>';
        const fabricCanvas = new fabric.Canvas('ckpp-preview-canvas', { selection: false });
        fabricCanvas.loadFromJSON(config, function() {
            // Update text fields and placeholders
            const form = document.getElementById('ckpp-customizer-form');
            if (form) {
                config.objects.forEach(function(obj, idx) {
                    if (obj.type === 'i-text') {
                        const val = form['text_' + idx] ? form['text_' + idx].value : obj.text;
                        fabricCanvas.item(idx).set('text', val);
                    }
                    if (obj.placeholderType === 'dropdown') {
                        const val = form['dropdown_' + idx] ? form['dropdown_' + idx].value : (obj.options ? obj.options[0] : '');
                        fabricCanvas.item(idx).set('fill', '#e0e0e0');
                        fabricCanvas.item(idx).set('text', val);
                    }
                    if (obj.placeholderType === 'swatch') {
                        const radios = form.querySelectorAll('input[name="swatch_' + idx + '"]:checked');
                        const val = radios.length ? radios[0].value : (obj.swatches ? obj.swatches[0] : '');
                        fabricCanvas.item(idx).set('fill', val);
                    }
                    if (obj.placeholderType === 'image') {
                        const previewImg = document.getElementById('ckpp-image-preview-' + idx);
                        if (previewImg && previewImg.src && previewImg.style.display !== 'none') {
                            fabric.Image.fromURL(previewImg.src, function(img) {
                                img.set({ left: fabricCanvas.item(idx).left, top: fabricCanvas.item(idx).top, scaleX: fabricCanvas.item(idx).width / img.width, scaleY: fabricCanvas.item(idx).height / img.height });
                                fabricCanvas.remove(fabricCanvas.item(idx));
                                fabricCanvas.insertAt(img, idx, false);
                                fabricCanvas.renderAll();
                            });
                        }
                    }
                });
            }
            fabricCanvas.renderAll();
        });
        fabricCanvas.discardActiveObject();
        fabricCanvas.selection = false;
        fabricCanvas.forEachObject(function(obj) { obj.selectable = false; });
    }
    function savePersonalization() {
        const form = document.getElementById('ckpp-customizer-form');
        const data = {};
        if (!config || !config.objects) return;
        config.objects.forEach(function(obj, idx) {
            if (obj.type === 'i-text') {
                data['text_' + idx] = form['text_' + idx] ? form['text_' + idx].value : '';
            }
            if (obj.placeholderType === 'dropdown') {
                data['dropdown_' + idx] = form['dropdown_' + idx] ? form['dropdown_' + idx].value : '';
            }
            if (obj.placeholderType === 'swatch') {
                const radios = form.querySelectorAll('input[name="swatch_' + idx + '"]:checked');
                data['swatch_' + idx] = radios.length ? radios[0].value : '';
            }
            if (obj.placeholderType === 'image') {
                const fileInput = form['image_' + idx];
                if (fileInput && fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        data['image_' + idx] = ev.target.result;
                        finalizeSave();
                    };
                    reader.readAsDataURL(fileInput.files[0]);
                } else {
                    data['image_' + idx] = '';
                    finalizeSave();
                }
            }
        });
        // If no image placeholders, finalize immediately
        if (!config.objects.some(obj => obj.placeholderType === 'image')) finalizeSave();
        function finalizeSave() {
            // Add data to add-to-cart form
            const addToCartForm = document.querySelector('form.cart');
            if (addToCartForm) {
                let input = addToCartForm.querySelector('input[name="ckpp_personalization_data"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ckpp_personalization_data';
                    addToCartForm.appendChild(input);
                }
                input.value = JSON.stringify(data);
            }
            closeCustomizer();
        }
    }
    function showLoading(show) {
        document.getElementById('ckpp-customizer-loading').style.display = show ? 'block' : 'none';
    }
    function showError(msg) {
        const err = document.getElementById('ckpp-customizer-error');
        err.textContent = msg;
        err.style.display = 'block';
    }
    // Focus trap for modal accessibility
    function trapFocus(modal) {
        const focusable = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        modal.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === first) { e.preventDefault(); last.focus(); }
                } else {
                    if (document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            }
        });
        setTimeout(function() { first && first.focus(); }, 100);
    }
    // Live preview on product page
    function initLivePreviewWhenReady() {
        var tries = 0;
        function tryInit() {
            var previewDiv = document.getElementById('ckpp-live-preview');
            if (window.CKPP_LIVE_PREVIEW && previewDiv) {
                var livePreviewNonce = window.CKPP_LIVE_PREVIEW.nonce || (window.CKPPCustomizer && CKPPCustomizer.nonce);
                $.get(CKPPCustomizer.ajaxUrl, {
                    action: 'ckpp_get_product_config',
                    nonce: livePreviewNonce,
                    productId: CKPP_LIVE_PREVIEW.productId
                }, function(resp) {
                    if (resp.success && resp.data.config) {
                        var config = null;
                        try { config = JSON.parse(resp.data.config); } catch(e) {}
                        if (config && config.objects) {
                            if (!window.fabric) {
                                var fabricScript = document.createElement('script');
                                fabricScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.2.4/fabric.min.js';
                                fabricScript.onload = function() { renderLivePreview(config); };
                                document.body.appendChild(fabricScript);
                            } else {
                                renderLivePreview(config);
                            }
                        } else {
                            previewDiv.innerHTML = '<em>No personalization preview available.</em>';
                        }
                    } else {
                        previewDiv.innerHTML = '<em>No personalization preview available.</em>';
                    }
                });
            } else if (tries < 20) {
                tries++;
                setTimeout(tryInit, 100);
            }
        }
        tryInit();
    }
    $(document).ready(initLivePreviewWhenReady);
    function renderLivePreview(config) {
        var previewDiv = document.getElementById('ckpp-live-preview');
        previewDiv.innerHTML = '<canvas id="ckpp-live-preview-canvas" width="500" height="400" style="border:1px solid #ccc;"></canvas>';
        var fabricCanvas = new fabric.Canvas('ckpp-live-preview-canvas', { selection: false });
        fabricCanvas.loadFromJSON(config, function() { fabricCanvas.renderAll(); });
        fabricCanvas.discardActiveObject();
        fabricCanvas.selection = false;
        fabricCanvas.forEachObject(function(obj) { obj.selectable = false; });
        // Debug panel
        if (window.CKPP_DEBUG_MODE) {
            var debugPanel = document.createElement('pre');
            debugPanel.style.background = '#f8f8f8';
            debugPanel.style.border = '1px solid #ccc';
            debugPanel.style.padding = '1em';
            debugPanel.style.marginTop = '1em';
            debugPanel.style.fontSize = '12px';
            debugPanel.style.overflowX = 'auto';
            debugPanel.textContent = JSON.stringify(config, null, 2);
            previewDiv.appendChild(debugPanel);
        }
    }
    // Remove or comment out the replaceBlockGalleryWithPreview function and its invocation
    // function replaceBlockGalleryWithPreview() {
    //     if (!window.CKPP_LIVE_PREVIEW) return;
    //     var tries = 0;
    //     function tryReplace() {
    //         var blockGallery = document.querySelector('.wp-block-woocommerce-product-image-gallery .woocommerce-product-gallery');
    //         if (blockGallery) {
    //             blockGallery.innerHTML = '';
    //             var previewDiv = document.createElement('div');
    //             previewDiv.id = 'ckpp-live-preview';
    //             blockGallery.appendChild(previewDiv);
    //             var livePreviewNonce = window.CKPP_LIVE_PREVIEW.nonce || (window.CKPPCustomizer && CKPPCustomizer.nonce);
    //             $.get(CKPPCustomizer.ajaxUrl, {
    //                 action: 'ckpp_get_product_config',
    //                 nonce: livePreviewNonce,
    //                 productId: CKPP_LIVE_PREVIEW.productId
    //             }, function(resp) {
    //                 if (resp.success && resp.data.config) {
    //                     var config = null;
    //                     try { config = JSON.parse(resp.data.config); } catch(e) {}
    //                     if (config && config.objects) {
    //                         if (!window.fabric) {
    //                             var fabricScript = document.createElement('script');
    //                             fabricScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.2.4/fabric.min.js';
    //                             fabricScript.onload = function() { renderLivePreview(config); };
    //                             document.body.appendChild(fabricScript);
    //                         } else {
    //                             renderLivePreview(config);
    //                         }
    //                     } else {
    //                         previewDiv.innerHTML = '<em>No personalization preview available.</em>';
    //                     }
    //                 } else {
    //                     previewDiv.innerHTML = '<em>No personalization preview available.</em>';
    //                 }
    //             });
    //         } else if (tries < 20) {
    //             tries++;
    //             setTimeout(tryReplace, 100);
    //         }
    //     }
    //     tryReplace();
    // }
    // $(document).ready(replaceBlockGalleryWithPreview);
})(jQuery); 