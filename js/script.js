// Mobile Navigation
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

if(hamburger) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
    });
}

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-link').forEach(n => n.addEventListener('click', () => {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
}));

// Image Gallery for Product Details
function initImageGallery() {
    const mainImage = document.querySelector('.main-product-image');
    const thumbnails = document.querySelectorAll('.thumbnail');
    
    if(mainImage && thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                // Remove active class from all thumbnails
                thumbnails.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked thumbnail
                this.classList.add('active');
                
                // Update main image
                mainImage.src = this.src;
                mainImage.alt = this.alt;
            });
        });
    }
}

// Form Validation
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if(!input.value.trim()) {
            isValid = false;
            input.style.borderColor = 'red';
            
            // Add error message if not exists
            if(!input.nextElementSibling || !input.nextElementSibling.classList.contains('error-message')) {
                const error = document.createElement('div');
                error.className = 'error-message';
                error.style.color = 'red';
                error.style.fontSize = '0.8rem';
                error.style.marginTop = '0.25rem';
                error.textContent = 'This field is required';
                input.parentNode.appendChild(error);
            }
        } else {
            input.style.borderColor = '';
            
            // Remove error message if exists
            if(input.nextElementSibling && input.nextElementSibling.classList.contains('error-message')) {
                input.nextElementSibling.remove();
            }
        }
    });
    
    return isValid;
}

// Password Strength Checker
function checkPasswordStrength(password) {
    const strength = {
        0: "Very Weak",
        1: "Weak",
        2: "Medium",
        3: "Strong",
        4: "Very Strong"
    };
    
    let score = 0;
    
    // Check length
    if(password.length >= 8) score++;
    
    // Check for lowercase
    if(/[a-z]/.test(password)) score++;
    
    // Check for uppercase
    if(/[A-Z]/.test(password)) score++;
    
    // Check for numbers
    if(/[0-9]/.test(password)) score++;
    
    // Check for special characters
    if(/[^A-Za-z0-9]/.test(password)) score++;
    
    return {
        score: score,
        text: strength[score]
    };
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initImageGallery();
    
    // Add event listeners to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if(!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if(passwordInput) {
        const strengthIndicator = document.createElement('div');
        strengthIndicator.style.marginTop = '0.5rem';
        strengthIndicator.style.fontSize = '0.8rem';
        passwordInput.parentNode.appendChild(strengthIndicator);
        
        passwordInput.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            strengthIndicator.textContent = `Password Strength: ${strength.text}`;
            strengthIndicator.style.color = 
                strength.score < 2 ? 'red' : 
                strength.score < 3 ? 'orange' : 
                strength.score < 4 ? 'blue' : 'green';
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if(target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
