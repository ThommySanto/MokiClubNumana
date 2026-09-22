<?php
require_once dirname(__DIR__) . '/security_headers.php';
?>
<footer class="site-footer">
    <p>&copy; <?php echo date("Y"); ?> Moki Club Numana - Tutti i diritti riservati</p>
    <nav class="footer-legal-links" aria-label="Link legali">
        <a href="/privacy-policy.php">Privacy Policy</a>
        <a href="/cookie-policy.php">Cookie Policy</a>
    </nav>
</footer>

<script src="/assets/js/footer.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/footer.js'); ?>"></script>
<script src="/assets/js/cookie-banner.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/cookie-banner.js'); ?>"></script>
<script src="/assets/js/nav-mobile.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/nav-mobile.js'); ?>"></script>
</body>

</html>