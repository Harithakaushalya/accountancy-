/**
 * ApexFinance Client Script & Loan Calculator
 */

document.addEventListener('DOMContentLoaded', function () {

  // Auto-hide alert banners after 5 seconds
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.opacity = '0';
      alert.style.transition = 'opacity 0.5s ease';
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });

  // Client-side Instant Table Search Filter
  const searchInputs = document.querySelectorAll('[data-table-search]');
  searchInputs.forEach(function (input) {
    const tableId = input.getAttribute('data-table-search');
    const table = document.getElementById(tableId);

    if (table) {
      input.addEventListener('keyup', function () {
        const filter = input.value.toLowerCase().trim();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(function (row) {
          const text = row.textContent.toLowerCase();
          if (text.includes(filter)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      });
    }
  });

  // Live Loan EMI Calculator Handler
  const amountInput = document.getElementById('calc_amount');
  const termInput = document.getElementById('calc_term');
  const packageSelect = document.getElementById('calc_package');

  if (amountInput && termInput && packageSelect) {
    function updateLoanPreview() {
      const selectedOption = packageSelect.options[packageSelect.selectedIndex];
      if (!selectedOption) return;

      const rate = parseFloat(selectedOption.getAttribute('data-rate') || 10.0);
      const amount = parseFloat(amountInput.value || 0);
      const months = parseInt(termInput.value || 0);

      if (amount > 0 && months > 0) {
        const monthlyRate = (rate / 12) / 100;
        let monthly = 0;

        if (monthlyRate === 0) {
          monthly = amount / months;
        } else {
          monthly = (amount * monthlyRate * Math.pow(1 + monthlyRate, months)) / (Math.pow(1 + monthlyRate, months) - 1);
        }

        const totalPayable = monthly * months;
        const totalInterest = totalPayable - amount;

        document.getElementById('calc_monthly_out').textContent = '$ ' + monthly.toFixed(2);
        document.getElementById('calc_total_out').textContent = '$ ' + totalPayable.toFixed(2);
        document.getElementById('calc_interest_out').textContent = '$ ' + totalInterest.toFixed(2);
        document.getElementById('calc_rate_out').textContent = rate.toFixed(2) + '% p.a.';
      }
    }

    amountInput.addEventListener('input', updateLoanPreview);
    termInput.addEventListener('input', updateLoanPreview);
    packageSelect.addEventListener('change', updateLoanPreview);
    updateLoanPreview();
  }
});

// Modal Helpers
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
  }
}
