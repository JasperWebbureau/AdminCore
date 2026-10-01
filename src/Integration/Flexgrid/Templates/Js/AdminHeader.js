if (!window.adminSharedPeriodYearBound) {
    window.adminSharedPeriodYearBound = true;
    document.addEventListener('change', function (event) {
        const year = event.target instanceof Element ? event.target.closest('[data-admin-period-year]') : null;
        if (!year || !year.form) return;
        const quarter = year.form.querySelector('[data-admin-period-quarter]');
        if (!quarter) return;
        if (year.value === '') quarter.value = '';
        quarter.disabled = year.value === '';
    });
}
