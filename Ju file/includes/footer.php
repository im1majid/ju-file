<?php /** تذييل الصفحة المشترك. متغير اختياري: $pageScripts (قائمة ملفات JS إضافية) */ ?>
</main>

<footer class="border-top py-4 mt-5 bg-white">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-secondary">
        <div><i class="bi bi-folder2-open text-accent"></i> <strong><?= e(APP_NAME) ?></strong> — كل أدوات ملفاتك في مكان واحد</div>
        <nav class="d-flex gap-3">
            <?php foreach (tool_categories() as $footerCat): ?>
                <a class="link-secondary text-decoration-none" href="<?= e($footerCat['page']) ?>"><?= e($footerCat['title']) ?></a>
            <?php endforeach; ?>
            <a class="link-secondary text-decoration-none" href="share.php">المشاركة</a>
        </nav>
        <div>&copy; <?= date('Y') ?></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/qrcode.js"></script>
<script src="assets/js/app.js"></script>
<?php foreach ($pageScripts ?? [] as $src): ?>
    <script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>

