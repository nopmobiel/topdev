/**
 * TOP Application - UI Enhancements
 * 
 * This script adds subtle animations and interactive elements
 * to improve the user experience while maintaining the application's
 * professional appearance.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Form feedback
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(input => {
        // Add focus animation
        input.addEventListener('focus', function() {
            this.classList.add('input-active');
        });
        
        input.addEventListener('blur', function() {
            this.classList.remove('input-active');
        });
    });
    
    // Add fade-in effect to the form container
    const formContainer = document.querySelector('.form-container');
    if (formContainer) {
        formContainer.classList.add('fade-in');
    }
    
    // Add visual feedback to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            // Add a subtle pulse effect
            this.classList.add('btn-pulse');
            
            // Remove the effect after animation completes
            setTimeout(() => {
                this.classList.remove('btn-pulse');
            }, 500);
        });
    });
    
    // Add smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    
    // File input enhancement - show selected file name
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const fileLabel = document.createElement('div');
            fileLabel.className = 'file-selected';
            
            // Remove previous label if exists
            const previousLabel = document.querySelector('.file-selected');
            if (previousLabel) {
                previousLabel.remove();
            }
            
            if (this.files && this.files.length > 0) {
                fileLabel.textContent = 'Bestand geselecteerd: ' + this.files[0].name;
                this.parentNode.appendChild(fileLabel);
            }
        });
    }
    
    // Add animation to success/error messages
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.classList.add('alert-animate');
    });
});

// Add CSS classes for the animations
document.head.insertAdjacentHTML('beforeend', `
<style>
    .fade-in {
        animation: fadeIn 0.5s ease forwards;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .btn-pulse {
        animation: pulse 0.5s ease;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .input-active {
        transition: all 0.3s ease;
        border-color: #4169E1 !important;
        box-shadow: 0 0 0 0.2rem rgba(65, 105, 225, 0.25) !important;
    }
    
    .file-selected {
        margin-top: 8px;
        font-size: 0.9rem;
        color: #FFFFE0;
    }
    
    .alert-animate {
        animation: slideIn 0.5s ease forwards;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
</style>
`); 