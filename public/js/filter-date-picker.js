(() => {
    const pad = (value) => String(value).padStart(2, '0');
    const toIso = (year, month, day) => `${year}-${pad(month)}-${pad(day)}`;
    const fromIso = (value) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
        if (!match) return null;

        const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
        return date.getFullYear() === Number(match[1])
            && date.getMonth() === Number(match[2]) - 1
            && date.getDate() === Number(match[3])
            ? date
            : null;
    };
    const displayDate = (date) => new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(date);
    const formatHint = new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(new Date(2001, 10, 22))
        .map((part) => part.type === 'year' ? 'yyyy' : part.type === 'month' ? 'mm' : part.type === 'day' ? 'dd' : part.value)
        .join('');

    const enhance = (source, index) => {
        if (source.dataset.datePickerEnhanced === 'true' || source.multiple) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'rg-date-picker';
        wrapper.innerHTML = `
            <div class="rg-date-picker-control">
                <input type="text" class="form-control" autocomplete="off"
                    inputmode="numeric" data-date-picker-input>
                <button type="button" class="rg-date-picker-toggle"
                    aria-label="Open calendar" aria-expanded="false" data-date-picker-toggle>
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                </button>
            </div>
            <div class="rg-date-picker-popover" role="dialog" hidden data-date-picker-popover>
                <div class="rg-date-picker-heading">
                    <button type="button" class="rg-date-picker-nav" aria-label="Previous month" data-date-picker-prev>
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </button>
                    <strong data-date-picker-month></strong>
                    <button type="button" class="rg-date-picker-nav" aria-label="Next month" data-date-picker-next>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="rg-date-picker-grid" role="group" data-date-picker-grid></div>
                <div class="rg-date-picker-footer">
                    <button type="button" data-date-picker-clear>Clear</button>
                    <button type="button" data-date-picker-today>Today</button>
                </div>
            </div>
        `;
        source.insertAdjacentElement('afterend', wrapper);

        const input = wrapper.querySelector('[data-date-picker-input]');
        const toggle = wrapper.querySelector('[data-date-picker-toggle]');
        const popover = wrapper.querySelector('[data-date-picker-popover]');
        const monthLabel = wrapper.querySelector('[data-date-picker-month]');
        const grid = wrapper.querySelector('[data-date-picker-grid]');
        const previous = wrapper.querySelector('[data-date-picker-prev]');
        const next = wrapper.querySelector('[data-date-picker-next]');
        const minDate = () => fromIso(source.min);
        const maxDate = () => fromIso(source.max);
        let isRequired = source.required;
        let viewDate = fromIso(source.value) || maxDate() || new Date();

        const formatMonth = (date) => new Intl.DateTimeFormat(undefined, {
            month: 'long',
            year: 'numeric',
        }).format(date);
        const weekdayLabels = Array.from({ length: 7 }, (_, day) => new Intl.DateTimeFormat(undefined, {
            weekday: 'short',
        }).format(new Date(2023, 0, 1 + day)));
        const parseTypedDate = (value) => {
            const isoDate = fromIso(value);
            if (isoDate) return isoDate;

            const numbers = value.match(/\d+/g) || [];
            if (numbers.length !== 3) return null;
            const parts = new Intl.DateTimeFormat(undefined, {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            }).formatToParts(new Date(2001, 10, 22))
                .filter((part) => ['year', 'month', 'day'].includes(part.type))
                .map((part) => part.type);
            const fields = Object.fromEntries(parts.map((part, index) => [part, Number(numbers[index])]));
            return fromIso(toIso(fields.year, fields.month, fields.day));
        };
        const setValidity = () => {
            const date = fromIso(source.value);
            const minimum = minDate();
            const maximum = maxDate();
            const outsideLimits = date && ((minimum && date < minimum) || (maximum && date > maximum));
            const invalidText = input.value.trim() && !parseTypedDate(input.value.trim());
            input.required = isRequired;
            input.setCustomValidity(invalidText
                ? `Enter a valid date, for example ${formatHint}.`
                : outsideLimits ? 'Choose a date within the allowed range.' : '');
        };
        const syncValue = () => {
            const date = fromIso(source.value);
            input.value = date ? displayDate(date) : '';
            viewDate = date || maxDate() || new Date();
            setValidity();
            if (!popover.hidden) renderCalendar();
        };
        const close = (restoreFocus = false) => {
            popover.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            if (restoreFocus) input.focus();
        };
        const choose = (date) => {
            source.value = toIso(date.getFullYear(), date.getMonth() + 1, date.getDate());
            input.value = displayDate(date);
            viewDate = date;
            setValidity();
            source.dispatchEvent(new Event('input', { bubbles: true }));
            source.dispatchEvent(new Event('change', { bubbles: true }));
            close();
            input.focus();
        };
        const isSelectable = (date) => {
            const minimum = minDate();
            const maximum = maxDate();
            return (!minimum || date >= minimum) && (!maximum || date <= maximum);
        };
        const renderCalendar = () => {
            const year = viewDate.getFullYear();
            const month = viewDate.getMonth();
            monthLabel.textContent = formatMonth(viewDate);
            grid.replaceChildren();

            weekdayLabels.forEach((weekday) => {
                const label = document.createElement('span');
                label.className = 'rg-date-picker-weekday';
                label.setAttribute('role', 'columnheader');
                label.textContent = weekday;
                grid.append(label);
            });

            const firstWeekday = new Date(year, month, 1).getDay();
            for (let blank = 0; blank < firstWeekday; blank++) {
                const spacer = document.createElement('span');
                spacer.className = 'rg-date-picker-spacer';
                spacer.setAttribute('aria-hidden', 'true');
                grid.append(spacer);
            }

            const today = toIso(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate());
            const selectedValue = source.value;
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const value = toIso(year, month + 1, day);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'rg-date-picker-day';
                button.textContent = String(day);
                button.dataset.date = value;
                button.setAttribute('aria-label', new Intl.DateTimeFormat(undefined, {
                    dateStyle: 'full',
                }).format(date));
                button.setAttribute('aria-current', value === today ? 'date' : 'false');
                button.setAttribute('aria-selected', String(value === selectedValue));
                button.disabled = !isSelectable(date);
                grid.append(button);
            }

            const previousMonth = new Date(year, month - 1, 1);
            const nextMonth = new Date(year, month + 1, 1);
            const minimum = minDate();
            const maximum = maxDate();
            previous.disabled = Boolean(minimum && new Date(previousMonth.getFullYear(), previousMonth.getMonth() + 1, 0) < minimum);
            next.disabled = Boolean(maximum && nextMonth > maximum);
        };
        const open = () => {
            viewDate = fromIso(source.value) || maxDate() || new Date();
            renderCalendar();
            const bounds = wrapper.getBoundingClientRect();
            popover.classList.toggle('is-right-aligned', bounds.left + 288 > window.innerWidth);
            popover.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        };
        const updateFromText = () => {
            const typed = input.value.trim();
            if (!typed) {
                source.value = '';
                setValidity();
                source.dispatchEvent(new Event('input', { bubbles: true }));
                source.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }

            const parsed = parseTypedDate(typed);
            if (parsed) source.value = toIso(parsed.getFullYear(), parsed.getMonth() + 1, parsed.getDate());

            setValidity();
            if (source.value) {
                source.dispatchEvent(new Event('input', { bubbles: true }));
                source.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                input.setCustomValidity(`Enter a valid date, for example ${formatHint}.`);
            }
        };

        input.id = `${source.id || `rg-date-picker-${index}`}-display`;
        input.setAttribute('aria-label', source.labels?.[0]?.textContent.trim() || source.name || 'Date');
        input.setAttribute('aria-haspopup', 'dialog');
        input.placeholder = formatHint;
        toggle.setAttribute('aria-controls', `${input.id}-calendar`);
        popover.id = `${input.id}-calendar`;
        source.dataset.datePickerEnhanced = 'true';
        source.hidden = true;
        source.required = false;
        wrapper.hidden = false;
        input.disabled = source.disabled;
        toggle.disabled = source.disabled;
        input.required = isRequired;
        syncValue();

        source.addEventListener('change', syncValue);
        source.addEventListener('input', syncValue);
        source.addEventListener('reset', () => window.setTimeout(syncValue, 0));
        source.form?.addEventListener('reset', () => window.setTimeout(syncValue, 0));
        new MutationObserver(() => {
            input.disabled = source.disabled;
            toggle.disabled = source.disabled;
            if (source.required !== isRequired) {
                isRequired = source.required;
                source.required = false;
            }
            input.required = isRequired;
            setValidity();
        }).observe(source, { attributes: true, attributeFilter: ['disabled', 'required', 'min', 'max'] });

        input.addEventListener('input', () => {
            if (input.value.trim()) open();
            setValidity();
        });
        input.addEventListener('change', updateFromText);
        input.addEventListener('blur', () => {
            if (!input.value.trim() || fromIso(source.value)) {
                input.setCustomValidity('');
                syncValue();
            }
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' && popover.hidden) {
                event.preventDefault();
                open();
            } else if (event.key === 'Escape' && !popover.hidden) {
                event.preventDefault();
                close();
            }
        });
        toggle.addEventListener('click', () => popover.hidden ? open() : close());
        previous.addEventListener('click', () => {
            viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1);
            renderCalendar();
        });
        next.addEventListener('click', () => {
            viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1);
            renderCalendar();
        });
        grid.addEventListener('click', (event) => {
            const day = event.target.closest('[data-date]');
            const date = fromIso(day?.dataset.date || '');
            if (date && !day.disabled) choose(date);
        });
        wrapper.querySelector('[data-date-picker-clear]').addEventListener('click', () => {
            source.value = '';
            input.value = '';
            setValidity();
            source.dispatchEvent(new Event('input', { bubbles: true }));
            source.dispatchEvent(new Event('change', { bubbles: true }));
            close();
            input.focus();
        });
        wrapper.querySelector('[data-date-picker-today]').addEventListener('click', () => {
            const today = new Date();
            const maximum = maxDate();
            const minimum = minDate();
            if (maximum && today > maximum) choose(maximum);
            else if (minimum && today < minimum) choose(minimum);
            else choose(today);
        });
        wrapper.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !popover.hidden) close(true);
        });
        wrapper.addEventListener('focusout', () => {
            window.setTimeout(() => {
                if (!wrapper.contains(document.activeElement)) close();
            }, 0);
        });
    };

    const initialize = () => {
        let nextId = 0;
        const enhanceDates = (root) => {
            if (root instanceof HTMLInputElement && root.type === 'date') enhance(root, nextId++);
            root.querySelectorAll?.('input[type="date"]').forEach((input) => enhance(input, nextId++));
        };
        enhanceDates(document);
        new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node instanceof Element) enhanceDates(node);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
        document.addEventListener('pointerdown', (event) => {
            document.querySelectorAll('[data-date-picker-popover]:not([hidden])').forEach((popover) => {
                const wrapper = popover.closest('.rg-date-picker');
                if (wrapper?.contains(event.target)) return;
                popover.hidden = true;
                wrapper?.querySelector('[data-date-picker-toggle]')?.setAttribute('aria-expanded', 'false');
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
