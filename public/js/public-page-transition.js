(function () {
    // Public navigation is a normal browser load. The previous handoff
    // delayed every link by 80ms and slid the new page into place, which
    // read as a knock on Home, Track, the map, the dashboard, and the
    // report form. Nothing here intercepts clicks.
    document.body?.classList.remove('rg-page-leaving', 'rg-page-arriving');
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
})();
