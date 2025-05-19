document.addEventListener('DOMContentLoaded', function() {
    // Auto-detect mobile and set compact view
    function detectMobile() {
        return window.innerWidth <= 768;
    }

    // Initialize mobile optimizations
    function initMobileOptimizations() {
        const isMobile = detectMobile();

        if (isMobile) {
            // Auto-hide filters on mobile
            const filterToggle = document.querySelector('.mobile-filter-toggle button');
            const advancedFilters = document.querySelector('.advanced-filters');

            if (advancedFilters) {
                // Start with advanced filters collapsed on mobile
                advancedFilters.style.display = 'none';
            }

            // Add smooth scrolling for mobile cards
            const mobileCards = document.querySelectorAll('.fundi-card');
            mobileCards.forEach(card => {
                card.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });

                card.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        }
    }

    // Handle filter toggle animations
    function handleFilterAnimations() {
        const filterToggle = document.querySelector('.mobile-filter-toggle button');
        const advancedFilters = document.querySelector('.advanced-filters');

        if (filterToggle && advancedFilters) {
            filterToggle.addEventListener('click', function() {
                const isVisible = advancedFilters.style.display !== 'none';

                if (isVisible) {
                    // Hide with animation
                    advancedFilters.style.opacity = '0';
                    advancedFilters.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        advancedFilters.style.display = 'none';
                    }, 200);
                } else {
                    // Show with animation
                    advancedFilters.style.display = 'block';
                    advancedFilters.style.opacity = '0';
                    advancedFilters.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        advancedFilters.style.opacity = '1';
                        advancedFilters.style.transform = 'translateY(0)';
                    }, 10);
                }
            });
        }
    }

    // Add loading states for better UX
    function addLoadingStates() {
        const tableContainer = document.querySelector('.custom-table');
        const searchInput = document.querySelector('#search');

        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                tableContainer?.classList.add('loading');

                searchTimeout = setTimeout(() => {
                    tableContainer?.classList.remove('loading');
                }, 500);
            });
        }

        // Add loading state for filter changes
        const filterSelects = document.querySelectorAll('select[wire\\:model]');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                tableContainer?.classList.add('loading');
                setTimeout(() => {
                    tableContainer?.classList.remove('loading');
                }, 300);
            });
        });
    }

    // Enhance table interactions
    function enhanceTableInteractions() {
        // Add click-to-copy for phone numbers
        const phoneNumbers = document.querySelectorAll('.phone-number');
        phoneNumbers.forEach(phone => {
            phone.style.cursor = 'pointer';
            phone.title = 'Click to copy';

            phone.addEventListener('click', function(e) {
                e.preventDefault();
                const text = this.textContent.trim();

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        showToast('Phone number copied!');
                    });
                } else {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showToast('Phone number copied!');
                }
            });
        });
    }

    // Simple toast notification
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            font-size: 14px;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        }, 10);

        // Animate out and remove
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    // Handle window resize for responsive adjustments
    function handleWindowResize() {
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                initMobileOptimizations();
            }, 150);
        });
    }

    // Initialize everything
    initMobileOptimizations();
    handleFilterAnimations();
    addLoadingStates();
    enhanceTableInteractions();
    handleWindowResize();

    // Listen for Livewire updates and reinitialize
    document.addEventListener('livewire:updated', function() {
        setTimeout(() => {
            initMobileOptimizations();
            addLoadingStates();
            enhanceTableInteractions();
        }, 100);
    });
});

// Export for potential external use
window.FundisTable = {
    reinitialize: function() {
        initMobileOptimizations();
        addLoadingStates();
        enhanceTableInteractions();
    }
};
