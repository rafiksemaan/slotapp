<?php
// Flush output buffer
ob_end_flush();
?>
</div><!-- .page-content -->
            </main>
        </div><!-- .main-container -->
        
        <footer class="app-footer">
		
		<div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $app_name; ?> v<?php echo $app_version; ?></p>
            </div>
        </footer>
        
        <!-- Personal Credit -->
        <div class="footer-credit">
            <p>Made with <span class="menu-icon heart"><img src="<?= icon('heart') ?>" alt="heart" /></span>by <strong>Raf</strong></p>
        </div>
    </div><!-- .app-container -->
    
    <!-- JavaScript -->
    <script src="assets/js/main.js" type="module"></script>
</body>
</html>
