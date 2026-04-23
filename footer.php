<footer class="footer-custom">
    <p>&copy; 2026 TripZo - Find the hidden gems of Addalaichenai</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.AOS) {
        AOS.init({
            duration: 650,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50
        });
    }

    if (window.GLightbox) {
        GLightbox({
            selector: '.tripzo-lightbox',
            touchNavigation: true,
            loop: true
        });
    }
});
</script>
</body>
</html>
