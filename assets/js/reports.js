document.addEventListener('DOMContentLoaded', () => {
    const scrollKey = 'reports-period-scroll-y';
    const scrollContainer = document.querySelector('main');
    if (!scrollContainer) return;

    const rememberPosition = () => sessionStorage.setItem(scrollKey, String(scrollContainer.scrollTop));
    document.querySelector('form[method="get"]')?.addEventListener('submit', rememberPosition);
    document.querySelectorAll('form[method="get"] a[href*="mode="]').forEach((link) => {
        link.addEventListener('click', rememberPosition);
    });
    document.querySelector('select[name="period"]')?.addEventListener('change', (event) => {
        rememberPosition();
        event.target.form.submit();
    });

    const savedPosition = sessionStorage.getItem(scrollKey);
    if (savedPosition === null) return;
    sessionStorage.removeItem(scrollKey);
    const restorePosition = () => { scrollContainer.scrollTop = Number(savedPosition) || 0; };
    window.addEventListener('load', restorePosition, { once: true });
    window.addEventListener('pageshow', restorePosition, { once: true });
    requestAnimationFrame(() => requestAnimationFrame(restorePosition));
    window.setTimeout(restorePosition, 100);
    window.setTimeout(restorePosition, 400);
});
