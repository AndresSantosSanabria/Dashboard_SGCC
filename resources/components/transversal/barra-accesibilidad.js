function cambiarContexto() {


  var botoncontraste = document.getElementById("botoncontraste");
  var botonaumentar = document.getElementById("botonaumentar");
  var botondisminuir = document.getElementById("botondisminuir");

    if (!botoncontraste.classList.contains("active-barra-accesibilidad-govco")) {
      botoncontraste.classList.toggle("active-barra-accesibilidad-govco");
      document.getElementById("titleaumentar").style.display="";
      document.getElementById("titledisminuir").style.display="";
      document.getElementById("titlecontraste").style.display="none";
    }
    if (botondisminuir.classList.contains("active-barra-accesibilidad-govco")) {
      botondisminuir.classList.remove("active-barra-accesibilidad-govco");
    }
    if (botonaumentar.classList.contains("active-barra-accesibilidad-govco")) {
      botonaumentar.classList.remove("active-barra-accesibilidad-govco");
    }

  var element = document.getElementById('para-mirar');
  if (element.className == 'modo_oscuro-govco') {
    var element = document.getElementById('para-mirar');
    element.className = "modo_claro-govco";
  } else {
    var element = document.getElementById('para-mirar');
    element.className = "modo_oscuro-govco";
  }
}

function disminuirTamanio(operador) {

  var botoncontraste = document.getElementById("botoncontraste");
  var botonaumentar = document.getElementById("botonaumentar");
  var botondisminuir = document.getElementById("botondisminuir");

    if (!botondisminuir.classList.contains("active-barra-accesibilidad-govco")) {
      botondisminuir.classList.toggle("active-barra-accesibilidad-govco");
      document.getElementById("titleaumentar").style.display="";
      document.getElementById("titledisminuir").style.display="none";
      document.getElementById("titlecontraste").style.display="";
    }
    if (botonaumentar.classList.contains("active-barra-accesibilidad-govco")) {
      botonaumentar.classList.remove("active-barra-accesibilidad-govco");
    }
    if (botoncontraste.classList.contains("active-barra-accesibilidad-govco")) {
      botoncontraste.classList.remove("active-barra-accesibilidad-govco");
    }

  var div1 = document.getElementById("para-mirar")
  var texto = div1.getElementsByTagName("p");
  for (let element of texto) {
    const total = tamanioElemento(element);
    const nuevoTamanio = (operador === 'aumentar' ? (total + 1) : (total - 1)) + 'px';
    element.style.fontSize = nuevoTamanio
  }
}

function aumentarTamanio(operador) {

  var botoncontraste = document.getElementById("botoncontraste");
  var botonaumentar = document.getElementById("botonaumentar");
  var botondisminuir = document.getElementById("botondisminuir");

    if (!botonaumentar.classList.contains("active-barra-accesibilidad-govco")) {
      botonaumentar.classList.toggle("active-barra-accesibilidad-govco");
      document.getElementById("titleaumentar").style.display="none";
      document.getElementById("titledisminuir").style.display="";
      document.getElementById("titlecontraste").style.display="";
    }
    if (botondisminuir.classList.contains("active-barra-accesibilidad-govco")) {
      botondisminuir.classList.remove("active-barra-accesibilidad-govco");
    }
    if (botoncontraste.classList.contains("active-barra-accesibilidad-govco")) {
      botoncontraste.classList.remove("active-barra-accesibilidad-govco");
    }

  var div1 = document.getElementById("para-mirar")
  var texto = div1.getElementsByTagName("p");
  for (let element of texto) {
    const total = tamanioElemento(element);
    if(total<=30)
    {
    const nuevoTamanio = (operador === 'aumentar' ? (total + 1) : (total - 1)) + 'px';
    element.style.fontSize = nuevoTamanio
    }
  }
}

function tamanioElemento(element) {
  const tamanioParrafo = window.getComputedStyle(element, null).getPropertyValue('font-size');
  return parseFloat(tamanioParrafo);
}