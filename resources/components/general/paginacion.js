const divData = document.getElementById("paginationExample");
const totalPages = divData.getAttribute("total");
let initialActive = Number(divData.getAttribute("initialpage"));
(document.getElementById("lista-paginador").innerHTML =
dibujarElementos(totalPages, initialActive));

function dibujarElementos(pages, page) {
    let liTag = "";
    let active;
    let beforePages = page - 1;
    let afterPages = page + 1;
  
    if (page > 1) {
      liTag += `<li class="page-item-govco prev-page-govco no"><a href="javascript:void(0)" onclick="dibujarElementos(${pages}, (${page}-1))"><span class="prev-page-icon-govco"></span><span class="prev-page-text-govco">Anterior</span></a></li>`;
    } else if (page == 1) {
      liTag += `<li class="page-item-govco prev-page-govco disabled-govco"><a href="javascript:void(0)"><span class="prev-page-icon-govco"></span><span class="prev-page-text-govco">Anterior</span></a></li>`;
    }
  
    if (pages < 6) {
      for (let p = 1; p <= pages; p++) {
        active = page == p ? "active-govco" : "no";
        liTag += `<li class="page-item-govco number-govco ${active}"><a href="javascript:void(0)" onclick="dibujarElementos(${pages}, ${p})">${p}</a></li>`;
      }
    } else {
      if (page > 2) {
        liTag += `<li class="page-item-govco number-govco"><a href="javascript:void(0)" onclick="dibujarElementos(${pages}, 1)">1</a></li>`;
        if (page > 3) {
          liTag += `<li class="page-item-govco dots-govco"><a>...</a></li>`;
        }
      }
  
      if (page === 1) {
        afterPages += 2;
      } else if (page === 2) {
        afterPages += 1;
      }
  
      if (page === pages) {
        beforePages -= 2;
      } else if (page === pages - 1) {
        beforePages -= 1;
      }
  
      for (let p = beforePages; p <= afterPages; p++) {
        if (p === 0) {
          p += 1;
        }
        if (p > pages) {
          continue;
        }
        active = page == p ? "active-govco" : "no";
        liTag += `<li class="page-item-govco number-govco ${active}"><a href="javascript:void(0)" onclick="dibujarElementos(${pages}, ${p})">${p}</a></li>`;
      }
  
      if (page < pages - 1) {
        if (page < pages - 2) {
          liTag += `<li class="page-item-govco dots-govco"><a>...</a></li>`;
        }
        liTag += `<li class="page-item-govco number-govco no"><a href="javascript:void(0)" onclick="dibujarElementos(${pages}, ${pages})">${pages}</a></li>`;
      }
    }
  
    if (page < pages) {
      liTag += `<li class="page-item-govco next-page-govco"><a href="javascript:void(0)" onclick="dibujarElementos(${pages}, (${page}+1))"><span class="next-page-icon-govco"></span><span class="next-page-text-govco">Siguiente</span></a></li>`;
    } else if (page == pages) {
      liTag += `<li class="page-item-govco next-page-govco disabled-govco"><a href="javascript:void(0)"><span class="next-page-icon-govco"></span><span class="next-page-text-govco">Siguiente</span></a></li>`;
    }
    document.getElementById("lista-paginador").innerHTML = liTag;
    return liTag;
  }