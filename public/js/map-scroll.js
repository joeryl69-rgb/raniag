/**
 * Keeps page scrolling predictable around interactive maps on touch devices.
 * Map gestures are enabled only after the visitor explicitly enters map mode.
 */
(function (global) {
    const guardedMaps = new WeakMap();

    function coarsePointer() {
        return window.matchMedia('(pointer: coarse)').matches;
    }

    function publish(map, interactive) {
        const container = map.getContainer();
        container.dataset.mapInteractive = interactive ? '1' : '0';
        container.dispatchEvent(new CustomEvent('raniag:map-mode', {
            bubbles: true,
            detail: { interactive: !!interactive },
        }));
    }

    function setMapInteractions(map, enabled) {
        [
            map.dragging,
            map.touchZoom,
            map.doubleClickZoom,
            map.scrollWheelZoom,
            map.boxZoom,
            map.keyboard,
            map.tap,
        ].forEach((handler) => {
            if (!handler) return;
            if (enabled) handler.enable();
            else handler.disable();
        });
        if (enabled && map.getContainer().dataset.drawHold === '1' && map.dragging) {
            map.dragging.disable();
        }
    }

    function bindWheel(map) {
        const container = map.getContainer();
        if (container.dataset.mapWheel === '1') return;
        container.dataset.mapWheel = '1';
        map.scrollWheelZoom.disable();
        container.addEventListener('wheel', (event) => {
            if (event.ctrlKey || event.metaKey) {
                if (!map.scrollWheelZoom.enabled()) map.scrollWheelZoom.enable();
                return;
            }
            if (map.scrollWheelZoom.enabled()) map.scrollWheelZoom.disable();
        }, { passive: true, capture: true });
    }

    function buildShield(map, state) {
        const container = map.getContainer();
        container.querySelector('.rg-map-scroll-shield')?.remove();
        state.doneControl?.remove();
        state.doneControl = null;

        const cover = document.createElement('div');
        cover.className = 'rg-map-scroll-shield';
        if (state.interactive) {
            const useButton = document.createElement('button');
            useButton.type = 'button';
            useButton.className = 'rg-map-scroll-action';
            useButton.textContent = 'Tap to use the map';
            useButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                setMapInteractions(map, true);
                cover.remove();
                publish(map, true);
                const doneControl = global.L.control({ position: 'bottomleft' });
                doneControl.onAdd = function () {
                    const control = global.L.DomUtil.create('div', 'leaflet-control rg-map-scroll-done-control');
                    const doneButton = global.L.DomUtil.create('button', 'rg-map-scroll-done', control);
                    doneButton.type = 'button';
                    doneButton.textContent = 'Back to scrolling';
                    doneButton.setAttribute('aria-label', 'Back to scrolling');
                    global.L.DomEvent.disableClickPropagation(control);
                    global.L.DomEvent.disableScrollPropagation(control);
                    doneButton.addEventListener('click', (doneEvent) => {
                        doneEvent.preventDefault();
                        doneEvent.stopPropagation();
                        setMapInteractions(map, false);
                        buildShield(map, state);
                    });
                    return control;
                };
                state.doneControl = doneControl.addTo(map);
            });
            cover.appendChild(useButton);
        } else {
            const label = document.createElement('span');
            label.textContent = 'Scroll to move the page';
            cover.appendChild(label);
        }

        container.appendChild(cover);
        setMapInteractions(map, false);
        publish(map, false);
    }

    function guard(map, options) {
        if (!map || typeof map.getContainer !== 'function') return;
        const container = map.getContainer();
        const interactive = options?.interactive !== false;
        let state = guardedMaps.get(map);
        if (!state) {
            state = { interactive, doneControl: null };
            guardedMaps.set(map, state);
        }
        state.interactive = interactive;

        if (interactive) bindWheel(map);
        if (interactive || coarsePointer()) {
            buildShield(map, state);
        }
    }

    global.RaniagMapScroll = { guard };
})(window);
