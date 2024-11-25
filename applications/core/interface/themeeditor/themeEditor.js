customElements.define('theme-editor', class extends HTMLElement {

    constructor(){
        super();

        // Start referencing required data/elements
        this.form = this.querySelector('form');
        this.inputs = this.form.elements;

        // Fetch panels and create an empty array to store panel navigation history
        this.panels = this.querySelectorAll('editor-panel');
        this.currentPanel = document.getElementById('panel__start');
        this.panelHistory = [];

        // Store modified settings in an object
        this.settings = {
            vars: {},
            text: {},
            layouts: {},
            images: {},
            headerPositions: {},
            dataAttributes: {}
        };

        // Fetch <iframe> and <style> tags, which we'll populate later with dynamic CSS
        this.iframe = document.getElementById('themeEditorIframe').contentWindow;
        this.iframeStyleTag = '';
        this.iframeTempStyleTag = '';
        this.styleTag = document.getElementById('themeEditorStyles');
        this.tempStyleTag = document.getElementById('themeEditorTempStyles');

        // Color picker text input
        this.colorPickerText = this.querySelector('[data-color-picker-text]');

        // Color preview block
        this.colorPreview = this.querySelector('#panel__colorSelector [data-color-preview]');

        // Custom CSS
        this.customCssDialog = this.querySelector("#dialog__customCSS");
        this.customCSSEditor = this.querySelector("#customCSS");

        this.customCssDialog.addEventListener('close', e => this.updateCustomCSS(e));

        // This determines what theme setting is being edited by the color/swatch pickers
        this.activeColor = '';

        // Run initial functions
        this.init();


        /*
            Events
        */

        // Events
        this.form.addEventListener('click', this);
        this.form.addEventListener('input', this);
        this.form.addEventListener('change', this);

        this.form.addEventListener('ips:codeboxAfterInit', e => {
            if (e?.detail?.instance) {
                this.cssEditorInstance = e.detail.instance;
                let debounceTimeout;
                e.detail.instance.on('update', () => {
                    clearTimeout(debounceTimeout);
                    debounceTimeout = setTimeout(() => {
                        this.showOrHideCSSWarning()
                        this.showOrHideRevertButton();
                    }, 200);
                });
                this.showOrHideCSSWarning();
            }
        });

        // Settings have been changed. Show dialog if quitting.
        this.form.addEventListener('change', e => this.unsavedChanges = true);      

        this.form.addEventListener('keydown', e => this.preventReturnKeySubmission(e));

        window.addEventListener("beforeunload", e => this.handleBeforeUnload(e));

        // iFrame Sync
        document.getElementById('themeEditorIframe').addEventListener('load', e => this.iframeSync());

        // Update value of "color-scheme" button if system setting changes, and preference was set to auto
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (document.documentElement.getAttribute('data-ips-scheme-active') != 'system') return;

            let active = (window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark':'light';
            this.querySelector(`[data-workspace='color'] [value='${active}']`).click();
        });

    }

    init(){

        // Create color picker
        ips.utils.color.loadIro()
            .then(() => {
                this.colorPicker = new ips.utils.iro.ColorPicker(this.querySelector('color-picker'), {
                    width: 250,
                    color: "hsl(0 0% 0%)",
                    layout: [
                        {
                            component: ips.utils.iro.ui.Box,
                            options: {}
                        },
                        {
                            component: ips.utils.iro.ui.Slider,
                            options: {
                                sliderType: 'hue'
                            }
                        }
                    ]
                });

                // When the color picker value is changed..
                this.colorPicker.on('input:change', color => this.colorPickerChange(color));
                this.colorPicker.on('input:end', color => this.colorPickerDone(color));
            });

        // There are currently no unsaved changes
        this.unsavedChanges = false;

        // Change src of iframe to referrer, if one exists - unless it's an admin page
        if(document.referrer && !document.referrer.includes("/admin/")){
            document.getElementById('themeEditorIframe').src = document.referrer;
        }

        // Add loading icon
        this.setAttribute('data-loading', true);

        // Remove the loading animation after 3 seconds incase it hasn't already been removed from the load event.
        // This can sometimes happen if the iframe has trouble loading due to other errors.
        setTimeout(() => {
            this.removeAttribute('data-loading');
        }, 3000);

        // Update the active Color Scheme button
        this.querySelector(`[data-workspace='color'] [value='${document.documentElement.getAttribute("data-ips-scheme")}']`).setAttribute('data-active', true);

        // Add custom setting UIs (header drag, etc)
        this.customSettingUIs();


/*
        // Sync up max attributes for logo/header height settings. This is an object containing ID's of "responding" and "controlling" inputs
        this.syncMaxInputs = {
            'set__i-logo--he': 'set__i-header-primary--he',
            'set__i-sidebar-ui-logo--he': 'set__i-sidebar-ui-header--he',
            'set__i-mobile-logo--he': 'set__i-mobile-header--he'
        };

        // Add "data-sync-max-value-from" attributes to responding inputs which need to adjust their "max" value depending on the value of controlling inputs
        for (const [key, value] of Object.entries(this.syncMaxInputs)) {
            this.inputs[key].setAttribute("data-sync-max-value-from", value);
        }

        // Then update the controlling input of the "Logo height" setting
        this.updateLogoMaxHeightController();

        // Then run the syncMax method on each of these responding inputs
        this.querySelectorAll("[data-sync-max-value-from]").forEach(receiver => this.updateDynamicMaxValueFor(receiver));
*/

    }

    /*
        Add custom setting UIs, such as the drag/drop editor
    */
    customSettingUIs(){

        let headerDrag = `
			<div id="content__header__design">
				<p class="i-padding-block_2 i-text-align_center i-color_soft i-flex_11">
					<span class="i-display_hover">Click and drag to reorder header elements</span><span class="i-display_no-hover">Tap, hold and then drag to reorder header elements for the desktop header.</span>
				</p>
				<ul class="theme-editor__reorder" data-ips-reorder>
					<li draggable="true" style="view-transition-name: el-1" data-pos="1"></li>
					<li draggable="true" style="view-transition-name: el-2" data-pos="2"></li>
					<li draggable="true" style="view-transition-name: el-3" data-pos="3"></li>
					<li class="theme-editor__reorder-break"></li>
					<li draggable="true" style="view-transition-name: el-4" data-pos="4"></li>
					<li draggable="true" style="view-transition-name: el-5" data-pos="5"></li>
					<li draggable="true" style="view-transition-name: el-6" data-pos="6"></li>
					<li class="theme-editor__reorder-break"></li>
					<li draggable="true" style="view-transition-name: el-7" data-pos="7"></li>
					<li draggable="true" style="view-transition-name: el-8" data-pos="8"></li>
					<li draggable="true" style="view-transition-name: el-9" data-pos="9"></li>
					<li class="theme-editor__reorder-break"></li>
					<li draggable="true" style="view-transition-name: el-10" data-pos="10"></li>
					<li draggable="true" style="view-transition-name: el-11" data-pos="11"></li>
					<li draggable="true" style="view-transition-name: el-12" data-pos="12"></li>
				</ul>
			</div>`;

        // Insert draggable UI
        this.querySelector('[data-setting="set__i-position-search"]').insertAdjacentHTML('afterend', headerDrag);

        // Populate draggable list
        let positionInputs = this.form.querySelectorAll('[name^="set__i-position-"]');

        positionInputs.forEach(input => {
            let label = this.querySelector(`[for="${input.id}"]`),
                draggableEl = this.querySelector(`[data-ips-reorder] [data-pos='${input.value}']`);

            // Add data-label and data-name to the draggable element
            draggableEl.setAttribute('data-label', label.textContent);
            draggableEl.setAttribute('data-name', input.id);
        });

        this.dragLabels = document.querySelectorAll('[data-ips-reorder] [draggable]');

        // Drag to reorder header
        this.dragLabels.forEach(item => {
            item.addEventListener('dragstart', ev => this.handleDragStart(ev), false);
            item.addEventListener('dragenter', ev => this.handleDragEnter(ev), false);
            item.addEventListener('dragover', ev => this.handleDragOver(ev), false);
            item.addEventListener('dragleave', ev => this.handleDragLeave(ev), false);
            item.addEventListener('drop', ev => this.handleDrop(ev), false);
            item.addEventListener('dragend', ev => this.handleDragEnd(ev), false);
        });
    }

   /**
    * Change the iframe size, color scheme, etc
    * @param {Event} e 
    * @param {HTMLElement} target 
    */
    changeWorkspace(e, target){

        let type = target.parentElement.getAttribute('data-workspace');

        // If it's a solo element, then toggle its state
        if(target.matches(':only-child')){
            target.toggleAttribute('data-active');
            document.documentElement.setAttribute(`data-workspace-${type}`, target.hasAttribute('data-active'));
        } else {
            // ..otherwise it's part of a group, so remove the active state from all siblings, then activate the chosen option
            for(let el of target.parentElement.children){
                el.removeAttribute('data-active');
            }
            target.setAttribute('data-active', true);
            document.documentElement.setAttribute(`data-workspace-${type}`, target.value);
        }

        // Update color scheme by clicking a color scheme button in the iframe
        if(type === 'color' && e.isTrusted){
            this.iframe.document.querySelector(`[data-ips-prefers-color-scheme=${target.value}]`).click();
        }
    }



    /**
     * Navigate through panels, and store the history in an array so we can 'go back'
     * @param {Event} event 
     * @param {HTMLElement} target 
     */
    panelNavigation(event, target){

        // Hide all panels
        this.panels.forEach(p => {
            p.setAttribute('aria-hidden', 'true');
            p.inert = true;
        });

        if(target.getAttribute('data-panel-nav') === 'back'){

            // Get the previous panel and open it
            this.currentPanel = document.getElementById(this.panelHistory.pop());
            this.currentPanel.setAttribute('aria-hidden', 'false');
            this.currentPanel.inert = false;
            this.currentPanel.removeAttribute('data-panel-prev');
            this.currentPanel.querySelector("button")?.focus();

        } else {

            if(target.hasAttribute('data-color-tool')){
                // Open the color picker and assign active swatch
                this.toggleColorPanel(target);
            }

            let oldPanel = target.closest('editor-panel');
            this.currentPanel = document.getElementById(target.getAttribute('aria-controls'));

            // Open the new panel
            this.currentPanel.setAttribute('aria-hidden', 'false');
            this.currentPanel.inert = false;
            this.currentPanel.querySelector("button, input, select, textarea")?.focus();

            // Set the text for the Back button
            this.currentPanel.querySelector(`[data-panel-nav="back"]`).innerText = oldPanel.querySelector('[data-panel-name]')?.innerText ?? 'Back';

            // Add the old panel to the history array, so we can navigate back using data-panel-nav="back" buttons
            this.panelHistory.push(oldPanel.id);

            // Then animate the old panel to the left
            oldPanel.setAttribute('data-panel-prev', true);

        }
    }

    /**
     * Update <style id="themeVariables"> with variables
     */
    buildVariablesStyleTag(){

        // Empty the style elements
        this.styleContent = '';
        this.iframeTempStyleTag.textContent = '';

        // Loop through each input, then output its value as a css variable
        for (const [key, value] of Object.entries(this.settings.vars)){
            if(['set__i-logo-text', 'set__i-logo-slogan'].includes(key)) continue;
            this.styleContent += `--${key}: ${value};`
        }

        // Wrap the variables in a :root selector and apply it to the <style> tag
        this.styleTag.textContent = `:root{ ${this.styleContent} }`;
        this.iframeStyleTag.textContent = `:root{ ${this.styleContent} }`;

        // This is the generated CSS code
        window.Debug?.log(this.iframeStyleTag.textContent);

    }


    /**
     * Dynamically update live text elements. This runs when a text input is changed, or when the iframe is navigated to a new page
     * @param {String} setting  The name of the setting being changed
     * @param {String} value    The value of the setting being changed
     */
    updateLiveText(setting, value){
        this.iframe.document.querySelectorAll(`[data-ips-theme-text='${setting}']`).forEach(le => le.innerText = value);
    }

    /**
     * Display a preview of uploaded images in the editor panel
     * This runs when the [type="file"] input is changed
     * @param {Event} e 
     */
    updateImagePreview(e){

        const preview = e.target.parentElement.querySelector('[data-file-preview]');

        // Erase existing preview and delete from object
        preview.textContent = '';
        delete this.settings.images[e.target.name];

        // If an image has been uploaded, create the preview image and add to object
        if(e.target.files.length > 0){
            const src = URL.createObjectURL(e.target.files[0]);

            // Build preview thumbnail
            preview.innerHTML = `<img src="${src}" alt="">`;

            // Remove the "delete__" input, since an image has been uploaded
            preview.parentElement.querySelector('[data-delete-logo]')?.remove();

            // Add the uploaded image to object
            this.settings.images[e.target.name] = src;

            // Then apply the image to the iframe
            this.applyUploadedImageToIframe(e.target.name, src);
        }

    }

    /**
     * Apply uploaded image to iframe
     * @param {String} name     Name of image
     * @param {String} value    src of image
     */
    applyUploadedImageToIframe(name, value = null){

        let setting = name.replace("set__", ""),
            elements = this.iframe.document.querySelectorAll(`[data-ips-theme-image='${setting}']`);

        elements.forEach(el => {

            let picture = el.closest('picture');

            if(value){

                // The image has a URL, so apply it
                el.src = value;

                // Safari fix: Repaint image so it gets correct dimensions
                el.offsetHeight;

                el.hidden = false;
                if(picture) picture.hidden = false;

                // Remove width/height attributes which are applied to initial logo image
                el.removeAttribute('width');
                el.removeAttribute('height');

            } else {

                // Hide images since they've been deleted
                el.hidden = true;
                if(picture) picture.hidden = true;

            }
        });

    }

    /**
     * Live update: Delete image
     * @param {Event} event The click event
     * @param {HTMLElement} target  The delete button belonging to the image which we're deleting
     */
    deleteLogoImage(event, target){

        const fileInput = target.parentElement.querySelector('[type=file]');

        // Reset the file input
        fileInput.value = '';

        // Remove the image from the object
        delete this.settings.images[target.getAttribute('data-file-preview-delete')];

        // Programatically run this.updateImagePreview
        fileInput.dispatchEvent(new Event("change", {bubbles: true}));

        // Add an input so the image can be deleted from the backend
        if(!fileInput.parentElement.querySelector('[data-delete-logo]')){
            fileInput.insertAdjacentHTML('afterend', `<input type='hidden' name='delete__${fileInput.id}' value='1' data-delete-logo>`);
        }

        // Delete the image from the iframe
        this.applyUploadedImageToIframe(target.getAttribute('data-file-preview-delete'));

    }

    /**
     * Update the "max value" for the logo height depending on its location in the header
     * This runs on init, and when the header elements are repositioned
     */
    updateLogoMaxHeightController(){
        /*

        // Get the position of the logo
        let logoPosition = this.inputs['set__i-position-logo'].value,
            logoController = "";
        if (['1','2','3'].includes(logoPosition)){
            logoController = 'set__i-header-top--he';
        } else if (['7','8','9'].includes(logoPosition)){
            logoController = 'set__i-header-secondary--he';
        } else {
            logoController = 'set__i-header-primary--he';
        }

        // The max value of the logo height setting should be controlled by the header position which holds the logo
        this.inputs['set__i-logo--he'].setAttribute("data-sync-max-value-from", logoController);

        // Update the max value of logo setting
        this.updateDynamicMaxValueFor(this.inputs['set__i-logo--he']);
        */

    }

    /**
     * Sync up max values for logo and header heights
     * This provides a more friendly way of resizing the logo, since its max value is always in sync with the current header height (creating a dynamic 0% - 100% range)
     * @param {HTMLElement} receiver     The input whose max value will change based on its controlling input
     */
    updateDynamicMaxValueFor(receiver){
        /*

        let controllerValue = this.querySelector("#" + receiver.getAttribute("data-sync-max-value-from")).value;

        // Ensure the max value of the receiving input is the same as the controlling inputs value
        receiver.max = controllerValue;

        if(receiver.value > controllerValue){
            receiver.value = controllerValue;
        }

        // Update the max value of the receiving inputs "num output" if it exists
        let output = this.querySelector(`[data-range-output="${receiver.id}"]`);
        if(output){
            output.max = controllerValue;
            if(output.value > controllerValue){
                output.value = controllerValue;
            }
        }
        */

    }


    /**
     * When a "change color-scheme" button inside the iframe is clicked, change the color of the theme editor.
     * @param {HTMLElement} target  The 'prefers color scheme' button
     */
    colorSchemeSync(target){

        document.querySelector('[data-ips-prefers-color-scheme=' + target.getAttribute('data-ips-prefers-color-scheme') + ']').click();

        // .. update the value of the workspace options to either light/dark
        this.querySelector('[data-workspace=color] [value=' + document.documentElement.getAttribute('data-ips-scheme') +']').click();

        // If we're editing a color setting (such as Body Background), and if we change color scheme, we need to flick back to the "color settings" list otherwise we'll be editing the wrong color	
        if(this.currentPanel.matches('#panel__colorSelector')){
            this.currentPanel.querySelector('[data-panel-nav="back"]').click();
        }

    }


    /**
     * Prevent the return key from submitting the form when a setting input is focused
     * @param {Event} e The submit event
     * @returns false
     */
    preventReturnKeySubmission(e){
        let input = e.target;
        if( input.matches('theme-editor input') && e.keyCode == 13 ) {
            e.preventDefault();

            // If a number outside of the min/max range is submitted, pull it back into the range
            if(input.min && input.min > Number(input.value)){
                input.value = input.min;
            } else if(input.max && input.max < Number(input.value)){
                input.value = input.max;
            }

            // Dispatch the 'change' event, so settingHasBeenChanged() runs and rebuilds the <style> element
            input.dispatchEvent(new Event("change", { bubbles: true }));

            return false;
        }
    }

    /**
     * When a range is being changed (via the 'input' eventListener), add its value to #themeEditorTempStyles
     * We should do as little as possible here for best performance.
     * Other updates are hanlded via settingHasBeenChanged()
     * @param {Event} e The 'input' event
     * @param {HTMLElement} e The target which triggered the event
     */
    changingRangeInput(e, target){

        cancelAnimationFrame(this.rebuildStyleDebounce);

        // Update the UI. The requestAnimationFrame ensures smoother framerates
        this.rebuildStyleDebounce = requestAnimationFrame(() => {

            // Add the new value into <style id='themeEditorTempStyles'>
            this.iframeTempStyleTag.textContent = `:root{ --${e.target.id}: ${e.target.value}; }`;

            // If this is an oklch range, update the theme editor styles so the color can be used in the other ranges
            if(e.target.matches("#panel__oklch input")){
                this.tempStyleTag.textContent = `:root{ --${e.target.id}: ${e.target.value}; }`;
            }

            // If the input is a range, update the "synced number input"
            let numInput = this.querySelector(`[data-range-output=${e.target.id}]`);
            if(numInput) numInput.value = e.target.value;

        });

    }

    /**
     * When a text input is being changed (via the 'input' eventListener), update the live text elements in the iframe
     * Other updates are hanlded via settingHasBeenChanged()
     * @param {Event} e The 'input' event
     * @param {HTMLElement} e The target which triggered the event
     */
    changingTextInput(e, target){

        // Run updateLiveText to update text in iframe
        this.updateLiveText(e.target.name, e.target.value);

        // Add the new value into <style id='themeEditorTempStyles'>
        //this.iframeTempStyleTag.textContent = `:root{ --${e.target.id}: ${e.target.value}; }`;

    }

    /**
     * Once a setting has been changed ('change' eventListener), add it to the settings.vars object, which tracks changed settings
     * The styles are then built into #themeEditorStyles via buildVariablesStyleTag()
     * @param {Event} e     'input' Event
     * @param {HTMLElement} target  The HTMLElement that triggered the event
     */
    settingHasBeenChanged(e, target){

        let setting = target.name,
            value = target.value;

        // Add the setting and value to the settings object, so we can reference/apply it during iframe navigation
        this.settings.vars[setting] = value;

        if (e.target.matches('[type="range"]')){

            let numInput = this.querySelector(`[data-range-output=${e.target.id}]`);
            if(numInput) numInput.value = value;

            // If an input with data-sync-max-value-from exists, with a value which matches the range that just changed, update the max value of that input
            /*
            let syncThisInput = this.querySelector(`[data-sync-max-value-from='${e.target.id}']`);
            if(syncThisInput){
                this.updateDynamicMaxValueFor(syncThisInput);
            }
            */

        } else if(target.matches('[type="text"]')){
            // If this is a text setting, add it to the settings.text object so updateLiveText can reference it on iframe navigation
            this.settings.text[setting] = value;
        }

        // If data-attributes on <html> exist for this setting, update them
        let attr = 'data-ips-theme-setting-' + setting.replace('set__i-', '');
        if(this.iframe.document.documentElement.hasAttribute(attr)){

            // Add attribute to object so we can reference it during iframe navigation
            this.settings.dataAttributes[attr] = value;

            // Set the attribute on the html element to the new value
            this.applyDataAttributes(attr, value);

        }

        // If this is <input type="file">, update the image prevent
        if(target.matches('[type="file"]')) this.updateImagePreview(e);       

        // Show the revert button
        this.toggleRevertButton(target);

        // Do we need to refresh the page?
        let container = document.querySelector( `theme-setting[data-setting='${setting}']` );
        if(container.matches('[data-setting-refresh]')) this.refreshIframe(e);

        // Rebuild <style id='themeEditorStyles'>
        this.buildVariablesStyleTag();
    }

    /**
     * When manually editing <input type="number">, this updates the value of the corresponding <input type="range">
     * For checkboxes: Update the value of hidden inputs when checkboxes are changed. This allows us to submit 1/0 with the form, instead of the default "on" value
     * For ranges: Pull the value back into range if it exceeds it
     * @param {Event} e             The 'change' event
     * @param {HTMLElement} target  The element that triggered the event
     */
    syncInputsWithPseudo(e, target){

        let pseudoInput = e.target,
            input = document.getElementById(e.target.getAttribute('data-range-output'));

        if (pseudoInput.max) pseudoInput.value = Math.min(parseInt(pseudoInput.max), Math.max(parseInt(pseudoInput.min) || 0, parseInt(pseudoInput.value) || 0));

        if(e.target.type == 'checkbox'){
            input.value = (e.target.checked) ? 1 : 0;
        } else {
            input.value = e.target.value;
        }

        // Dispatch the 'change' event, so settingHasBeenChanged() runs and rebuilds the <style> element
        input.dispatchEvent(new Event("change", { bubbles: true }));

    }

    /**
     * Dynamically update the custom CSS code when the textarea changes
     * We check to see if </style> exists, and we remove it if so. Otherwise we keep the content unchanged so undo/redo can still function across dialog toggles
     * @param {Event} e 
     * @param {HTMLElement} target
     */
    updateCustomCSS(e){

        let content = this.customCSSEditor.value;

        if(content.includes("</style>")){
            // Remove </style> to prevent the style tag from being closed
            let strippedContent = content.replace(/<\/style>/g, '');
            // Update textarea value with strippedContent. Add a comment at the end to prevent unclosed comments from breaking the <style> element
            this.customCSSEditor.value = strippedContent;
            // Apply new code to <style id="themeCustomCSS">
            this.iframeCustomCSS.textContent = strippedContent;
        } else {
            // Apply unmodified value to <style id="themeCustomCSS">
            this.iframeCustomCSS.textContent = this.customCSSEditor.value;
        }

    }

    /**
     * Revert Custom CSS
     *
     * @param {Event} event
     * @param {HTMLElement} target
     */
    revertCustomCSS(event, target){
        
        if (confirm("This will revert your CSS back to its previously saved value. Any unsaved changes will be lost.")) {
            this.querySelector("#customCSS").innerHTML = this.querySelector("#customCSS-saved").innerHTML;
            const currentContent = this.cssEditorInstance?.getJSON();
            const content = {
                type: currentContent?.type || "doc",
                content: [
                    {
                        type: currentContent?.content?.[0]?.type || "codeBlock",
                        attrs: currentContent?.content?.[0]?.attrs || {
                            language: "ipscss"
                        },
                        content: [
                            {
                                type: "text",
                                text: this.querySelector('#customCSS-saved').innerHTML
                            }
                        ]
                    }
                ]
            };

            this.cssEditorInstance?.chain().setContent(content).setTextSelection(0).focus().run();

            // Hide the revert button
            target.hidden = true;
        }
        
    }

    /**
     * Show or hide the warning about template tags
     */
    showOrHideCSSWarning() {
        const dialog = this.querySelector('#dialog__customCSS');
        const editor = dialog.querySelector('.tiptap');
        const warning = dialog.querySelector('#customCSS-warning');

        const hasTemplateTag = !!editor.querySelector('.language-ipscss :is(.ipsShortcut, .ipsVarTag, .ipsVarTagFilter, .ipsBlockTag)');
        if (hasTemplateTag) {
            warning.removeAttribute('hidden');
        } else {
            warning.setAttribute('hidden', '');
        }
    }

    /**
     * Show or hide the revert button depending on whether or not the styles have changed
     */
    showOrHideRevertButton() {
        const originalContent = this.querySelector('#customCSS-saved').innerHTML;
        const currentContent = this.querySelector('#customCSS').innerHTML;

        this.querySelectorAll('[data-revert-custom-css]').forEach(revertButton => {
            if (currentContent === originalContent) {
                revertButton.setAttribute('hidden', '');
            } else {
                this.unsavedChanges = true;
                revertButton.removeAttribute('hidden');
            }
        });
    }

    /**
     * Update the color preview block
     */
    setColorPickerFromComputedStyle(){
        this.colorPreview.style.setProperty('--_background', this.activeColor.value);

        let color = window.getComputedStyle(this.colorPreview).getPropertyValue('background-color');

        if (color.startsWith("color(srgb")){
            // Convert color(srgb) to rgb so it can be applied to the color picker
            let values = color.replace(/color\(srgb |\)/g, '').split(" ");
            this.colorPicker.color.set(`rgb(${values[0] * 255} ${values[1] * 255} ${values[2] * 255})`);

        } else {
            // This is hopefully already rgb or hsl (thanks to relative colors in CSS)
            this.colorPicker.color.set(color);
        }
        
    }

    /**
     * This opens the swatch/color picker panel
     * @param {HTMLElement} target 
     */
    toggleColorPanel(target){

        let panel = document.getElementById('panel__colorSelector');

        // Set active values
        this.activeColor = this.inputs[target.getAttribute('data-controls')];

        // Update setting name in header
        this.querySelector('[data-active-name]').innerText = target.innerText;

        // Optionally only show certain swatches in the swatch picker
        if(target.hasAttribute('data-swatch-categories')){
            panel.setAttribute('data-show-swatches', target.getAttribute('data-swatch-categories'));
        } else {
            panel.removeAttribute('data-show-swatches');
        }

        // Always open the panel with the swatch picker visible
        let swatchTab = document.querySelector('[aria-controls="content__colorSelector__swatches"]');
        if(swatchTab.getAttribute("aria-expanded") === "false"){
            swatchTab.click()
        }

        // Optionally hide swatches (ie. the Color Scheme creator)
        if(target.getAttribute('data-color-tool') != ''){
            panel.setAttribute('data-show-tool', target.getAttribute('data-color-tool'));
        }

        // Set aria-current on swatch if one exists
        this.querySelector('[data-swatch][aria-current]')?.removeAttribute('aria-current');
        this.querySelector(`[data-swatch][value='${this.activeColor.value}']`)?.setAttribute('aria-current', 'true');

        // Get the computed value of a CSS var (eg. if a swatch was previously selected) and apply it to the color picker
        this.setColorPickerFromComputedStyle();
        this.colorPickerText.value = `hsl(${this.colorPicker.color.hsl.h} ${this.colorPicker.color.hsl.s}% ${this.colorPicker.color.hsl.l}%)`;
    }

    /**
     * When the color picker is actively being dragged, only change <style id='themeEditorTempStyles'>
     * This improves performance, particularly in Safari.
     * The <style> tag is rebuilt via colorPickerDone() once dragging has finished.
     * @param {Object} color    The color object from the Color Picker
     */
    colorPickerChange(color){

        cancelAnimationFrame(this.rebuildStyleDebounce);

        // Update the UI. The requestAnimationFrame ensures smoother framerates
        this.rebuildStyleDebounce = requestAnimationFrame(() => {

            // We need to manually set this, so it uses the space separated syntax
            let newValue = `hsl(${color.hsl.h} ${color.hsl.s}% ${color.hsl.l}%)`,
                styleContent = `--${this.activeColor.name}: ${newValue};`;

            // Add the new color into <style id='themeEditorTempStyles'>
            this.iframeTempStyleTag.textContent = `:root{ ${styleContent} }`;

            // Update color picker text box and preview box
            this.colorPickerText.value = newValue;
            this.colorPreview.style.setProperty('--_background', newValue);

        });

    }

    /**
     * Contrast polyfill for primary-contrast and seconday-contrast variables in browsers which don't support relative colors: https://caniuse.com/css-relative-colors
     * This sets a value of 0-100 for approximate relative lightness
     * @param {Object} color The color object from the Color Picker
     * @returns {Number} The relative lightness of the color, from 0 - 100
     */
    colorPickerContrastPolyfill(color){
        let relativeLightness = ((color.red*299)+(color.green*587)+(color.blue*114)) / 1000 / 256 * 100;
        return Math.floor(relativeLightness);
    }

    /**
     * When the color picker is changed, update the value of the "input" which belongs to the setting
     * @param {Object} color The color object from the Color Picker
     */
    colorPickerDone(color){

        // Remove active swatch, if one exists
        this.querySelector('[data-swatch][aria-current]')?.removeAttribute('aria-current');

        // We need to manually set this, so it uses the space separated syntax
        let newValue = `hsl(${color.hsl.h} ${color.hsl.s}% ${color.hsl.l}%)`;

        // Dynamically update input value
        this.activeColor.value = newValue;
        this.settings.vars[this.activeColor.name] = this.activeColor.value;

        // Primary and secondary contrast polyfill for browsers which don't support relative color syntax: https://caniuse.com/css-relative-colors
        if(this.activeColor.matches('[name=light__i-primary], [name=light__i-secondary], [name=dark__i-primary], [name=dark__i-secondary]')){
            this.inputs[this.activeColor.name + '-relative-l'].value = this.colorPickerContrastPolyfill(color);
            this.settings.vars[this.activeColor.name + '-relative-l'] = this.colorPickerContrastPolyfill(color);
        }

        // Update color picker text box and preview box
        this.colorPickerText.value = newValue;
        this.colorPreview.style.setProperty('--_background', newValue);

        // Toggle revert button
        this.toggleRevertButton(this.activeColor);

        // Settings have been changed. Show dialog if quitting.
        this.unsavedChanges = true;

        // Apply styles
        this.buildVariablesStyleTag();
    }

    /**
     * When a color code (HEX, HSL, RGB, etc) is added to the text input, update the color picker
     * @param {Event} e The change event of the input
     * @param {HTMLElement} target
     * @returns 
     */
    colorPickerTextBlur(e, target){

        if (!e.target.value.startsWith('#') && !e.target.value.startsWith('hsl(') && !e.target.value.startsWith('rgb(')){
            window.Debug?.log('Not a supported color. Hex, RGB and HSL are supported.');
            return;
        }

        // Set these values for the setColorPickerFromComputedStyle() method can use them
        this.activeColor.value = e.target.value;
        this.settings.vars[this.activeColor.name] = this.activeColor.value;

        this.setColorPickerFromComputedStyle();
        this.colorPickerDone(this.colorPicker.color);

    }

    /**
     * When a swatch is clicked, grab its value and apply it to the active setting input
     * @param {Event} event The click event
     * @param {HTMLElement} target The swatch
     */
    swatchClick(event, target){

        // Set active swatch
        this.querySelector('[data-swatch][aria-current]')?.removeAttribute('aria-current');
        target.setAttribute('aria-current', 'true');

        // Update setting value
        this.activeColor.value = target.value;
        this.settings.vars[this.activeColor.name] = this.activeColor.value;

        // Update the preview swatch, style tag and revert button
        this.colorPreview.style.setProperty('--_background', target.value);
        this.buildVariablesStyleTag();
        this.toggleRevertButton(this.activeColor);

        // Set the value of the color picker to the same as the selected swatch
        this.setColorPickerFromComputedStyle();

        // Settings have been changed. Show dialog if quitting.
        this.unsavedChanges = true;
    }

   /**
    * Show or hide the revert button if the input value matches the data-default value
    * @param {HTMLInputElement} settingInput 
    */
    toggleRevertButton(settingInput){

        let revertButton = document.querySelector(`[data-revert="${settingInput.name}"]`),
            defaultValue = settingInput.getAttribute("data-default");

        if(!revertButton || !defaultValue) return;
        revertButton.hidden = (settingInput.value == defaultValue);

    }

    /**
     * Reverts a setting back to its data-default value
     * @param {Event} event The click event
     * @param {HTMLElement} target The Revert button
     */
    revertSetting(event, target){

        // Hide the revert button
        target.hidden = true;

        // Find out what setting this button reverts, and revert it to the data-default value
        let controls = target.getAttribute('data-revert'),
            defaultValue = this.inputs[controls].getAttribute('data-default');
        this.inputs[controls].value = defaultValue;

        // If we're reverting a checkbox, we need to manually change its checked value. The actual setting field is a text input, so the checkbox is really just a pseudo input.
        let checkbox = document.getElementById(`${controls}__checkbox`);
        if (checkbox){
            checkbox.checked = (defaultValue === "1") ? true : false;
        }

        // Ensure the reverted (default) value overwrites the saved value from CSS if it exists
        this.settings.vars[controls] = defaultValue;

        // Dispatch change event so we can update iframe preview
        this.inputs[controls].dispatchEvent(new Event("change", {bubbles: true}));

        // Rebuild <style> element
        this.buildVariablesStyleTag();

    }

    /**
     * Refresh the iframe if settings require it
     * @param {Event} e 
     */
    refreshIframe(e)  {
        // Add the new selection to the settings object
        this.settings.vars[e.target.name] = e.target.value;

        // Update the layout cookie with the new value
        ips.utils.cookie.set('theme_editor_vars', JSON.stringify( this.settings ) );

        // Add loading icon to iframe
        this.setAttribute('data-loading', true);

        // Remove the loading animation after 3 seconds incase it hasn't already been removed from the load event.
        // This can sometimes happen if the iframe has trouble loading due to other errors.
        setTimeout(() => {
            this.removeAttribute('data-loading');
        }, 3000);

        // ..then reload it so the new layout can be shown
        document.getElementById('themeEditorIframe').contentDocument.location.reload(true);
    }

    /**
     * Apply data-attributes to iframe
     * @param {String} attr The data-attribute
     * @param {String} value The value
     */
    applyDataAttributes(attr, value){
        this.iframe.document.documentElement.setAttribute(attr, value);
    }

    /**
     * iFrame navigation
     */
    iframeSync(){

        // Get <style> tag so we can fill it with our new values
        this.iframeStyleTag = this.iframe.document.getElementById('themeEditorStyles');
        this.iframeTempStyleTag = this.iframe.document.getElementById('themeEditorTempStyles');
        this.iframeCustomCSS = this.iframe.document.getElementById('themeCustomCSS');

        this.buildVariablesStyleTag();

        // Reapply uploaded images
        for(const [setting, value] of Object.entries(this.settings.images)){
            this.applyUploadedImageToIframe(setting, value);
        }

        // Reapply live text
        for(const [setting, value] of Object.entries(this.settings.text)){
            this.updateLiveText(setting, value);
        }

        // Reapply header positions
        for(const [setting, value] of Object.entries(this.settings.headerPositions)){
            this.repositionHeaderElement(setting, value);
        }
        this.repositionHeaderElementPolyfills();

        // Reapply data-attributes
        for(const [attr, value] of Object.entries(this.settings.dataAttributes)){
            this.applyDataAttributes(attr, value);
        }

        // Add click events to iframe (for color scheme sync)
        this.iframe.addEventListener('click', e => this.handleIframeClicks(e));

        this.removeAttribute('data-loading');

    }

    /**
     * Handle all events (click, input, change, etc). When data-on-click="x", data-on-change="x", data-on-input="x", etc is assigned to an element, the method will run when the event fires.
     * @param {Event} event 
     * @returns Method from the custom element class
     */
    handleEvent(event){

        const target = event.target.closest(`[data-on-${event.type}]`);
        if(!target) return;
        const method = target.getAttribute(`data-on-${event.type}`);

        if(this[`${method}`]){
            return this[`${method}`](event, target)
        } else {
            console.log(`The ${method} method, triggered by [data-on-${event.type}="${method}"] was not found`);
        }

    }

    /**
     * Handle all iframe click events
     * @param {Event} e 
     */
    handleIframeClicks(e){
        let el;
        if(el = e.target.closest('[data-ips-prefers-color-scheme]')) this.colorSchemeSync(el);
    }


    /* Handle drag/reorder elements */
    handleDragStart(e) {
        e.currentTarget.setAttribute('data-dragging', true);
        this.firstDrag = e.currentTarget;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', e.currentTarget.outerHTML); // Required for iOS
    }

    handleDragOver(e) {
        e?.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        return false;
    }

    handleDragEnter(e) {
        e.currentTarget.setAttribute('data-dropzone', true);
    }

    handleDragLeave(e) {
        e.currentTarget.removeAttribute('data-dropzone');
    }

    handleDrop(e) {
        e?.stopPropagation();

        if (this.firstDrag != e.currentTarget) {
            this.secondDrag = e.currentTarget;
            if (document.startViewTransition){
                document.startViewTransition(() => this.swapDraggables(this.firstDrag, this.secondDrag))
            } else {
                this.swapDraggables(this.firstDrag, this.secondDrag)
            }
        }
        // return false;
    }

    handleDragEnd(e) {
        e.currentTarget.removeAttribute('data-dragging');
        this.dragLabels.forEach(item => item.removeAttribute('data-dropzone'));
    }

    /**
     * Swap the position of header elements once they've been dragged
     * @param {HTMLElement} origin          The origin element
     * @param {HTMLElement} destination     The destination element
     */
    swapDraggables(origin, destination){

        let el = {
            fromName: origin.getAttribute('data-name'),
            fromPos: origin.getAttribute('data-pos'),
            fromDrag: origin.previousSibling,
            toName: destination.getAttribute('data-name'),
            toPos: destination.getAttribute('data-pos'),
            toDrag: destination.previousSibling
        }

        // Reposition draggables
        el.fromDrag.after(destination);
        el.toDrag.after(origin);

        // Swap data-pos
        origin.setAttribute('data-pos', el.toPos);
        destination.setAttribute('data-pos', el.fromPos);

        let input;

        if(input = this.form.querySelector(`[id='${el.fromName}']`)){
            input.value = el.toPos;
            this.settings.vars[input.name] = input.value;
            this.settings.headerPositions[input.name] = input.value;
            this.repositionHeaderElement(input.name, input.value);
        }
        if(input = this.form.querySelector(`[id='${el.toName}']`)){
            input.value = el.fromPos;
            this.settings.vars[input.name] = input.value;
            this.settings.headerPositions[input.name] = input.value;
            this.repositionHeaderElement(input.name, input.value);
        }

        // Reapply :has() selector for empty headers
        this.repositionHeaderElementPolyfills();

        // Update the setting which controls the max height of the logo
        this.updateLogoMaxHeightController();

        // Rebuild variable <style> element (future-proofing for @container style queries)
        this.buildVariablesStyleTag();

        // Setting has been changed. Show dialog if quitting.
        this.unsavedChanges = true;

    }

    repositionHeaderElement(settingName, settingValue){

        let name = settingName.replace(/(^set__i-position-)/gi, ""),
            elementOne = this.iframe.document.querySelector(`[data-ips-header-content='${name}']`),
            destination = this.iframe.document.querySelector(`[data-ips-header-position='${settingValue}']`);

        // If these elements don't exist (ie. when using the sidebar UI), or if this element is already in the right position, return early.
        if (!elementOne || !destination || destination.contains(elementOne)) return;

        // First, find the position where our content currently is..
        const parentOne = elementOne.closest(`[data-ips-header-position]`);

        // Next, append the content to the correct position..
        destination.append(elementOne);

        // If the destination previously contained a child which needs to be moved, move it.
        if(destination.firstElementChild != destination.lastElementChild){
            parentOne.append(destination.firstElementChild);
        }

    }

    /*
        Adjust the visibility of header elements via the [hidden] attribute
    */
    repositionHeaderElementPolyfills(){
        this.iframe.document.querySelectorAll('.ipsHeader__top, .ipsHeader__primary, .ipsHeader__secondary, .ipsHeaderExtra').forEach(h => {
            h.hidden = !h.querySelector('[data-ips-header-content]');
        })
    }

    /**
     * Save changes
     * @param {Event} event
     * @param {HTMLElement} target
     */
    saveChanges(event, target){
        // This disables the unload listener since we're saving the changes
        this.unsavedChanges = false;
        // window.removeEventListener("beforeunload", this.handleBeforeUnload);
    }

    /**
     * Close editor (and check for unsaved changes)
     * @param {Event} e 
     * @param {HTMLElement} target
     */
    closeEditor(e, target){
        ips.utils.cookie.set('themeEditorLocation', document.getElementById('themeEditorIframe').contentDocument.location );
        if(this.unsavedChanges){
            e.preventDefault();
            // Clicking the toggle instead of using showModal() ensures the hidden/aria attributes are updated correctly
            document.getElementById('toggleCloseConfirmationDialog').click();
        }
    }

    /**
     * If there are unsaved changes AND (the confirmation dialog is closed OR a submit button isn't pressed), prevent the page from unloading
     * @param {Event} e 
     */
    handleBeforeUnload(e){
 
        if(this.unsavedChanges && !(document.getElementById('closeConfirmationDialog').open)){
            e.preventDefault();
            e.returnValue = true;
        }
    }

});