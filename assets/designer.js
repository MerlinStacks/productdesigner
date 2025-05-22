// assets/designer.js
(function($) {
    // Global font cache to track loaded fonts
    window.CKPP_LOADED_FONTS = window.CKPP_LOADED_FONTS || {};
    
    /**
     * Preload all custom fonts from the data-fonts attribute
     * Returns a promise that resolves when all fonts are loaded
     */
    function preloadAllFonts() {
        var msgSpan = document.getElementById('ckpp-template-msg');
        var fontsDataDiv = document.getElementById('ckpp-fonts-data');
        var uploadedFonts = [];
        
        // Set initial loading message
        if (msgSpan) msgSpan.textContent = 'Loading fonts...';
        
        if (!fontsDataDiv) {
            if (window.CKPP_DEBUG_MODE) {
                console.log('CKPP Debug: No fonts data found, skipping preload');
            }
            if (msgSpan) msgSpan.textContent = '';
            return Promise.resolve(); // No fonts to load
        }
        
        try {
            uploadedFonts = JSON.parse(fontsDataDiv.getAttribute('data-fonts') || '[]');
        } catch(e) {
            if (window.CKPP_DEBUG_MODE) {
                console.error('CKPP Debug: Error parsing fonts data', e);
            }
            if (msgSpan) msgSpan.textContent = '';
            return Promise.resolve(); // Error parsing fonts
        }
        
        if (!uploadedFonts.length) {
            if (window.CKPP_DEBUG_MODE) {
                console.log('CKPP Debug: No custom fonts to preload');
            }
            if (msgSpan) msgSpan.textContent = '';
            return Promise.resolve(); // No fonts to load
        }
        
        if (window.CKPP_DEBUG_MODE) {
            console.log('CKPP Debug: Preloading', uploadedFonts.length, 'custom fonts');
        }
        
        // Create container for font preloading elements
        var preloadContainer = document.createElement('div');
        preloadContainer.id = 'ckpp-font-preload-container';
        preloadContainer.style.position = 'absolute';
        preloadContainer.style.visibility = 'hidden';
        preloadContainer.style.top = '-9999px';
        document.body.appendChild(preloadContainer);
        
        // For each font, add the @font-face rule if not exists
        uploadedFonts.forEach(function(font) {
            var styleId = 'ckpp-font-' + font.name.replace(/[^a-zA-Z0-9_-]/g, '');
            if (!document.getElementById(styleId)) {
                var style = document.createElement('style');
                style.id = styleId;
                style.textContent = `@font-face { font-family: '${font.name}'; src: url('${font.url}'); font-display: swap; }`;
                document.head.appendChild(style);
                
                if (window.CKPP_DEBUG_MODE) {
                    console.log('CKPP Debug: Added @font-face for', font.name);
                }
            }
            
            // Create a forcing element for each font
            var preloadDiv = document.createElement('div');
            preloadDiv.style.fontFamily = "'" + font.name + "'";
            preloadDiv.innerText = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ abcdefghijklmnopqrstuvwxyz 0123456789';
            preloadContainer.appendChild(preloadDiv);
        });
        
        // Function to load a single font
        function loadFont(fontName) {
            // Skip if already loaded
            if (window.CKPP_LOADED_FONTS[fontName]) {
                return Promise.resolve();
            }
            
            // Use Font Loading API if available
            if (document.fonts && document.fonts.load) {
                return document.fonts.load('16px "' + fontName + '"')
                    .then(function(loadedFonts) {
                        window.CKPP_LOADED_FONTS[fontName] = true;
                        if (window.CKPP_DEBUG_MODE) {
                            console.log('CKPP Debug: Preloaded font via API:', fontName);
                        }
                        return Promise.resolve();
                    })
                    .catch(function(err) {
                        if (window.CKPP_DEBUG_MODE) {
                            console.warn('CKPP Debug: Font API load error for', fontName, err);
                        }
                        // Mark as loaded anyway, we'll try to use it
                        window.CKPP_LOADED_FONTS[fontName] = true;
                        return Promise.resolve();
                    });
            } else {
                // Fallback for browsers without Font API
                return new Promise(function(resolve) {
                    if (window.CKPP_DEBUG_MODE) {
                        console.log('CKPP Debug: Font API not available, using timeout for', fontName);
                    }
                    setTimeout(function() {
                        window.CKPP_LOADED_FONTS[fontName] = true;
                        resolve();
                    }, 100);
                });
            }
        }
        
        // Update loading message to show progress
        function updateLoadingMessage(current, total) {
            if (msgSpan) {
                msgSpan.textContent = `Loading fonts (${current}/${total})...`;
            }
        }
        
        // Load fonts sequentially with progress updates
        return uploadedFonts.reduce(function(promise, font, index) {
            return promise.then(function() {
                updateLoadingMessage(index, uploadedFonts.length);
                return loadFont(font.name);
            });
        }, Promise.resolve()).then(function() {
            // All fonts loaded
            if (window.CKPP_DEBUG_MODE) {
                console.log('CKPP Debug: All fonts preloaded successfully');
            }
            
            // Clear loading message
            if (msgSpan) {
                msgSpan.textContent = 'Fonts loaded!';
                setTimeout(function() {
                    if (msgSpan && msgSpan.textContent === 'Fonts loaded!') {
                        msgSpan.textContent = '';
                    }
                }, 1000);
            }
            
            // Clean up the preload container after a delay
            setTimeout(function() {
                if (preloadContainer && preloadContainer.parentNode) {
                    preloadContainer.parentNode.removeChild(preloadContainer);
                }
            }, 1000);
            
            return Promise.resolve();
        });
    }
    
    function renderMinimalDesigner() {
        var root = document.getElementById('ckpp-product-designer-root') || document.getElementById('ckpp-designer-modal');
        if (!root) {
            if (window.CKPP_DEBUG_MODE) {
                console.log('CKPP Debug: Designer root element not found, skipping initialization');
            }
            return;
        }
        
        // Check if we have an initial config to load
        var initialConfig = null;
        if (window.CKPP_INITIAL_CONFIG) {
            try {
                if (typeof window.CKPP_INITIAL_CONFIG === 'string') {
                    initialConfig = JSON.parse(window.CKPP_INITIAL_CONFIG);
                    if (window.CKPP_DEBUG_MODE) {
                        console.log('CKPP Debug: Parsed initial config from JSON string');
                    }
                } else if (typeof window.CKPP_INITIAL_CONFIG === 'object') {
                    initialConfig = window.CKPP_INITIAL_CONFIG;
                    if (window.CKPP_DEBUG_MODE) {
                        console.log('CKPP Debug: Using initial config object directly');
                    }
                }
            } catch (e) {
                console.error('CKPP: Error parsing initial config', e);
                initialConfig = null;
            }
        }
        
        // Make designName a property of window so it's accessible everywhere
        window.ckppDesignName = window.CKPP_DESIGN_TITLE || 'Untitled Design';
        
        // Before root.innerHTML assignment, set the body background to #fafbfc
        document.body.style.background = '#fafbfc';
        
        // --- Template UI ---
        var templatesDataDiv = document.getElementById('ckpp-templates-data');
        var templates = [];
        if (templatesDataDiv) {
            try { 
                templates = JSON.parse(templatesDataDiv.getAttribute('data-templates') || '[]'); 
            } catch(e) { 
                templates = []; 
                if (window.CKPP_DEBUG_MODE) {
                    console.error('CKPP Debug: Error parsing templates data', e);
                }
            }
        }
        
        var templateOptions = templates.map(function(t) {
            return `<option value="${t.id}">${t.title.replace('Template: ', '')}</option>`;
        }).join('');
        
        var templateUI = `
            <div class="ckpp-templates-ui">
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
            <div style=\"background:#fafbfc; padding:32px; border-radius:18px; box-shadow:0 2px 12px rgba(0,0,0,0.04); max-width:1200px; width:100%; margin:0 auto; box-sizing:border-box;\">
              <div style=\"display:flex; gap:32px; align-items:flex-start;\">
                <!-- Tools Sidebar -->
                <div class="tools-sidebar" style=\"width:160px; min-width:120px;\">
                  <div style=\"color:#fec610; font-weight:bold; font-size:18px; margin-bottom:18px;\">Tools</div>
                  ${toolsSidebar}
                </div>
                <!-- Center Canvas Area -->
                <div class="canvas-area" style=\"flex:1; text-align:center; display:flex; flex-direction:column; align-items:center;\">
                  <div class="ckpp-canvas-container">
                    <canvas id=\"ckpp-canvas\" width=\"1000\" height=\"1000\"></canvas>
                  </div>
                  <div style=\"margin-top:1em;\"></div>
                </div>
                <!-- Properties/Layers Sidebar -->
                <div class="properties-sidebar" style="width:220px; min-width:160px;">
                  <div style="color:#fec610; font-weight:bold; font-size:18px; margin-bottom:18px;">Layers</div>
                  <div id="ckpp-layers-panel" style="margin-bottom:32px;"></div>
                  <div style="color:#fec610; font-weight:bold; font-size:18px; margin-bottom:18px;">Properties</div>
                  <div id="ckpp-properties-panel"></div>
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
            if (window.CKPP_DEBUG_MODE) {
                console.warn('CKPP Debug: Reset Canvas button not found in DOM when trying to attach event handler.');
            }
        }
        
        if (window.CKPP_DEBUG_MODE) {
            console.log('CKPP Debug: Initializing minimal designer, has config:', !!initialConfig);
        }
        
        // Wait for Fabric.js to be loaded
        function initAfterDOM() {
            var canvasEl = document.getElementById('ckpp-canvas');
            if (!canvasEl) {
                if (window.CKPP_DEBUG_MODE) {
                    console.error('CKPP Debug: Canvas element not found after HTML injection.');
                }
                return;
            }
            
            if (typeof fabric === 'undefined') {
                var script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.2.4/fabric.min.js';
                script.onload = function() { 
                    setupMinimalDesigner(initialConfig); 
                };
                document.body.appendChild(script);
            } else {
                setupMinimalDesigner(initialConfig);
            }
        }
        
        setTimeout(initAfterDOM, 0);
    }
    function setupMinimalDesigner(initialConfig) {
        let lastSaveStatus = '';
        let saveTimeout = null;
        let resizeTimeout = null;
        
        // Initialize the Fabric.js canvas with fixed virtual dimensions
        const CANVAS_VIRTUAL_WIDTH = 1000;
        const CANVAS_VIRTUAL_HEIGHT = 1000;
        
        var fabricCanvas = new fabric.Canvas('ckpp-canvas', {
            width: CANVAS_VIRTUAL_WIDTH,
            height: CANVAS_VIRTUAL_HEIGHT,
            selection: false,
            allowTouchScrolling: true
        });
        
        window.ckppFabricCanvas = fabricCanvas;
        
        // Handle canvas resize properly
        function handleCanvasResize() {
            // Clear any pending resize
            if (resizeTimeout) {
                clearTimeout(resizeTimeout);
            }
            
            // Use setTimeout to debounce resize events
            resizeTimeout = setTimeout(function() {
                const canvasContainer = document.querySelector('.ckpp-canvas-container');
                if (!canvasContainer) return;
                
                // Get container's computed dimensions
                const containerWidth = canvasContainer.offsetWidth;
                const containerHeight = canvasContainer.offsetHeight;
                
                if (containerWidth <= 0 || containerHeight <= 0) return;
                
                // The canvas element should already have correct dimensions from CSS
                const canvasEl = document.getElementById('ckpp-canvas');
                if (!canvasEl) return;
                
                // Ensure canvas doesn't have inline dimensions that would override CSS
                canvasEl.removeAttribute('style');
                canvasEl.style.width = '100%';
                canvasEl.style.height = '100%';
                
                // Keep fabric canvas dimensions at 1000x1000 virtual units
                fabricCanvas.setWidth(CANVAS_VIRTUAL_WIDTH);
                fabricCanvas.setHeight(CANVAS_VIRTUAL_HEIGHT);
                
                // Calculate zoom factor - use the smaller dimension to ensure it fits
                const zoomFactor = Math.min(
                    canvasEl.offsetWidth / CANVAS_VIRTUAL_WIDTH,
                    canvasEl.offsetHeight / CANVAS_VIRTUAL_HEIGHT
                );
                
                if (window.CKPP_DEBUG_MODE) {
                    console.log(`CKPP Debug: Container: ${containerWidth}x${containerHeight}`);
                    console.log(`CKPP Debug: Canvas element: ${canvasEl.offsetWidth}x${canvasEl.offsetHeight}`);
                    console.log(`CKPP Debug: Setting zoom to ${zoomFactor}`);
                }
                
                // Apply zoom (scale) factor to the Fabric.js canvas
                fabricCanvas.setZoom(zoomFactor);
                fabricCanvas.requestRenderAll();
            }, 250); // 250ms debounce
        }
        
        // Create a mutation observer to monitor and prevent style changes
        function setupMutationObserver() {
            const canvasEl = document.getElementById('ckpp-canvas');
            if (!canvasEl || typeof MutationObserver === 'undefined') return;
            
            // Create observer to watch for style attribute changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        // Force our CSS dimensions
                        canvasEl.style.width = '100%';
                        canvasEl.style.height = '100%';
                    }
                });
            });
            
            // Start observing
            observer.observe(canvasEl, { 
                attributes: true,
                attributeFilter: ['style']
            });
            
            // Store for cleanup
            window.ckppMutationObserver = observer;
        }
        
        // Initial resize after DOM is ready
        setTimeout(handleCanvasResize, 100);
        
        // Set up mutation observer after DOM is ready
        setTimeout(setupMutationObserver, 150);
        
        // Set up resize listener
        window.addEventListener('resize', handleCanvasResize);
        
        // Also resize on load
        window.addEventListener('load', handleCanvasResize);
        
        // Clean up on unload
        window.addEventListener('beforeunload', function() {
            window.removeEventListener('resize', handleCanvasResize);
            window.removeEventListener('load', handleCanvasResize);
            
            // Disconnect mutation observer
            if (window.ckppMutationObserver) {
                window.ckppMutationObserver.disconnect();
            }
        });

        // If we have an initial configuration, load it
        if (initialConfig) {
            try {
                fabricCanvas.loadFromJSON(initialConfig, function() {
                    fabricCanvas.renderAll();
                    // After loading, make sure zoom is correct
                    setTimeout(handleCanvasResize, 100);
                    
                    if (window.CKPP_DEBUG_MODE) {
                        console.log('CKPP Debug: Successfully loaded initial canvas configuration');
                    }
                });
            } catch (e) {
                console.error('CKPP: Error loading initial canvas configuration', e);
            }
        }
        
        // Configure Fabric.js
        fabricCanvas.selection = false;
        
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
                html += `<div data-layer-idx="${idx}" draggable="true" tabindex="0" style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;margin-bottom:10px;border-radius:10px;cursor:pointer;background:${isSelected ? '#fff7e0' : '#fff'};box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1.5px solid ${isSelected ? '#fec610' : 'transparent'};font-size:15px;transition:box-shadow 0.2s,border 0.2s;
                outline:none;gap:10px;">
                  <span style="flex:1; text-align:left; font-weight:500; color:#222; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${obj.label ? obj.label : (obj.placeholderType ? obj.placeholderType : obj.type)}</span>
                  <span style="display:flex;gap:8px;align-items:center;justify-content:flex-end;">
                    <button data-toggle-visible="${idx}" style="font-size:17px; background:none; border:none; color:#888; border-radius:5px; padding:4px; cursor:pointer; transition:background 0.15s;" title="${isVisible ? 'Hide' : 'Show'}" aria-label="${isVisible ? 'Hide layer' : 'Show layer'}">${isVisible ? '👁' : '🚫'}</button>
                    <button data-toggle-lock="${idx}" style="font-size:17px; background:none; border:none; color:#888; border-radius:5px; padding:4px; cursor:pointer; transition:background 0.15s;" title="${isLocked ? 'Unlock' : 'Lock'}" aria-label="${isLocked ? 'Unlock layer' : 'Lock layer'}">${isLocked ? '🔒' : '🔓'}</button>
                    <button data-delete-layer="${idx}" style="font-size:17px; background:none; border:none; color:#e74c3c; border-radius:5px; padding:4px; cursor:pointer; transition:background 0.15s;" title="Delete" aria-label="Delete layer">🗑️</button>
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
            if (isText || sel.placeholderType === 'image') {
                // Name
                html += `<div style=\"margin-bottom:6px; font-size:15px;\"><b>Name:</b></div>`;
                html += `<input id=\"ckpp-prop-name\" type=\"text\" value=\"${name.replace(/\"/g, '&quot;')}\" style=\"width:100%; padding:6px 8px; border-radius:6px; border:1px solid #ccc; font-size:15px; margin-bottom:10px;\" />`;
                // Required checkbox
                html += `<div style=\"margin-bottom:10px;\"><label><input type=\"checkbox\" id=\"ckpp-prop-required\" ${sel.required ? 'checked' : ''} /> Required</label></div>`;
            }
            if (isText) {
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
                
                // Debug font information to help diagnose issues
                if (window.CKPP_DEBUG_MODE) {
                    console.log('CKPP Debug: Current font:', currentFont);
                    console.log('CKPP Debug: Available fonts:', allFonts);
                }
                
                var fontOptions = allFonts.map(function(f) {
                    // Check if the current font matches either the name or value
                    var isSelected = currentFont === f.name || currentFont === f.value;
                    return `<option value="${f.value}"${isSelected ? ' selected' : ''}>${f.name}${f.isCustom ? ' (Custom)' : ''}</option>`;
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
                    // Update the object with the new label
                    sel.label = nameInput.value;
                    
                    // Force canvas redraw
                    fabricCanvas.requestRenderAll();
                    
                    // Update layers panel to reflect the new name
                    updateLayersPanel();
                    
                    // Trigger debounced save
                    if (saveTimeout) clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(function() {
                        fabricCanvas.fire('object:modified', { target: sel });
                        if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
                    }, 300);
                };
                
                // Also add Enter key support
                nameInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        sel.label = nameInput.value;
                        fabricCanvas.requestRenderAll();
                        updateLayersPanel();
                        fabricCanvas.fire('object:modified', { target: sel });
                        if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
                        nameInput.blur();
                    }
                });
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
                    var msgSpan = document.getElementById('ckpp-template-msg');
                    
                    // Show status message to indicate processing
                    if (msgSpan) msgSpan.textContent = 'Applying font...';
                    
                    if (window.CKPP_DEBUG_MODE) {
                        console.log('CKPP Debug: Selected font value:', selectedFont);
                    }
                    
                    // Get list of available fonts
                    var fontsDataDiv = document.getElementById('ckpp-fonts-data');
                    var uploadedFonts = [];
                    if (fontsDataDiv) {
                        try { 
                            uploadedFonts = JSON.parse(fontsDataDiv.getAttribute('data-fonts') || '[]'); 
                        } catch(e) { 
                            uploadedFonts = []; 
                            if (window.CKPP_DEBUG_MODE) {
                                console.error('CKPP Debug: Error parsing fonts data', e);
                            }
                        }
                    }
                    
                    // Get web safe fonts list
                    var webSafeFonts = [
                        { name: 'Arial', value: 'Arial, Helvetica, sans-serif' },
                        { name: 'Georgia', value: 'Georgia, serif' },
                        { name: 'Impact', value: 'Impact, Charcoal, sans-serif' },
                        { name: 'Tahoma', value: 'Tahoma, Geneva, sans-serif' },
                        { name: 'Times New Roman', value: '"Times New Roman", Times, serif' },
                        { name: 'Trebuchet MS', value: '"Trebuchet MS", Helvetica, sans-serif' },
                        { name: 'Verdana', value: 'Verdana, Geneva, sans-serif' }
                    ];
                    
                    // Check if it's a web safe font first
                    var webSafeFont = webSafeFonts.find(function(f) { 
                        return f.value === selectedFont || f.name === selectedFont; 
                    });
                    
                    if (webSafeFont) {
                        if (window.CKPP_DEBUG_MODE) {
                            console.log('CKPP Debug: Using web safe font:', webSafeFont);
                        }
                        // Apply web safe font immediately
                        applyFont(webSafeFont.value);
                        return;
                    }
                    
                    // If not web safe, check if it's a custom font
                    var customFont = uploadedFonts.find(function(f) { 
                        return f.name === selectedFont; 
                    });
                    
                    // Function to apply the font after ensuring it's loaded
                    function applyFont(fontValue) {
                        try {
                            if (window.CKPP_DEBUG_MODE) {
                                console.log('CKPP Debug: Applying font with value:', fontValue);
                            }
                            
                            // Apply to selected object
                            sel.set({
                                'fontFamily': fontValue,
                                dirty: true
                            });
                            
                            // Force object boundaries to update
                            sel.setCoords();
                            
                            // Request full canvas redraw
                            fabricCanvas.requestRenderAll();
                            
                            if (window.CKPP_DEBUG_MODE) {
                                console.log('CKPP Debug: Applied font', fontValue, 'to object', sel);
                            }
                            
                            // Clear message and trigger save
                            if (msgSpan) {
                                msgSpan.textContent = 'Font applied!';
                                setTimeout(function() { 
                                    if (msgSpan && msgSpan.textContent === 'Font applied!') {
                                        msgSpan.textContent = '';
                                    }
                                }, 1000);
                            }
                            
                            // Trigger save after short delay
                            setTimeout(function() {
                                fabricCanvas.fire('object:modified', { target: sel });
                                if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
                            }, 100);
                            
                        } catch (err) {
                            if (window.CKPP_DEBUG_MODE) {
                                console.error('CKPP Debug: Error applying font', fontValue, err);
                            }
                            if (msgSpan) {
                                msgSpan.textContent = 'Error applying font';
                                setTimeout(function() { msgSpan.textContent = ''; }, 2000);
                            }
                        }
                    }
                    
                    if (customFont) {
                        // For custom fonts, we need to ensure the font is loaded
                        if (window.CKPP_DEBUG_MODE) {
                            console.log('CKPP Debug: Found custom font:', customFont);
                        }
                        
                        // Check if already preloaded
                        if (window.CKPP_LOADED_FONTS[customFont.name]) {
                            if (window.CKPP_DEBUG_MODE) {
                                console.log('CKPP Debug: Font already preloaded:', customFont.name);
                            }
                            // Apply immediately since it's already loaded
                            applyFont(customFont.name);
                        } else {
                            // Font not preloaded, need to load it first
                            if (window.CKPP_DEBUG_MODE) {
                                console.log('CKPP Debug: Font not preloaded, loading:', customFont.name);
                            }
                            
                            // 1. Add the font-face style if not already present
                            var styleId = 'ckpp-font-' + customFont.name.replace(/[^a-zA-Z0-9_-]/g, '');
                            if (!document.getElementById(styleId)) {
                                var style = document.createElement('style');
                                style.id = styleId;
                                style.textContent = `@font-face { font-family: '${customFont.name}'; src: url('${customFont.url}'); font-display: swap; }`;
                                document.head.appendChild(style);
                                
                                if (window.CKPP_DEBUG_MODE) {
                                    console.log('CKPP Debug: Added @font-face style for', customFont.name);
                                }
                            }
                            
                            // 2. Force load the font and apply
                            if (document.fonts && document.fonts.load) {
                                if (msgSpan) msgSpan.textContent = 'Loading custom font...';
                                
                                // Create a small div to force font loading
                                var tempDiv = document.createElement('div');
                                tempDiv.style.fontFamily = "'" + customFont.name + "'";
                                tempDiv.style.visibility = 'hidden';
                                tempDiv.style.position = 'absolute';
                                tempDiv.style.top = '-9999px';
                                tempDiv.innerHTML = 'Font Loading Test';
                                document.body.appendChild(tempDiv);
                                
                                // Use the Fonts API to explicitly load the font
                                document.fonts.load('16px "' + customFont.name + '"')
                                    .then(function(loadedFonts) {
                                        if (window.CKPP_DEBUG_MODE) {
                                            console.log('CKPP Debug: Font loaded via API:', loadedFonts, customFont.name);
                                        }
                                        
                                        // Mark as loaded
                                        window.CKPP_LOADED_FONTS[customFont.name] = true;
                                        
                                        // Apply the font once loaded
                                        applyFont(customFont.name);
                                        
                                        // Clean up
                                        document.body.removeChild(tempDiv);
                                    })
                                    .catch(function(err) {
                                        if (window.CKPP_DEBUG_MODE) {
                                            console.error('CKPP Debug: Font load error via API:', err, customFont.name);
                                        }
                                        
                                        // Mark as loaded anyway
                                        window.CKPP_LOADED_FONTS[customFont.name] = true;
                                        
                                        // Apply anyway, it might still work
                                        applyFont(customFont.name);
                                        
                                        // Clean up
                                        document.body.removeChild(tempDiv);
                                    });
                            } else {
                                // For browsers without the fonts API, use a timeout as a fallback
                                if (window.CKPP_DEBUG_MODE) {
                                    console.log('CKPP Debug: Font API not available, using timeout fallback for', customFont.name);
                                }
                                
                                // Create a forcing element
                                var tempDiv = document.createElement('div');
                                tempDiv.style.fontFamily = "'" + customFont.name + "'";
                                tempDiv.style.visibility = 'hidden';
                                tempDiv.style.position = 'absolute';
                                tempDiv.style.top = '-9999px';
                                tempDiv.innerHTML = 'Font Loading Test';
                                document.body.appendChild(tempDiv);
                                
                                // Give the font some time to load
                                setTimeout(function() {
                                    // Mark as loaded
                                    window.CKPP_LOADED_FONTS[customFont.name] = true;
                                    
                                    // Apply the font
                                    applyFont(customFont.name);
                                    
                                    // Clean up
                                    document.body.removeChild(tempDiv);
                                }, 500);
                            }
                        }
                    } else {
                        // Not a custom font or web safe font, just apply as is
                        if (window.CKPP_DEBUG_MODE) {
                            console.log('CKPP Debug: Using unknown font, applying directly:', selectedFont);
                        }
                        applyFont(selectedFont);
                    }
                };
                
                // Also add a focus/click event to make it clear the selection is being processed
                fontFamilyInput.addEventListener('focus', function() {
                    var msgSpan = document.getElementById('ckpp-template-msg');
                    if (msgSpan) msgSpan.textContent = 'Select a font...';
                });
                
                fontFamilyInput.addEventListener('blur', function() {
                    var msgSpan = document.getElementById('ckpp-template-msg');
                    if (msgSpan && msgSpan.textContent === 'Select a font...') {
                        msgSpan.textContent = '';
                    }
                });
            }
            var fontSizeInput = document.getElementById('ckpp-prop-font-size');
            if (fontSizeInput) {
                fontSizeInput.oninput = function() {
                    var size = Math.max(6, parseInt(fontSizeInput.value) || 24);
                    sel.set('fontSize', size);
                    
                    // Force immediate canvas update
                    sel.setCoords();
                    fabricCanvas.requestRenderAll();
                    
                    // Debounced save after brief delay
                    if (saveTimeout) clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(function() {
                        fabricCanvas.fire('object:modified', { target: sel });
                        if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
                    }, 300);
                };
                
                // Add keydown handler to apply changes on Enter key
                fontSizeInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        var size = Math.max(6, parseInt(fontSizeInput.value) || 24);
                        sel.set('fontSize', size);
                        sel.setCoords();
                        fabricCanvas.requestRenderAll();
                        fabricCanvas.fire('object:modified', { target: sel });
                        if (typeof saveCanvasConfig === 'function') saveCanvasConfig();
                        fontSizeInput.blur();
                    }
                });
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
                
                // Handle save event (when user clicks the save button)
                window.ckppPickr.on('save', function(color) {
                    if (!color) return;
                    
                    var hex = color.toHEXA().toString();
                    sel.set('fill', hex);
                    sel.setCoords();
                    sel.dirty = true;
                    
                    // Force immediate canvas update
                    fabricCanvas.renderAll();
                    
                    // Visual feedback
                    var msgSpan = document.getElementById('ckpp-template-msg');
                    if (msgSpan) {
                        msgSpan.textContent = 'Color saved!';
                        setTimeout(function() { msgSpan.textContent = ''; }, 800);
                    }
                    
                    // Signal the change for saving
                    fabricCanvas.fire('object:modified', { target: sel });
                    window.ckppPickr.hide();
                });
                
                // Handle live color changes in the picker
                window.ckppPickr.on('change', function(color) {
                    if (!color) return;
                    
                    var hex = color.toHEXA().toString();
                    sel.set('fill', hex);
                    sel.setCoords();
                    sel.dirty = true;
                    
                    // Force immediate canvas update 
                    fabricCanvas.renderAll();
                    
                    // Only trigger save after a small delay to avoid excessive saves during color dragging
                    if (saveTimeout) clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(function() {
                        fabricCanvas.fire('object:modified', { target: sel });
                    }, 200);
                });
                
                // Add additional event handlers for smoother UX
                window.ckppPickr.on('init', function() {
                    if (window.CKPP_DEBUG_MODE) {
                        console.log('CKPP Debug: Color picker initialized with', initialColor);
                    }
                });
                
                window.ckppPickr.on('show', function() {
                    var msgSpan = document.getElementById('ckpp-template-msg');
                    if (msgSpan) {
                        msgSpan.textContent = 'Selecting color...';
                    }
                });
                
                window.ckppPickr.on('hide', function() {
                    var msgSpan = document.getElementById('ckpp-template-msg');
                    if (msgSpan && msgSpan.textContent === 'Selecting color...') {
                        msgSpan.textContent = '';
                    }
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
                        
                        // Provide visual feedback that alignment is changing
                        var msgSpan = document.getElementById('ckpp-template-msg');
                        if (msgSpan) {
                            msgSpan.textContent = 'Aligning text...';
                            setTimeout(function() { msgSpan.textContent = ''; }, 800);
                        }
                        
                        // Apply alignment immediately with proper refresh
                        sel.set({
                            textAlign: align
                        }).setCoords();
                        
                        // Update button styles
                        Array.from(alignGroup.querySelectorAll('button[data-align]')).forEach(function(b) {
                            b.classList.remove('ckpp-align-btn-active');
                            b.style.background = '#f3f3f3';
                        });
                        btn.classList.add('ckpp-align-btn-active');
                        btn.style.background = '#fec610';
                        
                        // Force immediate canvas update with multiple renders if needed
                        fabricCanvas.discardActiveObject();
                        fabricCanvas.renderAll();
                        
                        // Re-select the object to ensure proper refresh
                        setTimeout(() => {
                            fabricCanvas.setActiveObject(sel);
                            fabricCanvas.renderAll();
                        }, 10);
                        
                        // Signal the change for saving
                        fabricCanvas.fire('object:modified', { target: sel });
                        if (typeof saveCanvasConfig === 'function') {
                            saveCanvasConfig();
                        }
                        
                        // Additional render after a short delay to ensure UI updates
                        setTimeout(() => fabricCanvas.renderAll(), 50);
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
            // Required checkbox
            var requiredInput = document.getElementById('ckpp-prop-required');
            if (requiredInput) {
                requiredInput.onchange = function() {
                    sel.required = requiredInput.checked;
                    fabricCanvas.requestRenderAll();
                    fabricCanvas.fire('object:modified', { target: sel });
                };
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
                    // Count existing text objects for unique naming
                    var count = canvas.getObjects().filter(function(obj) {
                        return obj.type === 'i-text' || obj.type === 'textbox' || obj.type === 'text';
                    }).length + 1;
                    var label = 'Text ' + count;
                    var text = new fabric.IText('Text', { fontSize: 24, fill: '#222222', label: label });
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
                    // Count existing text objects for unique naming
                    var count = canvas.getObjects().filter(function(obj) {
                        return obj.type === 'i-text' || obj.type === 'textbox' || obj.type === 'text';
                    }).length + 1;
                    var label = 'Text Box ' + count;
                    
                    // Define fixed dimensions
                    var textboxWidth = 200;
                    var textboxHeight = 40;
                    
                    // Create a text object with fixed dimensions and auto-scaling text
                    var textbox = new fabric.Textbox('Double click to edit', {
                        left: 100,
                        top: 100,
                        width: textboxWidth,
                        height: textboxHeight,
                        fontSize: 24,
                        fontFamily: 'Arial, sans-serif',
                        fill: '#333333',
                        textAlign: 'center',
                        editable: true,
                        splitByGrapheme: false,
                        borderColor: 'rgba(74, 144, 226, 0.3)',
                        cornerColor: '#4a90e2',
                        cornerSize: 8,
                        transparentCorners: false,
                        hasControls: true,
                        hasBorders: true,
                        padding: 8,
                        textBackgroundColor: 'rgba(255, 255, 255, 0.9)',
                        originalFontSize: 24,
                        originalWidth: textboxWidth,
                        originalHeight: textboxHeight,
                        isEditing: false,
                        type: 'textbox',
                        label: 'Text Box ' + count,
                        hoverCursor: 'text',
                        transitionDuration: 150,
                        shadow: new fabric.Shadow({
                            color: 'rgba(0,0,0,0.1)',
                            blur: 3,
                            offsetX: 0,
                            offsetY: 1
                        })
                    });
                    
                    // Add to canvas
                    canvas.add(textbox);
                    canvas.centerObject(textbox);
                    textbox.setCoords();
                    canvas.setActiveObject(textbox);
                    
                    // Set initial scale
                    scaleTextToFit(textbox, canvas);
                    
                    // Handle scaling when text changes
                    textbox.on('changed', function() {
                        scaleTextToFit(textbox, canvas);
                    });

                    // Handle scaling when resized
                    textbox.on('scaling', function() {
                        // Only update width/height based on user scaling, never programmatically
                        textbox.set({
                            width: textbox.width * (textbox.scaleX || 1),
                            height: textbox.height * (textbox.scaleY || 1),
                            scaleX: 1,
                            scaleY: 1
                        });
                        scaleTextToFit(textbox, canvas);
                    });
                    
                    // Make textbox editable on double click
                    textbox.on('mousedblclick', function() {
                        // Create a temporary input element for editing
                        var input = document.createElement('input');
                        input.type = 'text';
                        input.value = textbox.text === 'Double click to edit' ? '' : textbox.text;
                        input.style.position = 'fixed';
                        input.style.left = (canvas.getSelectionElement().getBoundingClientRect().left + textbox.left - 5) + 'px';
                        input.style.top = (canvas.getSelectionElement().getBoundingClientRect().top + textbox.top - 5) + 'px';
                        input.style.width = (textbox.width * textbox.scaleX + 10) + 'px';
                        input.style.height = (textbox.height * textbox.scaleY + 10) + 'px';
                        input.style.fontSize = textbox.fontSize + 'px';
                        input.style.fontFamily = textbox.fontFamily || 'Arial, sans-serif';
                        input.style.textAlign = textbox.textAlign || 'center';
                        input.style.border = '2px solid #4a90e2';
                        input.style.borderRadius = '4px';
                        input.style.padding = '8px 12px';
                        input.style.boxSizing = 'border-box';
                        input.style.zIndex = '1000';
                        input.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
                        input.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.15)';
                        input.style.outline = 'none';
                        input.style.transition = 'all 0.2s ease';
                        input.style.transform = 'scale(1.02)';
                        input.style.opacity = '0';
                        
                        // Add a subtle animation when appearing
                        setTimeout(() => {
                            input.style.opacity = '1';
                            input.style.transform = 'scale(1.05)';
                        }, 10);
                        
                        document.body.appendChild(input);
                        input.focus();
                        
                        // Handle input submission with smooth transition
                        function handleSubmit() {
                            // Animate out
                            input.style.transform = 'scale(0.95)';
                            input.style.opacity = '0';
                            
                            // Update text after animation
                            setTimeout(() => {
                                if (input.parentNode) {
                                    document.body.removeChild(input);
                                }
                                
                                // Only update if text changed
                                if (textbox.text !== input.value) {
                                    textbox.set('text', input.value || ' ');
                                    scaleTextToFit(textbox, canvas);
                                    canvas.renderAll();
                                }
                                
                                // Clean up
                                input.removeEventListener('blur', handleSubmit);
                                input.removeEventListener('keydown', handleKeyDown);
                            }, 150);
                        }
                        
                        // Handle keyboard interactions
                        function handleKeyDown(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                handleSubmit();
                            } else if (e.key === 'Escape') {
                                // Animate out without saving
                                input.style.transform = 'scale(0.95)';
                                input.style.opacity = '0';
                                
                                setTimeout(() => {
                                    if (input.parentNode) {
                                        document.body.removeChild(input);
                                    }
                                    canvas.renderAll();
                                    input.removeEventListener('blur', handleSubmit);
                                    input.removeEventListener('keydown', handleKeyDown);
                                }, 150);
                            }
                        }
                        
                        input.addEventListener('blur', handleSubmit);
                        input.addEventListener('keydown', handleKeyDown);
                    });
                    
                    // Add max length control to properties panel
                    updatePropertiesPanel(textbox, canvas);
                    
                    canvas.requestRenderAll();
                }
            };
            
            // Function to scale text to fit within the current box dimensions (single line, no box resize)
            function scaleTextToFit(textObj, canvas) {
                if (!textObj || !canvas) return;

                // Only ever set fontSize, never width/height
                var boxWidth = textObj.width * (textObj.scaleX || 1);
                var currentText = (textObj.text || ' ').replace(/\n/g, ' '); // enforce single line
                var maxFontSize = textObj.originalFontSize || 48;
                var minFontSize = 8;
                var fontSize = maxFontSize;

                // Binary search for the best font size that fits the box width
                var context = document.createElement('canvas').getContext('2d');
                let low = minFontSize, high = maxFontSize;
                while (low < high) {
                    let mid = Math.floor((low + high + 1) / 2);
                    context.font = mid + 'px ' + (textObj.fontFamily || 'Arial');
                    let w = context.measureText(currentText).width;
                    if (w <= boxWidth - 16) {
                        low = mid;
                    } else {
                        high = mid - 1;
                    }
                }
                fontSize = low;
                if (fontSize < minFontSize) fontSize = minFontSize;

                textObj.set({
                    fontSize: fontSize,
                    scaleX: 1,
                    scaleY: 1,
                    text: currentText
                });
                textObj.setCoords();
                canvas.requestRenderAll();
            }
            // Update properties panel with max length control
            function updatePropertiesPanel(obj, canvas) {
                if (!obj || obj.type !== 'textbox') return;
                
                // Create or update max length control
                var maxLengthControl = document.getElementById('ckpp-prop-maxlength');
                if (!maxLengthControl) {
                    var propsPanel = document.querySelector('.ckpp-properties-panel');
                    if (propsPanel) {
                        var maxLengthHtml = `
                            <div class="ckpp-property-group">
                                <label for="ckpp-prop-maxlength">Max Characters</label>
                                <input type="number" id="ckpp-prop-maxlength" min="1" max="1000" 
                                       value="${obj.maxLength || 100}" class="ckpp-form-control">
                            </div>`;
                        propsPanel.insertAdjacentHTML('beforeend', maxLengthHtml);
                        maxLengthControl = document.getElementById('ckpp-prop-maxlength');
                        
                        maxLengthControl.addEventListener('change', function() {
                            var value = parseInt(this.value) || 100;
                            if (value < 1) value = 1;
                            if (value > 1000) value = 1000;
                            obj.set('maxLength', value);
                            canvas.requestRenderAll();
                            canvas.fire('object:modified', { target: obj });
                        });
                    }
                } else {
                    maxLengthControl.value = obj.maxLength || 100;
                }
            }
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
            // First check if we have the required global variables
            if (!window.CKPPDesigner || !CKPPDesigner.ajaxUrl || !CKPPDesigner.nonce) {
                console.error('CKPP: Missing required global variables for saving. Check if CKPPDesigner is properly initialized.');
                showSaving('Config Error');
                return;
            }
            
            var designId = CKPPDesigner.designId || (window.CKPP_DESIGN_ID ? window.CKPP_DESIGN_ID : 0);
            var title = document.getElementById('ckpp-design-name') ? document.getElementById('ckpp-design-name').value : 'Untitled Design';
            
            if (!title || title.trim() === '') {
                title = 'Untitled Design';
            }
            
            if (window.CKPP_DEBUG_MODE) {
                console.log('CKPP Debug: Saving design ID:', designId, 'Title:', title);
            }
            
            // Get canvas JSON
            var config = JSON.stringify(fabricCanvas.toJSON(['label', 'required']));
            
            // Generate preview as base64 PNG
            var preview = null;
            try {
                preview = fabricCanvas.toDataURL({
                    format: 'png',
                    quality: 0.8,
                    multiplier: 0.5
                });
                
                if (window.CKPP_DEBUG_MODE) {
                    console.log('CKPP Debug: Generated preview image');
                }
            } catch (e) {
                if (window.CKPP_DEBUG_MODE) {
                    console.error('CKPP Debug: Error generating preview', e);
                }
            }
            
            showSaving('Saving...');
            
            $.ajax({
                url: CKPPDesigner.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ckpp_save_design',
                    nonce: CKPPDesigner.nonce,
                    designId: designId,
                    title: title,
                    config: config,
                    preview: preview
                },
                timeout: 30000, // 30 second timeout
                success: function(resp) {
                    if (window.CKPP_DEBUG_MODE) {
                        console.log('CKPP Debug: Save response', resp);
                    }
                    
                    if (resp && resp.success) {
                        showSaving('Saved');
                        lastSaveStatus = 'Saved';
                        
                        // Update design ID if this was a new design
                        if (resp.data && resp.data.designId && designId === 0) {
                            if (window.CKPP_DESIGN_ID) {
                                window.CKPP_DESIGN_ID = resp.data.designId;
                            }
                            if (window.CKPPDesigner) {
                                window.CKPPDesigner.designId = resp.data.designId;
                            }
                            
                            if (window.CKPP_DEBUG_MODE) {
                                console.log('CKPP Debug: Updated design ID to', resp.data.designId);
                            }
                        }
                    } else {
                        showSaving('Save failed');
                        lastSaveStatus = 'Save failed';
                        console.error('CKPP: Save error', resp);
                    }
                    
                    setTimeout(function() {
                        if (lastSaveStatus === 'Saved') showSaving('');
                    }, 1200);
                },
                error: function(xhr, status, error) {
                    if (window.CKPP_DEBUG_MODE) {
                        console.error('CKPP Debug: Save AJAX failed', status, error, xhr);
                    }
                    showSaving('Save failed');
                    lastSaveStatus = 'Save failed';
                }
            });
        }
        function debouncedSave() {
            if (window.CKPP_DEBUG_MODE) {
                console.log('CKPP: debouncedSave called');
            }
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
                    
                    if (window.CKPP_DEBUG_MODE) {
                        console.log('CKPP Debug: Loading template ID:', tplId);
                    }
                    
                    msgSpan.textContent = 'Loading...';
                    
                    $.ajax({
                        url: CKPPDesigner.ajaxUrl,
                        type: 'GET',
                        data: {
                            action: 'ckpp_load_design',
                            nonce: CKPPDesigner.nonce,
                            designId: tplId
                        },
                        timeout: 30000,
                        success: function(resp) {
                            if (window.CKPP_DEBUG_MODE) {
                                console.log('CKPP Debug: Load template response', resp);
                            }
                            
                            if (resp && resp.success && resp.data && resp.data.config) {
                                try {
                                    var configObj = typeof resp.data.config === 'string' ? JSON.parse(resp.data.config) : resp.data.config;
                                    var canvas = window.ckppFabricCanvas;
                                    
                                    if (!canvas) { 
                                        msgSpan.textContent = 'Canvas not found.'; 
                                        console.error('CKPP: Canvas not found for template loading');
                                        setTimeout(function() { msgSpan.textContent = ''; }, 2000); 
                                        return; 
                                    }
                                    
                                    canvas.loadFromJSON(configObj, function() {
                                        canvas.renderAll();
                                        msgSpan.textContent = 'Template loaded!';
                                        
                                        // Ensure we save the template content to our design
                                        debouncedSave();
                                        
                                        setTimeout(function() { msgSpan.textContent = ''; }, 2000);
                                    });
                                } catch (e) {
                                    msgSpan.textContent = 'Load failed.';
                                    console.error('CKPP: Error loading template:', e);
                                    setTimeout(function() { msgSpan.textContent = ''; }, 2000);
                                }
                            } else {
                                msgSpan.textContent = 'Load failed.';
                                console.error('CKPP: Load failed, invalid response', resp);
                                setTimeout(function() { msgSpan.textContent = ''; }, 2000);
                            }
                        },
                        error: function(xhr, status, error) {
                            msgSpan.textContent = 'Load failed.';
                            console.error('CKPP: AJAX error loading template', status, error);
                            setTimeout(function() { msgSpan.textContent = ''; }, 2000);
                        }
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
            if (typeof this.required !== 'undefined') obj.required = this.required;
            return obj;
        };
        // Custom serialization for text and textbox objects ---
        var origITextToObject = fabric.IText.prototype.toObject;
        fabric.IText.prototype.toObject = function(propertiesToInclude) {
            var obj = origITextToObject.call(this, propertiesToInclude);
            if (this.label) obj.label = this.label;
            if (typeof this.required !== 'undefined') obj.required = this.required;
            return obj;
        };
        var origTextboxToObject = fabric.Textbox.prototype.toObject;
        fabric.Textbox.prototype.toObject = function(propertiesToInclude) {
            var obj = origTextboxToObject.call(this, propertiesToInclude);
            if (this.label) obj.label = this.label;
            if (typeof this.required !== 'undefined') obj.required = this.required;
            return obj;
        };
        // Migration: On design load, assign labels to text objects that lack one
        if (window.ckppFabricCanvas) {
            window.ckppFabricCanvas.getObjects().forEach(function(obj, idx) {
                if ((obj.type === 'i-text' || obj.type === 'textbox' || obj.type === 'text') && !obj.label) {
                    obj.label = 'Text ' + (idx + 1);
                }
            });
        }
    }
    // For modal or in-page designer
    $(document).ready(function() {
        // If CKPP_DESIGN_ID is set, load the config before initializing the designer
        if (window.CKPP_DESIGN_ID && window.CKPPDesigner && CKPPDesigner.ajaxUrl && CKPPDesigner.nonce) {
            if (window.CKPP_DEBUG_MODE) {
                console.log('CKPP Debug: Loading design config for designId', CKPP_DESIGN_ID);
            }
            
            // Show loading indicator
            var designerRoot = document.getElementById('ckpp-product-designer-root');
            if (designerRoot) {
                designerRoot.innerHTML = '<div class="ckpp-loading"><span class="spinner is-active"></span> ' + 
                    '<span>Loading design...</span></div>';
            }
            
            // First preload all fonts, then load the design config
            preloadAllFonts().then(function() {
                // Now load the design config
                $.ajax({
                    url: CKPPDesigner.ajaxUrl,
                    type: 'GET',
                    data: {
                        action: 'ckpp_load_design',
                        nonce: CKPPDesigner.nonce,
                        designId: CKPP_DESIGN_ID
                    },
                    timeout: 30000, // 30 second timeout
                    success: function(resp) {
                        if (window.CKPP_DEBUG_MODE) {
                            console.log('CKPP Debug: Initial load design response', resp);
                        }
                        
                        if (resp && resp.success && resp.data && resp.data.config) {
                            try {
                                // Store the config for use in the designer initialization
                                window.CKPP_INITIAL_CONFIG = resp.data.config;
                                
                                // Update the design title if available
                                if (resp.data.title) {
                                    window.CKPP_DESIGN_TITLE = resp.data.title;
                                }
                                
                                if (window.CKPP_DEBUG_MODE) {
                                    console.log('CKPP Debug: Successfully loaded initial config');
                                }
                            } catch (e) {
                                console.error('CKPP: Error processing initial config', e);
                                window.CKPP_INITIAL_CONFIG = null;
                                showLoadError(designerRoot);
                            }
                        } else {
                            console.error('CKPP: Failed to load initial config, invalid response', resp);
                            window.CKPP_INITIAL_CONFIG = null;
                            showLoadError(designerRoot);
                        }
                        
                        // Always render the designer, even if loading failed
                        renderMinimalDesigner();
                    },
                    error: function(xhr, status, error) {
                        console.error('CKPP: AJAX error loading initial config', status, error, xhr);
                        window.CKPP_INITIAL_CONFIG = null;
                        showLoadError(designerRoot);
                        renderMinimalDesigner();
                    }
                });
            });
        } else {
            // No design ID or missing config
            // Still preload fonts before rendering with empty canvas
            preloadAllFonts().then(function() {
                if (window.CKPP_DEBUG_MODE) {
                    if (!window.CKPP_DESIGN_ID) {
                        console.log('CKPP Debug: No design ID provided, starting with empty canvas');
                    } else {
                        console.log('CKPP Debug: Missing CKPPDesigner config, cannot load design');
                    }
                }
                window.CKPP_INITIAL_CONFIG = null;
                renderMinimalDesigner();
            });
        }
        
        // Helper function to show load error
        function showLoadError(container) {
            if (container) {
                var errorDiv = document.createElement('div');
                errorDiv.className = 'notice notice-error';
                errorDiv.innerHTML = '<p>Error loading design. You may continue to use the designer, but your changes may not save correctly.</p>';
                container.insertBefore(errorDiv, container.firstChild);
            }
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