console.log('Product Designer JavaScript Loaded');

document.addEventListener('DOMContentLoaded', () => {
    const canvasArea = document.getElementById('product_personalization_canvas_area');
    const propertiesPanel = document.getElementById('personalization_area_properties_panel');
    const areaNameInput = document.getElementById('personalization_area_name');
    const fontSelect = document.getElementById('personalization_area_font');
    const colorSelect = document.getElementById('personalization_area_color');
    const typeSelect = document.getElementById('personalization_area_type');
    const textOptionsPanel = document.getElementById('text_options_panel');
    const textDefaultInput = document.getElementById('personalization_text_default');
    const textMaxLengthInput = document.getElementById('personalization_text_maxlength');
    const imageOptionsPanel = document.getElementById('image_options_panel'); // New
    const imageClipartSelect = document.getElementById('personalization_image_clipart_select'); // New

    if (!canvasArea || !propertiesPanel || !areaNameInput || !fontSelect || !colorSelect || !typeSelect || !textOptionsPanel || !textDefaultInput || !textMaxLengthInput || !imageOptionsPanel || !imageClipartSelect) {
        console.warn('Required elements for product designer not found.', {
            canvasArea: !!canvasArea,
            propertiesPanel: !!propertiesPanel,
            areaNameInput: !!areaNameInput,
            fontSelect: !!fontSelect,
            colorSelect: !!colorSelect,
            typeSelect: !!typeSelect,
            textOptionsPanel: !!textOptionsPanel,
            textDefaultInput: !!textDefaultInput,
            textMaxLengthInput: !!textMaxLengthInput,
            imageOptionsPanel: !!imageOptionsPanel, // New
            imageClipartSelect: !!imageClipartSelect // New
        });
        return;
    }

    console.log('Product Personalization Canvas Area, Properties Panel, and all input/select elements Found.');

    if (getComputedStyle(canvasArea).position === 'static') {
        canvasArea.style.position = 'relative';
        console.log('Set canvasArea position to relative.');
    }

    let isDrawing = false;
    let startX, startY;
    let currentDrawingRectangleElement = null; // The DOM element being drawn
    let drawnRectanglesData = []; // Array to store data of all drawn rectangles { x, y, width, height, name, element } - Will be initialized later
    let selectedRectangleData = null; // The data object of the selected rectangle
    
    const styleDefaults = {
        border: '1px solid #ccc',
        backgroundColor: 'rgba(0, 0, 0, 0.2)',
    };
    const styleSelected = {
        border: '2px solid #007bff',
        backgroundColor: 'rgba(0, 123, 255, 0.25)',
    };
    const styleDrawing = {
        border: '1px dashed #000',
        backgroundColor: 'rgba(0, 0, 0, 0.1)',
    };

    function applyStyle(element, style) {
        element.style.border = style.border;
        element.style.backgroundColor = style.backgroundColor;
    }

    function updateTextPreview(rectData) {
        if (!rectData || !rectData.element || rectData.type !== 'text') {
            if (rectData && rectData.element) clearTextPreview(rectData);
            return;
        }

        let previewElement = rectData.element.querySelector('.text-preview-element');
        if (!previewElement) {
            previewElement = document.createElement('span');
            previewElement.className = 'text-preview-element';
            previewElement.style.position = 'absolute'; 
            previewElement.style.top = '0';
            previewElement.style.left = '0';
            previewElement.style.width = '100%';
            previewElement.style.height = '100%';
            previewElement.style.display = 'flex';
            previewElement.style.alignItems = 'center'; 
            previewElement.style.justifyContent = 'center'; 
            previewElement.style.padding = '2px'; 
            previewElement.style.boxSizing = 'border-box';
            previewElement.style.overflow = 'hidden'; 
            previewElement.style.pointerEvents = 'none'; 
            rectData.element.appendChild(previewElement);
            rectData.element.style.overflow = 'hidden'; 
        }

        previewElement.innerText = rectData.text_options?.default_text || '';
        previewElement.style.color = rectData.color_hex || '#000000'; 
        previewElement.style.fontSize = '12px'; // Default font size for now

        if (rectData.font_id && typeof ppDesignerData !== 'undefined' && ppDesignerData.fonts) {
            const fontInfo = ppDesignerData.fonts.find(f => String(f.id) === String(rectData.font_id));
            if (fontInfo && fontInfo.font_family) {
                previewElement.style.fontFamily = fontInfo.font_family;
            } else {
                previewElement.style.fontFamily = 'sans-serif'; // Fallback
            }
        } else {
            previewElement.style.fontFamily = 'sans-serif'; // Fallback
        }
        console.log('Updated text preview for:', rectData.name, previewElement.innerText);
    }

    function clearTextPreview(rectData) {
        if (rectData && rectData.element) {
            const previewElement = rectData.element.querySelector('.text-preview-element');
            if (previewElement) {
                previewElement.remove();
            }
        }
    }

    function selectRectangle(rectData) {
        if (selectedRectangleData && selectedRectangleData.element) {
            applyStyle(selectedRectangleData.element, styleDefaults); // Deselect previous
        }
        selectedRectangleData = rectData;
        if (selectedRectangleData && selectedRectangleData.element) {
            applyStyle(selectedRectangleData.element, styleSelected);
            areaNameInput.value = selectedRectangleData.name || '';
            fontSelect.value = selectedRectangleData.font_id || '';
            colorSelect.value = selectedRectangleData.color_hex || selectedRectangleData.color_id || ''; 
            
            typeSelect.value = selectedRectangleData.type || '';
            
            textOptionsPanel.style.display = 'none';
            imageOptionsPanel.style.display = 'none';

            if (selectedRectangleData.type === 'text') {
                textOptionsPanel.style.display = 'block';
                if (selectedRectangleData.text_options) {
                    textDefaultInput.value = selectedRectangleData.text_options.default_text || '';
                    textMaxLengthInput.value = selectedRectangleData.text_options.max_length || '';
                } else {
                    textDefaultInput.value = '';
                    textMaxLengthInput.value = '';
                }
                updateTextPreview(selectedRectangleData); 
            } else if (selectedRectangleData.type === 'image') {
                imageOptionsPanel.style.display = 'block';
                imageClipartSelect.value = selectedRectangleData.clipart_id || '';
                clearTextPreview(selectedRectangleData); 
            } else {
                textDefaultInput.value = '';
                textMaxLengthInput.value = '';
                imageClipartSelect.value = '';
                clearTextPreview(selectedRectangleData); 
            }

            propertiesPanel.style.display = 'block';
            console.log('Selected rectangle:', selectedRectangleData);
        }
    }

    function deselectRectangle() {
        if (selectedRectangleData && selectedRectangleData.element) {
            applyStyle(selectedRectangleData.element, styleDefaults);
            // No need to explicitly clear preview here, will be handled by next selection or if type changes
        }
        selectedRectangleData = null;
        propertiesPanel.style.display = 'none';
        areaNameInput.value = '';
        fontSelect.value = '';
        colorSelect.value = '';
        typeSelect.value = '';
        textOptionsPanel.style.display = 'none';
        textDefaultInput.value = '';
        textMaxLengthInput.value = '';
        imageOptionsPanel.style.display = 'none'; 
        imageClipartSelect.value = ''; 
        console.log('Deselected rectangle.');
    }


    canvasArea.addEventListener('mousedown', (e) => {
        if (e.target.classList.contains('drawn-personalization-rectangle')) {
            const clickedRectElement = e.target;
            const rectData = drawnRectanglesData.find(r => r.element === clickedRectElement);
            if (rectData) {
                selectRectangle(rectData);
            }
            return; 
        }

        if (e.target === canvasArea || (e.target.tagName === 'IMG' && e.target.parentElement === canvasArea)) {
            if (selectedRectangleData) {
                deselectRectangle();
            }

            isDrawing = true;
            startX = e.offsetX;
            startY = e.offsetY;

            currentDrawingRectangleElement = document.createElement('div');
            currentDrawingRectangleElement.style.position = 'absolute';
            applyStyle(currentDrawingRectangleElement, styleDrawing);
            currentDrawingRectangleElement.style.left = startX + 'px';
            currentDrawingRectangleElement.style.top = startY + 'px';
            currentDrawingRectangleElement.style.width = '0px';
            currentDrawingRectangleElement.style.height = '0px';
            currentDrawingRectangleElement.classList.add('drawn-personalization-rectangle');
            canvasArea.appendChild(currentDrawingRectangleElement);
            console.log('Mousedown - Start drawing:', { startX, startY });
        }
    });

    canvasArea.addEventListener('mousemove', (e) => {
        if (!isDrawing || !currentDrawingRectangleElement) return;

        const currentX = e.offsetX;
        const currentY = e.offsetY;

        const width = Math.abs(currentX - startX);
        const height = Math.abs(currentY - startY);
        const newLeft = Math.min(currentX, startX);
        const newTop = Math.min(currentY, startY);

        currentDrawingRectangleElement.style.width = width + 'px';
        currentDrawingRectangleElement.style.height = height + 'px';
        currentDrawingRectangleElement.style.left = newLeft + 'px';
        currentDrawingRectangleElement.style.top = newTop + 'px';
    });

    canvasArea.addEventListener('mouseup', (e) => {
        if (!isDrawing || !currentDrawingRectangleElement) return;
        isDrawing = false;

        const finalWidth = parseInt(currentDrawingRectangleElement.style.width, 10);
        const finalHeight = parseInt(currentDrawingRectangleElement.style.height, 10);

        if (finalWidth > 5 && finalHeight > 5) { 
            const newRectData = {
                x: parseInt(currentDrawingRectangleElement.style.left, 10),
                y: parseInt(currentDrawingRectangleElement.style.top, 10),
                width: finalWidth,
                height: finalHeight,
                name: '', 
                element: currentDrawingRectangleElement,
                type: 'text', // Default new areas to text, or make this selectable
                text_options: { default_text: '', max_length: '' } // Initialize text options
            };
            drawnRectanglesData.push(newRectData);
            
            selectRectangle(newRectData); 

            console.log('Mouseup - Rectangle finalized and selected:', newRectData);
            console.log('All drawn rectangles:', drawnRectanglesData);
        } else {
            canvasArea.removeChild(currentDrawingRectangleElement);
            console.log('Mouseup - Rectangle too small, removed.');
        }
        currentDrawingRectangleElement = null; 
    });

    areaNameInput.addEventListener('input', () => { 
        if (selectedRectangleData) {
            selectedRectangleData.name = areaNameInput.value;
            console.log('Updated rectangle name. All rectangles:', drawnRectanglesData);
        }
    });

    fontSelect.addEventListener('change', () => {
        if (selectedRectangleData) {
            selectedRectangleData.font_id = fontSelect.value;
            console.log('Updated rectangle font_id. All rectangles:', drawnRectanglesData);
            if (selectedRectangleData.type === 'text') {
                updateTextPreview(selectedRectangleData);
            }
        }
    });

    colorSelect.addEventListener('change', () => {
        if (selectedRectangleData) {
            selectedRectangleData.color_hex = colorSelect.value;
            console.log('Updated rectangle color_hex/color_id. All rectangles:', drawnRectanglesData);
            if (selectedRectangleData.type === 'text') {
                updateTextPreview(selectedRectangleData);
            }
        }
    });

    typeSelect.addEventListener('change', () => {
        if (selectedRectangleData) {
            selectedRectangleData.type = typeSelect.value;

            textOptionsPanel.style.display = 'none';
            imageOptionsPanel.style.display = 'none';

            if (typeSelect.value === 'text') {
                textOptionsPanel.style.display = 'block';
                if (!selectedRectangleData.text_options) {
                    selectedRectangleData.text_options = { default_text: '', max_length: '' };
                }
                delete selectedRectangleData.clipart_id;
                updateTextPreview(selectedRectangleData); 
            } else if (typeSelect.value === 'image') {
                imageOptionsPanel.style.display = 'block';
                if (typeof selectedRectangleData.clipart_id === 'undefined') {
                     selectedRectangleData.clipart_id = ''; 
                }
                delete selectedRectangleData.text_options;
                clearTextPreview(selectedRectangleData); 
            } else {
                delete selectedRectangleData.text_options;
                delete selectedRectangleData.clipart_id;
                clearTextPreview(selectedRectangleData); 
            }
            console.log('Updated rectangle type. All rectangles:', drawnRectanglesData);
            console.log('Selected rectangle data:', selectedRectangleData);
        }
    });

    textDefaultInput.addEventListener('input', () => {
        if (selectedRectangleData && selectedRectangleData.type === 'text') {
            if (!selectedRectangleData.text_options) {
                selectedRectangleData.text_options = {};
            }
            selectedRectangleData.text_options.default_text = textDefaultInput.value;
            updateTextPreview(selectedRectangleData);
            console.log('Updated text_options.default_text. All rectangles:', drawnRectanglesData);
            console.log('Selected rectangle data:', selectedRectangleData);
        }
    });

    textMaxLengthInput.addEventListener('input', () => {
        if (selectedRectangleData && selectedRectangleData.type === 'text') {
            if (!selectedRectangleData.text_options) {
                selectedRectangleData.text_options = {};
            }
            selectedRectangleData.text_options.max_length = textMaxLengthInput.value;
            // No visual preview update needed for max_length
            console.log('Updated text_options.max_length. All rectangles:', drawnRectanglesData);
            console.log('Selected rectangle data:', selectedRectangleData);
        }
    });

    imageClipartSelect.addEventListener('change', () => {
        if (selectedRectangleData && selectedRectangleData.type === 'image') {
            selectedRectangleData.clipart_id = imageClipartSelect.value;
            // Future: Update image preview if implementing clipart preview
            console.log('Updated clipart_id. All rectangles:', drawnRectanglesData);
            console.log('Selected rectangle data:', selectedRectangleData);
        }
    });

    const img = canvasArea.querySelector('img');
    if (img) {
        img.addEventListener('dragstart', (e) => e.preventDefault());
    }

    function redrawAreaFromData(areaData) {
        const rectElement = document.createElement('div');
        rectElement.style.position = 'absolute';
        rectElement.style.left = areaData.x + 'px';
        rectElement.style.top = areaData.y + 'px';
        rectElement.style.width = areaData.width + 'px';
        rectElement.style.height = areaData.height + 'px';
        rectElement.classList.add('drawn-personalization-rectangle');
        applyStyle(rectElement, styleDefaults); 

        canvasArea.appendChild(rectElement);
        areaData.element = rectElement;

        if (areaData.type === 'text') {
            updateTextPreview(areaData); 
        }
        return areaData; 
    }

    function initializeDesigner() {
        if (typeof ppDesignerData !== 'undefined' && ppDesignerData.saved_config) {
            try {
                const parsedConfig = JSON.parse(ppDesignerData.saved_config);
                if (Array.isArray(parsedConfig) && parsedConfig.length > 0) {
                    drawnRectanglesData = parsedConfig.map(area => redrawAreaFromData(area)); 
                    console.log('Designer initialized with saved configuration:', drawnRectanglesData);
                } else {
                    drawnRectanglesData = []; 
                    console.log('Saved configuration is empty or invalid. Initializing with no areas.');
                }
            } catch (error) {
                console.error('Error parsing saved configuration:', error);
                drawnRectanglesData = []; 
            }
        } else {
            drawnRectanglesData = []; 
            console.log('No saved configuration found. Initializing with no areas.');
        }
    }

    initializeDesigner(); 

    const saveButton = document.getElementById('save_personalization_config_button');
    const saveStatusSpan = document.getElementById('personalization_save_status');

    if (saveButton && saveStatusSpan) {
        saveButton.addEventListener('click', () => {
            console.log('Save Configuration button clicked.');
            saveStatusSpan.textContent = 'Saving...';
            saveButton.disabled = true;

            const productId = ppDesignerData.product_id;
            const nonce = ppDesignerData.save_nonce;
            const ajaxUrl = ppDesignerData.ajax_url;
            const action = ppDesignerData.save_action;

            const configDataToSave = drawnRectanglesData.map(rect => {
                const { element, ...rest } = rect;
                return rest;
            });

            const data = new URLSearchParams();
            data.append('action', action);
            data.append('nonce', nonce);
            data.append('product_id', productId);
            data.append('config_data', JSON.stringify(configDataToSave));

            console.log('Sending AJAX request with data:', {
                action: action,
                nonce: nonce,
                product_id: productId,
                config_data: JSON.stringify(configDataToSave)
            });

            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: data,
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; }).catch(() => {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(result => {
                console.log('AJAX Success:', result);
                if (result.success) {
                    saveStatusSpan.textContent = result.data.message || 'Saved successfully!';
                    saveStatusSpan.style.color = 'green';
                } else {
                    saveStatusSpan.textContent = 'Error: ' + (result.data.message || 'Unknown error');
                    saveStatusSpan.style.color = 'red';
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                let errorMessage = 'AJAX request failed.';
                if (error && error.message) {
                    errorMessage = error.message;
                } else if (typeof error === 'string') {
                    errorMessage = error;
                }
                saveStatusSpan.textContent = 'Error: ' + errorMessage;
                saveStatusSpan.style.color = 'red';
            })
            .finally(() => {
                saveButton.disabled = false;
                setTimeout(() => {
                    saveStatusSpan.textContent = '';
                }, 5000);
            });
        });
    } else {
        console.warn('Save button or status span not found.');
        if (!saveButton) console.warn('Save button (#save_personalization_config_button) is missing.');
        if (!saveStatusSpan) console.warn('Status span (#personalization_save_status) is missing.');
    }
});