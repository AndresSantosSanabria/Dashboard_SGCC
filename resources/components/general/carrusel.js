/**
 * Gov.co (https://www.gov.co) - Gobierno de Colombia
 *  - Componente: Carrusel
 *  - Version: 4.0.0
 */
window.addEventListener("load", function(event) {
  window.addEventListener('resize', windowSizeCarrusel);
  windowSizeCarrusel();
});

var carousels = document.querySelectorAll('.carrusel-govco');
var configurationCarousel = {};
for (const carousel of carousels) {
  configurationCarousel[carousel.id] = new bootstrap.Carousel(carousel, {
    pause: false
  });
  
  var controlsCarrusel = carousel.querySelectorAll('.control-start-pause .controls');
  methodAssign("click", startStopCarousel, controlsCarrusel);
  
  carousel.addEventListener('slid.bs.carousel', function () {
    this.querySelectorAll('.carousel-item:not(.active) a').forEach(x => x.tabIndex = -1);
    this.querySelectorAll('.carousel-item.active a').forEach(x => x.tabIndex = 0);
  });
}

function windowSizeCarrusel() {
  let containerInputsFile = document.querySelectorAll('.carrusel-govco');
  for (let e of containerInputsFile) {
    if (e.offsetWidth < 652) {
      e.classList.add('responsive-carrusel-govco');
    } else {
      e.classList.remove('responsive-carrusel-govco');
    }
  }
}

function methodAssign(e, f, a) {
  for (let i of a) {
    i.addEventListener(e, f, false);
  }
}

function startStopCarousel() {
  const container = this.parentNode.parentNode;
  this.classList.remove("active");

  if (this.classList.contains("start")) {
    this.nextElementSibling.classList.add("active");
    configurationCarousel[container.id].cycle();
  } else {
    this.previousElementSibling.classList.add("active");
    configurationCarousel[container.id].pause();
  }
}
