        </div> <!-- End container-fluid -->
    </div> <!-- End main-content -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Mobile Menu JS (inline para garantir carregamento) -->
    <?php
    $mobile_menu_js = __DIR__ . '/../js/mobile-menu.js';
    if (file_exists($mobile_menu_js)) {
        echo "<script>\n" . file_get_contents($mobile_menu_js) . "\n</script>";
    } else {
        echo '<script src="js/mobile-menu.js"></script>';
    }
    ?>

</body>
</html>
