document.addEventListener('DOMContentLoaded', () => {

  function showFieldError(input, message) {
    clearFieldError(input);
    input.classList.add('border-red-500');
    input.style.borderColor = '#ba1a1a';
    
    const errSpan = document.createElement('div');
    errSpan.className = 'field-validation-error text-[#ba1a1a] text-[12px] mt-1 font-medium';
    errSpan.textContent = message;
    
    const wrapper = input.closest('.password-field-wrapper');
    if (wrapper) {
      wrapper.parentNode.appendChild(errSpan);
    } else {
      input.parentNode.appendChild(errSpan);
    }
  }

  function clearFieldError(input) {
    input.classList.remove('border-red-500');
    input.style.borderColor = '';
    const wrapper = input.closest('.password-field-wrapper');
    const parent = wrapper ? wrapper.parentNode : input.parentNode;
    const errSpan = parent.querySelector('.field-validation-error');
    if (errSpan) errSpan.remove();
  }

  // Toggle password visibility
  const passwordInputs = document.querySelectorAll('input[type="password"], input[name="password"], input[name="new_password"]');
  passwordInputs.forEach(input => {
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

      const isMaterial = document.querySelector('link[href*="Material+Symbols"]');
      toggleBtn.innerHTML = isMaterial 
        ? '<span class="material-symbols-outlined text-[18px] select-none">visibility</span>' 
        : '<i class="fas fa-eye"></i>';

      wrapper.appendChild(toggleBtn);

      toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        if (isMaterial) {
          const icon = toggleBtn.querySelector('.material-symbols-outlined');
          if (icon) icon.textContent = isPassword ? 'visibility_off' : 'visibility';
        } else {
          const icon = toggleBtn.querySelector('i');
          if (icon) icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        }
      });
    }
  });

  // Minimum date enforcement for booking inputs
  const dateInputs = document.querySelectorAll('input[type="date"], input[name="booking_date"]');
  const today = new Date().toISOString().split('T')[0];
  dateInputs.forEach(input => {
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

  // Email format validation
  const emailInputs = document.querySelectorAll('input[type="email"]');
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  emailInputs.forEach(input => {
    input.addEventListener('blur', () => {
      const val = input.value.trim();
      if (val && !emailRegex.test(val)) {
        showFieldError(input, 'Please enter a valid email address.');
      } else {
        clearFieldError(input);
      }
    });
  });

  // Phone number validation (10 digits)
  const phoneInputs = document.querySelectorAll('input[name="phone"], input[name="contact"], input[name="emergency"]');
  phoneInputs.forEach(input => {
    input.setAttribute('maxlength', '10');
    input.addEventListener('input', () => {
      const rawVal = input.value.trim();
      const digitsOnly = rawVal.replace(/[^0-9]/g, '');
      if (rawVal !== digitsOnly) {
        input.value = digitsOnly;
      }
      
      const val = input.value.trim();
      if (val && val.length !== 10) {
        showFieldError(input, 'Phone number must be exactly 10 digits.');
      } else {
        clearFieldError(input);
      }
      checkEmergencyPhoneConflict();
    });

    input.addEventListener('blur', () => {
      const val = input.value.trim();
      if (val && val.length !== 10) {
        showFieldError(input, 'Phone number must be exactly 10 digits.');
      } else {
        clearFieldError(input);
      }
      checkEmergencyPhoneConflict();
    });
  });

  function checkEmergencyPhoneConflict() {
    const phoneInput = document.querySelector('input[name="phone"]');
    const emergencyInput = document.querySelector('input[name="emergency"]');
    if (phoneInput && emergencyInput) {
      const p = phoneInput.value.trim();
      const e = emergencyInput.value.trim();
      if (p && e && p === e) {
        showFieldError(emergencyInput, 'Emergency contact should belong to a family member/friend, not your own phone.');
      }
    }
  }

  // Category name input restriction
  const categoryInputs = document.querySelectorAll('form[action*="bed"] input[name="name"], .main input[name="name"]');
  const letterOnlyRegex = /^[a-zA-Z\s\-]+$/;
  categoryInputs.forEach(input => {
    const isCategoryForm = input.closest('form') && (
      window.location.pathname.includes('add-bed') ||
      window.location.pathname.includes('edit-bed') ||
      window.location.pathname.includes('manage-beds')
    );
    if (!isCategoryForm) return;

    input.setAttribute('pattern', '[A-Za-z\\s\\-]+');
    input.setAttribute('title', 'Category name must contain only letters and spaces.');

    input.addEventListener('input', () => {
      const val = input.value.trim();
      if (val && !letterOnlyRegex.test(val)) {
        showFieldError(input, 'Category name must contain only letters and spaces (no numbers).');
      } else if (val && val.length < 2) {
        showFieldError(input, 'Category name must be at least 2 characters long.');
      } else {
        clearFieldError(input);
      }
    });
  });

  // Client-side file size and extension checks
  const fileInputs = document.querySelectorAll('input[type="file"]');
  const MAX_FILE_BYTES = 5 * 1024 * 1024;
  fileInputs.forEach(input => {
    input.addEventListener('change', () => {
      const file = input.files[0];
      if (!file) return;

      if (file.size > MAX_FILE_BYTES) {
        showFieldError(input, `File size is ${(file.size / (1024 * 1024)).toFixed(1)}MB. Maximum allowed is 5MB.`);
        input.value = '';
        return;
      }

      const accept = input.getAttribute('accept');
      if (accept) {
        const allowed = accept.split(',').map(ext => ext.trim().toLowerCase().replace('.', ''));
        const fileExt = file.name.split('.').pop().toLowerCase();
        if (!allowed.includes(fileExt)) {
          showFieldError(input, `Invalid file type (.${fileExt}). Allowed: ${accept}`);
          input.value = '';
          return;
        }
      }

      clearFieldError(input);
    });
  });

  // Form submission validation
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', (e) => {
      const actionInput = form.querySelector('input[name="action"]');
      if (actionInput) {
        const actionVal = actionInput.value;
        if (actionVal === 'delete_category' || actionVal === 'remove_bed') {
          return;
        }
      }

      const dateEl = form.querySelector('input[type="date"], input[name="booking_date"]');
      if (dateEl && dateEl.value && dateEl.value < today) {
        e.preventDefault();
        showFieldError(dateEl, 'Booking date cannot be in the past.');
        dateEl.focus();
        return false;
      }

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

      const catEl = form.querySelector('input[name="name"]');
      const isCategorySubmit = catEl && form.querySelector('input[name="action"][value="update_details"]') || (catEl && window.location.pathname.includes('add-bed'));
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
});

