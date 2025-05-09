document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality (existing from PHP output)
    const tabs = document.querySelectorAll('.nav-tab-wrapper .nav-tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function(event) {
            event.preventDefault();

            // Deactivate all tabs and hide all content
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            tabContents.forEach(content => content.style.display = 'none');

            // Activate clicked tab and show its content
            this.classList.add('nav-tab-active');
            const activeTabContentId = 'tab-content-' + this.getAttribute('data-tab');
            const activeTabContent = document.getElementById(activeTabContentId);
            if (activeTabContent) {
                activeTabContent.style.display = 'block';
            }
        });
    });

    // Color Swatch Color Picker Functionality
    const hexInput = document.getElementById('color_swatch_hex');
    const colorPicker = document.getElementById('color_swatch_hex_picker');

    if (hexInput && colorPicker) {
        // Update hex input when color picker changes
        colorPicker.addEventListener('input', function() {
            hexInput.value = this.value;
        });

        // Optional: Update color picker when hex input changes (basic validation)
        hexInput.addEventListener('input', function() {
            if (/^#([0-9A-F]{3}){1,2}$/i.test(this.value)) {
                colorPicker.value = this.value;
            }
        });
    }
});