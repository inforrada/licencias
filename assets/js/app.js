// Interactive Javascript for License Management System

document.addEventListener('DOMContentLoaded', () => {
  // Modal handlers
  const modals = document.querySelectorAll('.modal-backdrop');
  
  document.querySelectorAll('[data-modal-target]').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = trigger.getAttribute('data-modal-target');
      const modal = document.getElementById(targetId);
      if (modal) modal.classList.add('open');
    });
  });

  document.querySelectorAll('.modal-close, [data-modal-close]').forEach(closeBtn => {
    closeBtn.addEventListener('click', () => {
      modals.forEach(m => m.classList.remove('open'));
    });
  });

  modals.forEach(modal => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) modal.classList.remove('open');
    });
  });

  // Copy to clipboard functionality
  document.querySelectorAll('.btn-copy').forEach(btn => {
    btn.addEventListener('click', () => {
      const text = btn.getAttribute('data-copy');
      if (text) {
        navigator.clipboard.writeText(text).then(() => {
          const origText = btn.innerHTML;
          btn.innerHTML = '✓ Copiado';
          setTimeout(() => {
            btn.innerHTML = origText;
          }, 1800);
        });
      }
    });
  });

  // API Tester Form submit handler
  const apiTesterForm = document.getElementById('api-tester-form');
  if (apiTesterForm) {
    apiTesterForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const key = document.getElementById('test-key').value;
      const domain = document.getElementById('test-domain').value;
      const product = document.getElementById('test-product').value;
      
      const responseContainer = document.getElementById('api-response-container');
      const responseCode = document.getElementById('api-response-code');
      const responseBody = document.getElementById('api-response-body');

      responseContainer.style.display = 'block';
      responseBody.textContent = 'Enviando petición a la API...';

      try {
        const queryParams = new URLSearchParams({
          license_key: key,
          domain: domain,
          product_code: product
        });

        const res = await fetch(`api/verify.php?${queryParams.toString()}`);
        const data = await res.json();

        responseCode.textContent = `HTTP Status: ${res.status} ${res.statusText}`;
        responseCode.className = res.ok ? 'badge badge-active' : 'badge badge-inactive';
        responseBody.textContent = JSON.stringify(data, null, 2);

      } catch (err) {
        responseCode.textContent = 'Error de Red / Servidor';
        responseCode.className = 'badge badge-inactive';
        responseBody.textContent = `Error: ${err.message}`;
      }
    });
  }
});
