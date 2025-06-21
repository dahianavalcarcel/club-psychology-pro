// assets/src/js/components/userPanel.js

/**
 * Inicializa el panel de usuario: listado de tests solicitados
 * y manejo de reenvío de invitaciones.
 */
export default function initUserPanel(listSelector = '.user-tests-list') {
  const list = document.querySelector(listSelector);
  if (!list) return;

  list.addEventListener('click', async (e) => {
    if (!e.target.matches('.resend-invitation-btn')) return;
    const invitationId = e.target.dataset.invitationId;
    e.target.disabled = true;
    e.target.textContent = 'Reenviando...';

    try {
      const res = await fetch(cppData.ajaxUrl, {
        method: 'POST',
        headers: { 'X-WP-Nonce': cppData.nonce },
        body: new URLSearchParams({
          action: 'cpp_resend_test',
          invitation_id: invitationId
        })
      });
      const json = await res.json();
      if (json.success) {
        e.target.textContent = 'Reenviado';
      } else {
        throw new Error(json.data);
      }
    } catch (err) {
      console.error('Error reenviando invitación:', err);
      e.target.textContent = 'Error';
    }
  });
}
