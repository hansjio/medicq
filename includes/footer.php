    </main>
    
    <!-- JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <?php if (isset($pageScript)): ?>
    <script src="<?php echo SITE_URL; ?>/assets/js/<?php echo $pageScript; ?>.js"></script>
    <?php endif; ?>
</body>
</html>
