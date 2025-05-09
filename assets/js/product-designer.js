console.log('Product Designer JavaScript Loaded');

document.addEventListener('DOMContentLoaded', () => {
    const canvasArea = document.getElementById('product_personalization_canvas_area');

    if (canvasArea) {
        console.log('Product Personalization Canvas Area Found.');
        // Drawing logic will be added here in subsequent steps.

        // Ensure canvasArea has relative positioning for absolute positioning of drawn rectangles
        if (getComputedStyle(canvasArea).position === 'static') {
            canvasArea.style.position = 'relative';
            console.log('Set canvasArea position to relative.');
        }

        let isDrawing = false;
        let startX, startY;
        let currentRectangle = null;
        const drawnRectangles = [];

        canvasArea.addEventListener('mousedown', (e) => {
            // Check if the click is directly on the canvasArea or its direct image child, not on an existing rectangle
            if (e.target === canvasArea || e.target.tagName === 'IMG' && e.target.parentElement === canvasArea) {
                isDrawing = true;
                startX = e.offsetX;
                startY = e.offsetY;

                currentRectangle = document.createElement('div');
                currentRectangle.style.position = 'absolute';
                currentRectangle.style.border = '1px dashed #000';
                currentRectangle.style.backgroundColor = 'rgba(0, 0, 0, 0.1)';
                currentRectangle.style.left = startX + 'px';
                currentRectangle.style.top = startY + 'px';
                currentRectangle.style.width = '0px';
                currentRectangle.style.height = '0px';
                // Add a class to identify these drawn rectangles if needed later
                currentRectangle.classList.add('drawn-personalization-rectangle');
                canvasArea.appendChild(currentRectangle);
                console.log('Mousedown - Start drawing:', { startX, startY });
            }
        });

        canvasArea.addEventListener('mousemove', (e) => {
            if (!isDrawing || !currentRectangle) return;

            const currentX = e.offsetX;
            const currentY = e.offsetY;

            const width = Math.abs(currentX - startX);
            const height = Math.abs(currentY - startY);
            const newLeft = Math.min(currentX, startX);
            const newTop = Math.min(currentY, startY);

            currentRectangle.style.width = width + 'px';
            currentRectangle.style.height = height + 'px';
            currentRectangle.style.left = newLeft + 'px';
            currentRectangle.style.top = newTop + 'px';
            // console.log('Mousemove - Resizing:', { newLeft, newTop, width, height }); // Too noisy
        });

        canvasArea.addEventListener('mouseup', (e) => {
            if (!isDrawing || !currentRectangle) return;
            isDrawing = false;

            const finalWidth = parseInt(currentRectangle.style.width, 10);
            const finalHeight = parseInt(currentRectangle.style.height, 10);
            const finalX = parseInt(currentRectangle.style.left, 10);
            const finalY = parseInt(currentRectangle.style.top, 10);

            if (finalWidth > 0 && finalHeight > 0) {
                const rectangleData = {
                    x: finalX,
                    y: finalY,
                    width: finalWidth,
                    height: finalHeight,
                };
                drawnRectangles.push(rectangleData);
                console.log('Mouseup - Rectangle finalized:', rectangleData);
                console.log('All drawn rectangles:', drawnRectangles);
                // Optionally, give it a more permanent style
                currentRectangle.style.border = '1px solid red';
                currentRectangle.style.backgroundColor = 'rgba(255, 0, 0, 0.2)';
            } else {
                // If the rectangle has no area (e.g., just a click), remove it
                canvasArea.removeChild(currentRectangle);
                console.log('Mouseup - Rectangle too small, removed.');
            }
            currentRectangle = null;
        });

        // Prevent dragging image if canvasArea contains an image
        const img = canvasArea.querySelector('img');
        if (img) {
            img.addEventListener('dragstart', (e) => e.preventDefault());
        }

    } else {
        console.warn('Product Personalization Canvas Area NOT Found.');
    }
});