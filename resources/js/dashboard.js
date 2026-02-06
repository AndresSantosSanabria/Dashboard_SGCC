import * as XLSX from 'xlsx';

window.downloadTemplate = function() {
    // Definición de campos basada en tu index.html
    const CAMPOS = [
        "NUMERO DE CONTRATO", "CONTRATISTA", "CEDULA", "RP", "FECHA RP", "VALOR RP",
        "FECHA DE INICIO", "FECHA DE TERMINACIÓN", "SUPERVISOR@", "NUMERO DE CUENTA EN PROCESO DE CUENTAS",
        "NUMERO DE PAGOS TOTALES", "N° DE FACTURAS RADICADA HACIENDA", "% DE CUENTAS",
        "ENTIDAD SALUD", "ENTIDAD PENSIÓN", "ENTIDAD ARL", "MES PLANILLA SEGURIDAD SOCIAL ULTIMA CUENTA",
        "RADICADO POR", "FECHA DE RADICACIÓN TANTO INICIAL COMO SUS CORRECIONES", "OBSERVACIONES"
    ];

    // 1. Crear una hoja de trabajo a partir de los encabezados (Array of Arrays)
    const header = CAMPOS;
    const ws = XLSX.utils.aoa_to_sheet([header]);

    // 2. Crear un nuevo libro de trabajo
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Plantilla SGP');

    // 3. Generar nombre de archivo con la fecha actual
    const fecha = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    const fileName = `plantilla_sgp_${fecha}.xlsx`;

    // 4. Disparar la descarga
    XLSX.writeFile(wb, fileName);
};

// Auto-attach to button if present
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelector('.plantilla-btn');
    if (btn) {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            window.downloadTemplate();
        });
    }
});
