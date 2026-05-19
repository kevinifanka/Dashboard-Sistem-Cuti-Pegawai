<?php
// app/views/layouts/admin/footer.php
?>

  </div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<!-- Global JS config -->
<script>window.EMS_API_URL = '<?= PUBLIC_URL ?>/api.php';</script>

<!-- Sidebar JS -->
<script src="<?= ASSET_URL ?>/js/sidebar.js"></script>

<!-- Page-specific JS -->
<?php if (!empty($pageJs)): ?>
  <?php foreach ((array)$pageJs as $js): ?>
    <script src="<?= ASSET_URL ?>/js/<?= htmlspecialchars($js) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

<!-- Initialize Lucide Icons -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  });
</script>

</body>
</html>
