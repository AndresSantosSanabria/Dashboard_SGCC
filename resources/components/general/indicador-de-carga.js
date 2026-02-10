/**
 * Gov.co (https://www.gov.co) - Gobierno de Colombia
 *  - Componente: Indicador de carga
 *  - Version: 4.0.0
 */

function showSpinner(show) {
  const spinner = document.querySelector('.spinner-modal-govco');
  const ariaHidden = !show;
  const display = show ? "flex" : "none";
  spinner.style.display = display;
  spinner.setAttribute("aria-hidden", ariaHidden);

  if(show) {
    const backdrop = document.createElement('div');
    backdrop.classList.add('modal-backdrop', 'fade', 'show', 'backdrop-govco');
    document.body.appendChild(backdrop);
  } else {
    const element = document.querySelector('.backdrop-govco');
    document.body.removeChild(element);
  }
}
