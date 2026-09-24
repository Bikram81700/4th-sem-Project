/**
 * BedTrack - Client-Side Form Validation & Interactivity
 * Provides real-time field validation, password visibility toggling,
 * file upload constraints, and form submission safety guards.
 */

document.addEventListener('DOMContentLoaded', () => {
  // Initialize all interactive validation features
  setupPasswordToggles();
  setupDateConstraints();
  setupEmailValidation();
  setupPhoneValidation();
  setupCategoryValidation();
  setupFileValidation();
  setupFormSubmitGuard();
});

/* ==========================================================================
   1. UI Error Display Helpers
   ========================================================================== */

/**
 * Renders an inline validation error message beneath the input element.
 */
function showFieldError(input, message) {
  clearFieldError(input);

  input.classList.add('border-red-500');
  input.style.borderColor = '#ba1a1a';

  const errSpan = document.createElement('div');
  errSpan.className = 'field-validation-error text-[#ba1a1a] text-[12px] mt-1 font-medium';
  errSpan.textContent = message;

  const wrapper = input.closest('.password-field-wrapper');
  const parent = wrapper ? wrapper.parentNode : input.parentNode;
  parent.appendChild(errSpan);
}

/**
 * Removes any existing validation error message and resets input border styles.
 */
function clearFieldError(input) {
  input.classList.remove('border-red-500');
  input.style.borderColor = '';

  const wrapper = input.closest('.password-field-wrapper');
  const parent = wrapper ? wrapper.parentNode : input.parentNode;
  const existingError = parent.querySelector('.field-validation-error');
  
  if (existingError) {
    existingError.remove();
  }
}

/* ==========================================================================
   2. Feature Setup Handlers
   ========================================================================== */

/**
 * Attaches visibility toggle buttons (eye icon) to password input fields.
 */
function setupPasswordToggles() {
  const passwordInputs = document.querySelectorAll(
    'input[type="password"], input[name="password"], input[name="new_password"]'
  );

  passwordInputs.forEach((input) => {
    let wrapper = input.closest('.password-field-wrapper');
    if (!wrapper) {
      wrapper = document.createElement('div');
      wrapper.className = 'password-field-wrapper';
      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(input);
    }

    let toggleBtn = wrapper.querySelector('.password-toggle-btn');
    if (!toggleBtn) {
      toggleBtn = document.createElement('button');
      toggleBtn.type = 'button';
      toggleBtn.className = 'password-toggle-btn';
      toggleBtn.setAttribute('aria-label', 'Toggle password visibility');
      toggleBtn.setAttribute('tabindex', '-1');

      const isMaterialIcon = document.querySelector('link[href*="Material+Symbols"]');
      toggleBtn.innerHTML = isMaterialIcon
        ? '<span class="material-symbols-outlined text-[18px] select-none">visibility</span>'
        : '<i class="fas fa-eye"></i>';

      wrapper.appendChild(toggleBtn);

      toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        if (isMaterialIcon) {
          const icon = toggleBtn.querySelector('.material-symbols-outlined');
          if (icon) icon.textContent = isPassword ? 'visibility_off' : 'visibility';
        } else {
          const icon = toggleBtn.querySelector('i');
          if (icon) icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        }
      });
    }
  });
}

/**
 * Enforces minimum booking date to prevent selecting past dates.
 */
function setupDateConstraints() {
  const dateInputs = document.querySelectorAll('input[type="date"], input[name="booking_date"]');
  const today = new Date().toISOString().split('T')[0];

  dateInputs.forEach((input) => {
    input.setAttribute('min', today);
    input.addEventListener('change', () => {
      if (input.value && input.value < today) {
        showFieldError(input, 'Booking date cannot be in the past.');
        input.value = today;
      } else {
        clearFieldError(input);
      }
    });
  });
}

/**
 * Validates email input format on blur.
 */
function setupEmailValidation() {
  const emailInputs = document.querySelectorAll('input[type="email"]');
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

  emailInputs.forEach((input) => {
    input.addEventListener('blur', () => {
      const value = input.value.trim();
      if (value && !emailRegex.test(value)) {
        showFieldError(input, 'Please enter a valid email address.');
      } else {
        clearFieldError(input);
      }
    });
  });
}

/**
 * Restricts phone number inputs to exactly 10 digits and handles emergency number conflict checks.
 */
function setupPhoneValidation() {
  const phoneInputs = document.querySelectorAll(
    'input[name="phone"], input[name="contact"], input[name="emergency"]'
  );

  phoneInputs.forEach((input) => {
    input.setAttribute('maxlength', '10');

    const handlePhoneCheck = () => {
      const rawValue = input.value.trim();
      const digitsOnly = rawValue.replace(/[^0-9]/g, '');
      
      if (rawValue !== digitsOnly) {
        input.value = digitsOnly;
      }

      const value = input.value.trim();
      if (value && value.length !== 10) {
        showFieldError(input, 'Phone number must be exactly 10 digits.');
      } else {
        clearFieldError(input);
      }

      checkEmergencyPhoneConflict();
    };

    input.addEventListener('input', handlePhoneCheck);
    input.addEventListener('blur', handlePhoneCheck);
  });
}

