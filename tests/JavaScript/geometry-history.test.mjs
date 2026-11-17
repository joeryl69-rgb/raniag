import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = await readFile(new URL('../../public/js/geometry-history.js', import.meta.url), 'utf8');
const context = { window: {} };
vm.runInNewContext(source, context);

const createHistory = context.window.RaniagGeometryHistory.create;
const assertJsonEqual = (actual, expected) => {
    assert.deepEqual(JSON.parse(JSON.stringify(actual)), expected);
};

test('geometry history restores snapshots and clears redo after a new edit', () => {
    const applied = [];
    const history = createHistory({ type: 'Point', coordinates: [1, 2] }, (value) => applied.push(value));

    history.push({ type: 'Point', coordinates: [3, 4] });
    assert.equal(history.undo(), true);
    assertJsonEqual(applied.at(-1), { type: 'Point', coordinates: [1, 2] });
    assert.equal(history.redo(), true);
    assertJsonEqual(applied.at(-1), { type: 'Point', coordinates: [3, 4] });

    history.undo();
    history.push(null);
    assert.equal(history.redo(), false);
    assert.equal(history.state().canUndo, true);
    assert.equal(history.state().canRedo, false);
});

test('geometry history clones snapshots, ignores duplicates, and supports cleared geometry', () => {
    const applied = [];
    const initial = { type: 'Point', coordinates: [1, 2] };
    const history = createHistory(initial, (value) => applied.push(value));
    initial.coordinates[0] = 9;

    assert.equal(history.push({ type: 'Point', coordinates: [1, 2] }), false);
    assert.equal(history.push(null), true);
    assert.equal(history.undo(), true);
    assertJsonEqual(applied.at(-1), { type: 'Point', coordinates: [1, 2] });
    assert.equal(history.state().canUndo, false);
    assert.equal(history.state().canRedo, true);
});
