(function () {
    document.querySelectorAll('[data-export-link]').forEach((link) => {
        link.addEventListener('click', () => {
            const original = link.textContent;
            link.dataset.originalText = original;
            link.textContent = 'Gerando...';
            link.classList.add('is-loading');

            window.setTimeout(() => {
                link.textContent = link.dataset.originalText || original;
                link.classList.remove('is-loading');
            }, 2500);
        });
    });
})();
