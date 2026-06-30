document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toast').forEach(t => new bootstrap.Toast(t).show());

    let pendingForm = null;
    const modalEl = document.getElementById('confirmModal');
    const yesBtn = document.getElementById('confirmModalYes');
    const confirmModal = modalEl ? new bootstrap.Modal(modalEl) : null;
    document.querySelectorAll('form.confirm-form').forEach(form => {
        form.addEventListener('submit', e => {
            e.preventDefault();
            pendingForm = form;
            confirmModal?.show();
        });
    });
    yesBtn?.addEventListener('click', () => {
        if (pendingForm) {
            pendingForm.classList.add('loading');
            pendingForm.submit();
        }
    });

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('button[type="submit"],button:not([type])');
            if (btn && !form.classList.contains('no-loader')) {
                btn.dataset.originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing';
            }
        });
    });

    const searchInput = document.querySelector('[data-ajax-product-search]');
    const productResults = document.querySelector('[data-product-results]');
    let timer = null;
    if (searchInput && productResults) {
        const form = searchInput.closest('form');
        const fetchProducts = () => {
            clearTimeout(timer);
            timer = setTimeout(async () => {
                const params = new URLSearchParams(new FormData(form));
                params.set('ajax', '1');
                productResults.classList.add('loading');
                try {
                    const response = await fetch(form.action + '?' + params.toString(), {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                    productResults.innerHTML = await response.text();
                } finally {
                    productResults.classList.remove('loading');
                }
            }, 350);
        };
        searchInput.addEventListener('input', fetchProducts);
        form.querySelectorAll('select,input[type="number"]').forEach(el => el.addEventListener('change', fetchProducts));
    }

    document.querySelectorAll('[data-print]').forEach(btn => btn.addEventListener('click', () => window.print()));
});
