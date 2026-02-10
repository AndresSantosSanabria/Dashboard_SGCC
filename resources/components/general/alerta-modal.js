window.onload = function () {
  openModal();
  document.addEventListener('keyup', function (event) {
    if(event.key == 'Escape') {
      closeModal('modal_basico')
    }
  })
};
function openModal() {
  let modalItems = document.querySelectorAll('.container-modal-govco');
  modalItems.forEach(function(modalItem) {
    modalItem.style.display = 'block'
  });
}

function closeModal(nombre_modal) {
  document.getElementById(nombre_modal).style.display = 'none';
}