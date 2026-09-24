/**
 * BedTrack - Modern Asynchronous UI & Action Handler
 * Provides real-time notifications, AJAX form processing, modal confirmations,
 * and live search filtering without full page reloads.
 */

(function () {
  'use strict';

  window.BedTrack = window.BedTrack || {};



  /**
   * Displays a dynamic toast alert notification.
   */
  BedTrack.toast = function (message, type = 'success', duration = 4000) {
    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const iconMap = {
      success: 'fa-check-circle',
      error: 'fa-exclamation-circle',
      info: 'fa-info-circle'
    };
    const iconClass = iconMap[type] || 'fa-info-circle';

    toast.innerHTML = [
      '<div style="display:flex;align-items:center;gap:8px;">',
      `  <i class="fas ${iconClass}"></i>`,
      `  <span>${escapeHtml(message)}</span>`,
      '</div>',
      '<button type="button" class="toast-close" aria-label="Close">&times;</button>'
    ].join('');

    container.appendChild(toast);

    const closeToast = () => {
      toast.classList.add('toast-hide');
      setTimeout(() => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 260);
    };

    toast.querySelector('.toast-close').addEventListener('click', closeToast);

    let timer = setTimeout(closeToast, duration);
    toast.addEventListener('mouseenter', () => clearTimeout(timer));
    toast.addEventListener('mouseleave', () => {
      timer = setTimeout(closeToast, 2000);
    });
  };

  /**
   * Safely escapes plain text strings for insertion into HTML elements.
   */
  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  /**
   * Helper: Fades out and removes a table row or card element from the DOM.
   */
  function removeRowWithFade(element) {
    if (!element) return;
    element.classList.add('fade-out');
    setTimeout(() => {
      if (element.parentNode) {
        element.parentNode.removeChild(element);
      }
    }, 300);
  }

  /* ==========================================================================
     2. AJAX Response Action Handlers
     ========================================================================== */

  /**
   * Handles hospital activation, deactivation, and deletion updates in table rows.
   */
  function handleManageHospital(form, data, csrfToken) {
    const row = form.closest('tr');
    if (!row) return;

    if (data.action === 'delete') {
      removeRowWithFade(row);
      return;
    }

    const statusCell = row.querySelector('.status-cell');
    if (statusCell && data.badge) {
      statusCell.innerHTML = data.badge;
    }

    const toggleWrapper = row.querySelector('.status-toggle-wrapper');
    if (toggleWrapper) {
      if (data.action === 'deactivate') {
        toggleWrapper.innerHTML = [
          '<form method="post" class="ajax-form" data-type="manage-hospital" style="display:inline;">',
          `  <input type="hidden" name="csrf_token" value="${csrfToken}">`,
          `  <input type="hidden" name="id" value="${data.id}">`,
          '  <input type="hidden" name="action" value="reactivate">',
          '  <button class="btn btn-sm" type="submit">Reactivate</button>',
          '</form>'
        ].join('');
      } else if (data.action === 'reactivate') {
        toggleWrapper.innerHTML = [
          '<form method="post" class="ajax-form" data-type="manage-hospital" style="display:inline;">',
          `  <input type="hidden" name="csrf_token" value="${csrfToken}">`,
          `  <input type="hidden" name="id" value="${data.id}">`,
          '  <input type="hidden" name="action" value="deactivate">',
          '  <button class="btn btn-sm btn-outline" type="submit" data-confirm="Deactivate this hospital? Patients won\'t be able to book here.">Deactivate</button>',
          '</form>'
        ].join('');
      }
    }
  }

  /**
   * Handles pending hospital request approval, rejection, and UI count updates.
   */
  function handlePendingHospital(form, data) {
    const card = form.closest('.pending-card');
    if (!card) return;

    card.classList.add('fade-out');
    setTimeout(() => {
      if (card.parentNode) {
        card.parentNode.removeChild(card);
      }

      // Update remaining count badge
      const countBadge = document.getElementById('pending-count-badge');
      if (countBadge && typeof data.remaining_count !== 'undefined') {
        const countNum = countBadge.querySelector('.count-num');
        if (countNum) {
          countNum.textContent = data.remaining_count;
        }
      }

      // Show empty state if no pending requests remain
      const remainingCards = document.querySelectorAll('.pending-card');
      if (remainingCards.length === 0) {
        const emptyState = document.getElementById('no-pending-state');
        if (emptyState) emptyState.style.display = 'block';

        const list = document.getElementById('pending-list');
        if (list) list.style.display = 'none';
      }
    }, 300);
  }

  /**
   * Handles patient booking updates (accept, reject, discharge, delete).
   */
  function handleBooking(form, data, csrfToken) {
    const row = form.closest('tr');
    if (!row) return;

    if (data.action === 'delete') {
      removeRowWithFade(row);
      return;
    }

    const statusCell = row.querySelector('.status-cell');
    if (statusCell && data.badge) {
      statusCell.innerHTML = data.badge;
    }

    const actionWrapper = row.querySelector('.status-action-wrapper');
    if (actionWrapper) {
      if (data.action === 'accept') {
        actionWrapper.innerHTML = [
          '<form method="post" class="ajax-form" data-type="booking" style="display:inline;">',
          `  <input type="hidden" name="csrf_token" value="${csrfToken}">`,
          `  <input type="hidden" name="booking_id" value="${data.booking_id}">`,
          '  <input type="hidden" name="action" value="discharge">',
          '  <button class="btn btn-sm" type="submit" data-confirm="Mark this patient as discharged and free the bed?">Mark discharged</button>',
          '</form>'
        ].join('');
      } else if (data.action === 'reject' || data.action === 'discharge') {
        actionWrapper.innerHTML = '';
      }
    }
  }

  /**
   * Handles user management actions (password resets, deletion).
   */
  function handleManageUser(form, data) {
    const row = form.closest('tr');
    if (data.action === 'delete' && row) {
      removeRowWithFade(row);
    } else {
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.classList.remove('btn-loading');
        submitBtn.disabled = false;
      }
    }
  }

  /**
   * Handles bed removal and updates category totals and Sequential numbering.
   */
  function handleRemoveBed(form, data) {
    const row = form.closest('tr');
    if (row) {
      row.classList.add('fade-out');
      setTimeout(() => {
        const tbody = row.parentNode;
        if (tbody) {
          tbody.removeChild(row);

          // Renumber remaining beds sequentially
          const remainingRows = tbody.querySelectorAll('tr[data-bed-id]');
          remainingRows.forEach((r, index) => {
            const labelEl = r.querySelector('td:first-child strong');
            if (labelEl) {
              labelEl.textContent = `Bed ${index + 1}`;
            }
          });

          if (remainingRows.length === 0) {
            const cardEl = tbody.closest('.card');
            if (cardEl) {
              cardEl.innerHTML = '<div style="text-align:center;padding:32px 16px;color:var(--ink-soft);font-size:14px;"><i class="fas fa-bed" style="font-size:28px;opacity:0.4;display:block;margin-bottom:8px;"></i>No beds in this category yet. Use "Add beds" above to add beds.</div>';
            }
          }
        }
      }, 300);
    }

    const lede = document.getElementById('bed-count-lede');
    if (lede) {
      const totalEl = lede.querySelector('.total-beds-count');
      const availEl = lede.querySelector('.avail-beds-count');

      if (totalEl && typeof data.total !== 'undefined') {
        totalEl.textContent = data.total;
      }
      if (availEl && typeof data.available !== 'undefined') {
        availEl.textContent = data.available;
      }
    }
  }

  /* ==========================================================================
     3. Initialization & Event Binding Functions
     ========================================================================== */

  /**
   * Intercepts clicks on standard buttons with [data-confirm] to show native confirmation dialogs.
   */
  function setupConfirmationPrompts() {
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-confirm]');
      if (btn && !btn.closest('.ajax-form')) {
        const confirmMsg = btn.getAttribute('data-confirm');
        if (!confirm(confirmMsg)) {
          e.preventDefault();
          e.stopImmediatePropagation();
        }
      }
    });
  }

  /**
   * Updates file label previews when a user selects a file.
   */
  function setupFilePreviews() {
    document.querySelectorAll('input[type="file"]').forEach((input) => {
      input.addEventListener('change', () => {
        const label = document.querySelector(`[data-file-preview-for="${input.name}"]`);
        if (label) {
          label.textContent = input.files.length ? input.files[0].name : '';
        }
      });
    });
  }

  /**
   * Listens to form submissions on .ajax-form and submits them via fetch API.
   */
  function setupAjaxFormHandler() {
    document.addEventListener('submit', (e) => {
      const form = e.target.closest('.ajax-form');
      if (!form) return;

      const submitBtn = e.submitter || form.querySelector('button[type="submit"]');

      // Check confirmation prompt
      const confirmMsg = (submitBtn && submitBtn.getAttribute('data-confirm')) || form.getAttribute('data-confirm');
      if (confirmMsg && !confirm(confirmMsg)) {
        e.preventDefault();
        return;
      }

      e.preventDefault();

      if (submitBtn) {
        submitBtn.classList.add('btn-loading');
        submitBtn.disabled = true;
      }

      const formData = new FormData(form);
      formData.append('ajax', '1');

      const actionUrl = form.getAttribute('action') || window.location.href;
      const formType = form.getAttribute('data-type');
      const csrfInput = form.querySelector('input[name="csrf_token"]');
      const csrfToken = csrfInput ? csrfInput.value : '';

      fetch(actionUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then((res) => {
          return res.json().then((data) => ({ ok: res.ok, status: res.status, data }))
            .catch(() => ({ ok: res.ok, status: res.status, data: { message: 'Unexpected server response' } }));
        })
        .then((result) => {
          const resData = result.data || {};

          if (!result.ok || !resData.success) {
            const errMsg = resData.message || 'Action could not be completed.';
            BedTrack.toast(errMsg, 'error');
            if (submitBtn) {
              submitBtn.classList.remove('btn-loading');
              submitBtn.disabled = false;
            }
            return;
          }

          // Show success toast notification
          if (resData.message) {
            BedTrack.toast(resData.message, resData.type || 'success');
          }

          // Redirect if specified by backend response
          if (resData.redirect) {
            setTimeout(() => {
              window.location.href = resData.redirect;
            }, 350);
            return;
          }

          // Route DOM update actions
          if (formType === 'manage-hospital') {
            handleManageHospital(form, resData, csrfToken);
          } else if (formType === 'pending-hospital') {
            handlePendingHospital(form, resData);
          } else if (formType === 'booking') {
            handleBooking(form, resData, csrfToken);
          } else if (formType === 'remove-bed') {
            handleRemoveBed(form, resData);
          } else if (formType === 'delete-category') {
            const catRow = form.closest('tr');
            if (catRow) removeRowWithFade(catRow);
          } else if (formType === 'manage-user' || formType === 'reset-password') {
            handleManageUser(form, resData);
          }
        })
        .catch((err) => {
          console.error('AJAX Action Error:', err);
          BedTrack.toast('Network error. Please try again.', 'error');
          if (submitBtn) {
            submitBtn.classList.remove('btn-loading');
            submitBtn.disabled = false;
          }
        });
    });
  }

  /**
   * Live search & dynamic filtering for hospitals without reloading the page.
   */
  function setupHospitalSearch() {
    const searchForm = document.getElementById('hospital-search-form');
    const resultsContainer = document.getElementById('hospitals-results-container');
    if (!searchForm || !resultsContainer) return;

    const queryInput = document.getElementById('hospital-search-query');
    const citySelect = document.getElementById('hospital-search-city');
    let searchTimeout = null;

    const performSearch = () => {
      const q = queryInput ? queryInput.value.trim() : '';
      const city = citySelect ? citySelect.value : '';
      const params = new URLSearchParams();

      if (q) params.set('q', q);
      if (city) params.set('city', city);

      const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
      history.replaceState(null, '', url);

      resultsContainer.style.opacity = '0.5';
      resultsContainer.style.transition = 'opacity 0.2s';

      fetch(url)
        .then((res) => res.text())
        .then((html) => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newResults = doc.getElementById('hospitals-results-container');
          if (newResults) {
            resultsContainer.innerHTML = newResults.innerHTML;
          }
          resultsContainer.style.opacity = '1';
        })
        .catch(() => {
          resultsContainer.style.opacity = '1';
        });
    };

    searchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      performSearch();
    });

    if (citySelect) {
      citySelect.addEventListener('change', performSearch);
    }

    if (queryInput) {
      queryInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300);
      });
    }
  }

  /* ==========================================================================
     4. App Initialization Entry Point
     ========================================================================== */

  document.addEventListener('DOMContentLoaded', () => {
    setupConfirmationPrompts();
    setupFilePreviews();
    setupAjaxFormHandler();
    setupHospitalSearch();
  });
})();

