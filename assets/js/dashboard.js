document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-dashboard-href]').forEach((element) => {
        element.addEventListener('click', () => {
            window.location.href = element.dataset.dashboardHref;
        });
    });
});
