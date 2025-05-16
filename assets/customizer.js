// assets/customizer.js
(function($) {
    let config = null;
    let modal = null;
    let lastFocused = null;
    let ckppImageUploadsInProgress = 0;
    // Ensure CKPP_LIVE_CONFIG is set from CKPP_LIVE_PREVIEW_CONFIG if present
    if (window.CKPP_LIVE_PREVIEW_CONFIG && !window.CKPP_LIVE_CONFIG) {
        try {
            window.CKPP_LIVE_CONFIG = JSON.parse(window.CKPP_LIVE_PREVIEW_CONFIG);
        } catch (e) {
            window.CKPP_LIVE_CONFIG = null;
            if (window.CKPP_DEBUG_MODE) console.error('[CKPP] Failed to parse CKPP_LIVE_PREVIEW_CONFIG', e);
        }
    }
    $(document).ready(function() {
        const btn = document.getElementById('ckpp-personalize-btn');
        if (!btn) return;
        btn.addEventListener('click', function() {
            lastFocused = document.activeElement;
            openCustomizer();
        });
        // Attach Add to Cart submit handler for inline personalization (classic and block themes)
        const addToCartForm = document.querySelector('form.cart');
        const addToCartBtn = document.querySelector('form.cart button[type="submit"], form.cart input[type="submit"]');
        if (addToCartForm) {
            // Submit handler (form)
            addToCartForm.addEventListener('submit', function(e) {
                if (ckppImageUploadsInProgress > 0) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    alert('Please wait for all image uploads to finish.');
                    return false;
                }
                var validation = ckppValidateRequiredFieldsInline();
                if (!validation.allFilled) {
                    var container = document.getElementById('ckpp-text-inputs-container');
                    var errorDiv = document.getElementById('ckpp-inline-error');
                    if (!errorDiv && container) {
                        errorDiv = document.createElement('div');
                        errorDiv.id = 'ckpp-inline-error';
                        errorDiv.style.color = '#b32d2e';
                        errorDiv.style.marginTop = '1em';
                        errorDiv.setAttribute('role', 'alert');
                        errorDiv.setAttribute('aria-live', 'assertive');
                        container.appendChild(errorDiv);
                    }
                    if (errorDiv) errorDiv.textContent = 'Please fill out all required fields' + (validation.firstError ? (': ' + validation.firstError) : '.');
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    if (window.CKPP_DEBUG_MODE) console.warn('[CKPP] Blocked Add to Cart (submit): missing required field', validation.firstError);
                    return false;
                } else if (errorDiv) {
                    errorDiv.textContent = '';
                }
            }, true);
        }
        // Click handler (button) for block themes and AJAX
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function(e) {
                var validation = ckppValidateRequiredFieldsInline();
                if (!validation.allFilled) {
                    var container = document.getElementById('ckpp-text-inputs-container');
                    var errorDiv = document.getElementById('ckpp-inline-error');
                    if (!errorDiv && container) {
                        errorDiv = document.createElement('div');
                        errorDiv.id = 'ckpp-inline-error';
                        errorDiv.style.color = '#b32d2e';
                        errorDiv.style.marginTop = '1em';
                        errorDiv.setAttribute('role', 'alert');
                        errorDiv.setAttribute('aria-live', 'assertive');
                        container.appendChild(errorDiv);
                    }
                    if (errorDiv) errorDiv.textContent = 'Please fill out all required fields' + (validation.firstError ? (': ' + validation.firstError) : '.');
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    if (window.CKPP_DEBUG_MODE) console.warn('[CKPP] Blocked Add to Cart (click): missing required field', validation.firstError);
                    return false;
                } else if (errorDiv) {
                    errorDiv.textContent = '';
                }
            }, true);
        }
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
        // Attach Add to Cart form submit handler while modal is open
        const addToCartForm = document.querySelector('form.cart');
        if (addToCartForm) {
            // Remove any previous handler to avoid duplicates
            addToCartForm.removeEventListener('submit', ckppHandleModalAddToCartSubmit, true);
            addToCartForm.addEventListener('submit', ckppHandleModalAddToCartSubmit, true);
        }
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
            // Required indicator and ARIA
            const isRequired = !!obj.required;
            const requiredMark = isRequired ? ' <span class="ckpp-required" aria-hidden="true">*</span><span class="screen-reader-text">' + CKPPCustomizer.requiredLabel + '</span>' : '';
            const ariaRequired = isRequired ? ' aria-required="true" required' : '';
            // Text input
            if (obj.type === 'i-text') {
                html += '<label for="ckpp-input-text-' + idx + '">' +
                    (obj.label || CKPPCustomizer.textLabel.replace('%d', idx+1)) + requiredMark + '</label>' +
                    '<input type="text" id="ckpp-input-text-' + idx + '" name="text_' + idx + '" value="' + (obj.text || '') + '"' + ariaRequired + ' autocomplete="off" />';
            }
            // Textarea
            if (obj.type === 'textbox') {
                html += '<label for="ckpp-input-textbox-' + idx + '">' +
                    (obj.label || (CKPPCustomizer.textboxLabel ? CKPPCustomizer.textboxLabel.replace('%d', idx+1) : ('Text Box ' + (idx+1)))) + requiredMark + '</label>' +
                    '<textarea id="ckpp-input-textbox-' + idx + '" name="textbox_' + idx + '" rows="2" style="width:100%;resize:vertical;"' + ariaRequired + '>' + (obj.text || '') + '</textarea>';
            }
            // Image upload
            if (obj.placeholderType === 'image') {
                html += '<label for="ckpp-input-image-' + idx + '">' +
                    (obj.label || CKPPCustomizer.imageLabel || 'Image Upload') + requiredMark + '</label>' +
                    '<input type="file" id="ckpp-input-image-' + idx + '" name="image_' + idx + '" accept="image/*"' + ariaRequired + ' aria-describedby="ckpp-image-desc-' + idx + '" />' +
                    '<span id="ckpp-image-desc-' + idx + '" class="screen-reader-text">' + CKPPCustomizer.imageInstructions + '</span>' +
                    '<img id="ckpp-image-preview-' + idx + '" src="" alt="' + CKPPCustomizer.imagePreviewAlt + '" style="display:none;max-width:120px;max-height:80px;margin-top:0.5em;" />';
            }
            // Add spacing between fields
            html += '<div class="ckpp-field-spacer"></div>';
        });
        form.innerHTML = html;
        // Set ARIA live region for error
        const errorDiv = document.getElementById('ckpp-customizer-error');
        if (errorDiv) {
            errorDiv.setAttribute('aria-live', 'assertive');
            errorDiv.setAttribute('role', 'alert');
        }
        form.oninput = renderPreview;
        form.onchange = function(e) {
            // Image preview logic and upload
            config.objects.forEach(function(obj, idx) {
                if (obj.placeholderType === 'image') {
                    const fileInput = form['image_' + idx];
                    const previewImg = document.getElementById('ckpp-image-preview-' + idx);
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        const file = fileInput.files[0];
                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            previewImg.src = ev.target.result;
                            previewImg.style.display = 'block';
                            previewImg.setAttribute('aria-label', 'Image preview');
                        };
                        reader.readAsDataURL(file);
                        // Upload to backend
                        const formData = new FormData();
                        formData.append('action', 'ckpp_upload_customer_image');
                        formData.append('nonce', CKPPCustomizer.nonce);
                        formData.append('file', file);
                        uploadImageAndTrack(file, function(data) {
                            if (data.success && data.data && data.data.url) {
                                fileInput.setAttribute('data-uploaded-url', data.data.url);
                            } else {
                                fileInput.removeAttribute('data-uploaded-url');
                            }
                        }, function(err) {
                            fileInput.removeAttribute('data-uploaded-url');
                        });
                    } else {
                        previewImg.src = '';
                        previewImg.style.display = 'none';
                        fileInput && fileInput.removeAttribute('data-uploaded-url');
                    }
                }
            });
            renderPreview();
        };
        // Debug panel: show config as JSON
        if (window.CKPP_DEBUG_MODE) {
            const debugPanel = document.createElement('pre');
            debugPanel.style.background = '#f8f8f8';
            debugPanel.style.border = '1px solid #ccc';
            debugPanel.style.padding = '1em';
            debugPanel.style.marginTop = '1em';
            debugPanel.style.fontSize = '12px';
            debugPanel.style.overflowX = 'auto';
            debugPanel.textContent = JSON.stringify(config, null, 2);
            document.getElementById('ckpp-customizer-window').appendChild(debugPanel);
        }
    }
    function uploadImageAndTrack(file, onSuccess, onError) {
        ckppImageUploadsInProgress++;
        // ... existing upload logic ...
        fetch(/* ... */)
            .then(function(response) { /* ... */ })
            .catch(function(err) { /* ... */ })
            .finally(function() { ckppImageUploadsInProgress--; });
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
                    if (obj.type === 'textbox') {
                        const val = form['textbox_' + idx] ? form['textbox_' + idx].value : obj.text;
                        var textboxObj = fabricCanvas.item(idx);
                        textboxObj.set('text', val);
                        // Auto-fit font size to fit in bounding box
                        var maxFont = 48, minFont = 10;
                        var boxWidth = textboxObj.width * (textboxObj.scaleX || 1);
                        var boxHeight = textboxObj.height * (textboxObj.scaleY || 1);
                        var fontSize = textboxObj.fontSize || 24;
                        textboxObj.set('fontSize', fontSize);
                        fabricCanvas.renderAll();
                        // Shrink font if text overflows
                        while ((textboxObj.height > boxHeight || textboxObj.width > boxWidth) && fontSize > minFont) {
                            fontSize -= 1;
                            textboxObj.set('fontSize', fontSize);
                            fabricCanvas.renderAll();
                        }
                        // Grow font if text fits and there is room
                        while ((textboxObj.height < boxHeight && textboxObj.width < boxWidth) && fontSize < maxFont) {
                            fontSize += 1;
                            textboxObj.set('fontSize', fontSize);
                            fabricCanvas.renderAll();
                            if (textboxObj.height > boxHeight || textboxObj.width > boxWidth) {
                                fontSize -= 1;
                                textboxObj.set('fontSize', fontSize);
                                fabricCanvas.renderAll();
                                break;
                            }
                        }
                    }
                    if (obj.placeholderType === 'image') {
                        const previewImg = document.getElementById('ckpp-image-preview-' + idx);
                        if (previewImg && previewImg.src && previewImg.style.display !== 'none') {
                            fabric.Image.fromURL(previewImg.src, function(img) {
                                img.set({
                                    left: fabricCanvas.item(idx).left,
                                    top: fabricCanvas.item(idx).top,
                                    scaleX: fabricCanvas.item(idx).width / img.width,
                                    scaleY: fabricCanvas.item(idx).height / img.height,
                                    selectable: false,
                                    evented: false,
                                    hasControls: false,
                                    hasBorders: false
                                });
                                fabricCanvas.remove(fabricCanvas.item(idx));
                                fabricCanvas.insertAt(img, idx, false);
                                fabricCanvas.renderAll();
                            });
                        }
                    }
                });
            }
            fabricCanvas.renderAll();
            // After loading the canvas, ensure all images are not selectable/movable
            fabricCanvas.forEachObject(function(obj) {
                if (obj.type === 'image') {
                    obj.selectable = false;
                    obj.evented = false;
                    obj.hasControls = false;
                    obj.hasBorders = false;
                }
            });
        });
        fabricCanvas.discardActiveObject();
        fabricCanvas.selection = false;
        fabricCanvas.forEachObject(function(obj) { obj.selectable = false; });
        fabricCanvas.forEachObject(function(obj) { if (obj.type === 'textbox') { obj.editable = false; obj.evented = false; }});
    }
    function savePersonalization() {
        const form = document.getElementById('ckpp-customizer-form');
        const data = {};
        if (!config || !config.objects) return;
        config.objects.forEach(function(obj, idx) {
            if (obj.type === 'i-text') {
                data['text_' + idx] = form['text_' + idx] ? form['text_' + idx].value : '';
            }
            if (obj.type === 'textbox') {
                data['textbox_' + idx] = form['textbox_' + idx] ? form['textbox_' + idx].value : '';
            }
            if (obj.placeholderType === 'image') {
                const fileInput = form['image_' + idx];
                if (fileInput && fileInput.files && fileInput.files[0]) {
                    const uploadedUrl = fileInput.getAttribute('data-uploaded-url');
                    if (uploadedUrl) {
                        data['image_' + idx] = uploadedUrl;
                    } else {
                        // fallback: use data URL (legacy, if upload failed)
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            data['image_' + idx] = ev.target.result;
                        };
                        reader.readAsDataURL(fileInput.files[0]);
                    }
                } else {
                    data['image_' + idx] = '';
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
            // Add a random unique value to the personalization data
            data['ckpp_unique'] = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
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
                            // Insert text input boxes above Add to Cart
                            insertTextInputsAboveAddToCart(config);
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
        // Responsive sizing logic
        function getAspectRatio(config) {
            // Try to get from config.printSettings or fallback to 1:1
            if (config && config.printSettings && config.printSettings.width && config.printSettings.height) {
                var w = parseFloat(config.printSettings.width);
                var h = parseFloat(config.printSettings.height);
                if (!isNaN(w) && !isNaN(h) && w > 0 && h > 0) return w / h;
            }
            // fallback to 1:1
            return 1;
        }
        // Helper: get all font families used in config
        function getUsedFonts(config) {
            var fonts = new Set();
            if (config && config.objects && Array.isArray(config.objects)) {
                config.objects.forEach(function(obj) {
                    if (obj.fontFamily) fonts.add(obj.fontFamily);
                });
            }
            return Array.from(fonts);
        }
        // Helper: load a font using FontFace API
        function loadFont(fontFamily) {
            // Robustly find the @font-face rule for the fontFamily (case-insensitive, ignore spaces/quotes)
            var found = false;
            var fontUrl = null;
            for (var i = 0; i < document.styleSheets.length; i++) {
                var sheet = document.styleSheets[i];
                try {
                    var rules = sheet.cssRules || sheet.rules;
                    if (!rules) continue;
                    for (var j = 0; j < rules.length; j++) {
                        var rule = rules[j];
                        if (rule.type === CSSRule.FONT_FACE_RULE) {
                            var famMatch = rule.cssText.match(/font-family:\s*['\"]?([^;'"]+)['\"]?/i);
                            if (famMatch && famMatch[1].trim().toLowerCase() === fontFamily.trim().toLowerCase()) {
                                var match = rule.cssText.match(/src: url\(['"]?([^'")]+)['"]?\)/);
                                if (match) {
                                    fontUrl = match[1];
                                    found = true;
                                    break;
                                }
                            }
                        }
                    }
                } catch (e) { continue; }
                if (found) break;
            }
            if (fontUrl) {
                var font = new FontFace(fontFamily, 'url(' + fontUrl + ')');
                document.fonts.add(font);
                return font.load().then(function() {
                    console.log('[CKPP] Font loaded:', fontFamily, fontUrl);
                }).catch(function(err) {
                    console.warn('[CKPP] Failed to load font:', fontFamily, fontUrl, err);
                });
            } else {
                // If not found, log a warning
                console.warn('[CKPP] FontFace CSS not found for:', fontFamily);
                return Promise.resolve();
            }
        }
        function resizeAndRender() {
            var parent = previewDiv.parentElement;
            var maxWidth = parent ? parent.offsetWidth : 500;
            var aspect = getAspectRatio(config);
            var maxHeight = Math.round(maxWidth / aspect);
            previewDiv.innerHTML = '';
            var canvasEl = document.createElement('canvas');
            canvasEl.id = 'ckpp-live-preview-canvas';
            canvasEl.width = maxWidth;
            canvasEl.height = maxHeight;
            canvasEl.style.width = '100%';
            canvasEl.style.height = 'auto';
            canvasEl.style.maxWidth = maxWidth + 'px';
            canvasEl.style.maxHeight = maxHeight + 'px';
            canvasEl.style.border = '1px solid #ccc';
            previewDiv.appendChild(canvasEl);
            // Load all fonts, then render
            var usedFonts = getUsedFonts(config);
            Promise.all(usedFonts.map(loadFont)).then(function() {
                var fabricCanvas = new fabric.Canvas('ckpp-live-preview-canvas', { selection: false });
                window.ckppLivePreviewCanvas = fabricCanvas; // Store globally for input sync
                fabricCanvas.loadFromJSON(config, function() {
                    // Disable direct editing for text objects
                    fabricCanvas.getObjects().forEach(function(obj) {
                        if (obj.type === 'i-text') {
                            obj.editable = false;
                            obj.evented = false;
                            obj.selectable = false;
                        }
                    });
                    var origW = 1000, origH = 1000;
                    if (config && config.printSettings && config.printSettings.width && config.printSettings.height) {
                        origW = parseFloat(config.printSettings.width);
                        origH = parseFloat(config.printSettings.height);
                    } else if (config && config.width && config.height) {
                        origW = parseFloat(config.width);
                        origH = parseFloat(config.height);
                    }
                    var scaleX = maxWidth / origW;
                    var scaleY = maxHeight / origH;
                    var scale = Math.min(scaleX, scaleY);
                    fabricCanvas.setDimensions({ width: maxWidth, height: maxHeight });
                    fabricCanvas.setZoom(scale);
                    fabricCanvas.renderAll();
                });
                fabricCanvas.discardActiveObject();
                fabricCanvas.selection = false;
                fabricCanvas.forEachObject(function(obj) { obj.selectable = false; });
            });
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
        // Initial render
        resizeAndRender();
        // Responsive: re-render on window resize
        window.addEventListener('resize', resizeAndRender);
    }
    // Insert text input boxes above Add to Cart for each text object and text box and image placeholder
    function insertTextInputsAboveAddToCart(config) {
        var addToCartBtn = document.querySelector('form.cart button[type="submit"], form.cart input[type="submit"]');
        if (!addToCartBtn) return;
        var form = addToCartBtn.closest('form.cart');
        if (!form) return;
        // Remove any previous container
        var prev = document.getElementById('ckpp-text-inputs-container');
        if (prev) prev.remove();
        // Find all text, textbox, and image placeholder objects
        var inputObjs = (config.objects || []).map(function(obj, idx) { return {obj, idx}; }).filter(function(pair) {
            return pair.obj.type === 'i-text' || pair.obj.type === 'textbox' || pair.obj.placeholderType === 'image';
        });
        if (inputObjs.length === 0) return;
        var container = document.createElement('div');
        container.id = 'ckpp-text-inputs-container';
        container.style.marginBottom = '1em';
        inputObjs.forEach(function(pair) {
            var obj = pair.obj;
            var idx = pair.idx;
            // Use label from backend config, fallback to sensible default
            var rawLabel = obj.label || (obj.type === 'i-text' ? 'Text ' + (idx+1) : obj.type === 'textbox' ? 'Text Box ' + (idx+1) : obj.placeholderType === 'image' ? 'Image Upload' : 'Field ' + (idx+1));
            // Add required indicator if needed
            var labelText = rawLabel + (obj.required ? ' *' : '');
            // Sanitize label for use in name/id: lowercase, dashes, alphanumeric only
            var sanitizedLabel = rawLabel.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            var inputId, inputName;
            if (obj.type === 'i-text') {
                inputId = 'ckpp-text-input-' + sanitizedLabel + '-' + idx;
                inputName = 'ckpp_text_input_' + sanitizedLabel + '_' + idx;
            } else if (obj.type === 'textbox') {
                inputId = 'ckpp-textarea-input-' + sanitizedLabel + '-' + idx;
                inputName = 'ckpp_textarea_input_' + sanitizedLabel + '_' + idx;
            } else if (obj.placeholderType === 'image') {
                inputId = 'ckpp-image-input-' + sanitizedLabel + '-' + idx;
                inputName = 'ckpp_image_input_' + sanitizedLabel + '_' + idx;
            }
            var label = document.createElement('label');
            label.setAttribute('for', inputId);
            label.textContent = labelText + ':';
            label.style.display = 'block';
            label.style.fontWeight = 'bold';
            label.style.marginBottom = '0.2em';
            container.appendChild(label);
            if (obj.type === 'i-text') {
                var input = document.createElement('input');
                input.type = 'text';
                input.id = inputId;
                input.name = inputName;
                input.value = obj.text || '';
                if (obj.required) input.setAttribute('aria-required', 'true');
                input.style.width = '100%';
                input.style.marginBottom = '0.7em';
                input.setAttribute('autocomplete', 'off');
                input.setAttribute('maxlength', obj.maxLength || 100);
                input.addEventListener('input', function() {
                    var canvas = window.ckppLivePreviewCanvas;
                    if (canvas && canvas.getObjects) {
                        var objs = canvas.getObjects();
                        var textObj = objs.filter(function(o) { return o.type === 'i-text'; })[getTextIndex(config, idx, 'i-text')];
                        if (textObj) {
                            textObj.set('text', input.value);
                            canvas.renderAll();
                        }
                    }
                    validateTextInputs();
                });
                container.appendChild(input);
            } else if (obj.type === 'textbox') {
                var textarea = document.createElement('textarea');
                textarea.id = inputId;
                textarea.name = inputName;
                textarea.value = obj.text || '';
                if (obj.required) textarea.setAttribute('aria-required', 'true');
                textarea.setAttribute('rows', '2');
                textarea.style.width = '100%';
                textarea.style.resize = 'vertical';
                textarea.style.marginBottom = '0.7em';
                textarea.setAttribute('maxlength', obj.maxLength || 500);
                textarea.addEventListener('input', function() {
                    var canvas = window.ckppLivePreviewCanvas;
                    if (canvas && canvas.getObjects) {
                        var objs = canvas.getObjects();
                        var textboxObj = objs.filter(function(o) { return o.type === 'textbox'; })[getTextIndex(config, idx, 'textbox')];
                        if (textboxObj) {
                            textboxObj.set('text', textarea.value);
                            canvas.renderAll();
                        }
                    }
                    validateTextInputs();
                });
                container.appendChild(textarea);
            } else if (obj.placeholderType === 'image') {
                var fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.id = inputId;
                fileInput.name = inputName;
                fileInput.accept = 'image/*';
                if (obj.required) fileInput.setAttribute('aria-required', 'true');
                fileInput.style.marginBottom = '0.7em';
                var previewImg = document.createElement('img');
                previewImg.id = 'ckpp-image-preview-' + sanitizedLabel + '-' + idx;
                previewImg.alt = 'Image preview';
                previewImg.style.display = 'none';
                previewImg.style.maxWidth = '120px';
                previewImg.style.maxHeight = '80px';
                previewImg.style.marginTop = '0.5em';
                fileInput.onchange = function(e) {
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        const file = fileInput.files[0];
                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            previewImg.src = ev.target.result;
                            previewImg.style.display = 'block';
                            previewImg.setAttribute('aria-label', 'Image preview');
                        };
                        reader.readAsDataURL(file);
                        // Upload to backend
                        const formData = new FormData();
                        formData.append('action', 'ckpp_upload_customer_image');
                        formData.append('nonce', CKPPCustomizer.nonce);
                        formData.append('file', file);
                        uploadImageAndTrack(file, function(data) {
                            if (data.success && data.data && data.data.url) {
                                fileInput.setAttribute('data-uploaded-url', data.data.url);
                            } else {
                                fileInput.removeAttribute('data-uploaded-url');
                            }
                        }, function(err) {
                            fileInput.removeAttribute('data-uploaded-url');
                        });
                    } else {
                        previewImg.src = '';
                        previewImg.style.display = 'none';
                        fileInput && fileInput.removeAttribute('data-uploaded-url');
                    }
                };
                container.appendChild(fileInput);
                container.appendChild(previewImg);
            }
        });
        // Insert above Add to Cart
        form.insertBefore(container, addToCartBtn);
        // Initial validation
        validateTextInputs();
        // Add this after insertTextInputsAboveAddToCart is called (or at the end of that function):
        var addToCartForm = document.querySelector('form.cart');
        if (addToCartForm) {
            addToCartForm.addEventListener('submit', function(e) {
                if (ckppImageUploadsInProgress > 0) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    alert('Please wait for all image uploads to finish.');
                    return false;
                }
                // Collect personalization data from inline fields
                var data = {};
                (config.objects || []).forEach(function(obj, idx) {
                    if (obj.type === 'i-text') {
                        var input = document.getElementById('ckpp-text-input-' + (obj.label || ('Text ' + (idx+1))).toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + idx);
                        data['text_' + idx] = input ? input.value : '';
                    }
                    if (obj.type === 'textbox') {
                        var textarea = document.getElementById('ckpp-textarea-input-' + (obj.label || ('Text Box ' + (idx+1))).toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + idx);
                        data['textbox_' + idx] = textarea ? textarea.value : '';
                    }
                    if (obj.placeholderType === 'image') {
                        var fileInput = document.getElementById('ckpp-image-input-' + (obj.label || 'Image Upload').toLowerCase().replace(/[^a-z0-9]+/g, '-') + '-' + idx);
                        var uploadedUrl = fileInput ? fileInput.getAttribute('data-uploaded-url') : '';
                        data['image_' + idx] = uploadedUrl || '';
                    }
                });
                data['ckpp_unique'] = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                var input = addToCartForm.querySelector('input[name="ckpp_personalization_data"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ckpp_personalization_data';
                    addToCartForm.appendChild(input);
                }
                input.value = JSON.stringify(data);
            }, true);
        }
    }
    // Helper to get the Nth index of a type in config.objects
    function getTextIndex(config, idx, type) {
        var count = -1;
        for (var i = 0; i <= idx; i++) {
            if (config.objects[i].type === type) count++;
        }
        return count;
    }
    // Helper to get the Nth image placeholder index
    function getImagePlaceholderIndex(config, idx) {
        var count = -1;
        for (var i = 0; i <= idx; i++) {
            if (config.objects[i].placeholderType === 'image') count++;
        }
        return count;
    }
    // Validate text, textarea, and image inputs and enable/disable Add to Cart
    function validateTextInputs() {
        var container = document.getElementById('ckpp-text-inputs-container');
        if (!container) return;
        var addToCartBtn = document.querySelector('form.cart button[type="submit"], form.cart input[type="submit"]');
        var validation = ckppValidateRequiredFieldsInline();
        
        // Debug output if debug mode is enabled
        if (window.CKPP_DEBUG_MODE) {
            console.log('[CKPP] Validation result:', validation);
            console.log('[CKPP] Required fields status:', validation.allFilled ? 'FILLED' : 'NOT FILLED');
            if (validation.firstError) {
                console.log('[CKPP] First error:', validation.firstError);
            }
        }
        
        if (addToCartBtn) {
            // Only enable the button if all required fields are filled
            addToCartBtn.disabled = !validation.allFilled;
        }
    }
    // Move and show live preview only if a gallery container is found
    function placeLivePreviewDiv() {
        var previewDiv = document.getElementById('ckpp-live-preview');
        if (!previewDiv) return;
        // Try classic WooCommerce gallery
        var classicGallery = document.querySelector('.woocommerce-product-gallery');
        if (classicGallery) {
            classicGallery.style.display = 'none';
            classicGallery.parentNode.insertBefore(previewDiv, classicGallery);
            previewDiv.style.display = 'block';
            return;
        }
        // Try WooCommerce block gallery
        var blockGallery = document.querySelector('.wp-block-woocommerce-product-image-gallery');
        if (blockGallery) {
            blockGallery.style.display = 'none';
            blockGallery.parentNode.insertBefore(previewDiv, blockGallery);
            previewDiv.style.display = 'block';
            return;
        }
        // If neither found, leave preview hidden
    }
    $(document).ready(placeLivePreviewDiv);
    // Example for debug panel on main product page:
    if (window.CKPP_DEBUG_MODE && window.CKPP_LIVE_CONFIG) {
        var debugPanel = document.createElement('pre');
        debugPanel.style.background = '#f8f8f8';
        debugPanel.style.border = '1px solid #ccc';
        debugPanel.style.padding = '1em';
        debugPanel.style.marginTop = '1em';
        debugPanel.style.fontSize = '12px';
        debugPanel.style.overflowX = 'auto';
        debugPanel.textContent = JSON.stringify(window.CKPP_LIVE_CONFIG, null, 2);
        var addToCartBtn = document.querySelector('form.cart button[type="submit"], form.cart input[type="submit"]');
        if (addToCartBtn) {
            var form = addToCartBtn.closest('form.cart');
            if (form) form.appendChild(debugPanel);
        }
    }
    // Handler for Add to Cart submit when modal is open
    function ckppHandleModalAddToCartSubmit(e) {
        if (ckppImageUploadsInProgress > 0) {
            e.preventDefault();
            e.stopImmediatePropagation();
            document.getElementById('ckpp-customizer-error').textContent = 'Please wait for all image uploads to finish.';
            document.getElementById('ckpp-customizer-error').style.display = 'block';
            return false;
        }
        const form = document.getElementById('ckpp-customizer-form');
        let allFilled = true;
        let firstError = null;
        if (config && config.objects) {
            config.objects.forEach(function(obj, idx) {
                if (obj.required) {
                    if (obj.type === 'i-text') {
                        const input = form['text_' + idx];
                        if (!input || !input.value.trim()) {
                            allFilled = false;
                            if (!firstError) firstError = obj.label || 'Text ' + (idx+1);
                        }
                    } else if (obj.type === 'textbox') {
                        const textarea = form['textbox_' + idx];
                        if (!textarea || !textarea.value.trim()) {
                            allFilled = false;
                            if (!firstError) firstError = obj.label || 'Text Box ' + (idx+1);
                        }
                    } else if (obj.placeholderType === 'image') {
                        const fileInput = form['image_' + idx];
                        const previewImg = document.getElementById('ckpp-image-preview-' + idx);
                        if (!fileInput || !previewImg || !previewImg.src || previewImg.style.display === 'none') {
                            allFilled = false;
                            if (!firstError) firstError = obj.label || 'Image Upload';
                        }
                    }
                }
            });
        }
        if (!allFilled) {
            const err = document.getElementById('ckpp-customizer-error');
            err.textContent = 'Please fill out all required fields' + (firstError ? (': ' + firstError) : '.');
            err.style.display = 'block';
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        } else {
            document.getElementById('ckpp-customizer-error').style.display = 'none';
        }
        // Save personalization data as hidden input
        const data = {};
        config.objects.forEach(function(obj, idx) {
            if (obj.type === 'i-text') {
                data['text_' + idx] = form['text_' + idx] ? form['text_' + idx].value : '';
            }
            if (obj.type === 'textbox') {
                data['textbox_' + idx] = form['textbox_' + idx] ? form['textbox_' + idx].value : '';
            }
            if (obj.placeholderType === 'image') {
                const fileInput = form['image_' + idx];
                if (fileInput && fileInput.files && fileInput.files[0]) {
                    const uploadedUrl = fileInput.getAttribute('data-uploaded-url');
                    if (uploadedUrl) {
                        data['image_' + idx] = uploadedUrl;
                    } else {
                        // fallback: use data URL (legacy, if upload failed)
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            data['image_' + idx] = ev.target.result;
                        };
                        reader.readAsDataURL(fileInput.files[0]);
                    }
                } else {
                    data['image_' + idx] = '';
                }
            }
        });
        // Add a random unique value to the personalization data
        data['ckpp_unique'] = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        let input = e.target.querySelector('input[name="ckpp_personalization_data"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ckpp_personalization_data';
            e.target.appendChild(input);
        }
        input.value = JSON.stringify(data);
        // Allow form to submit
        closeCustomizer();
    }
    // Validate required fields for inline personalization
    function ckppValidateRequiredFieldsInline() {
        var container = document.getElementById('ckpp-text-inputs-container');
        var errorDiv = document.getElementById('ckpp-inline-error');
        var config = window.CKPP_LIVE_CONFIG;
        var allFilled = true;
        var firstError = null;
        var requiredFieldCount = 0;
        
        if (!config || !config.objects) {
            allFilled = false;
            firstError = 'Personalization configuration missing. Please refresh the page.';
            if (window.CKPP_DEBUG_MODE) {
                console.error('[CKPP] No config found during validation');
            }
        } else {
            if (window.CKPP_DEBUG_MODE) {
                console.log('[CKPP] Validating with config:', config);
            }
            
            var inputObjs = (config.objects || []).map(function(obj, idx) { return {obj, idx}; }).filter(function(pair) {
                return pair.obj.type === 'i-text' || pair.obj.type === 'textbox' || pair.obj.placeholderType === 'image';
            });
            
            // Debug: Count total fields and required fields
            if (window.CKPP_DEBUG_MODE) {
                console.log('[CKPP] Total input fields:', inputObjs.length);
                var requiredFields = inputObjs.filter(function(pair) {
                    return pair.obj.required === true;
                });
                console.log('[CKPP] Required fields:', requiredFields.length, requiredFields.map(function(pair) {
                    return pair.obj.label || 'Field ' + pair.idx;
                }));
            }
            
            inputObjs.forEach(function(pair) {
                var obj = pair.obj;
                var idx = pair.idx;
                
                // Skip validation entirely if the field is not explicitly required
                if (obj.required !== true) {
                    if (window.CKPP_DEBUG_MODE) {
                        console.log('[CKPP] Field not required, skipping validation:', obj.label || 'Field ' + idx);
                    }
                    return;
                }
                
                // Count required fields for debugging
                requiredFieldCount++;
                
                var rawLabel = obj.label || (obj.type === 'i-text' ? 'Text ' + (idx+1) : obj.type === 'textbox' ? 'Text Box ' + (idx+1) : obj.placeholderType === 'image' ? 'Image Upload' : 'Field ' + (idx+1));
                var sanitizedLabel = rawLabel.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                var inputId;
                
                if (obj.type === 'i-text') {
                    inputId = 'ckpp-text-input-' + sanitizedLabel + '-' + idx;
                    var input = document.getElementById(inputId);
                    var value = input ? input.value.trim() : '';
                    if (!input || !value) {
                        allFilled = false;
                        if (!firstError) firstError = rawLabel;
                        if (window.CKPP_DEBUG_MODE) {
                            console.warn('[CKPP] Required text field empty:', rawLabel, inputId);
                        }
                    } else if (window.CKPP_DEBUG_MODE) {
                        console.log('[CKPP] Required text field filled:', rawLabel, inputId, value);
                    }
                } else if (obj.type === 'textbox') {
                    inputId = 'ckpp-textarea-input-' + sanitizedLabel + '-' + idx;
                    var textarea = document.getElementById(inputId);
                    var value = textarea ? textarea.value.trim() : '';
                    if (!textarea || !value) {
                        allFilled = false;
                        if (!firstError) firstError = rawLabel;
                        if (window.CKPP_DEBUG_MODE) {
                            console.warn('[CKPP] Required textarea empty:', rawLabel, inputId);
                        }
                    } else if (window.CKPP_DEBUG_MODE) {
                        console.log('[CKPP] Required textarea filled:', rawLabel, inputId, value);
                    }
                } else if (obj.placeholderType === 'image') {
                    inputId = 'ckpp-image-input-' + sanitizedLabel + '-' + idx;
                    var fileInput = document.getElementById(inputId);
                    var previewImg = document.getElementById('ckpp-image-preview-' + sanitizedLabel + '-' + idx);
                    if (!fileInput || !previewImg || !previewImg.src || previewImg.style.display === 'none') {
                        allFilled = false;
                        if (!firstError) firstError = rawLabel;
                        if (window.CKPP_DEBUG_MODE) {
                            console.warn('[CKPP] Required image missing:', rawLabel, inputId);
                        }
                    } else if (window.CKPP_DEBUG_MODE) {
                        console.log('[CKPP] Required image uploaded:', rawLabel, inputId);
                    }
                }
            });
        }
        
        // Final debug info
        if (window.CKPP_DEBUG_MODE) {
            console.log('[CKPP] Validation complete - Required fields:', requiredFieldCount, 'All filled:', allFilled);
        }
        
        // Special case: if there are no required fields, always return true
        if (requiredFieldCount === 0) {
            if (window.CKPP_DEBUG_MODE) {
                console.log('[CKPP] No required fields found, allowing Add to Cart');
            }
            allFilled = true;
            firstError = null;
        }
        
        return { allFilled, firstError };
    }
    // WooCommerce Blocks: Add personalization data to AJAX Add to Cart
    // This ensures ckpp_personalization_data is sent with block theme AJAX requests
    if (typeof window !== 'undefined') {
        document.addEventListener('wc-blocks_add_to_cart_form_data', function(e) {
            // e.detail.form is the form element
            // e.detail.data is the data array to be sent
            var form = e.detail && e.detail.form;
            if (!form) return;
            var input = form.querySelector('input[name="ckpp_personalization_data"]');
            if (input && input.value) {
                e.detail.data.push({
                    name: 'ckpp_personalization_data',
                    value: input.value
                });
                if (window.CKPP_DEBUG_MODE) {
                    console.log('[CKPP] Added personalization data to block AJAX:', input.value);
                }
            }
        });
    }
    // Universal: Intercept all WooCommerce AJAX Add to Cart requests and inject personalization data (XHR version)
    (function() {
        var origOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function() {
            this._ckpp_is_wc_add_to_cart = arguments[1] && arguments[1].includes('wc-ajax=add_to_cart');
            return origOpen.apply(this, arguments);
        };
        var origSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.send = function(body) {
            if (this._ckpp_is_wc_add_to_cart && typeof body === 'string') {
                var form = document.querySelector('form.cart');
                if (form) {
                    var input = form.querySelector('input[name="ckpp_personalization_data"]');
                    if (input && input.value && !body.includes('ckpp_personalization_data=')) {
                        body += '&ckpp_personalization_data=' + encodeURIComponent(input.value);
                        if (window.CKPP_DEBUG_MODE) {
                            console.log('[CKPP] Injected personalization data into XHR:', input.value);
                        }
                    }
                }
            }
            return origSend.call(this, body);
        };
    })();
})(jQuery); 