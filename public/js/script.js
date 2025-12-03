// public/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    // Message Box functionality
    const messageBox = document.getElementById('messageBox');
    if (messageBox) {
        // Show the message box
        messageBox.classList.add('show');

        // Hide the message box after 5 seconds
        setTimeout(() => {
            messageBox.classList.remove('show');
            // Optionally remove from DOM after fading out
            setTimeout(() => {
                messageBox.remove();
            }, 500); // Wait for fade-out transition
        }, 5000);
    }

    // Delete confirmation (assuming it's handled via JavaScript)
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.id;
            const productName = this.dataset.productname;
            if (confirm(`Are you sure you want to delete item "${productName}"? This action cannot be undone.`)) {
                window.location.href = `?action=delete&id=${itemId}`;
            }
        });
    });

    // Image Preview functionality for item_form.php
    const imgInput = document.getElementById('img');
    const imgPreview = document.getElementById('imagePreview');
    // IMPORTANT: Ensure this placeholder URL matches the one in ItemController and item_form.php
    const DEFAULT_PLACEHOLDER_IMG = 'https://placehold.co/100x100/aabbcc/ffffff?text=No+Image';

    if (imgInput && imgPreview) {
        imgInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            } else {
                // If no file selected (e.g., input cleared), revert to placeholder
                imgPreview.src = DEFAULT_PLACEHOLDER_IMG;
            }
        });
    }

    // Toggle Club field based on Grafted checkbox for item_form.php
    const graftedCheckbox = document.getElementById('grafted');
    const clubFieldContainer = document.getElementById('clubFieldContainer');

    function toggleClubField() {
        if (graftedCheckbox.checked) {
            clubFieldContainer.classList.remove('d-none');
            clubFieldContainer.querySelector('input').setAttribute('required', 'required'); // Make required when visible
        } else {
            clubFieldContainer.classList.add('d-none');
            clubFieldContainer.querySelector('input').removeAttribute('required'); // Remove required when hidden
            clubFieldContainer.querySelector('input').value = ''; // Clear value when hidden
        }
    }

    if (graftedCheckbox && clubFieldContainer) {
        // Initial state on page load
        toggleClubField();
        // Listen for changes
        graftedCheckbox.addEventListener('change', toggleClubField);
    }


    // Filter logic for item_list.php (if still active, this might be redundant with form submission)
    const filterGraftedSelect = document.getElementById('filter_grafted');
    const filterClubContainer = document.getElementById('filterClubContainer');

    if (filterGraftedSelect && filterClubContainer) {
        function toggleFilterClubField() {
            if (filterGraftedSelect.value === '1') { // '1' means grafted (finished)
                filterClubContainer.style.display = 'block';
            } else {
                filterClubContainer.style.display = 'none';
                document.getElementById('filter_club').value = ''; // Clear club filter if not showing grafted
            }
        }

        // Initial check
        toggleFilterClubField();

        // Event listener for filter_grafted select change
        filterGraftedSelect.addEventListener('change', toggleFilterClubField);
    }
});



// document.addEventListener('DOMContentLoaded', function () {
//     const sidebarToggle = document.getElementById('sidebarToggle');
//     const sidebar = document.getElementById('sidebar');
//     const mainContent = document.getElementById('mainContent'); // Get main content
//     const sidebarTexts = sidebar.querySelectorAll('.sidebar-text');

//     // Define initial states for desktop (md and up)
//     const sidebarExpandedClasses = ['col-md-3', 'col-lg-2'];
//     const mainContentExpandedClasses = ['col-md-9', 'ms-sm-auto', 'col-lg-10'];

//     // Define classes for the collapsed state
//     // 'col-md-auto' will make it as wide as its content (icons + padding)
//     const sidebarCollapsedClasses = ['col-md-auto']; 
//     // 'col-md' will take up the remaining space, 'ms-sm-0' to remove margin-start
//     const mainContentCollapsedClasses = ['col-md', 'ms-sm-0']; 

//     // Initialize collapsed state for screens smaller than md (mobile)
//     // This makes the sidebar collapsed by default on mobile, expanded on desktop
//     let collapsed = window.innerWidth < 768; // Bootstrap's 'md' breakpoint is typically 768px

//     // Function to apply/remove classes based on collapsed state
//     function applySidebarState() {
//         if (collapsed) {
//             // Collapse sidebar
//             sidebarTexts.forEach(span => {
//                 span.style.display = 'none'; // Hide text
//             });
//             sidebar.classList.remove(...sidebarExpandedClasses);
//             sidebar.classList.add(...sidebarCollapsedClasses);

//             // Expand main content
//             mainContent.classList.remove(...mainContentExpandedClasses);
//             mainContent.classList.add(...mainContentCollapsedClasses);
//         } else {
//             // Expand sidebar
//             sidebarTexts.forEach(span => {
//                 span.style.display = ''; // Show text
//             });
//             sidebar.classList.remove(...sidebarCollapsedClasses);
//             sidebar.classList.add(...sidebarExpandedClasses);

//             // Shrink main content
//             mainContent.classList.remove(...mainContentCollapsedClasses);
//             mainContent.classList.add(...mainContentExpandedClasses);
//         }
//     }

//     // Initial application of state on load
//     applySidebarState();

//     // Event listener for the toggle button
//     sidebarToggle.addEventListener('click', function () {
//         collapsed = !collapsed;
//         applySidebarState();
//     });

//     // Optional: Re-apply state on window resize for responsiveness
//     // This ensures the sidebar behaves correctly if the user resizes the browser window
//     window.addEventListener('resize', function() {
//         // Only adjust based on breakpoint change if current state is not manual toggle
//         // Or re-evaluate `collapsed` based on window width
//         const isMobileView = window.innerWidth < 768;
//         if ((isMobileView && !collapsed) || (!isMobileView && collapsed)) {
//             // Re-evaluate if we are on mobile or desktop and set default
//             collapsed = isMobileView;
//             applySidebarState();
//         }
//     });
// });


// Toggle sidebar
document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarTexts = sidebar.querySelectorAll('.sidebar-text');

    function toggleSidebar() {
        const collapsed = sidebar.classList.contains('col-md-auto');

        if (collapsed) {
            sidebar.classList.remove('col-md-auto');
            sidebar.classList.add('col-md-3', 'col-lg-2');

            mainContent.classList.remove('col-md', 'ms-sm-0');
            mainContent.classList.add('col-md-9', 'ms-sm-auto', 'col-lg-10');

            sidebarTexts.forEach(el => el.style.display = '');
        } else {
            sidebar.classList.remove('col-md-3', 'col-lg-2');
            sidebar.classList.add('col-md-auto');

            mainContent.classList.remove('col-md-9', 'ms-sm-auto', 'col-lg-10');
            mainContent.classList.add('col-md', 'ms-sm-0');

            sidebarTexts.forEach(el => el.style.display = 'none');
        }

        sessionStorage.setItem('sidebarCollapsed', !collapsed);
    }

    sidebarToggle.addEventListener('click', toggleSidebar);
});
