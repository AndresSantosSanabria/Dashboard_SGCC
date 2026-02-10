/**
 * Gov.co (https://www.gov.co) - Gobierno de Colombia
 *  - Componente: Volver arriba
 *  - Version: 4.0.0
 */

 var backGoToUpElement = document.querySelector(".volver-arriba-govco");
 backGoToUpElement.addEventListener("click", backGoToUp, false);

 function backGoToUp() {
   document.body.scrollTop = document.documentElement.scrollTop = 0;
 }