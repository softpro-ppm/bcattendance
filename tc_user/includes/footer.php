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
            console.log('Toggle button position:', $('.sidebar-toggle').css('position'));
            console.log('Toggle button z-index:', $('.sidebar-toggle').css('z-index'));
            
                    // Test click binding
        $('.sidebar-toggle').on('click', function() {
            console.log('Direct click test successful!');
        });
        
        // Test if button is clickable
        console.log('Button is clickable:', $('.sidebar-toggle').is(':visible'));
        
        // Check for any CSS conflicts
        console.log('Toggle button computed styles:', window.getComputedStyle($('.sidebar-toggle')[0]));
        
        // Additional event binding test
        $('.sidebar-toggle').on('mousedown', function() {
            console.log('Mousedown event triggered!');
        });
        
        // Check if jQuery events are properly bound
        console.log('jQuery events on toggle button:', $._data($('.sidebar-toggle')[0], 'events'));
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
        $('.sidebar-toggle').on('click touchstart mousedown', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Sidebar toggle clicked!');
            console.log('Button element:', this);
            console.log('Button classes:', this.className);
            console.log('Button styles:', this.style.cssText);
            console.log('Sidebar element:', $('#mainSidebar')[0]);
            console.log('Overlay element:', $('#sidebarOverlay')[0]);
            
            try {
                $('#mainSidebar').toggleClass('show');
                $('#sidebarOverlay').toggleClass('show');
                $('body').toggleClass('sidebar-open');
                
                console.log('Sidebar classes updated');
                console.log('Sidebar has show class:', $('#mainSidebar').hasClass('show'));
                console.log('Overlay has show class:', $('#sidebarOverlay').hasClass('show'));
                
                // Force a repaint
                $('#mainSidebar')[0].offsetHeight;
                
                console.log('Sidebar toggle completed successfully');
            } catch (error) {
                console.error('Error during sidebar toggle:', error);
            }
        });
        
        // Alternative sidebar toggle binding for better compatibility
        $('#mainSidebarToggle').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Main sidebar toggle clicked via ID!');
            
            $('#mainSidebar').toggleClass('show');
            $('#sidebarOverlay').toggleClass('show');
            $('body').toggleClass('sidebar-open');
        });
        
        // Ensure sidebar toggle works on all mobile devices
        $(document).on('click', '.sidebar-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Sidebar toggle clicked via document delegation!');
            
            $('#mainSidebar').toggleClass('show');
            $('#sidebarOverlay').toggleClass('show');
            $('body').toggleClass('sidebar-open');
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
        
        // Close sidebar when clicking on navigation links (MOBILE FIX)
        $('.nav-sidebar .nav-link').on('click', function(e) {
            // Only close sidebar on mobile devices
            if ($(window).width() <= 767.98) {
                console.log('Navigation link clicked, closing sidebar...');
                
                // Close the sidebar
                $('#mainSidebar').removeClass('show');
                $('#sidebarOverlay').removeClass('show');
                $('body').removeClass('sidebar-open');
                
                // Add a small delay to ensure smooth transition
                setTimeout(function() {
                    // Allow the link to proceed normally
                    console.log('Sidebar closed, allowing navigation...');
                }, 300);
            }
        });
        
        // Ensure all sidebar links work properly
        $('.nav-sidebar a').on('click', function(e) {
            // Check if the link is valid
            var href = $(this).attr('href');
            if (href && href !== '#' && href !== 'javascript:void(0)') {
                console.log('Navigating to:', href);
                
                // On mobile, close sidebar first
                if ($(window).width() <= 767.98) {
                    $('#mainSidebar').removeClass('show');
                    $('#sidebarOverlay').removeClass('show');
                    $('body').removeClass('sidebar-open');
                }
                
                // Allow normal navigation
                return true;
            }
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