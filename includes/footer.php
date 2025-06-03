</div><!-- .page-content -->
            </main>
        </div><!-- .main-container -->
        
        <footer class="app-footer">
		
		<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const monthSelect = document.getElementById('month');

    function resetMonth() {
        if (dateFrom.value || dateTo.value) {
            monthSelect.selectedIndex = 0;
        }
    }

    function resetDates() {
        if (monthSelect.value) {
            dateFrom.value = '';
            dateTo.value = '';
        }
    }

    dateFrom.addEventListener('change', resetMonth);
    dateTo.addEventListener('change', resetMonth);
    monthSelect.addEventListener('change', resetDates);
});
</script>
		
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $app_name; ?> v<?php echo $app_version; ?></p>
            </div>
        </footer>
    </div><!-- .app-container -->
    
    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>