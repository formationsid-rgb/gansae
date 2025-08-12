<?php
// Inclure les fonctions essentielles
require_once __DIR__.'/functions.php';
?>
<script src="assets/js/main.js"></script>
<script src="assets/js/dashboard.js"></script>
<script src="assets/js/upload.js"></script>
<script src="assets/js/scan.js"></script>
<script src="assets/js/indexing.js"></script>
<script src="assets/js/search.js"></script>
<?php if (has_role(ROLE_ADMIN)): ?>
<script src="assets/js/admin.js"></script>
<?php endif; ?>
</body>
</html>
