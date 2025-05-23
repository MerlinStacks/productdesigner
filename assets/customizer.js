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
                html += '<div class="ckpp-modern-form-row">';
                html += '<label for="ckpp-input-text-' + idx + '" class="ckpp-modern-label">' +
                    (obj.label || CKPPCustomizer.textLabel.replace('%d', idx+1)) + requiredMark + '</label>' +
                    '<input type="text" id="ckpp-input-text-' + idx + '" name="text_' + idx + '" value="' + (obj.text || '') + '"' + ariaRequired + ' autocomplete="off" class="ckpp-modern-input" />';
                html += '</div>';
            }
            // Textarea
            if (obj.type === 'textbox') {
                html += '<div class="ckpp-modern-form-row">';
                html += '<label for="ckpp-input-textbox-' + idx + '" class="ckpp-modern-label">' +
                    (obj.label || (CKPPCustomizer.textboxLabel ? CKPPCustomizer.textboxLabel.replace('%d', idx+1) : ('Text Box ' + (idx+1)))) + requiredMark + '</label>' +
                    '<textarea id="ckpp-input-textbox-' + idx + '" name="textbox_' + idx + '" rows="2" class="ckpp-modern-input" style="width:100%;resize:vertical;"' + ariaRequired + '>' + (obj.text || '') + '</textarea>';
                html += '</div>';
            }
            // Image upload (modern drag & drop)
            if (obj.placeholderType === 'image') {
                // Compute sanitized label
                let rawLabel = obj.label || (obj.type === 'i-text' ? 'Text ' + (idx+1) : obj.type === 'textbox' ? 'Text Box ' + (idx+1) : obj.placeholderType === 'image' ? 'Image Upload' : 'Field ' + (idx+1));
                let sanitizedLabel = rawLabel.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                html += '<div class="ckpp-modern-form-row">';
                html += '<label for="ckpp-input-image-' + idx + '" class="ckpp-modern-label">' +
                    (obj.label || CKPPCustomizer.imageLabel || 'Image Upload') + requiredMark + '</label>';
                html += '<div class="ckpp-upload-dropzone" id="ckpp-image-dropzone-' + idx + '" tabindex="0" role="button" aria-describedby="ckpp-image-desc-' + idx + '">' +
                    '<span class="ckpp-upload-icon dashicons dashicons-upload"></span>' +
                    '<span class="ckpp-upload-label">' + (CKPPCustomizer.imageInstructions || 'Drag & drop image here, or click to select') + '</span>' +
                    '<span class="ckpp-upload-filename" id="ckpp-upload-filename-' + idx + '"></span>' +
                    '</div>';
                html += '<input type="file" id="ckpp-input-image-' + idx + '" name="image_' + idx + '" accept="image/*"' + ariaRequired + ' aria-describedby="ckpp-image-desc-' + idx + '" style="display:none;" />';
                html += '<span id="ckpp-image-desc-' + idx + '" class="screen-reader-text">' + CKPPCustomizer.imageInstructions + '</span>';
                // Use sanitized label for preview <img> id
                html += '<img id="ckpp-image-preview-' + sanitizedLabel + '-' + idx + '" src="" alt="' + CKPPCustomizer.imagePreviewAlt + '" style="display:none;max-width:120px;max-height:80px;margin-top:0.5em;" />';
                html += '</div>';
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
        // Modern drag & drop logic for image upload
        config.objects.forEach(function(obj, idx) {
            if (obj.placeholderType === 'image') {
                const dropzone = document.getElementById('ckpp-image-dropzone-' + idx);
                const fileInput = document.getElementById('ckpp-input-image-' + idx);
                const filenameSpan = document.getElementById('ckpp-upload-filename-' + idx);
                const previewImg = document.getElementById('ckpp-image-preview-' + sanitizedLabel + '-' + idx);
                // Click/keyboard opens file dialog
                dropzone.addEventListener('click', function() { fileInput.click(); });
                dropzone.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); } });
                // Drag & drop
                dropzone.addEventListener('dragover', function(e) { e.preventDefault(); dropzone.classList.add('dragover'); });
                dropzone.addEventListener('dragleave', function(e) { e.preventDefault(); dropzone.classList.remove('dragover'); });
                dropzone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    dropzone.classList.remove('dragover');
                    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                        fileInput.files = e.dataTransfer.files;
                        fileInput.dispatchEvent(new Event('change'));
                    }
                });
                // Show filename on select
                fileInput.addEventListener('change', function() {
                    if (fileInput.files && fileInput.files[0]) {
                        filenameSpan.textContent = fileInput.files[0].name;
                    } else {
                        filenameSpan.textContent = '';
                    }
                });
                // Image preview and upload logic
                fileInput.addEventListener('change', function() {
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        const file = fileInput.files[0];
                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            if (previewImg) {
                                previewImg.src = ev.target.result;
                                previewImg.style.display = 'block';
                                previewImg.setAttribute('aria-label', 'Image preview');
                                // Update config and re-render live preview canvas after preview image is updated
                                if (typeof updateLivePreviewFromInputs === 'function') {
                                    updateLivePreviewFromInputs(config);
                                }
                            }
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
                                if (previewImg) {
                                    previewImg.src = data.data.url;
                                    previewImg.style.display = 'block';
                                    previewImg.setAttribute('aria-label', 'Image preview');
                                    // Update config and re-render live preview canvas after preview image is updated
                                    if (typeof updateLivePreviewFromInputs === 'function') {
                                        updateLivePreviewFromInputs(config);
                                    }
                                }
                            } else {
                                fileInput.removeAttribute('data-uploaded-url');
                            }
                        }, function(err) {
                            fileInput.removeAttribute('data-uploaded-url');
                        });
                    } else {
                        if (previewImg) {
                            previewImg.src = '';
                            previewImg.style.display = 'none';
                            // Update config and re-render live preview canvas after image is cleared
                            if (typeof updateLivePreviewFromInputs === 'function') {
                                updateLivePreviewFromInputs(config);
                            }
                        }
                        fileInput && fileInput.removeAttribute('data-uploaded-url');
                    }
                });
            }
        });
        form.oninput = renderPreview;
        form.onchange = function(e) {
            // Image preview logic and upload
            config.objects.forEach(function(obj, idx) {
                if (obj.placeholderType === 'image') {
                    const fileInput = form['image_' + idx];
                    const previewImg = document.getElementById('ckpp-image-preview-' + sanitizedLabel + '-' + idx);
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        const file = fileInput.files[0];
                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            if (previewImg) {
                                previewImg.src = ev.target.result;
                                previewImg.style.display = 'block';
                                previewImg.setAttribute('aria-label', 'Image preview');
                            }
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
                                if (previewImg) {
                                    previewImg.src = data.data.url;
                                    previewImg.style.display = 'block';
                                    previewImg.setAttribute('aria-label', 'Image preview');
                                }
                            } else {
                                fileInput.removeAttribute('data-uploaded-url');
                            }
                        }, function(err) {
                            fileInput.removeAttribute('data-uploaded-url');
                        });
                    } else {
                        if (previewImg) {
                            previewImg.src = '';
                            previewImg.style.display = 'none';
                        }
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
            // Disable direct editing for text objects
            fabricCanvas.getObjects().forEach(function(obj) {
                if (obj.type === 'i-text') {
                    obj.editable = false;
                    obj.evented = false;
                    obj.selectable = false;
                }
            });
            // --- BEGIN: Replace image placeholders with uploaded images ---
            fabricCanvas.getObjects().forEach(function(obj) {
                if (obj.placeholderType === 'image' && obj.src) {
                    // Original properties from the placeholder config object
                    const placeholderConfig = {
                        left: obj.left,
                        top: obj.top,
                        width: obj.width, // This should be the placeholder's defined width
                        height: obj.height, // This should be the placeholder's defined height
                        scaleX: obj.scaleX || 1,
                        scaleY: obj.scaleY || 1,
                        angle: obj.angle || 0,
                        originX: obj.originX || 'left',
                        originY: obj.originY || 'top',
                        selectable: obj.selectable || false,
                        evented: obj.evented || false,
                        src: obj.src // User uploaded image URL
                    };

                    fabricCanvas.remove(obj); // Remove the original placeholder

                    fabric.Image.fromURL(placeholderConfig.src, function(img) {
                        if(window.CKPP_DEBUG_MODE) console.log('[CKPP PREVIEW] Placeholder Config:', JSON.parse(JSON.stringify(placeholderConfig)));
                        
                        // 1. Determine the placeholder's visual bounding box on canvas
                        const pConfigWidth = placeholderConfig.width;
                        const pConfigHeight = placeholderConfig.height;
                        const pScaleXToApply = placeholderConfig.scaleX; // Placeholder's original scaleX
                        const pScaleYToApply = placeholderConfig.scaleY; // Placeholder's original scaleY

                        // Visual dimensions of the placeholder area
                        const visualPlaceholderWidth = pConfigWidth * pScaleXToApply;
                        const visualPlaceholderHeight = pConfigHeight * pScaleYToApply;

                        // Visual top-left corner of the placeholder area on canvas
                        let visualPlaceholderLeft = placeholderConfig.left;
                        let visualPlaceholderTop = placeholderConfig.top;

                        if (placeholderConfig.originX === 'center') {
                            visualPlaceholderLeft = placeholderConfig.left - (visualPlaceholderWidth / 2);
                        } else if (placeholderConfig.originX === 'right') {
                            visualPlaceholderLeft = placeholderConfig.left - visualPlaceholderWidth;
                        }

                        if (placeholderConfig.originY === 'center') {
                            visualPlaceholderTop = placeholderConfig.top - (visualPlaceholderHeight / 2);
                        } else if (placeholderConfig.originY === 'bottom') {
                            visualPlaceholderTop = placeholderConfig.top - visualPlaceholderHeight;
                        }

                        // 2. Calculate scale factor for the uploaded image to cover this viewport
                        const imgNativeWidth = img.width;  // fabric.Image gives native dimensions
                        const imgNativeHeight = img.height;

                        const placeholderAspectRatio = visualPlaceholderWidth / visualPlaceholderHeight;
                        const imageAspectRatio = imgNativeWidth / imgNativeHeight;

                        if(window.CKPP_DEBUG_MODE) {
                            console.log('[CKPP PREVIEW] Placeholder Visual WxH:', visualPlaceholderWidth, 'x', visualPlaceholderHeight);
                            console.log('[CKPP PREVIEW] Placeholder Visual L,T:', visualPlaceholderLeft, visualPlaceholderTop);
                            console.log('[CKPP PREVIEW] Uploaded Img Native WxH:', imgNativeWidth, 'x', imgNativeHeight);
                            console.log('[CKPP PREVIEW] Placeholder Aspect:', placeholderAspectRatio, 'Image Aspect:', imageAspectRatio);
                        }

                        let scaleFactor;
                        if (imageAspectRatio >= placeholderAspectRatio) { // Image is wider or same aspect as placeholder
                            scaleFactor = visualPlaceholderHeight / imgNativeHeight; // Fit to placeholder height
                        } else { // Image is taller than placeholder
                            scaleFactor = visualPlaceholderWidth / imgNativeWidth; // Fit to placeholder width
                        }

                        // 3. Calculate top-left position for the image object on canvas
                        const finalImageCanvasLeft = visualPlaceholderLeft - ((imgNativeWidth * scaleFactor) - visualPlaceholderWidth) / 2;
                        const finalImageCanvasTop = visualPlaceholderTop - ((imgNativeHeight * scaleFactor) - visualPlaceholderHeight) / 2;

                        // 4. Define the clipPath. It's in the image's local (unscaled) coordinates.
                        const clipPathRectLeft = (visualPlaceholderLeft - finalImageCanvasLeft) / scaleFactor;
                        const clipPathRectTop = (visualPlaceholderTop - finalImageCanvasTop) / scaleFactor;
                        const clipPathRectWidth = visualPlaceholderWidth / scaleFactor;
                        const clipPathRectHeight = visualPlaceholderHeight / scaleFactor;

                        if(window.CKPP_DEBUG_MODE) {
                            console.log('[CKPP PREVIEW] Calculated ScaleFactor:', scaleFactor);
                            console.log('[CKPP PREVIEW] Final Image Canvas L,T:', finalImageCanvasLeft, finalImageCanvasTop);
                            console.log('[CKPP PREVIEW] ClipPath Rect L,T:', clipPathRectLeft, clipPathRectTop);
                            console.log('[CKPP PREVIEW] ClipPath Rect WxH (local coords):', clipPathRectWidth, 'x', clipPathRectHeight);
                        }

                        img.set({
                            left: finalImageCanvasLeft,
                            top: finalImageCanvasTop,
                            angle: placeholderConfig.angle,
                            originX: 'left', // Crucial: Set image origin to top-left for consistent positioning
                            originY: 'top',  // Crucial: Set image origin to top-left
                            selectable: false,
                            evented: false,
                            scaleX: scaleFactor,
                            scaleY: scaleFactor,
                            // clipPath: new fabric.Rect({
                            //     originX: 'left', // Clip path rect's own origin
                            //     originY: 'top',  // Clip path rect's own origin
                            //     left: clipPathRectLeft,
                            //     top: clipPathRectTop,
                            //     width: clipPathRectWidth,
                            //     height: clipPathRectHeight
                            // })
                        });

                        fabricCanvas.add(img);
                        fabricCanvas.renderAll();
                    }, { crossOrigin: 'anonymous' });
                }
            });
            // --- END: Replace image placeholders with uploaded images ---
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
        fabricCanvas.forEachObject(function(obj) { 
            obj.selectable = false;
            obj.evented = false;
            if (obj.isType('i-text') || obj.isType('textbox')) {
                obj.editable = false;
            }
        });
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
                    // --- BEGIN: Replace image placeholders with uploaded images ---
                    fabricCanvas.getObjects().forEach(function(obj) {
                        if (obj.placeholderType === 'image' && obj.src) {
                            // Original properties from the placeholder config object
                            const placeholderConfig = {
                                left: obj.left,
                                top: obj.top,
                                width: obj.width, // This should be the placeholder's defined width
                                height: obj.height, // This should be the placeholder's defined height
                                scaleX: obj.scaleX || 1,
                                scaleY: obj.scaleY || 1,
                                angle: obj.angle || 0,
                                originX: obj.originX || 'left',
                                originY: obj.originY || 'top',
                                selectable: obj.selectable || false,
                                evented: obj.evented || false,
                                src: obj.src // User uploaded image URL
                            };

                            fabricCanvas.remove(obj); // Remove the original placeholder

                            fabric.Image.fromURL(placeholderConfig.src, function(img) {
                                if(window.CKPP_DEBUG_MODE) console.log('[CKPP PREVIEW] Placeholder Config:', JSON.parse(JSON.stringify(placeholderConfig)));
                                
                                // 1. Determine the placeholder's visual bounding box on canvas
                                const pConfigWidth = placeholderConfig.width;
                                const pConfigHeight = placeholderConfig.height;
                                const pScaleXToApply = placeholderConfig.scaleX; // Placeholder's original scaleX
                                const pScaleYToApply = placeholderConfig.scaleY; // Placeholder's original scaleY

                                // Visual dimensions of the placeholder area
                                const visualPlaceholderWidth = pConfigWidth * pScaleXToApply;
                                const visualPlaceholderHeight = pConfigHeight * pScaleYToApply;

                                // Visual top-left corner of the placeholder area on canvas
                                let visualPlaceholderLeft = placeholderConfig.left;
                                let visualPlaceholderTop = placeholderConfig.top;

                                if (placeholderConfig.originX === 'center') {
                                    visualPlaceholderLeft = placeholderConfig.left - (visualPlaceholderWidth / 2);
                                } else if (placeholderConfig.originX === 'right') {
                                    visualPlaceholderLeft = placeholderConfig.left - visualPlaceholderWidth;
                                }

                                if (placeholderConfig.originY === 'center') {
                                    visualPlaceholderTop = placeholderConfig.top - (visualPlaceholderHeight / 2);
                                } else if (placeholderConfig.originY === 'bottom') {
                                    visualPlaceholderTop = placeholderConfig.top - visualPlaceholderHeight;
                                }

                                // 2. Calculate scale factor for the uploaded image to cover this viewport
                                const imgNativeWidth = img.width;  // fabric.Image gives native dimensions
                                const imgNativeHeight = img.height;

                                const placeholderAspectRatio = visualPlaceholderWidth / visualPlaceholderHeight;
                                const imageAspectRatio = imgNativeWidth / imgNativeHeight;

                                if(window.CKPP_DEBUG_MODE) {
                                    console.log('[CKPP PREVIEW] Placeholder Visual WxH:', visualPlaceholderWidth, 'x', visualPlaceholderHeight);
                                    console.log('[CKPP PREVIEW] Placeholder Visual L,T:', visualPlaceholderLeft, visualPlaceholderTop);
                                    console.log('[CKPP PREVIEW] Uploaded Img Native WxH:', imgNativeWidth, 'x', imgNativeHeight);
                                    console.log('[CKPP PREVIEW] Placeholder Aspect:', placeholderAspectRatio, 'Image Aspect:', imageAspectRatio);
                                }

                                let scaleFactor;
                                if (imageAspectRatio >= placeholderAspectRatio) { // Image is wider or same aspect as placeholder
                                    scaleFactor = visualPlaceholderHeight / imgNativeHeight; // Fit to placeholder height
                                } else { // Image is taller than placeholder
                                    scaleFactor = visualPlaceholderWidth / imgNativeWidth; // Fit to placeholder width
                                }

                                // 3. Calculate top-left position for the image object on canvas
                                const finalImageCanvasLeft = visualPlaceholderLeft - ((imgNativeWidth * scaleFactor) - visualPlaceholderWidth) / 2;
                                const finalImageCanvasTop = visualPlaceholderTop - ((imgNativeHeight * scaleFactor) - visualPlaceholderHeight) / 2;

                                // 4. Define the clipPath. It's in the image's local (unscaled) coordinates.
                                const clipPathRectLeft = (visualPlaceholderLeft - finalImageCanvasLeft) / scaleFactor;
                                const clipPathRectTop = (visualPlaceholderTop - finalImageCanvasTop) / scaleFactor;
                                const clipPathRectWidth = visualPlaceholderWidth / scaleFactor;
                                const clipPathRectHeight = visualPlaceholderHeight / scaleFactor;

                                if(window.CKPP_DEBUG_MODE) {
                                    console.log('[CKPP PREVIEW] Calculated ScaleFactor:', scaleFactor);
                                    console.log('[CKPP PREVIEW] Final Image Canvas L,T:', finalImageCanvasLeft, finalImageCanvasTop);
                                    console.log('[CKPP PREVIEW] ClipPath Rect L,T:', clipPathRectLeft, clipPathRectTop);
                                    console.log('[CKPP PREVIEW] ClipPath Rect WxH (local coords):', clipPathRectWidth, 'x', clipPathRectHeight);
                                }

                                img.set({
                                    left: finalImageCanvasLeft,
                                    top: finalImageCanvasTop,
                                    angle: placeholderConfig.angle,
                                    originX: 'left', // Crucial: Set image origin to top-left for consistent positioning
                                    originY: 'top',  // Crucial: Set image origin to top-left
                                    selectable: false,
                                    evented: false,
                                    scaleX: scaleFactor,
                                    scaleY: scaleFactor,
                                    // clipPath: new fabric.Rect({
                                    //     originX: 'left', // Clip path rect's own origin
                                    //     originY: 'top',  // Clip path rect's own origin
                                    //     left: clipPathRectLeft,
                                    //     top: clipPathRectTop,
                                    //     width: clipPathRectWidth,
                                    //     height: clipPathRectHeight
                                    // })
                                });

                                fabricCanvas.add(img);
                                fabricCanvas.renderAll();
                            }, { crossOrigin: 'anonymous' });
                        }
                    });
                    // --- END: Replace image placeholders with uploaded images ---
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
                fabricCanvas.forEachObject(function(obj) { 
                    obj.selectable = false;
                    obj.evented = false;
                    if (obj.isType('i-text') || obj.isType('textbox')) {
                        obj.editable = false;
                    }
                });
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
            var labelText = rawLabel + (obj.required ? ' *' : '');
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
            // Modern markup
            var row = document.createElement('div');
            row.className = 'ckpp-modern-form-row';
            var label = document.createElement('label');
            label.setAttribute('for', inputId);
            label.className = 'ckpp-modern-label';
            label.innerHTML = labelText;
            row.appendChild(label);
            if (obj.type === 'i-text') {
                var input = document.createElement('input');
                input.type = 'text';
                input.id = inputId;
                input.name = inputName;
                input.value = obj.text || '';
                input.className = 'ckpp-modern-input';
                if (obj.required) input.setAttribute('aria-required', 'true');
                input.style.width = '100%';
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
                row.appendChild(input);
            } else if (obj.type === 'textbox') {
                var textarea = document.createElement('textarea');
                textarea.id = inputId;
                textarea.name = inputName;
                textarea.value = obj.text || '';
                textarea.className = 'ckpp-modern-input';
                if (obj.required) textarea.setAttribute('aria-required', 'true');
                textarea.setAttribute('rows', '2');
                textarea.style.width = '100%';
                textarea.style.resize = 'vertical';
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
                row.appendChild(textarea);
            } else if (obj.placeholderType === 'image') {
                // Modern drag & drop image upload
                var dropzone = document.createElement('div');
                dropzone.className = 'ckpp-upload-dropzone';
                dropzone.id = 'ckpp-image-dropzone-' + idx;
                dropzone.tabIndex = 0;
                dropzone.setAttribute('role', 'button');
                dropzone.setAttribute('aria-describedby', 'ckpp-image-desc-' + idx);
                dropzone.innerHTML =
                    '<span class="ckpp-upload-icon dashicons dashicons-upload"></span>' +
                    '<span class="ckpp-upload-label">Drag & drop image here, or click to select</span>' +
                    '<span class="ckpp-upload-filename" id="ckpp-upload-filename-' + idx + '"></span>';
                var fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.id = inputId;
                fileInput.name = inputName;
                fileInput.accept = 'image/*';
                fileInput.style.display = 'none';
                if (obj.required) fileInput.setAttribute('aria-required', 'true');
                var filenameSpan = dropzone.querySelector('.ckpp-upload-filename');
                // Use sanitized label for preview <img> id
                var previewImgSanitized = document.createElement('img');
                previewImgSanitized.id = 'ckpp-image-preview-' + sanitizedLabel + '-' + idx;
                previewImgSanitized.alt = 'Image preview';
                previewImgSanitized.style.display = 'none';
                previewImgSanitized.style.maxWidth = '120px';
                previewImgSanitized.style.maxHeight = '80px';
                previewImgSanitized.style.marginTop = '0.5em';
                // Click/keyboard opens file dialog
                dropzone.addEventListener('click', function() { fileInput.click(); });
                dropzone.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); } });
                // Drag & drop
                dropzone.addEventListener('dragover', function(e) { e.preventDefault(); dropzone.classList.add('dragover'); });
                dropzone.addEventListener('dragleave', function(e) { e.preventDefault(); dropzone.classList.remove('dragover'); });
                dropzone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    dropzone.classList.remove('dragover');
                    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                        fileInput.files = e.dataTransfer.files;
                        fileInput.dispatchEvent(new Event('change'));
                    }
                });
                // Show filename on select
                fileInput.addEventListener('change', function() {
                    if (fileInput.files && fileInput.files[0]) {
                        filenameSpan.textContent = fileInput.files[0].name;
                    } else {
                        filenameSpan.textContent = '';
                    }
                });
                // Image preview and upload logic
                fileInput.addEventListener('change', function() {
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        const file = fileInput.files[0];
                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            if (previewImgSanitized) {
                                previewImgSanitized.src = ev.target.result;
                                // previewImgSanitized.style.display = 'block'; // Ensure it remains hidden
                                previewImgSanitized.setAttribute('aria-label', 'Image preview (hidden)');
                                // Update config and re-render live preview canvas after preview image is updated
                                if (typeof updateLivePreviewFromInputs === 'function') {
                                    updateLivePreviewFromInputs(config);
                                }
                            }
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
                                if (previewImgSanitized) {
                                    previewImgSanitized.src = data.data.url;
                                    // previewImgSanitized.style.display = 'block'; // Ensure it remains hidden
                                    previewImgSanitized.setAttribute('aria-label', 'Image preview (hidden)');
                                    // Update config and re-render live preview canvas after preview image is updated
                                    if (typeof updateLivePreviewFromInputs === 'function') {
                                        updateLivePreviewFromInputs(config);
                                    }
                                }
                            } else {
                                fileInput.removeAttribute('data-uploaded-url');
                            }
                        }, function(err) {
                            fileInput.removeAttribute('data-uploaded-url');
                        });
                    } else {
                        if (previewImgSanitized) {
                            previewImgSanitized.src = '';
                            previewImgSanitized.style.display = 'none'; // Explicitly ensure it's hidden when cleared
                            // Update config and re-render live preview canvas after image is cleared
                            if (typeof updateLivePreviewFromInputs === 'function') {
                                updateLivePreviewFromInputs(config);
                            }
                        }
                        fileInput && fileInput.removeAttribute('data-uploaded-url');
                    }
                });
                row.appendChild(dropzone);
                row.appendChild(fileInput);
                row.appendChild(previewImgSanitized);
            }
            container.appendChild(row);
            // Add spacing
            var spacer = document.createElement('div');
            spacer.className = 'ckpp-field-spacer';
            container.appendChild(spacer);
        });
        // Insert above Add to Cart
        form.insertBefore(container, addToCartBtn);
        // Initial validation
        validateTextInputs();
        // After all input fields are created in insertTextInputsAboveAddToCart, add event listeners for live preview sync
        inputObjs.forEach(function(pair) {
            var obj = pair.obj;
            var idx = pair.idx;
            var rawLabel = obj.label || (obj.type === 'i-text' ? 'Text ' + (idx+1) : obj.type === 'textbox' ? 'Text Box ' + (idx+1) : obj.placeholderType === 'image' ? 'Image Upload' : 'Field ' + (idx+1));
            var sanitizedLabel = rawLabel.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            if (obj.type === 'i-text') {
                var input = document.getElementById('ckpp-text-input-' + sanitizedLabel + '-' + idx);
                if (input) input.addEventListener('input', function() { updateLivePreviewFromInputs(config); });
            } else if (obj.type === 'textbox') {
                var textarea = document.getElementById('ckpp-textarea-input-' + sanitizedLabel + '-' + idx);
                if (textarea) textarea.addEventListener('input', function() { updateLivePreviewFromInputs(config); });
            } else if (obj.placeholderType === 'image') {
                var fileInput = document.getElementById('ckpp-image-input-' + sanitizedLabel + '-' + idx);
                if (fileInput) fileInput.addEventListener('change', function() { updateLivePreviewFromInputs(config); });
            }
        });
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
    // Add modern styles for the new form
    const style = document.createElement('style');
    style.textContent = `
    .ckpp-modern-form-row {
      margin-bottom: 1.5em;
      display: flex;
      flex-direction: column;
      gap: 0.5em;
    }
    .ckpp-modern-label {
      font-weight: 600;
      margin-bottom: 0.2em;
      color: #222;
    }
    .ckpp-modern-input {
      border: 1px solid #ccd0d4;
      border-radius: 6px;
      padding: 0.6em 1em;
      font-size: 1em;
      background: #fafbfc;
      transition: border 0.2s;
    }
    .ckpp-modern-input:focus {
      border-color: #fec610;
      outline: none;
    }
    .ckpp-upload-dropzone {
      display: flex;
      align-items: center;
      gap: 0.7em;
      border: 2px dashed #ccd0d4;
      border-radius: 8px;
      background: #f8f8f8;
      padding: 1em 1.5em;
      cursor: pointer;
      transition: border 0.2s, background 0.2s;
      min-height: 48px;
      position: relative;
    }
    .ckpp-upload-dropzone.dragover {
      border-color: #fec610;
      background: #fffbe6;
    }
    .ckpp-upload-icon {
      font-size: 1.5em;
      color: #fec610;
    }
    .ckpp-upload-label {
      font-size: 1em;
      color: #666;
    }
    .ckpp-upload-filename {
      font-size: 0.95em;
      color: #333;
      margin-left: auto;
      font-style: italic;
    }
    `;
    document.head.appendChild(style);
    // Add this function after renderLivePreview
    function updateLivePreviewFromInputs(currentDesignConfig) {
        // Clone config deeply to avoid mutating original
        var updatedConfig = JSON.parse(JSON.stringify(currentDesignConfig));
        var inputObjs = (updatedConfig.objects || []).map(function(obj, idx) { return {obj, idx}; }).filter(function(pair) {
            return pair.obj.type === 'i-text' || pair.obj.type === 'textbox' || pair.obj.placeholderType === 'image';
        });
        inputObjs.forEach(function(pair) {
            var obj = pair.obj;
            var idx = pair.idx;
            var rawLabel = obj.label || (obj.type === 'i-text' ? 'Text ' + (idx+1) : obj.type === 'textbox' ? 'Text Box ' + (idx+1) : obj.placeholderType === 'image' ? 'Image Upload' : 'Field ' + (idx+1));
            var sanitizedLabel = rawLabel.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

            if (obj.type === 'i-text') {
                var input = document.getElementById('ckpp-text-input-' + sanitizedLabel + '-' + idx);
                if (input) obj.text = input.value;
            } else if (obj.type === 'textbox') {
                var textarea = document.getElementById('ckpp-textarea-input-' + sanitizedLabel + '-' + idx);
                if (textarea) obj.text = textarea.value;
            } else if (obj.placeholderType === 'image') {
                var fileInput = document.getElementById('ckpp-image-input-' + sanitizedLabel + '-' + idx);
                var uploadedUrl = fileInput ? fileInput.getAttribute('data-uploaded-url') : null;
                var previewImg = document.getElementById('ckpp-image-preview-' + sanitizedLabel + '-' + idx);

                if (uploadedUrl) {
                    obj.src = uploadedUrl; // Prefer successfully uploaded URL
                } else if (previewImg && previewImg.src && typeof previewImg.src === 'string' && previewImg.src.startsWith('data:image')) {
                    // If no uploaded URL, use the DataURL from the (hidden) preview img tag if it's a valid image DataURL
                    obj.src = previewImg.src;
                } else {
                    obj.src = ''; // Otherwise, no valid source
                }
            }
        });

        // ---- NEW CODE TO PREPARE SUBMISSION DATA ----
        const dataForSubmission = {};
        (currentDesignConfig.objects || []).forEach(function(originalObjConfig, originalIdx) {
            const currentValObj = updatedConfig.objects[originalIdx]; // Get value from the potentially modified object

            if (originalObjConfig.type === 'i-text') {
                dataForSubmission['text_' + originalIdx] = currentValObj.text || '';
            } else if (originalObjConfig.type === 'textbox') {
                dataForSubmission['textbox_' + originalIdx] = currentValObj.text || '';
            } else if (originalObjConfig.placeholderType === 'image') {
                dataForSubmission['image_' + originalIdx] = currentValObj.src || '';
            }
        });

        dataForSubmission['ckpp_unique'] = Date.now() + '-' + Math.random().toString(36).substr(2, 9);

        // --- CAPTURE LIVE PREVIEW CANVAS AS IMAGE ---
        if (window.ckppLivePreviewCanvas && typeof window.ckppLivePreviewCanvas.toDataURL === 'function') {
            try {
                dataForSubmission['preview_image'] = window.ckppLivePreviewCanvas.toDataURL('image/png', 0.92);
            } catch (e) {
                if (window.CKPP_DEBUG_MODE) console.warn('[CKPP] Could not capture live preview image:', e);
            }
        }
        // --- END CAPTURE ---

        const addToCartForm = document.querySelector('form.cart');
        if (addToCartForm) {
            let hiddenInput = addToCartForm.querySelector('input[name="ckpp_personalization_data"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'ckpp_personalization_data';
                addToCartForm.appendChild(hiddenInput);
            }
            hiddenInput.value = JSON.stringify(dataForSubmission);
            if (window.CKPP_DEBUG_MODE) {
                console.log('[CKPP] Updated hidden input ckpp_personalization_data (from inline):', hiddenInput.value);
            }
        }
        // ---- END NEW CODE ----

        renderLivePreview(updatedConfig); // This uses updatedConfig for the canvas
    }
    // --- Ensure preview image is injected into hidden input before add-to-cart ---
    function ckppInjectPreviewImageToHiddenInput() {
        var addToCartForm = document.querySelector('form.cart');
        if (!addToCartForm) return;
        var hiddenInput = addToCartForm.querySelector('input[name="ckpp_personalization_data"]');
        if (!hiddenInput) return;
        var data = {};
        try {
            data = JSON.parse(hiddenInput.value);
        } catch (e) {}
        if (window.ckppLivePreviewCanvas && typeof window.ckppLivePreviewCanvas.toDataURL === 'function') {
            data['preview_image'] = window.ckppLivePreviewCanvas.toDataURL('image/png', 0.92);
        }
        hiddenInput.value = JSON.stringify(data);
    }

    document.addEventListener('DOMContentLoaded', function() {
        var addToCartForm = document.querySelector('form.cart');
        if (addToCartForm) {
            addToCartForm.addEventListener('submit', ckppInjectPreviewImageToHiddenInput, true);
        }
        var addToCartBtn = document.querySelector('form.cart button[type="submit"], form.cart input[type="submit"]');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', ckppInjectPreviewImageToHiddenInput, true);
        }
    });
})(jQuery); 