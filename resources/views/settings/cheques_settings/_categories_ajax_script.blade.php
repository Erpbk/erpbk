<script>
(function() {
  function showChequeCategoryAjaxMessage(type, message) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: type,
        title: type === 'success' ? 'Success' : 'Error',
        text: message || (type === 'success' ? 'Done.' : 'Request failed.'),
      });
      return;
    }
    alert(message || (type === 'success' ? 'Done.' : 'Request failed.'));
  }

  function submitChequeCategoryAjaxForm(form) {
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;
    var formData = new FormData(form);

    return fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      .then(function(response) {
        return response.json().catch(function() {
          return {};
        }).then(function(data) {
          if (!response.ok || data.success === false) {
            return Promise.reject(data);
          }
          return data;
        });
      })
      .finally(function() {
        if (submitBtn) submitBtn.disabled = false;
      });
  }

  document.addEventListener('submit', function(e) {
    var updateForm = e.target.closest('.js-ajax-category-update-form');
    if (updateForm) {
      e.preventDefault();
      submitChequeCategoryAjaxForm(updateForm)
        .then(function(data) {
          var categoryId = updateForm.dataset.categoryId || '';
          var row = document.querySelector('tr[data-category-row-id="' + categoryId + '"]');
          var labelInput = updateForm.querySelector('input[name="label"]');
          var newLabel = (data && data.category && data.category.label) ? data.category.label : (labelInput ? labelInput.value : '');
          if (row) {
            var labelEl = row.querySelector('.js-category-label');
            if (labelEl) labelEl.textContent = newLabel;
          }
          showChequeCategoryAjaxMessage('success', (data && data.message) ? data.message : 'Category updated.');
        })
        .catch(function(error) {
          showChequeCategoryAjaxMessage('error', (error && error.message) ? error.message : 'Could not update category.');
        });
      return;
    }

    var deleteForm = e.target.closest('.js-ajax-category-delete-form');
    if (!deleteForm) return;
    e.preventDefault();

    var proceed = function() {
      submitChequeCategoryAjaxForm(deleteForm)
        .then(function(data) {
          var categoryId = deleteForm.dataset.categoryId || '';
          var row = document.querySelector('tr[data-category-row-id="' + categoryId + '"]');
          if (row) row.remove();
          showChequeCategoryAjaxMessage('success', (data && data.message) ? data.message : 'Category deleted.');
        })
        .catch(function(error) {
          showChequeCategoryAjaxMessage('error', (error && error.message) ? error.message : 'Could not delete category.');
        });
    };

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'warning',
        title: 'Delete category?',
        text: 'This action cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
      }).then(function(result) {
        if (result.isConfirmed) proceed();
      });
    } else if (confirm('Delete this category?')) {
      proceed();
    }
  });
})();
</script>
