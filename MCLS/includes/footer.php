            </main>
        </div>
        
        <!-- Footer -->
        <footer class="app-footer">
            <div style="text-align: center; font-size: 0.8rem;">
                <span>&copy; <?php echo date('Y'); ?> <?php echo APP_DEPARTMENT; ?></span>
                <span style="margin: 0 12px;">•</span>
                <span><?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></span>
                <span style="margin: 0 12px;">•</span>
                <span>For authorized users only</span>
            </div>
        </footer>
    </div>
    
    <!-- Core JavaScript -->
    <script src="<?php echo '/MCLS/assets/js/app.js'; ?>"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($page_js)): ?>
        <script>
            <?php echo $page_js; ?>
        </script>
    <?php endif; ?>
</body>
</html>