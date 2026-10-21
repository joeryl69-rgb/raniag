(() => {
    const enhancedSelects = new WeakSet();

    const initializeSelect = (select, index) => {
        if (enhancedSelects.has(select) || select.multiple || select.options.length === 0) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'rg-select-control';
        wrapper.innerHTML = `
            <button type="button" class="form-select rg-select-trigger"
                role="combobox" aria-haspopup="listbox" aria-expanded="false"
                data-select-trigger>
                <span data-select-value></span>
            </button>
            <div class="rg-select-menu" role="listbox" hidden data-select-menu></div>
        `;
        select.insertAdjacentElement('afterend', wrapper);

        const trigger = wrapper.querySelector('[data-select-trigger]');
        const valueLabel = wrapper.querySelector('[data-select-value]');
        const menu = wrapper.querySelector('[data-select-menu]');
        const label = select.labels?.[0]?.textContent.trim()
            || select.getAttribute('aria-label')
            || select.id.replace(/[-_]/g, ' ')
            || select.name.replace(/_/g, ' ')
            || 'Select an option';
        const selectedOption = () => select.options[select.selectedIndex];
        let activeIndex = -1;
        let menuOptions = [];
        let attemptedInvalid = false;

        const syncValue = () => {
            const option = selectedOption();
            valueLabel.textContent = option?.textContent.trim() || '';
            trigger.disabled = select.disabled;
            trigger.setAttribute('aria-label', `${label}: ${valueLabel.textContent}`);
            trigger.setAttribute('aria-required', String(select.required));
            if (attemptedInvalid) {
                trigger.setAttribute('aria-invalid', String(!select.validity.valid));
            }
            if (option) trigger.dataset.value = option.value;
            else delete trigger.dataset.value;
        };
        const positionMenu = () => {
            const bounds = trigger.getBoundingClientRect();
            const left = Math.max(8, Math.min(bounds.left, window.innerWidth - bounds.width - 8));
            const top = Math.min(bounds.bottom + 4, window.innerHeight - 120);
            menu.style.left = `${left}px`;
            menu.style.top = `${Math.max(8, top)}px`;
            menu.style.width = `${Math.min(bounds.width, window.innerWidth - 16)}px`;
            menu.style.maxHeight = `${Math.max(96, Math.min(288, window.innerHeight - top - 12))}px`;
        };
        const setOpen = (open) => {
            trigger.setAttribute('aria-expanded', String(open));
            menu.hidden = !open;
            if (open) {
                positionMenu();
            } else {
                trigger.removeAttribute('aria-activedescendant');
                activeIndex = -1;
            }
        };
        const renderMenu = () => {
            menu.replaceChildren();
            menuOptions = Array.from(select.options).map((option, optionIndex) => {
                const item = document.createElement('div');
                item.id = `${menu.id}-option-${optionIndex}`;
                item.className = 'rg-select-option';
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', String(option.selected));
                item.setAttribute('aria-disabled', String(option.disabled || option.parentElement.disabled));
                item.dataset.index = String(optionIndex);
                item.textContent = option.textContent.trim();
                menu.append(item);
                return item;
            });
        };
        const setActive = (index) => {
            if (!menuOptions.length) return;
            for (let step = 0; step < menuOptions.length; step++) {
                const candidate = (index + step + menuOptions.length) % menuOptions.length;
                if (menuOptions[candidate].getAttribute('aria-disabled') !== 'true') {
                    activeIndex = candidate;
                    break;
                }
            }
            menuOptions.forEach((option, optionIndex) => {
                const active = optionIndex === activeIndex;
                option.classList.toggle('is-active', active);
                if (active) {
                    trigger.setAttribute('aria-activedescendant', option.id);
                    option.scrollIntoView({ block: 'nearest' });
                }
            });
        };
        const open = () => {
            renderMenu();
            setOpen(true);
            const selectedIndex = select.selectedIndex;
            setActive(selectedIndex >= 0 ? selectedIndex : 0);
        };
        const choose = (index) => {
            const option = select.options[index];
            if (!option || option.disabled || option.parentElement.disabled) return;
            select.selectedIndex = index;
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            syncValue();
            setOpen(false);
            trigger.focus();
        };

        menu.id = `rg-select-menu-${index}`;
        menu.setAttribute('aria-label', label);
        trigger.setAttribute('aria-controls', menu.id);
        select.classList.forEach((className) => {
            if (!['form-select', 'rg-select-native'].includes(className)) trigger.classList.add(className);
        });
        select.classList.add('rg-select-native');
        select.setAttribute('aria-hidden', 'true');
        select.tabIndex = -1;
        enhancedSelects.add(select);
        syncValue();

        select.addEventListener('change', syncValue);
        select.addEventListener('input', syncValue);
        select.form?.addEventListener('reset', () => window.setTimeout(syncValue, 0));
        select.addEventListener('invalid', (event) => {
            event.preventDefault();
            attemptedInvalid = true;
            trigger.setAttribute('aria-invalid', 'true');
            open();
            trigger.focus();
        });
        select.addEventListener('change', () => {
            attemptedInvalid = false;
            trigger.removeAttribute('aria-invalid');
            trigger.classList.remove('is-invalid');
        });
        new MutationObserver(() => {
            renderMenu();
            syncValue();
        }).observe(select, {
            attributes: true,
            childList: true,
            characterData: true,
            subtree: true,
            attributeFilter: ['disabled', 'required', 'selected'],
        });

        trigger.addEventListener('click', () => menu.hidden ? open() : setOpen(false));
        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (menu.hidden) {
                    open();
                } else {
                    const direction = event.key === 'ArrowDown' ? 1 : -1;
                    setActive((activeIndex + direction + menuOptions.length) % menuOptions.length);
                }
            } else if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (menu.hidden) open();
                else if (activeIndex >= 0) choose(activeIndex);
            } else if (event.key === 'Escape' && !menu.hidden) {
                event.preventDefault();
                setOpen(false);
            } else if (event.key === 'Tab' && !menu.hidden) {
                setOpen(false);
            }
        });
        menu.addEventListener('pointerdown', (event) => {
            if (event.pointerType === 'mouse') event.preventDefault();
        });
        menu.addEventListener('click', (event) => {
            const option = event.target.closest('[role="option"]');
            if (option && option.getAttribute('aria-disabled') !== 'true') {
                choose(Number(option.dataset.index));
            }
        });
        wrapper.addEventListener('focusout', () => {
            window.setTimeout(() => {
                if (!wrapper.contains(document.activeElement)) setOpen(false);
            }, 0);
        });

        return wrapper;
    };

    const initialize = () => {
        let nextId = 0;
        const enhance = (root) => {
            if (root instanceof HTMLSelectElement) initializeSelect(root, nextId++);
            root.querySelectorAll?.('select:not([multiple])').forEach((select) => initializeSelect(select, nextId++));
        };

        enhance(document);
        new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node instanceof Element) enhance(node);
                });
                if (mutation.type === 'attributes' && mutation.target instanceof Element) {
                    enhance(mutation.target);
                }
            });
        }).observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['hidden'],
        });

        document.addEventListener('pointerdown', (event) => {
            document.querySelectorAll('.rg-select-menu:not([hidden])').forEach((menu) => {
                const wrapper = menu.closest('.rg-select-control');
                if (wrapper?.contains(event.target)) return;
                menu.hidden = true;
                wrapper?.querySelector('[data-select-trigger]')?.setAttribute('aria-expanded', 'false');
            });
        });
        const repositionOpenMenus = () => {
            document.querySelectorAll('.rg-select-menu:not([hidden])').forEach((menu) => {
                const trigger = menu.closest('.rg-select-control')?.querySelector('[data-select-trigger]');
                const bounds = trigger?.getBoundingClientRect();
                if (!bounds) return;
                const left = Math.max(8, Math.min(bounds.left, window.innerWidth - bounds.width - 8));
                const top = Math.min(bounds.bottom + 4, window.innerHeight - 120);
                menu.style.left = `${left}px`;
                menu.style.top = `${Math.max(8, top)}px`;
                menu.style.width = `${Math.min(bounds.width, window.innerWidth - 16)}px`;
                menu.style.maxHeight = `${Math.max(96, Math.min(288, window.innerHeight - top - 12))}px`;
            });
        };
        window.addEventListener('resize', repositionOpenMenus, { passive: true });
        window.addEventListener('scroll', repositionOpenMenus, { passive: true, capture: true });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
