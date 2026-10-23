(function (global) {
    'use strict';

    let audioContext = null;

    async function unlock() {
        const AudioContextClass = global.AudioContext || global.webkitAudioContext;
        if (!AudioContextClass) return false;

        try {
            audioContext = audioContext || new AudioContextClass();
            if (audioContext.state === 'suspended') await audioContext.resume();
            return audioContext.state === 'running';
        } catch (error) {
            console.warn('Notification alert sound could not be enabled:', error);
            return false;
        }
    }

    function chime(frequency, start, duration, peak) {
        const oscillator = audioContext.createOscillator();
        const gain = audioContext.createGain();
        const noteStart = audioContext.currentTime + 0.02 + start;
        oscillator.type = 'sine';
        oscillator.frequency.value = frequency;
        gain.gain.setValueAtTime(0.0001, noteStart);
        gain.gain.exponentialRampToValueAtTime(peak, noteStart + 0.012);
        gain.gain.exponentialRampToValueAtTime(0.0001, noteStart + duration);
        oscillator.connect(gain);
        gain.connect(audioContext.destination);
        oscillator.start(noteStart);
        oscillator.stop(noteStart + duration + 0.02);
    }

    async function play() {
        const unlocked = await unlock();
        if (!unlocked || audioContext?.state !== 'running') return false;

        // One short glass ding: a soft fundamental plus a quiet shimmer.
        chime(1318.5, 0, 0.62, 0.09);
        chime(1975.5, 0, 0.4, 0.02);
        chime(2637, 0.01, 0.28, 0.018);

        return true;
    }

    global.RANIAGAlertSound = { unlock, play };
})(window);
