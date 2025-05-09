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

    if (!canvasArea || !propertiesPanel || !areaNameInput || !fontSelect || !colorSelect || !typeSelect || !textOptionsPanel || !textDefaultInput || !textMaxLengthInput) {
        console.warn('Required elements for product designer not found.', {
            canvasArea: !!canvasArea,
            propertiesPanel: !!propertiesPanel,
            areaNameInput: !!areaNameInput,
            fontSelect: !!fontSelect,
            colorSelect: !!colorSelect,
            typeSelect: !!typeSelect,
            textOptionsPanel: !!textOptionsPanel,
            textDefaultInput: !!textDefaultInput,
            textMaxLengthInput: !!textMaxLengthInput
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

    function selectRectangle(rectData) {
        if (selectedRectangleData && selectedRectangleData.element) {
            applyStyle(selectedRectangleData.element, styleDefaults); // Deselect previous
        }
        selectedRectangleData = rectData;
        if (selectedRectangleData && selectedRectangleData.element) {
            applyStyle(selectedRectangleData.element, styleSelected);
            areaNameInput.value = selectedRectangleData.name || '';
            // Populate/Select on Area Selection
            fontSelect.value = selectedRectangleData.font_id || '';
            colorSelect.value = selectedRectangleData.color_hex || selectedRectangleData.color_id || ''; // Prioritize hex if available
            
            // Populate Personalization Type and Text Options
            typeSelect.value = selectedRectangleData.type || '';
            if (selectedRectangleData.type === 'text') {
                textOptionsPanel.style.display = 'block';
                if (selectedRectangleData.text_options) {
                    textDefaultInput.value = selectedRectangleData.text_options.default_text || '';
                    textMaxLengthInput.value = selectedRectangleData.text_options.max_length || '';
                } else {
                    textDefaultInput.value = '';
                    textMaxLengthInput.value = '';
                }
            } else {
                textOptionsPanel.style.display = 'none';
                textDefaultInput.value = '';
                textMaxLengthInput.value = '';
            }

            propertiesPanel.style.display = 'block';
            console.log('Selected rectangle:', selectedRectangleData);
        }
    }

    function deselectRectangle() {
        if (selectedRectangleData && selectedRectangleData.element) {
            applyStyle(selectedRectangleData.element, styleDefaults);
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
        console.log('Deselected rectangle.');
    }

    canvasArea.addEventListener('mousedown', (e) => {
        // If the click is on an existing drawn rectangle, its own listener will handle it.
        if (e.target.classList.contains('drawn-personalization-rectangle')) {
            // Find the data object for the clicked rectangle and select it
            const clickedRectElement = e.target;
            const rectData = drawnRectanglesData.find(r => r.element === clickedRectElement);
            if (rectData) {
                selectRectangle(rectData);
            }
            return; 
        }

        // If click is on canvas (not a drawn rectangle), deselect current and start drawing
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

        if (finalWidth > 5 && finalHeight > 5) { // Minimum size for a rectangle
            const newRectData = {
                x: parseInt(currentDrawingRectangleElement.style.left, 10),
                y: parseInt(currentDrawingRectangleElement.style.top, 10),
                width: finalWidth,
                height: finalHeight,
                name: '', // Initialize with an empty name
                element: currentDrawingRectangleElement 
            };
            drawnRectanglesData.push(newRectData);
            
            // The new rectangle is now selected
            selectRectangle(newRectData); 

            console.log('Mouseup - Rectangle finalized and selected:', newRectData);
            console.log('All drawn rectangles:', drawnRectanglesData);
        } else {
            canvasArea.removeChild(currentDrawingRectangleElement);
            console.log('Mouseup - Rectangle too small, removed.');
        }
        currentDrawingRectangleElement = null; // Reset for next drawing
    });

    areaNameInput.addEventListener('input', () => { // 'input' for real-time update, or 'blur'
        if (selectedRectangleData) {
            selectedRectangleData.name = areaNameInput.value;
            console.log('Updated rectangle name. All rectangles:', drawnRectanglesData);
        }
    });

    fontSelect.addEventListener('change', () => {
        if (selectedRectangleData) {
            selectedRectangleData.font_id = fontSelect.value;
            console.log('Updated rectangle font_id. All rectangles:', drawnRectanglesData);
        }
    });

    colorSelect.addEventListener('change', () => {
        if (selectedRectangleData) {
            // Assuming we store hex code directly. If it's an ID, adjust accordingly.
            selectedRectangleData.color_hex = colorSelect.value;
            // If you need to store color_id as well, you might need to adjust how options are valued or fetch it
            // For now, we'll assume the value of the color select is the hex code or a relevant ID.
            // If it's an ID, you might name the property `color_id` instead of `color_hex`.
            console.log('Updated rectangle color_hex/color_id. All rectangles:', drawnRectanglesData);
        }
    });

    typeSelect.addEventListener('change', () => {
        if (selectedRectangleData) {
            selectedRectangleData.type = typeSelect.value;
            if (typeSelect.value === 'text') {
                textOptionsPanel.style.display = 'block';
                // Initialize text_options if it doesn't exist
                if (!selectedRectangleData.text_options) {
                    selectedRectangleData.text_options = { default_text: '', max_length: '' };
                }
            } else {
                textOptionsPanel.style.display = 'none';
                // Optionally clear text_options or handle as needed
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
            console.log('Updated text_options.max_length. All rectangles:', drawnRectanglesData);
            console.log('Selected rectangle data:', selectedRectangleData);
        }
    });

    // Prevent dragging image if canvasArea contains an image
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
        applyStyle(rectElement, styleDefaults); // Apply default style

        // Add to canvas
        canvasArea.appendChild(rectElement);

        // Update the areaData object with the created element
        areaData.element = rectElement;

        // Make it selectable - event listener is on canvasArea, so class is enough
        // console.log('Redrawn area from data:', areaData);
        return areaData; // Return the updated areaData with the element
    }

    function initializeDesigner() {
        if (typeof ppDesignerData !== 'undefined' && ppDesignerData.saved_config) {
            try {
                const parsedConfig = JSON.parse(ppDesignerData.saved_config);
                if (Array.isArray(parsedConfig) && parsedConfig.length > 0) {
                    drawnRectanglesData = parsedConfig.map(area => redrawAreaFromData(area));
                    console.log('Designer initialized with saved configuration:', drawnRectanglesData);
                } else {
                    drawnRectanglesData = []; // Initialize as empty if config is empty or invalid
                    console.log('Saved configuration is empty or invalid. Initializing with no areas.');
                }
            } catch (error) {
                console.error('Error parsing saved configuration:', error);
                drawnRectanglesData = []; // Initialize as empty on error
            }
        } else {
            drawnRectanglesData = []; // Initialize as empty if no saved config
            console.log('No saved configuration found. Initializing with no areas.');
        }
    }

    initializeDesigner(); // Call this to load config on DOMContentLoaded

    // Save Configuration Button Logic
    const saveButton = document.getElementById('save_personalization_config_button');
    const saveStatusSpan = document.getElementById('personalization_save_status');

    if (saveButton && saveStatusSpan) {
        saveButton.addEventListener('click', () => {
            console.log('Save Configuration button clicked.');
            saveStatusSpan.textContent = 'Saving...';
            saveButton.disabled = true;

            // Prepare data for AJAX request
            // The ppDesignerData object is available due to wp_localize_script
            const productId = ppDesignerData.product_id;
            const nonce = ppDesignerData.save_nonce;
            const ajaxUrl = ppDesignerData.ajax_url;
            const action = ppDesignerData.save_action;

            // We need to send only the data, not the DOM elements
            const configDataToSave = drawnRectanglesData.map(rect => {
                // Create a new object without the 'element' property
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
                    // Try to get error message from response body if it's JSON
                    return response.json().then(err => { throw err; }).catch(() => {
                        // If not JSON, or JSON parsing fails, throw a generic error
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
                // Optionally clear the status message after a few seconds
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