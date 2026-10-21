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

    async function play() {
        if (audioContext?.state !== 'running') return false;

        const notes = [
            { frequency: 660, start: 0 },
            { frequency: 880, start: 0.16 },
            { frequency: 660, start: 0.42 },
            { frequency: 880, start: 0.58 },
        ];
        const now = audioContext.currentTime + 0.02;

        notes.forEach(({ frequency, start }) => {
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            const noteStart = now + start;
            oscillator.type = 'triangle';
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(0.0001, noteStart);
            gain.gain.exponentialRampToValueAtTime(0.09, noteStart + 0.018);
            gain.gain.exponentialRampToValueAtTime(0.0001, noteStart + 0.13);
            oscillator.connect(gain);
            gain.connect(audioContext.destination);
            oscillator.start(noteStart);
            oscillator.stop(noteStart + 0.15);
        });

        return true;
    }

    global.RANIAGAlertSound = { unlock, play };
})(window);
