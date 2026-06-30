<footer class="bg-dark text-white mt-5 py-4 no-print">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div><strong><?= e(setting('site_name', 'ShopEase')) ?></strong> &copy; <?= date('Y') ?>. All rights reserved.</div>
        <div><i class="bi bi-envelope"></i> <?= e(setting('site_email', 'support@shop.com')) ?> &nbsp; <i class="bi bi-telephone"></i> <?= e(setting('site_phone', '+8801700000000')) ?></div>
    </div>
</footer>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirm Action</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">Are you sure you want to continue?</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" id="confirmModalYes">Yes, Continue</button></div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
