<footer class="bg-light text-dark text-center py-2 position-fixed bottom-0 w-100">
    <div class="container">
        <small>&copy; <?php echo date('Y') ?> PT ARTHAWENASAKTI GEMILANG</small>
    </div>
</footer>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Script khusus untuk memperbaiki dropdown di mobile
    $(document).ready(function() {
        // Handle dropdown toggle di mobile
        $('.dropdown-toggle').on('click', function(e) {
            if ($(window).width() < 992) {
                e.preventDefault();
                var $menu = $(this).next('.dropdown-menu');
                $('.dropdown-menu').not($menu).removeClass('show');
                $menu.toggleClass('show');
            }
        });
        
        // Tutup dropdown ketika klik di luar
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.dropdown').length) {
                $('.dropdown-menu').removeClass('show');
            }
        });
    });
</script>
</body>

</html>