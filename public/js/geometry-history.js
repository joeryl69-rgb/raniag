(function (global) {
    function clone(value) {
        return value === null ? null : JSON.parse(JSON.stringify(value));
    }

    function create(initialValue, apply, onChange, limit) {
        const maxEntries = Number.isInteger(limit) && limit > 1 ? limit : 100;
        let entries = [clone(initialValue)];
        let cursor = 0;

        function state() {
            return {
                canUndo: cursor > 0,
                canRedo: cursor < entries.length - 1,
            };
        }

        function notify() {
            if (typeof onChange === 'function') onChange(state());
        }

        function push(value) {
            const snapshot = clone(value);
            if (JSON.stringify(snapshot) === JSON.stringify(entries[cursor])) return false;

            entries = entries.slice(0, cursor + 1);
            entries.push(snapshot);
            if (entries.length > maxEntries) entries.shift();
            cursor = entries.length - 1;
            notify();
            return true;
        }

        return {
            push,
            reset(value) {
                entries = [clone(value)];
                cursor = 0;
                notify();
            },
            undo() {
                if (cursor === 0) return false;
                cursor -= 1;
                apply(clone(entries[cursor]));
                notify();
                return true;
            },
            redo() {
                if (cursor >= entries.length - 1) return false;
                cursor += 1;
                apply(clone(entries[cursor]));
                notify();
                return true;
            },
            state,
        };
    }

    global.RaniagGeometryHistory = { create };
})(window);
