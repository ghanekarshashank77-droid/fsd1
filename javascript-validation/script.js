document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registrationForm');
    const successMessage = document.getElementById('successMessage');

    // Helper functions to show/hide errors
    const showError = (input, message) => {
        const formGroup = input.parentElement;
        const errorDisplay = formGroup.querySelector('.error-message');
        
        input.classList.add('error');
        errorDisplay.textContent = message;
        errorDisplay.style.display = 'block';
    };

    const clearError = (input) => {
        const formGroup = input.parentElement;
        const errorDisplay = formGroup.querySelector('.error-message');
        
        input.classList.remove('error');
        errorDisplay.style.display = 'none';
        errorDisplay.textContent = '';
    };

    // Validation Functions
    const validateName = (nameInput) => {
        const nameValue = nameInput.value.trim();
        const nameRegex = /^[A-Za-z\s]+$/;
        
        if (nameValue === '') {
            showError(nameInput, 'Name must not be empty');
            return false;
        } else if (!nameRegex.test(nameValue)) {
            showError(nameInput, 'Name must contain only letters and spaces');
            return false;
        } else {
            clearError(nameInput);
            return true;
        }
    };

    const validateEmail = (emailInput) => {
        const emailValue = emailInput.value.trim();
        // Basic email regex pattern
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (emailValue === '') {
            showError(emailInput, 'Email must not be empty');
            return false;
        } else if (!emailRegex.test(emailValue)) {
            showError(emailInput, 'Enter a valid email address');
            return false;
        } else {
            clearError(emailInput);
            return true;
        }
    };

    const validatePassword = (passwordInput) => {
        const passwordValue = passwordInput.value;
        // At least 6 chars, at least 1 number.
        // (?=.*\d) checks for at least one digit
        const passwordRegex = /^(?=.*\d).{6,}$/;
        
        if (passwordValue.length < 6) {
             showError(passwordInput, 'Password must be at least 6 characters');
             return false;
        } else if (!/\d/.test(passwordValue)) {
             showError(passwordInput, 'Password must contain at least one number');
             return false;
        } else {
            clearError(passwordInput);
            return true;
        }
    };

    const validateMobile = (mobileInput) => {
        const mobileValue = mobileInput.value.trim();
        const mobileRegex = /^\d{10}$/;
        
        if (!mobileRegex.test(mobileValue)) {
            showError(mobileInput, 'Mobile number must be exactly 10 digits');
            return false;
        } else {
            clearError(mobileInput);
            return true;
        }
    };

    // Form Submit Event Handler
    form.addEventListener('submit', (event) => {
        // Prevent default submission
        event.preventDefault();

        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const mobileInput = document.getElementById('mobile');

        // Run all validations
        const isNameValid = validateName(nameInput);
        const isEmailValid = validateEmail(emailInput);
        const isPasswordValid = validatePassword(passwordInput);
        const isMobileValid = validateMobile(mobileInput);

        // Check if all are valid
        if (isNameValid && isEmailValid && isPasswordValid && isMobileValid) {
            // Show success message
            successMessage.style.display = 'block';
            
            // Optionally clear the form
            // form.reset(); 
            
            // In a real app, you might submit the data here
            console.log('Form Submitted Successfully');
        } else {
            successMessage.style.display = 'none';
        }
    });

    // Optional: Real-time validation (removing errors as user types)
    ['name', 'email', 'password', 'mobile'].forEach(id => {
        const input = document.getElementById(id);
        input.addEventListener('input', () => {
            // We can choose to clear error on input, or re-validate.
            // Clearing error on input is a good UX pattern.
            if (input.classList.contains('error')) {
                clearError(input);
            }
        });
    });
});
