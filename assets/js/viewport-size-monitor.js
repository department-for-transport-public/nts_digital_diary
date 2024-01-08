function initViewportSizeMonitor() {
    const moduleName = '[Viewport size monitor] ';
    let viewportIndicator = document.getElementById('viewport-size-monitor');

    if (!viewportIndicator) {
        return;
    }

    let sizeIndicators = Array.from(viewportIndicator.querySelectorAll('[data-size]'));
    let aspectIndicators = Array.from(viewportIndicator.querySelectorAll('[data-aspect]'));

    let currentSize = null;
    let currentAspect = null;

    function reportSize() {
        if (typeof currentSize !== "string") {
            return;
        }
        console.log(moduleName + 'report the size');
        let formData = new FormData();
        formData.append('size', currentSize);
        formData.append('aspect', currentAspect);
        fetch("/ajax/viewport-size", {
            method: "POST",
            body: formData,
        });
    }

    let retries = 0;
    function doInitialise() {
        currentSize = detect(sizeIndicators, 'size');
        currentAspect = detect(aspectIndicators, 'aspect');

        if (typeof currentSize !== "string" && retries < 20) {
            retries++;
            setTimeout(doInitialise, 500);
        } else {
            reportSize();
        }
    }

    function detect(elements, type) {
        let displayedElements = Array.from(elements).filter(s =>
            window.getComputedStyle(s).getPropertyValue('display') !== 'none'
        );

        if (displayedElements.length > 1) {
            // CSS not loaded? Aspect: square?
            return false;
        }
        if (displayedElements.length < 1) {
            // failed... No visible elements
            return false;
        }
        return displayedElements[0].getAttribute('data-' + type);
    }

    document.addEventListener('DOMContentLoaded', doInitialise);
}


export {
    initViewportSizeMonitor
};