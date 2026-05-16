            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <script>
        // Auto-dismiss alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
