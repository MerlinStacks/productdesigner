console.log('Product Designer JavaScript Loaded');

document.addEventListener('DOMContentLoaded', () => {
    const canvasArea = document.getElementById('product_personalization_canvas_area');
    const propertiesPanel = document.getElementById('personalization_area_properties_panel');
    const areaNameInput = document.getElementById('personalization_area_name');
    const fontSelect = document.getElementById('personalization_area_font');
    const colorSelect = document.getElementById('personalization_area_color');

    if (!canvasArea || !propertiesPanel || !areaNameInput || !fontSelect || !colorSelect) {
        console.warn('Required elements for product designer not found. Canvas:', !!canvasArea, 'Panel:', !!propertiesPanel, 'NameInput:', !!areaNameInput, 'FontSelect:', !!fontSelect, 'ColorSelect:', !!colorSelect);
        return;
    }

    console.log('Product Personalization Canvas Area, Properties Panel, Font Select, and Color Select Found.');

    if (getComputedStyle(canvasArea).position === 'static') {
        canvasArea.style.position = 'relative';
        console.log('Set canvasArea position to relative.');
    }

    let isDrawing = false;
    let startX, startY;
    let currentDrawingRectangleElement = null; // The DOM element being drawn
    const drawnRectanglesData = []; // Array to store data of all drawn rectangles { x, y, width, height, name, element }
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

    // Prevent dragging image if canvasArea contains an image
    const img = canvasArea.querySelector('img');
    if (img) {
        img.addEventListener('dragstart', (e) => e.preventDefault());
    }
});