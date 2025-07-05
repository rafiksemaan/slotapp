// assets/js/validation_utils.js

/**
 * Basic validation functions
 */
export const isRequired = (value) => {
    return value !== null && value.trim() !== '';
};

export const isEmail = (value) => {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(value);
};

export const isNumber = (value) => {
    return !isNaN(parseFloat(value)) && isFinite(value);
};

export const isPositiveNumber = (value) => {
    return isNumber(value) && parseFloat(value) > 0;
};

export const isValidIP = (value) => {
    const ipPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
    if (!ipPattern.test(value)) return false;
    return value.split('.').every(segment => parseInt(segment, 10) >= 0 && parseInt(segment, 10) <= 255);
};

export const isValidMAC = (value) => {
    const macPattern = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/;
    return macPattern.test(value);
};

/**
 * Displays an error message for a given input field.
 * @param {HTMLElement} inputElement - The input field.
 * @param {string} message - The error message to display.
 */
const displayError = (inputElement, message) => {
    inputElement.classList.add('error');
    let errorMessageElement = inputElement.nextElementSibling;
    if (!errorMessageElement || !errorMessageElement.classList.contains('error-message')) {
        errorMessageElement = document.createElement('div');
        errorMessageElement.classList.add('error-message');
        inputElement.parentNode.insertBefore(errorMessageElement, inputElement.nextSibling);
    }
    errorMessageElement.textContent = message;
};

/**
 * Clears the error message for a given input field.
 * @param {HTMLElement} inputElement - The input field.
 */
const clearError = (inputElement) => {
    inputElement.classList.remove('error');
    const errorMessageElement = inputElement.nextElementSibling;
    if (errorMessageElement && errorMessageElement.classList.contains('error-message')) {
        errorMessageElement.remove();
    }
};

/**
 * Validates a form based on a set of rules.
 * @param {HTMLFormElement} form - The form element to validate.
 * @param {Object} rules - An object where keys are input names and values are arrays of validation objects.
 *                         Each validation object has { validator: Function, message: string }.
 * @returns {boolean} - True if the form is valid, false otherwise.
 */
export const validateForm = (form, rules) => {
    let isValid = true;

    // Clear all existing errors first
    form.querySelectorAll('.error').forEach(el => clearError(el));

    for (const fieldName in rules) {
        const inputElement = form.querySelector(`[name="${fieldName}"]`);
        if (!inputElement) {
            console.warn(`Validation rule for non-existent field: ${fieldName}`);
            continue;
        }

        for (const rule of rules[fieldName]) {
            const value = inputElement.value.trim();
            if (!rule.validator(value, inputElement)) { // Pass inputElement for potential custom logic
                displayError(inputElement, rule.message);
                isValid = false;
                break; // Stop at the first failed rule for this field
            }
        }
    }
    return isValid;
};

