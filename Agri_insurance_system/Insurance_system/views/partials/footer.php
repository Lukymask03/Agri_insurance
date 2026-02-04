</main>
    <!-- End Main Content -->
    
    <!-- Footer -->
    <footer class="bg-dark text-light pt-5 pb-3 mt-auto">
        <div class="container">
            <div class="row">
                <!-- About Section -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="text-success mb-3 fw-bold">
                        <i class="bi bi-shield-check me-2"></i>AgriInsure
                    </h5>
                    <p class="text-muted" style="text-align: justify;">
                        Protecting farmers and their livelihoods through comprehensive agricultural insurance solutions. We provide coverage for crops and livestock, ensuring your farming investments are secure.
                    </p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="text-light fs-5" title="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light fs-5" title="Twitter"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-light fs-5" title="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-light fs-5" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-success mb-3 fw-bold">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="../index.php" class="text-muted text-decoration-none d-flex align-items-center">
                                <i class="bi bi-chevron-right me-1"></i>Home
                            </a>
                        </li>
                        <?php if ($isLoggedIn): ?>
                            <?php if ($userRole === 'farmer'): ?>
                                <li class="mb-2">
                                    <a href="../farmer/dashboard.php" class="text-muted text-decoration-none d-flex align-items-center">
                                        <i class="bi bi-chevron-right me-1"></i>Dashboard
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="../farmer/my_policies.php" class="text-muted text-decoration-none d-flex align-items-center">
                                        <i class="bi bi-chevron-right me-1"></i>My Policies
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="../farmer/file_claim.php" class="text-muted text-decoration-none d-flex align-items-center">
                                        <i class="bi bi-chevron-right me-1"></i>File Claim
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="mb-2">
                                    <a href="../admin_dashboard.php" class="text-muted text-decoration-none d-flex align-items-center">
                                        <i class="bi bi-chevron-right me-1"></i>Admin Dashboard
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="../manage_claims.php" class="text-muted text-decoration-none d-flex align-items-center">
                                        <i class="bi bi-chevron-right me-1"></i>Manage Claims
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li class="mb-2">
                                <a href="../login.php" class="text-muted text-decoration-none d-flex align-items-center">
                                    <i class="bi bi-chevron-right me-1"></i>Login
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="../register.php" class="text-muted text-decoration-none d-flex align-items-center">
                                    <i class="bi bi-chevron-right me-1"></i>Register
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <a href="#contact" class="text-muted text-decoration-none d-flex align-items-center">
                                <i class="bi bi-chevron-right me-1"></i>Contact Us
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Services -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="text-success mb-3 fw-bold">Our Services</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="#" class="text-muted text-decoration-none d-flex align-items-center">
                                <i class="bi bi-flower1 me-2 text-success"></i>Crop Insurance
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-muted text-decoration-none d-flex align-items-center">
                                <i class="bi bi-bucket me-2 text-success"></i>Livestock Insurance
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-muted text-decoration-none d-flex align-items-center">
                                <i class="bi bi-cloud-rain me-2 text-success"></i>Weather Protection
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-muted text-decoration-none d-flex align-items-center">
                                <i class="bi bi-clipboard-check me-2 text-success"></i>Quick Claims Process
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-muted text-decoration-none d-flex align-items-center">
                                <i class="bi bi-headset me-2 text-success"></i>24/7 Support
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="text-success mb-3 fw-bold">Contact Us</h6>
                    <ul class="list-unstyled text-muted">
                        <li class="mb-3">
                            <i class="bi bi-geo-alt-fill me-2 text-success"></i>
                            <small>123 Agricultural Avenue<br>Davao City, Philippines 8000</small>
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-telephone-fill me-2 text-success"></i>
                            <a href="tel:+639123456789" class="text-muted text-decoration-none">
                                <small>+63 912 345 6789</small>
                            </a>
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-envelope-fill me-2 text-success"></i>
                            <a href="mailto:info@agriinsure.com" class="text-muted text-decoration-none">
                                <small>info@agriinsure.com</small>
                            </a>
                        </li>
                        <li class="mb-3">
                            <i class="bi bi-clock-fill me-2 text-success"></i>
                            <small>Monday - Friday<br>8:00 AM - 5:00 PM</small>
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr class="bg-secondary my-4">
            
            <!-- Bottom Footer -->
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <p class="mb-0 text-muted small">
                        &copy; <?php echo date('Y'); ?> AgriInsure. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-muted text-decoration-none small me-3 hover-link">Privacy Policy</a>
                    <a href="#" class="text-muted text-decoration-none small me-3 hover-link">Terms of Service</a>
                    <a href="#" class="text-muted text-decoration-none small hover-link">FAQ</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scroll to Top Button -->
    <button id="scrollTopBtn" class="btn btn-success rounded-circle position-fixed shadow" 
            style="display: none; bottom: 20px; right: 20px; z-index: 1000; width: 50px; height: 50px;"
            title="Back to top">
        <i class="bi bi-arrow-up fs-5"></i>
    </button>
    
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Scroll to Top Button Functionality
        const scrollTopBtn = document.getElementById('scrollTopBtn');
        
        // Show/hide button based on scroll position
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollTopBtn.style.display = 'block';
            } else {
                scrollTopBtn.style.display = 'none';
            }
        });
        
        // Smooth scroll to top
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ 
                top: 0, 
                behavior: 'smooth' 
            });
        });
        
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Initialize DataTables for tables with class 'data-table'
        $(document).ready(function() {
            if ($.fn.DataTable) {
                $('.data-table').DataTable({
                    responsive: true,
                    pageLength: 10,
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });
            }
        });
        
        // Form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
        
        // Confirm delete actions
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this item? This action cannot be undone.');
        }
        
        // Format numbers with commas
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        // Add hover effect for footer links
        document.querySelectorAll('footer a').forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.color = '#fff';
            });
            link.addEventListener('mouseleave', function() {
                this.style.color = '';
            });
        });
    </script>
    
    <!-- Additional JS if needed -->
    <?php if (isset($additionalJS)) echo $additionalJS; ?>
</body>
</html>