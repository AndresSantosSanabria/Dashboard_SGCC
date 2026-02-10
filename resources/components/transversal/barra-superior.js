/**
 * Gov.co (https://www.gov.co) - Gobierno de Colombia
 *  - Componente: Barra superior
 *  - Version: 4.0.0
 */
window.addEventListener("load", function() {
    initTopBar();
});


function initTopBar() {
    const translateElement = document.querySelector(".idioma-icon-barra-superior-govco");
    translateElement.addEventListener("click", translate, false);

    function translate() {
        // ... // Implementar traducción
    }
}
