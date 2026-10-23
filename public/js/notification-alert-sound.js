(function (global) {
    'use strict';

    let audioContext = null;

    function context() {
        const AudioContextClass = global.AudioContext || global.webkitAudioContext;
        if (!AudioContextClass) return null;

        try {
            audioContext = audioContext || new AudioContextClass();
            return audioContext;
        } catch (error) {
            console.warn('Notification alert sound could not be enabled:', error);
            return null;
        }
    }

    function prime() {
        const ctx = context();
        if (!ctx) return Promise.resolve(false);
        if (ctx.state === 'suspended') {
            return Promise.resolve(ctx.resume()).then(function () {
                return ctx.state === 'running';
            }).catch(function () {
                return false;
            });
        }

        return Promise.resolve(ctx.state === 'running');
    }

    function tone(ctx, destination, frequency, start, duration, peak) {
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();
        const noteStart = ctx.currentTime + 0.02 + start;
        oscillator.type = 'triangle';
        oscillator.frequency.setValueAtTime(frequency, noteStart);
        gain.gain.setValueAtTime(0.0001, noteStart);
        gain.gain.exponentialRampToValueAtTime(peak, noteStart + 0.012);
        gain.gain.exponentialRampToValueAtTime(0.0001, noteStart + duration);
        oscillator.connect(gain);
        gain.connect(destination);
        oscillator.start(noteStart);
        oscillator.stop(noteStart + duration + 0.02);
    }

    function play() {
        const ctx = context();
        if (!ctx || ctx.state !== 'running') return false;

        const master = ctx.createGain();
        master.gain.value = 0.85;
        master.connect(ctx.destination);

        // One short rising chime. Loud enough to hear, smooth rather than buzzy.
        tone(ctx, master, 1046.5, 0, 0.24, 0.62);
        tone(ctx, master, 1318.5, 0.11, 0.26, 0.55);
        tone(ctx, master, 1568, 0.22, 0.46, 0.48);

        return true;
    }

    global.RANIAGAlertSound = { unlock: prime, prime: prime, play: play };
})(window);
