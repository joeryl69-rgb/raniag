(() => {
    const moveModalToRoot = (element) => {
        if (element instanceof HTMLElement && element.matches('.modal') && element.parentElement !== document.body) {
            document.body.appendChild(element);
        }
    };

    document.querySelectorAll('.modal').forEach(moveModalToRoot);

    const observer = new MutationObserver((records) => {
        records.forEach((record) => {
            record.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) return;
                moveModalToRoot(node);
                node.querySelectorAll?.('.modal').forEach(moveModalToRoot);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
})();
