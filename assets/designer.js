// assets/designer.js
(function($) {
    function renderMinimalDesigner() {
        var root = document.getElementById('ckpp-product-designer-root') || document.getElementById('ckpp-designer-modal');
        if (!root) return;
        // Make designName a property of window so it's accessible everywhere
        window.ckppDesignName = window.CKPP_DESIGN_TITLE || 'Untitled Design';
        // Before root.innerHTML assignment, set the body background to #fafbfc
        document.body.style.background = '#fafbfc';
        // --- Template UI ---
        var templatesDataDiv = document.getElementById('ckpp-templates-data');
        var templates = [];
        if (templatesDataDiv) {
            try { templates = JSON.parse(templatesDataDiv.getAttribute('data-templates') || '[]'); } catch(e) { templates = []; }
        }
        var templateOptions = templates.map(function(t) {
            return `<option value="${t.id}">${t.title.replace('Template: ', '')}</option>`;
        }).join('');
        var templateUI = `
            <div style="display:flex; align-items:center; gap:16px; margin-bottom:18px;">
                <label for='ckpp-design-name' style='font-size:16px;font-weight:bold;'>Design Name:</label>
                <input id='ckpp-design-name' type='text' value='${window.ckppDesignName.replace(/'/g, "&#39;")}' style='font-size:16px;padding:4px 10px;border-radius:6px;border:1px solid #ccc;width:220px;max-width:100%;margin-right:16px;' />
                <button id="ckpp-save-template" style="background:#fec610; color:#222; border:none; border-radius:6px; padding:7px 18px; font-weight:bold; cursor:pointer;">Save as Template</button>
                <label style="font-size:15px; margin-left:8px;">Load Template:
                    <select id="ckpp-load-template" style="margin-left:8px;">
                        <option value="">-- Select Template --</option>
                        ${templateOptions}
                    </select>
                </label>
                <button id="ckpp-reset-canvas" style="margin-left:16px; background:#eee; color:#222; border:none; border-radius:6px; padding:7px 18px; font-weight:bold; cursor:pointer;">Reset Canvas</button>
                <span id="ckpp-template-msg" style="margin-left:16px; color:#008a00; font-size:14px;"></span>
            </div>
        `;
        // --- End Template UI ---
        // --- Tools Sidebar ---
        var toolsSidebar = `
            <div style="display:flex; flex-direction:column; gap:16px;">
                <button id="ckpp-tool-add-text" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:none;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.04);cursor:pointer;font-size:15px;" title="Add Text"><span style="font-size:18px;">🅣</span> Text</button>
                <button id="ckpp-tool-add-textbox" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:none;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.04);cursor:pointer;font-size:15px;" title="Add Text Box"><span style="font-size:18px;">🅣🅑</span> Text Box</button>
                <button id="ckpp-tool-add-image" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:none;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.04);cursor:pointer;font-size:15px;" title="Add Image"><span style="font-size:18px;">🖼️</span> Image</button>
                <button id="ckpp-tool-add-image-placeholder" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:none;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.04);cursor:pointer;font-size:15px;" title="Add Image Placeholder"><span style="font-size:18px;">⬜</span> Image Placeholder</button>
                <button id="ckpp-tool-add-shape" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:none;background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.04);cursor:pointer;font-size:15px;" title="Add Shape"><span style="font-size:18px;">⬛</span> Shape</button>
            </div>
        `;
        // --- End Tools Sidebar ---
        root.innerHTML = templateUI + `
            <div style=\"background:#fafbfc; padding:32px; border-radius:18px; box-shadow:0 2px 12px rgba(0,0,0,0.04); max-width:1100px; margin:32px auto;\">
              <div style=\"display:flex; gap:32px; align-items:flex-start;\">
                <!-- Tools Sidebar -->
                <div style=\"width:160px; min-width:120px;\">
                  <div style=\"color:#fec610; font-weight:bold; font-size:18px; margin-bottom:18px;\">Tools</div>
                  ${toolsSidebar}
                </div>
                <!-- Center Canvas Area -->
                <div style=\"flex:1; text-align:center; display:flex; flex-direction:column; align-items:center;\">
                  <div style=\"width:100%; max-width:1000px; aspect-ratio:1/1; background:#f3f3f3; display:flex; align-items:center; justify-content:center; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,0.06); border:3px solid #fec610;\">
                    <canvas id=\"ckpp-canvas\" width=\"1000\" height=\"1000\" style=\"width:100%; height:100%; max-width:1000px; max-height:1000px; background:transparent; display:block;\"></canvas>
                  </div>
                  <div style=\"margin-top:1em;\"></div>
                </div>
                <!-- Properties/Layers Sidebar -->
                <div style="width:220px; min-width:160px;">
                  <div style="color:#fec610; font-weight:bold; font-size:18px; margin-bottom:18px;">Properties</div>
                  <div id="ckpp-properties-panel" style="margin-bottom:32px;"></div>
                  <div style="color:#fec610; font-weight:bold; font-size:18px; margin-bottom:18px;">Layers</div>
                  <div id="ckpp-layers-panel"></div>
                </div>
              </div>
            </div>
        `;
        // Attach reset canvas event handler immediately after HTML injection
        var resetBtn = document.getElementById('ckpp-reset-canvas');
        if (resetBtn) {
            resetBtn.onclick = function() {
                if (window.ckppFabricCanvas) {
                    var fabricCanvas = window.ckppFabricCanvas;
                    fabricCanvas.getObjects().slice().forEach(function(obj) {
                        fabricCanvas.remove(obj);
                    });
                    fabricCanvas.discardActiveObject();
                    fabricCanvas.requestRenderAll();
                }
            };
        } else {
            console.warn('CKPP: Reset Canvas button not found in DOM when trying to attach event handler.');
        }
        // Wait for Fabric.js to be loaded
        function initAfterDOM() {
            var canvasEl = document.getElementById('ckpp-canvas');
            if (!canvasEl) {
                console.error('CKPP: Canvas element not found after HTML injection.');
                return;
            }
            if (typeof fabric === 'undefined') {
                var script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.2.4/fabric.min.js';
                script.onload = function() { setupMinimalDesigner(); };
                document.body.appendChild(script);
            } else {
                setupMinimalDesigner();
            }
        }
        setTimeout(initAfterDOM, 0);
    }
    function setupMinimalDesigner() {
        let lastSaveStatus = '';
        let saveTimeout = null;
        var fabricCanvas = new fabric.Canvas('ckpp-canvas');
        window.ckppFabricCanvas = fabricCanvas;
        // Load initial config if present
        if (window.CKPP_INITIAL_CONFIG) {
            try {
                var configObj = typeof window.CKPP_INITIAL_CONFIG === 'string' ? JSON.parse(window.CKPP_INITIAL_CONFIG) : window.CKPP_INITIAL_CONFIG;
                // Inject @font-face for all custom fonts used in config
                var fontsDataDiv = document.getElementById('ckpp-fonts-data');
                var uploadedFonts = [];
                if (fontsDataDiv) {
                    try { uploadedFonts = JSON.parse(fontsDataDiv.getAttribute('data-fonts') || '[]'); } catch(e) { uploadedFonts = []; }
                }
                var usedFonts = new Set();
                if (configObj.objects && Array.isArray(configObj.objects)) {
                    configObj.objects.forEach(function(obj) {
                        if ((obj.type === 'i-text' || obj.type === 'textbox' || obj.type === 'text') && obj.fontFamily) {
                            usedFonts.add(obj.fontFamily);
                        }
                    });
                }
                usedFonts.forEach(function(fontName) {
                    var customFont = uploadedFonts.find(function(f) { return f.name === fontName; });
                    if (customFont) {
                        var styleId = 'ckpp-font-' + customFont.name.replace(/[^a-zA-Z0-9_-]/g, '');
                        if (!document.getElementById(styleId)) {
                            var style = document.createElement('style');
                            style.id = styleId;
                            style.textContent = `@font-face { font-family: '${customFont.name}'; src: url('${customFont.url}'); font-display: swap; }`;
                            document.head.appendChild(style);
                        }
                    }
                });
                fabricCanvas.loadFromJSON(configObj, function() {
                    fabricCanvas.renderAll();
                    console.log('CKPP: Loaded config into canvas', configObj);
                });
            } catch (e) {
                console.error('CKPP: Failed to parse/load config', e);
            }
        }
        // --- Layers Panel Logic ---
        function updateLayersPanel() {
            var layersDiv = document.getElementById('ckpp-layers-panel');
            if (!layersDiv) return;
            var objs = fabricCanvas.getObjects();
            var sel = fabricCanvas.getActiveObject();
            var html = '';
            // Render in reverse order: topmost object first
            objs.slice().reverse().forEach(function(obj, revIdx) {
                var idx = objs.length - 1 - revIdx; // actual index in fabricCanvas
                var isSelected = sel === obj;
                var isVisible = obj.visible !== false;
                var isLocked = obj.lockMovementX && obj.lockMovementY && obj.selectable === false;
                html += `<div data-layer-idx="${idx}" draggable="true" style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;margin-bottom:4px;border-radius:6px;cursor:pointer;background:${isSelected ? '#ffe9a6' : 'transparent'};border:1px solid ${isSelected ? '#fec610' : 'transparent'};font-size:14px;">
                  <span style="flex:1;" >${obj.label ? obj.label : (obj.placeholderType ? obj.placeholderType : obj.type)} #${idx+1}</span>
                  <span style="display:flex;gap:2px;align-items:center;">
                    <button data-toggle-visible="${idx}" style="font-size:15px;" title="${isVisible ? 'Hide' : 'Show'}">${isVisible ? '👁' : '🚫'}</button>
                    <button data-toggle-lock="${idx}" style="font-size:15px;" title="${isLocked ? 'Unlock' : 'Lock'}">${isLocked ? '🔒' : '🔓'}</button>
                    <button data-delete-layer="${idx}" style="font-size:15px; color:#a00; background:none; border:none; cursor:pointer;" title="Delete">🗑️</button>
                  </span>
                </div>`;
            });
            layersDiv.innerHTML = html;
            // Drag-and-drop logic
            var dragSrcIdx = null;
            Array.from(layersDiv.querySelectorAll('[data-layer-idx]')).forEach(function(el) {
                el.ondragstart = function(e) {
                    dragSrcIdx = parseInt(el.getAttribute('data-layer-idx'));
                    e.dataTransfer.effectAllowed = 'move';
                    el.style.opacity = '0.5';
                };
                el.ondragend = function(e) {
                    el.style.opacity = '';
                };
                el.ondragover = function(e) {
                    e.preventDefault();
                    el.style.background = '#ffe9a6';
                };
                el.ondragleave = function(e) {
                    el.style.background = (fabricCanvas.getActiveObject() === fabricCanvas.getObjects()[parseInt(el.getAttribute('data-layer-idx'))]) ? '#ffe9a6' : 'transparent';
                };
                el.ondrop = function(e) {
                    e.preventDefault();
                    var dropIdx = parseInt(el.getAttribute('data-layer-idx'));
                    if (dragSrcIdx !== null && dragSrcIdx !== dropIdx) {
                        // Move object in stack
                        var objs = fabricCanvas.getObjects();
                        var obj = objs[dragSrcIdx];
                        // Remove and re-insert at new position
                        objs.splice(dragSrcIdx, 1);
                        objs.splice(dropIdx, 0, obj);
                        // Rebuild stack: bottom to top
                        objs.forEach(function(o, i) {
                            fabricCanvas.moveTo(o, i);
                        });
                        fabricCanvas.setActiveObject(obj);
                        fabricCanvas.requestRenderAll();
                        updateLayersPanel();
                        // Fire object:modified to trigger save
                        fabricCanvas.fire('object:modified');
                    }
                    dragSrcIdx = null;
                };
                // Click to select
                el.querySelector('span').onclick = function(e) {
                    var idx = parseInt(el.getAttribute('data-layer-idx'));
                    var obj = fabricCanvas.getObjects()[idx];
                    fabricCanvas.setActiveObject(obj);
                    fabricCanvas.requestRenderAll();
                    updateLayersPanel();
                };
            });
            // Show/hide, lock/unlock, delete handlers
            Array.from(layersDiv.querySelectorAll('button[data-toggle-visible]')).forEach(function(btn) {
                btn.onclick = function(e) {
                    e.stopPropagation();
                    var idx = parseInt(btn.getAttribute('data-toggle-visible'));
                    var obj = fabricCanvas.getObjects()[idx];
                    obj.visible = obj.visible === false ? true : false;
                    fabricCanvas.requestRenderAll();
                    updateLayersPanel();
                };
            });
            Array.from(layersDiv.querySelectorAll('button[data-toggle-lock]')).forEach(function(btn) {
                btn.onclick = function(e) {
                    e.stopPropagation();
                    var idx = parseInt(btn.getAttribute('data-toggle-lock'));
                    var obj = fabricCanvas.getObjects()[idx];
                    var isLocked = obj.lockMovementX && obj.lockMovementY && obj.selectable === false;
                    if (isLocked) {
                        obj.lockMovementX = false;
                        obj.lockMovementY = false;
                        obj.selectable = true;
                    } else {
                        obj.lockMovementX = true;
                        obj.lockMovementY = true;
                        obj.selectable = false;
                    }
                    fabricCanvas.discardActiveObject();
                    fabricCanvas.requestRenderAll();
                    updateLayersPanel();
                };
            });
            Array.from(layersDiv.querySelectorAll('button[data-delete-layer]')).forEach(function(btn) {
                btn.onclick = function(e) {
                    e.stopPropagation();
                    var idx = parseInt(btn.getAttribute('data-delete-layer'));
                    var obj = fabricCanvas.getObjects()[idx];
                    fabricCanvas.remove(obj);
                    fabricCanvas.discardActiveObject();
                    fabricCanvas.requestRenderAll();
                    updateLayersPanel();
                };
            });
        }
        fabricCanvas.on('object:added', updateLayersPanel);
        fabricCanvas.on('object:removed', updateLayersPanel);
        fabricCanvas.on('selection:created', updateLayersPanel);
        fabricCanvas.on('selection:updated', updateLayersPanel);
        fabricCanvas.on('selection:cleared', updateLayersPanel);
        // Initial render
        setTimeout(updateLayersPanel, 100);
        // --- End Layers Panel Logic ---
        // --- Properties Panel Logic ---
        function updatePropertiesPanel() {
            var propDiv = document.getElementById('ckpp-properties-panel');
            if (!propDiv) return;
            var sel = fabricCanvas.getActiveObject();
            if (!sel) {
                propDiv.innerHTML = '<div style="color:#bbb; font-size:14px;">Select an object to edit its properties.</div>';
                return;
            }
            var name = sel.label || sel.placeholderType || sel.type || '';
            var html = '';
            var isText = sel.type === 'i-text' || sel.type === 'textbox' || sel.type === 'text';
            var isShape = sel.type === 'rect' || sel.type === 'circle' || sel.type === 'triangle' || sel.type === 'polygon' || sel.type === 'ellipse';
            if (isText) {
                // Name
                html += `<div style=\"margin-bottom:6px; font-size:15px;\"><b>Name:</b></div>`;
                html += `<input id=\"ckpp-prop-name\" type=\"text\" value=\"${name.replace(/\"/g, '&quot;')}\" style=\"width:100%; padding:6px 8px; border-radius:6px; border:1px solid #ccc; font-size:15px; margin-bottom:10px;\" />`;
                // Font Family
                var uploadedFonts = [];
                var fontsDataDiv = document.getElementById('ckpp-fonts-data');
                if (fontsDataDiv) {
                    try { uploadedFonts = JSON.parse(fontsDataDiv.getAttribute('data-fonts') || '[]'); } catch(e) { uploadedFonts = []; }
                }
                var webSafeFonts = [
                    { name: 'Arial', value: 'Arial, Helvetica, sans-serif' },
                    { name: 'Georgia', value: 'Georgia, serif' },
                    { name: 'Impact', value: 'Impact, Charcoal, sans-serif' },
                    { name: 'Tahoma', value: 'Tahoma, Geneva, sans-serif' },
                    { name: 'Times New Roman', value: '"Times New Roman", Times, serif' },
                    { name: 'Trebuchet MS', value: '"Trebuchet MS", Helvetica, sans-serif' },
                    { name: 'Verdana', value: 'Verdana, Geneva, sans-serif' }
                ];
                var allFonts = uploadedFonts.map(function(f) {
                    return { name: f.name, value: f.name, url: f.url, isCustom: true };
                }).concat(webSafeFonts);
                var currentFont = sel.fontFamily || 'Arial';
                var fontOptions = allFonts.map(function(f) {
                    return `<option value=\"${f.name}\"${currentFont === f.name ? ' selected' : ''}>${f.name}${f.isCustom ? ' (Custom)' : ''}</option>`;
                }).join('');
                html += `<div style=\"margin-bottom:6px; font-size:15px;\"><b>Font Family:</b></div>`;
                html += `<select id=\"ckpp-prop-font-family\" style=\"width:100%; margin-bottom:10px;\">${fontOptions}</select>`;
                // Alignment
                var alignments = [
                    { key: 'left', icon: '<svg width="20" height="20" viewBox="0 0 20 20"><rect x="2" y="5" width="16" height="2" rx="1" fill="#444"/><rect x="2" y="9" width="10" height="2" rx="1" fill="#444"/><rect x="2" y="13" width="14" height="2" rx="1" fill="#444"/></svg>' },
                    { key: 'center', icon: '<svg width="20" height="20" viewBox="0 0 20 20"><rect x="4" y="5" width="12" height="2" rx="1" fill="#444"/><rect x="2" y="9" width="16" height="2" rx="1" fill="#444"/><rect x="5" y="13" width="10" height="2" rx="1" fill="#444"/></svg>' },
                    { key: 'right', icon: '<svg width="20" height="20" viewBox="0 0 20 20"><rect x="2" y="5" width="16" height="2" rx="1" fill="#444"/><rect x="8" y="9" width="10" height="2" rx="1" fill="#444"/><rect x="4" y="13" width="14" height="2" rx="1" fill="#444"/></svg>' },
                    { key: 'justify', icon: '<svg width="20" height="20" viewBox="0 0 20 20"><rect x="2" y="5" width="16" height="2" rx="1" fill="#444"/><rect x="2" y="9" width="16" height="2" rx="1" fill="#444"/><rect x="2" y="13" width="16" height="2" rx="1" fill="#444"/></svg>' }
                ];
                var currentAlign = sel.textAlign || 'left';
                html += `<div style=\"margin-bottom:6px; font-size:15px;\"><b>Alignment:</b></div>`;
                html += `<div id=\"ckpp-prop-text-align-group\" style=\"display:flex;gap:8px;margin-bottom:10px;\">`;
                alignments.forEach(function(a) {
                    html += `<button type=\"button\" class=\"ckpp-align-btn${currentAlign === a.key ? ' ckpp-align-btn-active' : ''}\" data-align=\"${a.key}\" style=\"background:${currentAlign === a.key ? '#fec610' : '#f3f3f3'};border:1px solid #ccc;border-radius:5px;padding:4px 6px;cursor:pointer;\" title=\"${a.key.charAt(0).toUpperCase() + a.key.slice(1)}\">${a.icon}</button>`;
                });
                html += `</div>`;
                // Colour & Font Size (side by side)
                html += `<div style=\"display:flex; gap:12px; align-items:center; margin-bottom:10px;\">`;
                // Colour (Pickr placeholder)
                html += `<div id=\"ckpp-prop-fill-picker\"></div>`;
                // Font Size
                html += `<div style=\"font-size:15px; margin-left:8px;\"><b>Font Size:</b></div>`;
                html += `<input id=\"ckpp-prop-font-size\" type=\"number\" min=\"6\" max=\"200\" value=\"${sel.fontSize || 24}\" style=\"width:70px;\" /> px`;
                html += `</div>`;
                // Rotation (new line)
                html += `<div style=\"display:flex; gap:12px; align-items:center; margin-bottom:10px;\">`;
                html += `<div style=\"font-size:15px;\"><b>Rotation:</b></div>`;
                html += `<input id=\"ckpp-prop-angle\" type=\"number\" value=\"${Math.round(sel.angle)}\" style=\"width:60px; margin-right:4px;\" />`;
                html += `<span style=\"font-size:14px; color:#888;\">&deg;</span>`;
                html += `</div>`;
                // Size (width/height)
                if (typeof sel.width === 'number' && typeof sel.height === 'number') {
                    html += `<div style=\"margin-bottom:6px; font-size:15px;\"><b>Size:</b></div>`;
                    html += `<div style=\"display:flex; gap:8px; margin-bottom:10px;\">`;
                    html += `<label style=\"flex:1;\">W: <input id=\"ckpp-prop-w\" type=\"number\" value=\"${Math.round(sel.width * sel.scaleX)}\" style=\"width:60px;\" /></label>`;
                    html += `<label style=\"flex:1;\">H: <input id=\"ckpp-prop-h\" type=\"number\" value=\"${Math.round(sel.height * sel.scaleY)}\" style=\"width:60px;\" /></label>`;
                    html += `</div>`;
                }
            } else if (isShape) {
                // Shape properties UI
                html += `<div style=\"margin-bottom:6px; font-size:15px;\"><b>Name:</b></div>`;
                html += `<input id=\"ckpp-prop-name\" type=\"text\" value=\"${name.replace(/\"/g, '&quot;')}\" style=\"width:100%; padding:6px 8px; border-radius:6px; border:1px solid #ccc; font-size:15px; margin-bottom:10px;\" />`;
                // Fill Color (Pickr)
                html += `<div style=\"margin-bottom:10px;\"><b>Fill Color:</b><div id=\"ckpp-prop-fill-picker\"></div></div>`;
                // Rotation
                html += `<div style=\"display:flex; gap:12px; align-items:center; margin-bottom:10px;\">`;
                html += `<div style=\"font-size:15px;\"><b>Rotation:</b></div>`;
                html += `<input id=\"ckpp-prop-angle\" type=\"number\" value=\"${Math.round(sel.angle)}\" style=\"width:60px; margin-right:4px;\" />`;
                html += `<span style=\"font-size:14px; color:#888;\">&deg;</span>`;
                html += `</div>`;
                // Size (width/height)
                if (typeof sel.width === 'number' && typeof sel.height === 'number') {
                    html += `<div style=\"margin-bottom:6px; font-size:15px;\"><b>Size:</b></div>`;
                    html += `<div style=\"display:flex; gap:8px; margin-bottom:10px;\">`;
                    html += `<label style=\"flex:1;\">W: <input id=\"ckpp-prop-w\" type=\"number\" value=\"${Math.round(sel.width * sel.scaleX)}\" style=\"width:60px;\" /></label>`;
                    html += `<label style=\"flex:1;\">H: <input id=\"ckpp-prop-h\" type=\"number\" value=\"${Math.round(sel.height * sel.scaleY)}\" style=\"width:60px;\" /></label>`;
                    html += `</div>`;
                }
            } else {
                // Non-text, non-shape objects: keep current order or show minimal info
                html += `<div style=\"color:#bbb; font-size:14px;\">No editable properties for this object.</div>`;
            }
            // Image Placeholder properties
            if (sel.placeholderType === 'image') {
                html += '<div style="margin-bottom:1em;"><label style="font-weight:bold;">Label<br/><input type="text" id="ckpp-prop-image-label" value="' + (sel.label || 'Image Placeholder') + '" style="width:100%;margin-top:4px;" /></label></div>';
            }
            propDiv.innerHTML = html;
            // Name
            var nameInput = document.getElementById('ckpp-prop-name');
            if (nameInput) {
                nameInput.oninput = function() {
                    sel.label = nameInput.value;
                    fabricCanvas.requestRenderAll();
                    updateLayersPanel();
                    fabricCanvas.fire('object:modified', { target: sel });
                };
            }
            // Size
            var wInput = document.getElementById('ckpp-prop-w');
            var hInput = document.getElementById('ckpp-prop-h');
            if (wInput && hInput) {
                wInput.oninput = function() {
                    var w = Math.max(1, parseFloat(wInput.value) || 1);
                    sel.scaleX = w / sel.width;
                    fabricCanvas.requestRenderAll();
                    fabricCanvas.fire('object:modified', { target: sel });
                };
                hInput.oninput = function() {
                    var h = Math.max(1, parseFloat(hInput.value) || 1);
                    sel.scaleY = h / sel.height;
                    fabricCanvas.requestRenderAll();
                    fabricCanvas.fire('object:modified', { target: sel });
                };
            }
            // Font controls
            var fontFamilyInput = document.getElementById('ckpp-prop-font-family');
            if (fontFamilyInput) {
                fontFamilyInput.onchange = function() {
                    var selectedFont = fontFamilyInput.value;
                    // If custom font, inject @font-face if not already present
                    var fontsDataDiv = document.getElementById('ckpp-fonts-data');
                    var uploadedFonts = [];
                    if (fontsDataDiv) {
                        try { uploadedFonts = JSON.parse(fontsDataDiv.getAttribute('data-fonts') || '[]'); } catch(e) { uploadedFonts = []; }
                    }
                    var customFont = uploadedFonts.find(function(f) { return f.name === selectedFont; });
                    if (customFont) {
                        // Inject @font-face if not already present
                        var styleId = 'ckpp-font-' + customFont.name.replace(/[^a-zA-Z0-9_-]/g, '');
                        if (!document.getElementById(styleId)) {
                            var style = document.createElement('style');
                            style.id = styleId;
                            style.textContent = `@font-face { font-family: '${customFont.name}'; src: url('${customFont.url}'); font-display: swap; }`;
                            document.head.appendChild(style);
                        }
                        sel.set('fontFamily', customFont.name);
                        // Wait for font to load before rendering and saving
                        if (document.fonts && document.fonts.load) {
                            document.fonts.load('16px "' + customFont.name + '"').then(function() {
                                fabricCanvas.requestRenderAll();
                                fabricCanvas.fire('object:modified', { target: sel });
                                console.log('CKPP: Forced save after custom font loaded');
                                if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
                            }).catch(function(err) {
                                console.error('CKPP: Failed to load custom font', customFont.name, err);
                                fabricCanvas.requestRenderAll();
                                fabricCanvas.fire('object:modified', { target: sel });
                                if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
                            });
                        } else {
                            fabricCanvas.requestRenderAll();
                            fabricCanvas.fire('object:modified', { target: sel });
                            if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
                        }
                    } else {
                        sel.set('fontFamily', selectedFont);
                        fabricCanvas.requestRenderAll();
                        fabricCanvas.fire('object:modified', { target: sel });
                        if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
                    }
                };
            }
            var fontSizeInput = document.getElementById('ckpp-prop-font-size');
            if (fontSizeInput) {
                fontSizeInput.oninput = function() {
                    var size = Math.max(6, parseInt(fontSizeInput.value) || 24);
                    sel.set('fontSize', size);
                    fabricCanvas.requestRenderAll();
                    fabricCanvas.fire('object:modified', { target: sel });
                    if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
                };
            }
            // Fill color (Pickr integration)
            var fillPickerDiv = document.getElementById('ckpp-prop-fill-picker');
            if (fillPickerDiv && typeof Pickr !== 'undefined') {
                // Destroy previous instance if any
                if (window.ckppPickr) { window.ckppPickr.destroyAndRemove(); }
                var initialColor = sel.fill && sel.fill.startsWith('#') ? sel.fill : (sel.fill ? rgbToHex(sel.fill) : '#000000');
                window.ckppPickr = Pickr.create({
                    el: fillPickerDiv,
                    theme: 'classic',
                    default: initialColor,
                    components: {
                        preview: true,
                        opacity: false,
                        hue: true,
                        interaction: {
                            hex: true,
                            rgba: true,
                            input: true,
                            save: true
                        }
                    }
                });
                window.ckppPickr.on('save', function(color) {
                    var hex = color.toHEXA().toString();
                    sel.set('fill', hex);
                    sel.dirty = true;
                    fabricCanvas.renderAll();
                    fabricCanvas.fire('object:modified', { target: sel });
                    window.ckppPickr.hide();
                });
                window.ckppPickr.on('change', function(color) {
                    var hex = color.toHEXA().toString();
                    sel.set('fill', hex);
                    sel.dirty = true;
                    fabricCanvas.renderAll();
                    fabricCanvas.fire('object:modified', { target: sel });
                });
            }
            var angleInput = document.getElementById('ckpp-prop-angle');
            if (angleInput) {
                angleInput.oninput = function() {
                    sel.set({ originX: 'center', originY: 'center' });
                    sel.angle = parseFloat(angleInput.value) || 0;
                    fabricCanvas.requestRenderAll();
                    fabricCanvas.fire('object:modified', { target: sel });
                };
            }
            // Alignment
            var alignGroup = document.getElementById('ckpp-prop-text-align-group');
            if (alignGroup) {
                Array.from(alignGroup.querySelectorAll('button[data-align]')).forEach(function(btn) {
                    btn.onclick = function() {
                        var align = btn.getAttribute('data-align');
                        sel.set('textAlign', align);
                        // Update button styles
                        Array.from(alignGroup.querySelectorAll('button[data-align]')).forEach(function(b) {
                            b.classList.remove('ckpp-align-btn-active');
                            b.style.background = '#f3f3f3';
                        });
                        btn.classList.add('ckpp-align-btn-active');
                        btn.style.background = '#fec610';
                        fabricCanvas.requestRenderAll();
                        fabricCanvas.fire('object:modified', { target: sel });
                        if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
                    };
                });
            }
            // Image Placeholder label change
            if (sel.placeholderType === 'image') {
                var labelInput = document.getElementById('ckpp-prop-image-label');
                if (labelInput) {
                    labelInput.oninput = function() {
                        sel.label = labelInput.value;
                        fabricCanvas.requestRenderAll();
                        fabricCanvas.fire('object:modified', { target: sel });
                    };
                }
            }
        }
        fabricCanvas.on('selection:created', updatePropertiesPanel);
        fabricCanvas.on('selection:updated', updatePropertiesPanel);
        fabricCanvas.on('selection:cleared', updatePropertiesPanel);
        fabricCanvas.on('object:added', updatePropertiesPanel);
        fabricCanvas.on('object:removed', updatePropertiesPanel);
        // Initial render
        setTimeout(updatePropertiesPanel, 100);
        // --- End Properties Panel Logic ---
        // --- Tools Sidebar logic ---
        setTimeout(function() {
            var toolAddText = document.getElementById('ckpp-tool-add-text');
            var toolAddTextbox = document.getElementById('ckpp-tool-add-textbox');
            var toolAddImage = document.getElementById('ckpp-tool-add-image');
            var toolAddImagePlaceholder = document.getElementById('ckpp-tool-add-image-placeholder');
            var toolAddShape = document.getElementById('ckpp-tool-add-shape');
            if (toolAddText) toolAddText.onclick = function() {
                if (window.ckppFabricCanvas) {
                    var canvas = window.ckppFabricCanvas;
                    var text = new fabric.IText('Text', { fontSize: 24, fill: '#222222' });
                    canvas.add(text);
                    canvas.centerObject(text);
                    text.setCoords();
                    canvas.setActiveObject(text);
                    canvas.requestRenderAll();
                }
            };
            if (toolAddTextbox) toolAddTextbox.onclick = function() {
                if (window.ckppFabricCanvas) {
                    var canvas = window.ckppFabricCanvas;
                    var textbox = new fabric.Textbox('Text Box', {
                        fontSize: 24,
                        fill: '#222222',
                        width: 200,
                        height: 60,
                        minWidth: 40,
                        minHeight: 24,
                        type: 'textbox', // ensure type is set for config
                    });
                    canvas.add(textbox);
                    canvas.centerObject(textbox);
                    textbox.setCoords();
                    canvas.setActiveObject(textbox);
                    canvas.requestRenderAll();
                }
            };
            if (toolAddImage) toolAddImage.onclick = function() {
                // Create a file input if not present
                var fileInput = document.getElementById('ckpp-upload-image-input');
                if (!fileInput) {
                    fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.accept = 'image/png,image/jpeg,image/jpg,image/gif,image/svg+xml';
                    fileInput.id = 'ckpp-upload-image-input';
                    fileInput.style.display = 'none';
                    document.body.appendChild(fileInput);
                }
                fileInput.value = '';
                fileInput.onchange = function(e) {
                    var file = fileInput.files[0];
                    if (!file) return;
                    // Show loading indicator
                    var msgSpan = document.getElementById('ckpp-template-msg');
                    if (msgSpan) msgSpan.textContent = 'Uploading image...';
                    var formData = new FormData();
                    formData.append('action', 'ckpp_upload_image');
                    formData.append('nonce', CKPPDesigner.nonce);
                    formData.append('file', file);
                    $.ajax({
                        url: CKPPDesigner.ajaxUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(resp) {
                            if (msgSpan) msgSpan.textContent = '';
                            if (resp && resp.success && resp.data && resp.data.url) {
                                fabric.Image.fromURL(resp.data.url, function(img) {
                                    var canvas = window.ckppFabricCanvas;
                                    img.set({ scaleX: 0.5, scaleY: 0.5 });
                                    canvas.add(img);
                                    canvas.centerObject(img);
                                    img.setCoords();
                                    canvas.setActiveObject(img);
                                    canvas.requestRenderAll();
                                }, { crossOrigin: 'anonymous' });
                            } else {
                                alert('Upload failed: ' + (resp && resp.data ? resp.data : 'Unknown error'));
                            }
                        },
                        error: function(xhr, status, error) {
                            if (msgSpan) msgSpan.textContent = '';
                            alert('Upload failed: ' + error);
                        }
                    });
                };
                fileInput.click();
            };
            if (toolAddImagePlaceholder) toolAddImagePlaceholder.onclick = function() {
                if (window.ckppFabricCanvas) {
                    var canvas = window.ckppFabricCanvas;
                    var imgPh = new fabric.Rect({
                        width: 180,
                        height: 180,
                        fill: 'rgba(0,0,0,0)',
                        stroke: '#0073aa',
                        strokeDashArray: [8, 6],
                        strokeWidth: 2,
                        selectable: true,
                        hasBorders: true,
                        hasControls: true,
                        placeholderType: 'image',
                        label: 'Image Placeholder',
                        type: 'rect',
                        objectCaching: false
                    });
                    canvas.add(imgPh);
                    canvas.centerObject(imgPh);
                    imgPh.setCoords();
                    canvas.setActiveObject(imgPh);
                    canvas.requestRenderAll();
                }
            };
            if (toolAddShape) toolAddShape.onclick = function() {
                if (window.ckppFabricCanvas) {
                    var canvas = window.ckppFabricCanvas;
                    var shape = new fabric.Rect({ width: 80, height: 50, fill: '#ff0000' });
                    canvas.add(shape);
                    canvas.centerObject(shape);
                    shape.setCoords();
                    canvas.setActiveObject(shape);
                    canvas.requestRenderAll();
                }
            };
        }, 100);
        // --- End Tools Sidebar logic ---
        // Add Delete Selected and Reset Canvas button handlers
        var deleteBtn = document.getElementById('ckpp-delete-selected');
        if (deleteBtn) {
            deleteBtn.onclick = function() {
                var sel = fabricCanvas.getActiveObject();
                if (sel) {
                    fabricCanvas.remove(sel);
                    fabricCanvas.discardActiveObject();
                    fabricCanvas.requestRenderAll();
                }
            };
        }
        // Add saving indicator below the canvas only if canvas exists
        var canvasEl = document.getElementById('ckpp-canvas');
        var savingIndicator = null;
        if (canvasEl && canvasEl.parentNode) {
            savingIndicator = document.createElement('div');
            savingIndicator.id = 'ckpp-saving-indicator';
            savingIndicator.style = 'margin-top:8px; color:#888; font-size:13px; min-height:18px;';
            canvasEl.parentNode.appendChild(savingIndicator);
        }
        // Debounced save function
        function showSaving(status) {
            if (savingIndicator) {
                savingIndicator.textContent = status;
            }
        }
        function saveCanvasConfig() {
            if (!window.CKPPDesigner || !CKPPDesigner.ajaxUrl || !CKPPDesigner.nonce) {
                console.error('CKPP: Missing CKPPDesigner, ajaxUrl, or nonce');
                return;
            }
            // Ensure all objects have fill/stroke explicitly set
            fabricCanvas.getObjects().forEach(function(obj) {
                if (typeof obj.fill === 'undefined') obj.set('fill', '#222');
                if (typeof obj.stroke === 'undefined') obj.set('stroke', null);
            });
            const config = JSON.stringify(fabricCanvas.toJSON());
            const designId = window.CKPPDesigner.designId || 0;
            const title = window.ckppDesignName || 'Untitled Design';
            showSaving('Saving…');
            console.log('CKPP: Saving design', { designId, title, config, nonce: CKPPDesigner.nonce });
            $.post(CKPPDesigner.ajaxUrl, {
                action: 'ckpp_save_design',
                nonce: CKPPDesigner.nonce,
                designId: designId,
                title: title,
                config: config
            }, function(resp) {
                console.log('CKPP: Save response', resp);
                if (resp && resp.success) {
                    showSaving('Saved');
                    lastSaveStatus = 'Saved';
                } else {
                    showSaving('Save failed');
                    lastSaveStatus = 'Save failed';
                }
                setTimeout(function() {
                    if (lastSaveStatus === 'Saved') showSaving('');
                }, 1200);
            }).fail(function(xhr, status, error) {
                console.error('CKPP: Save AJAX failed', status, error, xhr);
                showSaving('Save failed');
                lastSaveStatus = 'Save failed';
            });
        }
        function debouncedSave() {
            console.log('CKPP: debouncedSave called');
            if (saveTimeout) clearTimeout(saveTimeout);
            saveTimeout = setTimeout(saveCanvasConfig, 500);
        }
        // Save on all relevant Fabric.js events
        fabricCanvas.on('object:added', debouncedSave);
        fabricCanvas.on('object:removed', debouncedSave);
        fabricCanvas.on('object:modified', debouncedSave);
        fabricCanvas.on('selection:updated', debouncedSave);
        fabricCanvas.on('selection:cleared', debouncedSave);
        // --- Template UI logic ---
        setTimeout(function() {
            var saveBtn = document.getElementById('ckpp-save-template');
            var loadSel = document.getElementById('ckpp-load-template');
            var msgSpan = document.getElementById('ckpp-template-msg');
            if (saveBtn) {
                saveBtn.onclick = function() {
                    var tplName = prompt('Template name?');
                    if (!tplName) return;
                    saveBtn.disabled = true;
                    msgSpan.textContent = 'Saving...';
                    $.post(CKPPDesigner.ajaxUrl, {
                        action: 'ckpp_clone_design',
                        nonce: CKPPDesigner.nonce,
                        designId: CKPPDesigner.designId,
                        title: 'Template: ' + tplName
                    }, function(resp) {
                        saveBtn.disabled = false;
                        if (resp && resp.success) {
                            msgSpan.textContent = 'Template saved! Reload page to see in list.';
                        } else {
                            msgSpan.textContent = 'Save failed.';
                        }
                        setTimeout(function() { msgSpan.textContent = ''; }, 2000);
                    }).fail(function() {
                        saveBtn.disabled = false;
                        msgSpan.textContent = 'Save failed.';
                        setTimeout(function() { msgSpan.textContent = ''; }, 2000);
                    });
                };
            }
            if (loadSel) {
                loadSel.onchange = function() {
                    var tplId = loadSel.value;
                    if (!tplId) return;
                    if (!confirm('Load this template? This will overwrite your current design.')) { loadSel.value = ''; return; }
                    msgSpan.textContent = 'Loading...';
                    $.get(CKPPDesigner.ajaxUrl, {
                        action: 'ckpp_load_design',
                        nonce: CKPPDesigner.nonce,
                        designId: tplId
                    }, function(resp) {
                        if (resp && resp.success && resp.data && resp.data.config) {
                            try {
                                var configObj = typeof resp.data.config === 'string' ? JSON.parse(resp.data.config) : resp.data.config;
                                var canvas = window.ckppFabricCanvas;
                                if (!canvas) { msgSpan.textContent = 'Canvas not found.'; setTimeout(function() { msgSpan.textContent = ''; }, 2000); return; }
                                canvas.loadFromJSON(configObj, function() {
                                    canvas.renderAll();
                                    msgSpan.textContent = 'Template loaded!';
                                    setTimeout(function() { msgSpan.textContent = ''; }, 2000);
                                });
                            } catch (e) {
                                msgSpan.textContent = 'Load failed.';
                                setTimeout(function() { msgSpan.textContent = ''; }, 2000);
                            }
                        } else {
                            msgSpan.textContent = 'Load failed.';
                            setTimeout(function() { msgSpan.textContent = ''; }, 2000);
                        }
                    }).fail(function() {
                        msgSpan.textContent = 'Load failed.';
                        setTimeout(function() { msgSpan.textContent = ''; }, 2000);
                    });
                };
            }
        }, 100);
        // --- End Template UI logic ---
        // Keyboard accessibility: Delete selected object with Delete/Backspace
        if (!window.ckppDeleteKeyHandlerAdded) {
            document.addEventListener('keydown', function(e) {
                // Only trigger if canvas is focused or a canvas object is selected
                var canvas = window.ckppFabricCanvas;
                if (!canvas) return;
                var active = canvas.getActiveObject();
                // Don't delete if editing text
                if (active && (e.key === 'Delete' || e.key === 'Backspace')) {
                    if (active.isEditing) return; // Don't delete while editing text
                    // Prevent default browser action (esp. Backspace)
                    e.preventDefault();
                    canvas.remove(active);
                    canvas.discardActiveObject();
                    canvas.requestRenderAll();
                    if (typeof updateLayersPanel === 'function') updateLayersPanel();
                }
            });
            window.ckppDeleteKeyHandlerAdded = true;
        }
        var nameInput = document.getElementById('ckpp-design-name');
        if (nameInput) {
            nameInput.onchange = function() {
                window.ckppDesignName = nameInput.value;
                if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
            };
        }
        // Fire object:modified after moving to trigger save
        fabricCanvas.on('object:moving', function(e) {
            fabricCanvas.fire('object:modified', { target: e.target });
        });
        // Custom rendering for image placeholder: draw label in center
        fabric.Rect.prototype._render = (function(_super) {
            return function(ctx) {
                _super.call(this, ctx);
                if (this.placeholderType === 'image') {
                    ctx.save();
                    ctx.font = 'bold 16px Arial';
                    ctx.fillStyle = '#0073aa';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.globalAlpha = 0.7;
                    ctx.fillText(this.label || 'Image Placeholder', this.width/2, this.height/2);
                    ctx.restore();
                }
            };
        })(fabric.Rect.prototype._render);
        // --- Custom serialization for image placeholders ---
        var origRectToObject = fabric.Rect.prototype.toObject;
        fabric.Rect.prototype.toObject = function(propertiesToInclude) {
            var obj = origRectToObject.call(this, propertiesToInclude);
            if (this.placeholderType) obj.placeholderType = this.placeholderType;
            if (this.label) obj.label = this.label;
            return obj;
        };
    }
    // For modal or in-page designer
    $(document).ready(function() {
        // If CKPP_DESIGN_ID is set, load the config before initializing the designer
        if (window.CKPP_DESIGN_ID && window.CKPPDesigner && CKPPDesigner.ajaxUrl && CKPPDesigner.nonce) {
            console.log('CKPP: Loading design config for designId', CKPP_DESIGN_ID);
            $.get(CKPPDesigner.ajaxUrl, {
                action: 'ckpp_load_design',
                nonce: CKPPDesigner.nonce,
                designId: CKPP_DESIGN_ID
            }, function(resp) {
                console.log('CKPP: Load design response', resp);
                if (resp && resp.success && resp.data && resp.data.config) {
                    window.CKPP_INITIAL_CONFIG = resp.data.config;
                } else {
                    window.CKPP_INITIAL_CONFIG = null;
                }
                renderMinimalDesigner();
            }).fail(function(xhr, status, error) {
                console.error('CKPP: Failed to load design config', status, error, xhr);
                window.CKPP_INITIAL_CONFIG = null;
                renderMinimalDesigner();
            });
        } else {
            renderMinimalDesigner();
        }
    });
    // Replace previous renderMinimalDesigner with this version
    window.renderMinimalDesigner = renderMinimalDesigner;
    // Helper to convert rgb/rgba to hex
    function rgbToHex(rgb) {
        var result = /^rgba?\((\d+),\s*(\d+),\s*(\d+)/i.exec(rgb);
        return result ? "#" + ((1 << 24) + (parseInt(result[1]) << 16) + (parseInt(result[2]) << 8) + parseInt(result[3])).toString(16).slice(1) : '#000000';
    }
    // Helper to convert hex to rgb string
    function hexToRgbString(hex) {
        var h = hex.replace('#', '');
        if (h.length !== 6) return 'rgb(0,0,0)';
        var r = parseInt(h.substring(0,2), 16);
        var g = parseInt(h.substring(2,4), 16);
        var b = parseInt(h.substring(4,6), 16);
        return `rgb(${r},${g},${b})`;
    }
})(jQuery); 