/**
 * Ensures user emergency contact phone does not match their personal phone.
 */
function checkEmergencyPhoneConflict() {
  const phoneInput = document.querySelector('input[name="phone"]');
  const emergencyInput = document.querySelector('input[name="emergency"]');

  if (phoneInput && emergencyInput) {
    const mainPhone = phoneInput.value.trim();
    const emergencyPhone = emergencyInput.value.trim();

    if (mainPhone && emergencyPhone && mainPhone === emergencyPhone) {
      showFieldError(
        emergencyInput,
        'Emergency contact should belong to a family member/friend, not your own phone.'
      );
    }
  }
}

/**
 * Restricts bed category name inputs to letters and spaces only.
 */
function setupCategoryValidation() {
  const categoryInputs = document.querySelectorAll('form[action*="bed"] input[name="name"], .main input[name="name"]');
  const letterOnlyRegex = /^[a-zA-Z\s\-]+$/;

  categoryInputs.forEach((input) => {
    const isCategoryForm = input.closest('form') && (
      window.location.pathname.includes('add-bed') ||
      window.location.pathname.includes('edit-bed') ||
      window.location.pathname.includes('manage-beds')
    );

    if (!isCategoryForm) return;

    input.setAttribute('pattern', '[A-Za-z\\s\\-]+');
    input.setAttribute('title', 'Category name must contain only letters and spaces.');

    input.addEventListener('input', () => {
      const value = input.value.trim();
      if (value && !letterOnlyRegex.test(value)) {
        showFieldError(input, 'Category name must contain only letters and spaces (no numbers).');
      } else if (value && value.length < 2) {
        showFieldError(input, 'Category name must be at least 2 characters long.');
      } else {
        clearFieldError(input);
      }
    });
  });
}

/**
 * Validates file uploads for maximum allowed size (5MB) and accepted file extensions.
 */
function setupFileValidation() {
  const fileInputs = document.querySelectorAll('input[type="file"]');
  const MAX_FILE_BYTES = 5 * 1024 * 1024;

  fileInputs.forEach((input) => {
    input.addEventListener('change', () => {
      const file = input.files[0];
      if (!file) return;

      if (file.size > MAX_FILE_BYTES) {
        const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
        showFieldError(input, `File size is ${sizeMb}MB. Maximum allowed is 5MB.`);
        input.value = '';
        return;
      }

      const acceptAttr = input.getAttribute('accept');
      if (acceptAttr) {
        const allowedExts = acceptAttr.split(',').map((ext) => ext.trim().toLowerCase().replace('.', ''));
        const fileExt = file.name.split('.').pop().toLowerCase();

        if (!allowedExts.includes(fileExt)) {
          showFieldError(input, `Invalid file type (.${fileExt}). Allowed: ${acceptAttr}`);
          input.value = '';
          return;
        }
      }

      clearFieldError(input);
    });
  });
}

/* ==========================================================================
   3. Form Submit Validation Guard
   ========================================================================== */

/**
 * Final client-side check on form submission to block invalid data.
 */
function setupFormSubmitGuard() {
  const forms = document.querySelectorAll('form');
  const today = new Date().toISOString().split('T')[0];
  const letterOnlyRegex = /^[a-zA-Z\s\-]+$/;

  forms.forEach((form) => {
    form.addEventListener('submit', (e) => {
      const actionInput = form.querySelector('input[name="action"]');
      if (actionInput) {
        const actionValue = actionInput.value;
        if (actionValue === 'delete_category' || actionValue === 'remove_bed') {
          return;
        }
      }

      // Check date fields
      const dateEl = form.querySelector('input[type="date"], input[name="booking_date"]');
      if (dateEl && dateEl.value && dateEl.value < today) {
        e.preventDefault();
        showFieldError(dateEl, 'Booking date cannot be in the past.');
        dateEl.focus();
        return false;
      }

      // Check phone numbers
      const phoneEls = form.querySelectorAll('input[name="phone"], input[name="contact"], input[name="emergency"]');
      for (const pEl of phoneEls) {
        const val = pEl.value.trim();
        if (val && val.length !== 10) {
          e.preventDefault();
          showFieldError(pEl, 'Phone number must be exactly 10 digits.');
          pEl.focus();
          return false;
        }
      }

      // Check category name inputs
      const catEl = form.querySelector('input[name="name"]');
      const isCategorySubmit = catEl && (
        form.querySelector('input[name="action"][value="update_details"]') ||
        window.location.pathname.includes('add-bed')
      );

      if (isCategorySubmit && catEl) {
        const val = catEl.value.trim();
        if (!val || !letterOnlyRegex.test(val)) {
          e.preventDefault();
          showFieldError(catEl, 'Category name must contain only letters and spaces (no numbers).');
          catEl.focus();
          return false;
        }
      }
    });
  });
}


