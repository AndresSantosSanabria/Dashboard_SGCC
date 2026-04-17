/**
 * MOTOR DE VISUALIZACIÓN BI (ApexCharts)
 * 
 * Este script gestiona la interactividad del tablero analítico.
 * Sigue un patrón de "Data-Driven UI", donde los gráficos se destruyen y 
 * recrean dinámicamente según la respuesta del servidor (AJAX).
 */
document.addEventListener('DOMContentLoaded', function () {
    let chartData = window.chartData || {};
    let charts = {};
    const slider = document.getElementById('rangeSlider');
    const filterForm = document.getElementById('filterForm');
    const captureArea = document.getElementById('captureArea');

    // PALETA DE COLORES INSTITUCIONAL
    // Basada en la guía GOV.CO con extensiones para semántica de BI (Green/Amber/Red).
    const P = {
        primary: '#0f172a',
        accent: '#3b82f6',
        success: '#10b981',
        warning: '#f59e0b',
        danger: '#ef4444',
        info: '#6366f1',
        slate: '#475569',
        slateLt: '#94a3b8',
        muted: '#f1f5f9',
        blue: '#0f172a',
        blueLt: '#3b82f6',
        indigo: '#6366f1',
        teal: '#14b8a6',
        green: '#10b981',
        greenLt: '#34d399',
        amber: '#f59e0b',
        orange: '#f97316',
        red: '#ef4444',
        redLt: '#f87171',
        gray: '#475569',
        grayLt: '#94a3b8'
    };

    const baseFont = { fontFamily: 'Inter, sans-serif' };
    const noToolbar = { show: false };

    /**
     * Inicialización Dinámica de Gráficos
     * Esta función separa la lógica de configuración de la lógica de datos.
     */
    function initCharts(data) {
        // 1. DISTRIBUCIÓN POR ESTADO (Donut)
        // Permite ver rápidamente la proporción de cuentas devueltas vs proceso.
        if (data.estado_anillos) {
            if (charts.donut) charts.donut.destroy();
            charts.donut = new ApexCharts(document.querySelector("#donutChart"), {
                series: data.estado_anillos.series,
                chart: { type: 'donut', height: 320, ...baseFont },
                labels: data.estado_anillos.labels,
                colors: [P.accent, P.danger],
                legend: {
                    position: 'bottom',
                    fontSize: '13px',
                    fontWeight: 500,
                    fontFamily: 'Inter, sans-serif',
                    markers: { radius: 12 },
                    itemMargin: { horizontal: 15, vertical: 8 }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '75%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total General',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    color: P.slateLt,
                                    formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                },
                                value: {
                                    fontSize: '2.2rem',
                                    fontWeight: 800,
                                    color: P.primary,
                                    offsetY: 5
                                }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false },
                stroke: { width: 5, colors: ['#fff'] },
            });
            charts.donut.render();
        }

        // 1.5 DELAY – Demora por Etapa
        if (data.demora_bloques && data.demora_bloques.length > 0) {
            const promedios = data.demora_bloques.map(d => d.promedio_horas);
            const maxTime = Math.max(...promedios);
            const minTime = Math.min(...promedios);
            const range = maxTime - minTime;

            const delayColors = data.demora_bloques.map(d => {
                const h = d.promedio_horas;
                if (range < 0.1) return P.green;
                const band1 = minTime + (range * 0.33);
                const band2 = minTime + (range * 0.66);
                if (h <= band1) return P.green;
                if (h <= band2) return P.orange;
                return P.red;
            });

            if (charts.delay) charts.delay.destroy();
            charts.delay = new ApexCharts(document.querySelector("#delayChart"), {
                series: [{
                    name: 'Promedio (Horas)',
                    data: data.demora_bloques.map(d => d.promedio_horas)
                }],
                chart: { type: 'bar', height: 280, toolbar: noToolbar, ...baseFont, animations: { enabled: true, easing: 'easeinout', speed: 800 } },
                plotOptions: {
                    bar: { horizontal: true, borderRadius: 8, barHeight: '45%', distributed: true }
                },
                colors: delayColors,
                xaxis: {
                    categories: data.demora_bloques.map(d => d.bloque),
                    labels: { style: { fontSize: '11px', colors: P.slateLt, fontWeight: 500 } }
                },
                yaxis: { labels: { style: { fontSize: '12px', fontWeight: 600, colors: P.slate } } },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: val => {
                            const h = Math.floor(val);
                            const m = Math.round((val - h) * 60);
                            return `${h}h ${m}m en promedio`;
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: val => {
                        const h = Math.floor(val);
                        const m = Math.round((val - h) * 60);
                        return m > 0 ? `${h}h ${m}m` : `${h}h`;
                    },
                    style: { fontSize: '11px', fontWeight: 700, colors: ['#fff'] },
                    offsetX: 5
                },
                grid: { borderColor: '#f8fafc', strokeDashArray: 4 },
                legend: { show: false }
            });
            charts.delay.render();
        } else {
            document.querySelector("#delayChart").innerHTML = '<div class="empty-chart-state"><i class="bi bi-clock"></i>Sin datos de demora históricos</div>';
        }

        // 2. PIPELINE – Carga por Etapa
        if (data.gap_chart && data.gap_chart.labels.length > 0) {
            if (charts.gap) charts.gap.destroy();
            charts.gap = new ApexCharts(document.querySelector("#gapChart"), {
                series: [{ name: 'Contratos', data: data.gap_chart.series }],
                chart: { type: 'bar', height: 320, toolbar: noToolbar, ...baseFont, animations: { enabled: true, speed: 800 } },
                plotOptions: {
                    bar: { horizontal: true, borderRadius: 10, barHeight: '55%', distributed: true },
                },
                colors: [P.blue, P.indigo, P.teal, P.green, P.amber, P.red],
                xaxis: {
                    categories: data.gap_chart.labels,
                    labels: { style: { fontSize: '11px', colors: P.slateLt } }
                },
                yaxis: { labels: { style: { fontSize: '12px', fontWeight: 600, colors: P.slate } } },
                legend: { show: false },
                tooltip: { theme: 'dark', y: { formatter: val => val + " contratos" } },
                dataLabels: {
                    enabled: true,
                    textAnchor: 'start',
                    style: { colors: ['#fff'], fontSize: '12px', fontWeight: 700 },
                    offsetX: 10
                },
                grid: { borderColor: '#f8fafc', strokeDashArray: 4 }
            });
            charts.gap.render();
        } else {
            document.querySelector("#gapChart").innerHTML = '<div class="empty-chart-state"><i class="bi bi-bar-chart"></i>Sin datos en el pipeline</div>';
        }

        // 3. TIMELINE – Actividad últimos 30 días
        if (data.timeline && data.timeline.labels.length > 0) {
            if (charts.timeline) charts.timeline.destroy();
            charts.timeline = new ApexCharts(document.querySelector("#timelineChart"), {
                series: [{ name: 'Transiciones', data: data.timeline.series }],
                chart: { type: 'area', height: 280, toolbar: noToolbar, ...baseFont, sparkline: { enabled: false } },
                colors: [P.accent],
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.0, stops: [0, 100] }
                },
                stroke: { curve: 'smooth', width: 3 },
                xaxis: {
                    categories: data.timeline.labels,
                    labels: { rotate: -45, style: { fontSize: '10px', colors: P.slateLt } },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: { formatter: val => Math.round(val), style: { colors: P.slateLt } }
                },
                dataLabels: { enabled: false },
                grid: { borderColor: '#f8fafc', strokeDashArray: 4 },
                markers: { size: 5, colors: ['#fff'], strokeColors: P.accent, strokeWidth: 3, hover: { size: 7 } },
                tooltip: { theme: 'dark', y: { formatter: val => val + " transiciones" } }
            });
            charts.timeline.render();
        } else {
            document.querySelector("#timelineChart").innerHTML = '<div class="empty-chart-state"><i class="bi bi-activity"></i>Sin actividad reciente (30 días)</div>';
        }
    }

    // ==============================
    // UI UPDATES
    // ==============================
    function updateKPIs(data) {
        const fmt = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });
        const fmtDec = new Intl.NumberFormat('es-CO', { minimumFractionDigits: 1, maximumFractionDigits: 1 });

        // Hero indicator
        const miniValue = document.querySelector('.mini-value');
        if (miniValue) {
            miniValue.style.opacity = '0';
            setTimeout(() => {
                miniValue.textContent = '$' + fmt.format(data.indicadorTotalUnico || 0);
                miniValue.style.transition = 'opacity 0.5s ease';
                miniValue.style.opacity = '1';
            }, 300);
        }

        // KPI Cards dynamic update
        const cards = document.querySelectorAll('.kpi-card');
        cards.forEach(card => {
            const val = card.querySelector('.kpi-value');
            if (!val) return;
            val.style.opacity = '0';

            setTimeout(() => {
                if (card.classList.contains('kpi-blue')) val.textContent = fmt.format(data.contratistasUnicos);
                else if (card.classList.contains('kpi-amber')) val.textContent = fmt.format(data.cuentasTramite);
                else if (card.classList.contains('kpi-green')) val.textContent = fmt.format(data.cuentasRadicadas);
                else if (card.classList.contains('kpi-red')) val.textContent = fmt.format(Math.max(0, data.pagosTotales - data.cuentasRadicadas));
                else if (card.classList.contains('kpi-indigo')) val.textContent = fmt.format(data.pagosTotales);
                else if (card.classList.contains('kpi-teal')) val.textContent = fmtDec.format(data.avanceGlobal) + '%';


                val.style.transition = 'opacity 0.5s ease';
                val.style.opacity = '1';
            }, 300);
        });

        // Global progress bar
        const globalProgress = document.querySelector('.kpi-teal .progress-bar');
        if (globalProgress) {
            globalProgress.style.width = Math.min(data.avanceGlobal, 100) + '%';
        }
    }



    function updateTable(html) {
        const table = $('#alertTable').DataTable();
        table.destroy();
        document.getElementById('tableBody').innerHTML = html;
        initDataTable();
        applyMinimizedColumns();
    }

    /**
     * ESTRATEGIA DE REFRESCO AJAX (Seamless BI)
     * 
     * Implementa un patrón de "Single Page Component" dentro de la vista de analítica.
     * En lugar de recargar la página, solicitamos los datos al controlador, 
     * actualizamos los KPIs, redibujamos los gráficos y reemplazamos el HTML 
     * de la tabla de forma atómica.
     */
    async function refreshDashboard() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);

        // INDICADOR DE CARGA: Feedback visual inmediato al usuario
        if (captureArea) captureArea.classList.add('loading');

        try {
            const response = await window.apiFetch(filterForm.action + '?' + params.toString());
            const data = await response.json();

            // ACTUALIZACIÓN DE ESTADO: 
            // Sincronizamos KPIs, Gráficos y Tabla sin perder el scroll del usuario.
            updateKPIs(data);
            initCharts(data.chartData);
            updateTable(data.tableHtml);

            // MANEJO DE HISTORIAL (Browser History API): 
            // Permite que el usuario pueda usar el botón "Atrás" o compartir 
            // la URL con los filtros actuales aplicados.
            const newUrl = window.location.pathname + '?' + params.toString();
            window.history.pushState({ path: newUrl }, '', newUrl);

        } catch (error) {
            console.error('Error refreshing dashboard:', error);
            window.showSnackbar("Error al actualizar los datos. Intente de nuevo.", "error");
        } finally {
            captureArea.classList.remove('loading');
        }
    }

    // ==============================
    // INITIALIZATION
    // ==============================
    if (captureArea) captureArea.classList.remove('loading');

    initCharts(chartData);
    initDataTable();

    function initDataTable() {
        const table = $('#alertTable').DataTable({
            pageLength: 10,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
            dom: 'Bfrtip',
            buttons: [
                { extend: 'excelHtml5', text: 'Excel', className: 'd-none', filename: 'BI_Report_2026' }
            ],
            order: [[6, 'desc']]
        });
        return table;
    }

    // ==============================
    // EVENT LISTENERS
    // ==============================

    // Intercept form submit
    document.getElementById('filterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        refreshDashboard();
    });

    // Reset button
    $('#btnReset').on('click', function () {
        if (filterForm) filterForm.reset();

        // Clear hidden inputs manually since reset() doesn't always clear value=""
        if (filterForm) $(filterForm).find('input[type="hidden"]').val('');

        // Reset nuevo picker de estado
        const pickerEl  = document.getElementById('estadoPicker');
        const labelEl   = document.getElementById('selectedEstadoLabel');
        const hiddenEl  = document.getElementById('hiddenSearchEstado');
        if (pickerEl && labelEl && hiddenEl) {
            hiddenEl.value = '';
            labelEl.textContent = 'Todos los estados';
            pickerEl.querySelectorAll('.analitica-picker-item').forEach(el => el.classList.remove('is-active'));
            const allItem = pickerEl.querySelector('.analitica-picker-item[data-value=""]');
            if (allItem) allItem.classList.add('is-active');
            pickerEl.classList.remove('is-open');
        }

        // Reset date display
        const dateDisplay = document.getElementById('dateDisplay');
        if (dateDisplay) {
            dateDisplay.textContent = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
        }

        // Reset flatpickr if exists
        const fp = document.getElementById('dateRangePicker')?._flatpickr;
        if (fp) fp.clear();

        // Specific resets for third party plugins
        if (slider && slider.noUiSlider) slider.noUiSlider.set([0, 100]);

        refreshDashboard();
    });

    // Auto-refresh on select change
    $('#filterForm select').on('change', function () {
        refreshDashboard();
    });

    // Range Slider
    if (slider) {
        const minValInput = document.getElementById('minVal');
        const maxValInput = document.getElementById('maxVal');

        if (!slider.noUiSlider) {
            noUiSlider.create(slider, {
                start: [parseInt(minValInput.value), parseInt(maxValInput.value)],
                connect: true,
                range: { 'min': 0, 'max': 100 },
                step: 1,
                tooltips: true,
                format: { to: val => Math.round(val), from: val => val }
            });

            slider.noUiSlider.on('change', function () {
                refreshDashboard();
            });

            slider.noUiSlider.on('update', function (values) {
                minValInput.value = values[0];
                maxValInput.value = values[1];
            });
        }
    }

    // Flatpickr
    const dateRangePicker = document.getElementById('dateRangePicker');
    if (dateRangePicker) {
        flatpickr(dateRangePicker, {
            mode: "range",
            dateFormat: "Y-m-d",
            locale: "es",
            defaultDate: [
                document.getElementById('fecha_desde').value,
                document.getElementById('fecha_hasta').value
            ],
            onChange: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const start = instance.formatDate(selectedDates[0], "Y-m-d");
                    const end = instance.formatDate(selectedDates[1], "Y-m-d");

                    document.getElementById('fecha_desde').value = start;
                    document.getElementById('fecha_hasta').value = end;

                    const displayStart = instance.formatDate(selectedDates[0], "d F, Y");
                    const displayEnd = instance.formatDate(selectedDates[1], "d F, Y");
                    document.getElementById('dateDisplay').textContent = `${displayStart} - ${displayEnd}`;

                    refreshDashboard();
                }
            }
        });
    }

    // PDF Export mapping
    $('#btnExportPDF').on('click', function () {
        const btn = $(this);
        const originalContent = btn.html();
        const table = $('#alertTable').DataTable();

        if (!window.jspdf) {
            alert('Error: La librería de PDF no está lista.');
            return;
        }

        btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Generando reporte...');
        btn.prop('disabled', true);

        const originalLength = table.page.len();
        table.page.len(-1).draw();

        const element = document.getElementById('captureArea');
        element.classList.add('is-exporting');
        window.scrollTo(0, 0);

        setTimeout(() => {
            html2canvas(element, {
                scale: 2,
                useCORS: true,
                allowTaint: true,
                logging: false,
                backgroundColor: "#ffffff",
                windowWidth: 1400, // Aumentado para evitar cortes laterales
                width: 1400,
                onclone: (clonedDoc) => {
                    const header = clonedDoc.getElementById('pdfHeader');
                    const footer = clonedDoc.getElementById('pdfFooter');
                    
                    if (header) {
                        header.classList.remove('d-none');
                        header.style.display = 'block';

                        // Valor RP
                        const rpValue = document.querySelector('.mini-value')?.textContent || '';
                        const indicators = clonedDoc.getElementById('pdfIndicators');
                        if (indicators) {
                            indicators.innerHTML = `
                                <div style="background: #0f172a; padding: 12px 20px; border-radius: 14px; border: 1px solid #1e293b; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                    <div style="font-size: 8px; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px;">VALOR TOTAL RP</div>
                                    <div style="font-size: 22px; color: #ffffff; font-weight: 900; line-height: 1;">${rpValue}</div>
                                </div>
                            `;
                        }

                        // Fecha del reporte (Rango visible)
                        const dateText = document.getElementById('dateDisplay')?.textContent || '';
                        const pdfDateDisplay = clonedDoc.getElementById('pdfDateDisplay');
                        if (pdfDateDisplay) pdfDateDisplay.textContent = dateText;

                        // Filtros aplicados humanizados
                        const filters = [];
                        const fForm = document.getElementById('filterForm');
                        if (fForm) {
                            const contrato = fForm.querySelector('[name="contrato"]')?.value;
                            if (contrato) filters.push(`Contrato: ${contrato}`);
                            
                            const supervisor = fForm.querySelector('[name="supervisor"] option:checked')?.text;
                            if (supervisor && supervisor !== 'Todos') filters.push(`Supervisor: ${supervisor}`);
                            
                            const responsable = fForm.querySelector('[name="responsable"] option:checked')?.text;
                            if (responsable && responsable !== 'Todos') filters.push(`Responsable: ${responsable}`);
                            
                            const estado = document.getElementById('selectedEstadoLabel')?.textContent;
                            if (estado && estado !== 'Todos los estados') filters.push(`Estado: ${estado}`);
                            
                            const minP = document.getElementById('minVal')?.value;
                            const maxP = document.getElementById('maxVal')?.value;
                            if (minP !== "0" || maxP !== "100") filters.push(`Avance: ${minP}% - ${maxP}%`);
                        }
                        
                        const pdfAppliedFilters = clonedDoc.getElementById('pdfAppliedFilters');
                        if (pdfAppliedFilters) {
                            pdfAppliedFilters.textContent = filters.length > 0 ? filters.join(' | ') : 'Sin filtros específicos (Global)';
                        }
                    }

                    if (footer) {
                        footer.classList.remove('d-none');
                        footer.style.display = 'block';
                    }

                    // Forzar fondos blancos en ApexCharts para el PDF
                    clonedDoc.querySelectorAll('.apexcharts-canvas').forEach(c => {
                         c.style.background = '#ffffff';
                         c.style.borderRadius = '0px';
                    });

                    // LÓGICA DE SALTO DE PÁGINA PARA TARJETAS
                    const pageHeightPx = 990; // Proporción A4 Landscape para 1400px de ancho
                    const cards = clonedDoc.querySelectorAll('.card, .kpi-card, .chart-container, .card-premium, .section-title');

                    cards.forEach(card => {
                        const rect = card.getBoundingClientRect();
                        let top = 0;
                        let curr = card;
                        const container = clonedDoc.getElementById('captureArea');

                        while (curr && curr !== container && curr !== clonedDoc.body) {
                            top += curr.offsetTop;
                            curr = curr.offsetParent;
                        }

                        const bottom = top + rect.height;
                        const pageNumAtTop = Math.floor(top / pageHeightPx);
                        const pageNumAtBottom = Math.floor(bottom / pageHeightPx);

                        if (pageNumAtTop !== pageNumAtBottom && rect.height < pageHeightPx * 0.8) {
                            const neededShift = (pageNumAtBottom * pageHeightPx) - top;
                            card.style.marginTop = (neededShift + 40) + 'px';
                        }
                    });
                }
            }).then(canvas => {
                const { jsPDF } = window.jspdf;
                const imgData = canvas.toDataURL('image/jpeg', 0.95);

                const pdf = new jsPDF({ orientation: 'l', unit: 'mm', format: 'a4' });

                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();

                const imgWidth = pdfWidth;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;

                let heightLeft = imgHeight;
                let position = 0;

                // Página 1
                pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight, undefined, 'FAST');
                heightLeft -= pdfHeight;

                // Páginas subsiguientes
                while (heightLeft > 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight, undefined, 'FAST');
                    heightLeft -= pdfHeight;
                }

                pdf.save(`SGCC_Reporte_Analitico_${new Date().toISOString().split('T')[0]}.pdf`);
                table.page.len(originalLength).draw();
                element.classList.remove('is-exporting');
                btn.html(originalContent).prop('disabled', false);
            }).catch(err => {
                console.error(err);
                table.page.len(originalLength).draw();
                element.classList.remove('is-exporting');
                btn.html(originalContent).prop('disabled', false);
            });
        }, 800);
    });

    // --- Lógica de Ocultar Columnas ---
    let minimizedColumns = new Set();

    window.resetColumns = function () {
        minimizedColumns.clear();
        applyMinimizedColumns();
    };

    function toggleColumn(index) {
        if (minimizedColumns.has(index)) {
            minimizedColumns.delete(index);
        } else {
            minimizedColumns.add(index);
        }
        applyMinimizedColumns();
    }

    function applyMinimizedColumns() {
        const table = document.getElementById('alertTable');
        const resetBtn = document.getElementById('btnResetColumns');
        if (!table) return;

        // Resetear todo
        table.querySelectorAll('.column-hidden').forEach(el => el.classList.remove('column-hidden'));

        // Ocultar columnas seleccionadas
        minimizedColumns.forEach(index => {
            const cells = table.querySelectorAll(`tr > *:nth-child(${index + 1})`);
            cells.forEach(cell => {
                cell.classList.add('column-hidden');
            });
        });

        // Mostrar/Ocultar botón de reset
        if (resetBtn) {
            resetBtn.style.display = minimizedColumns.size > 0 ? 'inline-flex' : 'none';
        }
    }

    // Listener delegado para los botones de ocultar
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.toggle-col-btn');
        if (btn) {
            const th = btn.closest('th');
            if (th) {
                const index = Array.from(th.parentNode.children).indexOf(th);
                toggleColumn(index);
            }
        }
    });

    // ==============================
    // ANALITICA ESTADO PICKER (Custom JS - No Bootstrap)
    // ==============================
    const picker       = document.getElementById('estadoPicker');
    const pickerBtn    = document.getElementById('estadoPickerBtn');
    const pickerMenu   = document.getElementById('estadoPickerMenu');
    const hiddenEstado = document.getElementById('hiddenSearchEstado');
    const estadoLabel  = document.getElementById('selectedEstadoLabel');

    if (picker && pickerBtn) {
        // Abrir/Cerrar al pulsar el botón
        pickerBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            picker.classList.toggle('is-open');
        });

        // Clic en un ítem de estado → seleccionar y cerrar
        if (pickerMenu) {
            pickerMenu.addEventListener('click', function (e) {
                const item = e.target.closest('.analitica-picker-item');
                const groupHeader = e.target.closest('.analitica-picker-group-header');

                // Toggle de grupo (bloque)
                if (groupHeader) {
                    e.stopPropagation();
                    const group = groupHeader.closest('.analitica-picker-group');
                    if (group) group.classList.toggle('is-expanded');
                    return;
                }

                // Selección de estado
                if (item) {
                    e.stopPropagation();
                    const value = item.dataset.value;
                    const label = item.dataset.label || item.textContent.trim();

                    if (hiddenEstado) hiddenEstado.value = value;
                    if (estadoLabel)  estadoLabel.textContent = label;

                    // Marcar activo
                    pickerMenu.querySelectorAll('.analitica-picker-item').forEach(el => el.classList.remove('is-active'));
                    item.classList.add('is-active');

                    // Cerrar el picker
                    picker.classList.remove('is-open');

                    // Disparar filtrado BI
                    refreshDashboard();
                }
            });
        }

        // Cerrar al hacer clic fuera
        document.addEventListener('click', function (e) {
            if (!picker.contains(e.target)) {
                picker.classList.remove('is-open');
            }
        });
    }
});
