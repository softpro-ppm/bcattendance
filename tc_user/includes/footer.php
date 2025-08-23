                </div>
                <!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        

    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Note: Removed admin.js to prevent dropdown conflicts -->
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <script>
    $(document).ready(function() {
        // Debug: Check if sidebar elements exist
        console.log('Document ready - checking sidebar elements...');
        console.log('Sidebar toggle button:', $('.sidebar-toggle').length);
        console.log('Main sidebar:', $('#mainSidebar').length);
        console.log('Sidebar overlay:', $('#sidebarOverlay').length);
        
        // Additional debugging
        console.log('Window width:', $(window).width());
        console.log('Is mobile:', $(window).width() <= 767.98);
        
        // Check if elements are visible
        if ($('.sidebar-toggle').length > 0) {
            console.log('Toggle button display:', $('.sidebar-toggle').css('display'));
            console.log('Toggle button visibility:', $('.sidebar-toggle').css('visibility'));
            console.log('Toggle button opacity:', $('.sidebar-toggle').css('opacity'));
            
            // Test click binding
            $('.sidebar-toggle').on('click', function() {
                console.log('Direct click test successful!');
            });
            
            // Test if button is clickable
            console.log('Button is clickable:', $('.sidebar-toggle').is(':visible'));
        }
        
        // Initialize DataTables with simplified settings (no search, no entries dropdown)
        if ($.fn.DataTable) {
            $('.data-table, table.table').DataTable({
                "pageLength": 25,
                "responsive": true,
                "searching": false,    // Remove search box
                "lengthChange": false, // Remove "show entries" dropdown
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
        }
        
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Auto-hide alerts after 10 seconds
        $('.alert').delay(10000).fadeOut();
        
        // Top navbar dropdown functionality (fixed)
        $('.navbar-nav .dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $dropdown = $(this).siblings('.dropdown-menu');
            var $allDropdowns = $('.dropdown-menu');
            
            // Close all other dropdowns first
            $allDropdowns.removeClass('show');
            
            // Toggle current dropdown
            $dropdown.addClass('show');
        });
        
        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.nav-item.dropdown').length) {
                $('.dropdown-menu').removeClass('show');
            }
        });
        
        // Sidebar toggle for mobile - multiple event binding methods
        $('.sidebar-toggle').on('click touchstart', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Sidebar toggle clicked!');
            console.log('Button element:', this);
            console.log('Button classes:', this.className);
            console.log('Button styles:', this.style.cssText);
            console.log('Sidebar element:', $('#mainSidebar')[0]);
            console.log('Overlay element:', $('#sidebarOverlay')[0]);
            
            $('#mainSidebar').toggleClass('show');
            $('#sidebarOverlay').toggleClass('show');
            $('body').toggleClass('sidebar-open');
            
            console.log('Sidebar classes updated');
            console.log('Sidebar has show class:', $('#mainSidebar').hasClass('show'));
            console.log('Overlay has show class:', $('#sidebarOverlay').hasClass('show'));
            
            // Force a repaint
            $('#mainSidebar')[0].offsetHeight;
        });
        
        // Close sidebar when clicking overlay
        $('#sidebarOverlay').on('click', function() {
            $('#mainSidebar').removeClass('show');
            $('#sidebarOverlay').removeClass('show');
            $('body').removeClass('sidebar-open');
        });
        
        // Close sidebar when clicking close button
        $('.sidebar-close').on('click', function() {
            $('#mainSidebar').removeClass('show');
            $('#sidebarOverlay').removeClass('show');
            $('body').removeClass('sidebar-open');
        });
        
        // Close sidebar when clicking outside on mobile
        $(document).on('click', function(e) {
            if ($(window).width() <= 767.98) {
                if (!$(e.target).closest('.main-sidebar, .sidebar-toggle').length) {
                    $('#mainSidebar').removeClass('show');
                    $('#sidebarOverlay').removeClass('show');
                    $('body').removeClass('sidebar-open');
                }
            }
        });
        
        // Close sidebar when window is resized to desktop
        $(window).on('resize', function() {
            if ($(window).width() > 767.98) {
                $('#mainSidebar').removeClass('show');
                $('#sidebarOverlay').removeClass('show');
                $('body').removeClass('sidebar-open');
            }
        });
        
        // Initialize confirmation dialogs
        $('[data-confirm]').on('click', function(e) {
            var message = $(this).data('confirm') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    </script>

</body>
</html>