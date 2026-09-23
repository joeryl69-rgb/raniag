/**
 * JO — public site guide (first-time visitors).
 * Prefs in localStorage key `raniag_guide`.
 */
(function () {
    const STORAGE_KEY = 'raniag_guide';
    const ASSET = '/images/guide';

    const NAV_TOUR = [
        { sel: '[data-rg-tour="home"]', pose: 'greeting', text: 'Home is where you start — overview, announcements, and how reporting works.' },
        { sel: '[data-rg-tour="track"]', pose: 'clipboard', text: 'Track Report — check status anytime with your tracking or reference code.' },
        { sel: '[data-rg-tour="hazard"]', pose: 'map', text: 'Hazard Map — live hazard zones and open evacuation centers in Pamplona.' },
        { sel: '[data-rg-tour="dashboard"]', pose: 'clipboard', text: 'Community Dashboard — a public picture of reports and situational updates.' },
        { sel: '[data-rg-tour="support"]', pose: 'phone', text: 'Support — contact MDRRMO for help. Use Report an Incident to file a new case.' },
        { sel: '[data-rg-tour="report"]', pose: 'alert', text: 'Report an Incident — file a new report. I can walk you through it the first time.' },
    ];

    const REPORT_LINES = [
        { pose: 'greeting', text: 'Pick the incident type that fits best, then describe what happened.' },
        { pose: 'gps', text: 'Pin the location. Use current location when you can so responders find the scene fast.' },
        { pose: 'camera', text: 'Add GPS-tagged photos if you have them. Evidence helps — you can continue without it.' },
        { pose: 'clipboard', text: 'Leave contact details or stay anonymous (anonymous needs evidence). Then submit.' },
    ];

    function readPrefs() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return {};
            const parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function writePrefs(patch) {
        const next = { ...readPrefs(), ...patch };
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
        } catch (e) { /* private mode */ }
        return next;
    }

    function poseUrl(pose) {
        return `${ASSET}/jo-${pose}.svg`;
    }

    function ensureDock() {
        let dock = document.getElementById('jo-guide-dock');
        if (dock) return dock;

        dock = document.createElement('div');
        dock.id = 'jo-guide-dock';
        dock.className = 'jo-guide-dock d-none';
        dock.setAttribute('role', 'dialog');
        dock.setAttribute('aria-label', 'JO guide');
        dock.innerHTML = `
            <div class="jo-guide-card">
                <img class="jo-guide-avatar" id="jo-guide-avatar" src="${poseUrl('greeting')}" alt="JO" width="72" height="90">
                <div class="jo-guide-body">
                    <div class="jo-guide-name">JO</div>
                    <p class="jo-guide-text mb-2" id="jo-guide-text"></p>
                    <div class="jo-guide-actions" id="jo-guide-actions"></div>
                </div>
                <button type="button" class="jo-guide-close btn-close" id="jo-guide-close" aria-label="Close guide"></button>
            </div>
        `;
        document.body.appendChild(dock);
        return dock;
    }

    function setPose(pose) {
        const img = document.getElementById('jo-guide-avatar');
        if (img) img.src = poseUrl(pose || 'greeting');
    }

    function setText(text) {
        const el = document.getElementById('jo-guide-text');
        if (el) el.textContent = text;
    }

    function setActions(nodes) {
        const wrap = document.getElementById('jo-guide-actions');
        if (!wrap) return;
        wrap.innerHTML = '';
        (nodes || []).forEach((n) => wrap.appendChild(n));
    }

    function btn(label, className, onClick) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = className;
        b.textContent = label;
        b.addEventListener('click', onClick);
        return b;
    }

    function showDock() {
        ensureDock().classList.remove('d-none');
    }

    function hideDock() {
        const dock = document.getElementById('jo-guide-dock');
        if (dock) dock.classList.add('d-none');
        clearNavHighlight();
    }

    function clearNavHighlight() {
        document.querySelectorAll('.jo-tour-spotlight').forEach((el) => el.classList.remove('jo-tour-spotlight'));
    }

    function highlight(sel) {
        clearNavHighlight();
        const el = document.querySelector(sel);
        if (el) {
            el.classList.add('jo-tour-spotlight');
            el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    let tourIndex = 0;

    function finishSiteTour() {
        writePrefs({ siteTourDone: true });
        clearNavHighlight();
        hideDock();
    }

    function renderTourStep() {
        const step = NAV_TOUR[tourIndex];
        if (!step) {
            setPose('resolved');
            setText('You’re set. Report an incident or track an existing one whenever you need.');
            setActions([
                btn('Report an Incident', 'btn btn-primary btn-sm', () => {
                    finishSiteTour();
                    window.location.href = document.querySelector('[data-rg-tour="report"]')?.href || '/report';
                }),
                btn('Got it', 'btn btn-outline-secondary btn-sm', finishSiteTour),
            ]);
            return;
        }
        setPose(step.pose);
        setText(step.text);
        highlight(step.sel);
        setActions([
            btn(tourIndex === 0 ? 'Next' : 'Next', 'btn btn-primary btn-sm', () => {
                tourIndex += 1;
                renderTourStep();
            }),
            btn('Skip', 'btn btn-link btn-sm', finishSiteTour),
        ]);
    }

    function startSiteTour() {
        tourIndex = 0;
        showDock();
        renderTourStep();
    }

    function offerSiteTour() {
        const prefs = readPrefs();
        if (prefs.siteTourDone) return;

        showDock();
        setPose('greeting');
        setText('Hi, I’m JO. New here? I’ll show you what each menu does.');
        setActions([
            btn('Show me', 'btn btn-primary btn-sm', startSiteTour),
            btn('Skip', 'btn btn-outline-secondary btn-sm', finishSiteTour),
        ]);

        document.getElementById('jo-guide-close')?.addEventListener('click', finishSiteTour, { once: true });
    }

    function replaySiteTour() {
        writePrefs({ siteTourDone: false });
        startSiteTour();
    }

    function pageTip(pageKey, pose, text) {
        const prefs = readPrefs();
        const seen = prefs.seenPages && typeof prefs.seenPages === 'object' ? prefs.seenPages : {};
        if (seen[pageKey] || !prefs.siteTourDone) return;

        showDock();
        setPose(pose);
        setText(text);
        setActions([
            btn('Got it', 'btn btn-primary btn-sm', () => {
                writePrefs({ seenPages: { ...seen, [pageKey]: true } });
                hideDock();
            }),
        ]);
        document.getElementById('jo-guide-close')?.addEventListener('click', () => {
            writePrefs({ seenPages: { ...seen, [pageKey]: true } });
            hideDock();
        }, { once: true });
    }

    /** Report wizard coach API */
    function reportCoachShouldShow() {
        return !readPrefs().reportCoachDismissed;
    }

    function dismissReportCoach() {
        writePrefs({ reportCoachDismissed: true });
    }

    function resetReportCoach() {
        writePrefs({ reportCoachDismissed: false });
    }

    function syncReportCoach(step, opts) {
        const panel = document.getElementById('jo-report-coach');
        const show = opts?.force || reportCoachShouldShow();
        document.body.classList.toggle('jo-report-coach-on', show);
        if (panel) panel.classList.toggle('d-none', !show);

        const line = REPORT_LINES[step] || REPORT_LINES[0];
        const pose = opts?.pose || line.pose;
        const text = opts?.text || line.text;
        const src = poseUrl(pose);

        const img = panel?.querySelector('.jo-report-coach-avatar');
        const txt = panel?.querySelector('.jo-report-coach-text');
        if (img) img.src = src;
        if (txt) txt.textContent = text;

        const mobile = document.getElementById('jo-report-coach-mobile');
        if (mobile) {
            mobile.classList.toggle('d-none', !show);
            const mImg = mobile.querySelector('.jo-mobile-avatar');
            const mTxt = mobile.querySelector('.jo-mobile-text');
            if (mImg) mImg.src = src;
            if (mTxt) mTxt.textContent = text;
        }
    }

    function initReportCoachUi() {
        const panel = document.getElementById('jo-report-coach');
        if (!panel) return;

        panel.querySelector('[data-jo-dismiss]')?.addEventListener('click', () => {
            dismissReportCoach();
            syncReportCoach(0);
            const help = document.getElementById('jo-need-help');
            if (help) help.classList.remove('d-none');
        });

        document.getElementById('jo-need-help')?.addEventListener('click', () => {
            resetReportCoach();
            const step = Number(document.querySelector('.report-wizard-pane.is-active')?.dataset.wizardStep || 0);
            syncReportCoach(step, { force: true });
            document.getElementById('jo-need-help')?.classList.add('d-none');
        });

        if (reportCoachShouldShow()) {
            syncReportCoach(0);
            document.getElementById('jo-need-help')?.classList.add('d-none');
        } else {
            panel.classList.add('d-none');
            document.getElementById('jo-need-help')?.classList.remove('d-none');
        }
    }

    window.RANIAG_JO = {
        readPrefs,
        writePrefs,
        offerSiteTour,
        startSiteTour,
        replaySiteTour,
        finishSiteTour,
        pageTip,
        reportCoachShouldShow,
        dismissReportCoach,
        syncReportCoach,
        initReportCoachUi,
        poseUrl,
        REPORT_LINES,
    };

    function boot() {
        ensureDock();
        document.getElementById('jo-replay-tour')?.addEventListener('click', (e) => {
            e.preventDefault();
            replaySiteTour();
        });
        document.getElementById('jo-start-tour-home')?.addEventListener('click', (e) => {
            e.preventDefault();
            startSiteTour();
        });

        const page = document.body.dataset.joPage;
        if (page === 'report') {
            initReportCoachUi();
        } else if (page === 'hazard') {
            offerSiteTour();
            pageTip('hazard', 'map', 'Toggle layers in the panel. Active hazard zones pulse so you can spot them quickly.');
        } else if (page === 'track') {
            offerSiteTour();
            pageTip('track', 'clipboard', 'Enter your tracking number here to see status updates.');
        } else if (page === 'support') {
            offerSiteTour();
            pageTip('support', 'phone', 'Send a support message here — for a new incident, use Report an Incident instead.');
        } else if (page === 'dashboard') {
            offerSiteTour();
            pageTip('dashboard', 'clipboard', 'This dashboard shows community-facing incident activity at a glance.');
        } else {
            offerSiteTour();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
