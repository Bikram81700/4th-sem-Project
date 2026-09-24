/**
 * BedTrack - Bed Booking Page Interactions (book-bed.php)
 * Handles patient name field auto-fill toggles and dynamic bed availability hints.
 */

document.addEventListener('DOMContentLoaded', () => {
  setupPatientToggle();
  setupCategoryAvailabilityHint();
});

/**
 * Manages switching between booking for oneself vs booking for another patient.
 */
function setupPatientToggle() {
  const radios = document.querySelectorAll('input[name="booking_for"]');
  const patientNameInput = document.getElementById('patientNameInput');
  if (!patientNameInput) return;

  const selfName = patientNameInput.getAttribute('data-self-name') || '';

  const syncPatientName = () => {
    const selectedOption = document.querySelector('input[name="booking_for"]:checked');
    if (!selectedOption) return;

    if (selectedOption.value === 'self') {
      patientNameInput.value = selfName;
      patientNameInput.readOnly = true;
    } else {
      patientNameInput.readOnly = false;
      if (patientNameInput.value === selfName) {
        patientNameInput.value = '';
      }
    }
  };

  radios.forEach((radio) => radio.addEventListener('change', syncPatientName));
  if (radios.length > 0) {
    syncPatientName();
  }
}

/**
 * Updates the "beds available" hint badge when selecting a bed category.
 */
function setupCategoryAvailabilityHint() {
  const categorySelect = document.getElementById('categorySelect');
  const availabilityHint = document.getElementById('categoryAvailHint');

  if (!categorySelect || !availabilityHint) return;

  const updateHintText = () => {
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    if (selectedOption) {
      const availableCount = selectedOption.getAttribute('data-avail');
      availabilityHint.textContent = `${availableCount} bed(s) currently available in this category`;
    } else {
      availabilityHint.textContent = '';
    }
  };

  categorySelect.addEventListener('change', updateHintText);
  updateHintText();
}

