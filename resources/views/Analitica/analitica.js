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
    const slider      = document.getElementById('rangeSlider');
    const filterForm  = document.getElementById('filterForm');
    const captureArea = document.getElementById('captureArea');

    // PALETA DE COLORES INSTITUCIONAL
    const P = {
        primary:  '#0f172a',
        accent:   '#3b82f6',
        success:  '#10b981',
        warning:  '#f59e0b',
        danger:   '#ef4444',
        info:     '#6366f1',
        slate:    '#475569',
        slateLt:  '#94a3b8',
        muted:    '#f1f5f9',
        blue:     '#0f172a',
        blueLt:   '#3b82f6',
        indigo:   '#6366f1',
        teal:     '#14b8a6',
        green:    '#10b981',
        greenLt:  '#34d399',
        amber:    '#f59e0b',
        orange:   '#f97316',
        red:      '#ef4444',
        redLt:    '#f87171',
        gray:     '#475569',
        grayLt:   '#94a3b8'
    };

    const baseFont  = { fontFamily: 'Inter, sans-serif' };
    const noToolbar = { show: false };



    // ==============================
    // CHARTS
    // ==============================
    function initCharts(data) {

        // 1. DISTRIBUCIÓN POR ESTADO (Donut)
        if (data.estado_anillos) {
            if (charts.donut) charts.donut.destroy();
            charts.donut = new ApexCharts(document.querySelector('#donutChart'), {
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
                stroke: { width: 5, colors: ['#fff'] }
            });
            charts.donut.render();
        }

        // 1.5 DELAY – Demora por Etapa
        if (data.demora_bloques && data.demora_bloques.length > 0) {
            const promedios = data.demora_bloques.map(d => d.promedio_horas);
            const maxTime   = Math.max(...promedios);
            const minTime   = Math.min(...promedios);
            const range     = maxTime - minTime;

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
            charts.delay = new ApexCharts(document.querySelector('#delayChart'), {
                series: [{ name: 'Promedio (Horas)', data: data.demora_bloques.map(d => d.promedio_horas) }],
                chart: { type: 'bar', height: 280, toolbar: noToolbar, ...baseFont, animations: { enabled: true, easing: 'easeinout', speed: 800 } },
                plotOptions: { bar: { horizontal: true, borderRadius: 8, barHeight: '45%', distributed: true } },
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
            const el = document.querySelector('#delayChart');
            if (el) el.innerHTML = '<div class="empty-chart-state"><i class="bi bi-clock"></i>Sin datos de demora históricos</div>';
        }

        // 2. PIPELINE – Carga por Etapa
        if (data.gap_chart && data.gap_chart.labels.length > 0) {
            if (charts.gap) charts.gap.destroy();
            charts.gap = new ApexCharts(document.querySelector('#gapChart'), {
                series: [{ name: 'Contratos', data: data.gap_chart.series }],
                chart: { type: 'bar', height: 320, toolbar: noToolbar, ...baseFont, animations: { enabled: true, speed: 800 } },
                plotOptions: { bar: { horizontal: true, borderRadius: 10, barHeight: '55%', distributed: true } },
                colors: [P.blue, P.indigo, P.teal, P.green, P.amber, P.red],
                xaxis: {
                    categories: data.gap_chart.labels,
                    labels: { style: { fontSize: '11px', colors: P.slateLt } }
                },
                yaxis: { labels: { style: { fontSize: '12px', fontWeight: 600, colors: P.slate } } },
                legend: { show: false },
                tooltip: { theme: 'dark', y: { formatter: val => val + ' contratos' } },
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
            const el = document.querySelector('#gapChart');
            if (el) el.innerHTML = '<div class="empty-chart-state"><i class="bi bi-bar-chart"></i>Sin datos en el pipeline</div>';
        }

        // 3. TIMELINE – Actividad últimos 30 días
        if (data.timeline && data.timeline.labels.length > 0) {
            if (charts.timeline) charts.timeline.destroy();
            charts.timeline = new ApexCharts(document.querySelector('#timelineChart'), {
                series: [{ name: 'Transiciones', data: data.timeline.series }],
                chart: { type: 'area', height: 280, toolbar: noToolbar, ...baseFont },
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
                yaxis: { labels: { formatter: val => Math.round(val), style: { colors: P.slateLt } } },
                dataLabels: { enabled: false },
                grid: { borderColor: '#f8fafc', strokeDashArray: 4 },
                markers: { size: 5, colors: ['#fff'], strokeColors: P.accent, strokeWidth: 3, hover: { size: 7 } },
                tooltip: { theme: 'dark', y: { formatter: val => val + ' transiciones' } }
            });
            charts.timeline.render();
        } else {
            const el = document.querySelector('#timelineChart');
            if (el) el.innerHTML = '<div class="empty-chart-state"><i class="bi bi-activity"></i>Sin actividad reciente (30 días)</div>';
        }
    }

    // ==============================
    // UI UPDATES
    // ==============================
    function updateKPIs(data) {
        const fmt    = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });
        const fmtDec = new Intl.NumberFormat('es-CO', { minimumFractionDigits: 1, maximumFractionDigits: 1 });

        const miniValue = document.querySelector('.mini-value');
        if (miniValue) {
            miniValue.style.opacity = '0';
            setTimeout(() => {
                miniValue.textContent = '$' + fmt.format(data.indicadorTotalUnico || 0);
                miniValue.style.transition = 'opacity 0.5s ease';
                miniValue.style.opacity = '1';
            }, 300);
        }

        const cards = document.querySelectorAll('.kpi-card');
        cards.forEach(card => {
            const val = card.querySelector('.kpi-value');
            if (!val) return;
            val.style.opacity = '0';
            setTimeout(() => {
                if (card.classList.contains('kpi-blue'))   val.textContent = fmt.format(data.contratistasUnicos);
                else if (card.classList.contains('kpi-amber'))  val.textContent = fmt.format(data.cuentasTramite);
                else if (card.classList.contains('kpi-green'))  val.textContent = fmt.format(data.cuentasRadicadas);
                else if (card.classList.contains('kpi-red'))    val.textContent = fmt.format(Math.max(0, data.pagosTotales - data.cuentasRadicadas));
                else if (card.classList.contains('kpi-indigo')) val.textContent = fmt.format(data.pagosTotales);
                else if (card.classList.contains('kpi-teal'))   val.textContent = fmtDec.format(data.avanceGlobal) + '%';
                val.style.transition = 'opacity 0.5s ease';
                val.style.opacity = '1';
            }, 300);
        });

        const globalProgress = document.querySelector('.kpi-teal .progress-bar');
        if (globalProgress) globalProgress.style.width = Math.min(data.avanceGlobal, 100) + '%';
    }

    function updateTable(html) {
        const table = $('#alertTable').DataTable();
        table.destroy();
        document.getElementById('tableBody').innerHTML = html;
        initDataTable();
        applyMinimizedColumns();
    }

    // ==============================
    // AJAX REFRESH
    // ==============================
    async function refreshDashboard() {
        const formData = new FormData(filterForm);
        const params   = new URLSearchParams(formData);

        if (captureArea) captureArea.classList.add('loading');

        try {
            const response = await window.apiFetch(filterForm.action + '?' + params.toString());
            const data     = await response.json();

            updateKPIs(data);
            initCharts(data.chartData);
            updateTable(data.tableHtml);

            const newUrl = window.location.pathname + '?' + params.toString();
            window.history.pushState({ path: newUrl }, '', newUrl);
        } catch (error) {
            console.error('Error refreshing dashboard:', error);
            window.showSnackbar('Error al actualizar los datos. Intente de nuevo.', 'error');
        } finally {
            if (captureArea) captureArea.classList.remove('loading');
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
                {
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'd-none',
                    filename: 'BI_Report_SGCC',
                    exportOptions: { columns: ':visible' }
                }
            ],
            order: [[6, 'desc']]
        });
        return table;
    }

    // ==============================
    // EVENT LISTENERS
    // ==============================
    document.getElementById('filterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        refreshDashboard();
    });

    $('#btnReset').on('click', function () {
        if (filterForm) filterForm.reset();
        if (filterForm) $(filterForm).find('input[type="hidden"]').val('');

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

        const dateDisplay = document.getElementById('dateDisplay');
        if (dateDisplay) {
            dateDisplay.textContent = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
        }

        const fp = document.getElementById('dateRangePicker')?._flatpickr;
        if (fp) fp.clear();

        if (slider && slider.noUiSlider) slider.noUiSlider.set([0, 100]);

        refreshDashboard();
    });

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

            slider.noUiSlider.on('change', function () { refreshDashboard(); });
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
            mode: 'range',
            dateFormat: 'Y-m-d',
            locale: 'es',
            defaultDate: [
                document.getElementById('fecha_desde').value,
                document.getElementById('fecha_hasta').value
            ],
            onChange: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const start = instance.formatDate(selectedDates[0], 'Y-m-d');
                    const end   = instance.formatDate(selectedDates[1], 'Y-m-d');

                    document.getElementById('fecha_desde').value = start;
                    document.getElementById('fecha_hasta').value = end;

                    const displayStart = instance.formatDate(selectedDates[0], 'd F, Y');
                    const displayEnd   = instance.formatDate(selectedDates[1], 'd F, Y');
                    document.getElementById('dateDisplay').textContent = `${displayStart} - ${displayEnd}`;

                    refreshDashboard();
                }
            }
        });
    }

    // ===================================================================
    // PDF EXPORT — Generación programática limpia con jsPDF + AutoTable
    //
    // Estrategia: construir el PDF directamente con jsPDF en lugar de
    // capturar el DOM con html2canvas (que no soporta oklch ni temas oscuros).
    // El resultado es un documento de impresión profesional con fondo blanco.
    // ===================================================================
    $('#btnExportPDF').on('click', async function () {
        const btn             = $(this);
        const originalContent = btn.html();

        try {
            const jsPDFConstructor = (window.jspdf && window.jspdf.jsPDF) ? window.jspdf.jsPDF : window.jsPDF;
            if (!jsPDFConstructor) {
                Swal.fire('Error', 'Librería jsPDF no cargada. Verifique la conexión a internet.', 'error');
                return;
            }

            btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Generando PDF...');
            btn.prop('disabled', true);

            // ── Paleta institucional ──
            const C = {
                navy:      [15, 23, 42],
                blue:      [59, 130, 246],
                green:     [16, 185, 129],
                amber:     [245, 158, 11],
                red:       [239, 68, 68],
                indigo:    [99, 102, 241],
                teal:      [20, 184, 166],
                slate:     [71, 85, 105],
                slateLt:   [148, 163, 184],
                border:    [226, 232, 240],
                bg:        [248, 250, 252],
                white:     [255, 255, 255],
            };

            const pdf = new jsPDFConstructor({ orientation: 'l', unit: 'mm', format: 'a4' });
            const W   = pdf.internal.pageSize.getWidth();   // 297mm
            const H   = pdf.internal.pageSize.getHeight();  // 210mm
            const M   = 14; // margen horizontal
            let   y   = M;

            // ── Helpers ──
            const rgb = (arr) => ({ r: arr[0], g: arr[1], b: arr[2] });

            function newPageIfNeeded(neededMm) {
                if (y + neededMm > H - M) {
                    pdf.addPage();
                    drawPageFooter();
                    y = M;
                    return true;
                }
                return false;
            }

            function drawPageFooter() {
                const pageNum = pdf.internal.getCurrentPageInfo().pageNumber;
                pdf.setDrawColor(...C.border);
                pdf.setLineWidth(0.2);
                pdf.line(M, H - 8, W - M, H - 8);
                pdf.setFontSize(7);
                pdf.setTextColor(...C.slateLt);
                pdf.text('© ' + new Date().getFullYear() + ' Gobernación de Cundinamarca — Informe Analítico BI', M, H - 4);
                pdf.text('Pág. ' + pageNum, W - M, H - 4, { align: 'right' });
            }

            // ── PÁGINA 1: ENCABEZADO ──────────────────────────────────────
            // Banda superior institucional
            pdf.setFillColor(...C.navy);
            pdf.rect(0, 0, W, 22, 'F');

            // Línea de acento
            pdf.setFillColor(...C.blue);
            pdf.rect(0, 22, W, 1.2, 'F');

            // Texto encabezado
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(14);
            pdf.setTextColor(...C.white);
            pdf.text('INFORME DE GESTIÓN BI', M, 10);

            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(7.5);
            pdf.setTextColor(...C.slateLt);
            pdf.text('Secretaría de Tecnologías de la Información y las Comunicaciones', M, 16);

            // Metadata derecha
            const now     = new Date();
            const dateStr = now.toLocaleDateString('es-CO', { day: '2-digit', month: 'long', year: 'numeric' });
            const timeStr = now.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
            const userName = document.querySelector('meta[name="user-name"]')?.content
                          || (document.querySelector('.navbar .dropdown-toggle')?.textContent?.trim() || 'Admin Sistema');

            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(7);
            pdf.setTextColor(...C.slateLt);
            pdf.text('GENERADO POR', W - M, 7, { align: 'right' });
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(8);
            pdf.setTextColor(...C.white);
            pdf.text(userName, W - M, 12, { align: 'right' });
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(7);
            pdf.setTextColor(...C.slateLt);
            pdf.text('FECHA: ' + dateStr + ' ' + timeStr, W - M, 17, { align: 'right' });

            y = 30;

            // ── Filtros aplicados ──
            const filters = [];
            const ff = document.getElementById('filterForm');
            if (ff) {
                const c = ff.querySelector('[name="contrato"]')?.value;
                if (c) filters.push('Contrato: ' + c);
                const s = ff.querySelector('[name="supervisor"] option:checked')?.text;
                if (s && s !== 'Todos') filters.push('Supervisor: ' + s);
                const r = ff.querySelector('[name="responsable"] option:checked')?.text;
                if (r && r !== 'Todos') filters.push('Responsable: ' + r);
                const e = document.getElementById('selectedEstadoLabel')?.textContent;
                if (e && e !== 'Todos los estados') filters.push('Estado: ' + e);
            }
            const dateRange = document.getElementById('dateDisplay')?.textContent?.trim() || dateStr;
            const filterTxt = filters.length > 0 ? filters.join('  |  ') : 'Sin filtros específicos (Global)';

            pdf.setFillColor(...C.bg);
            pdf.roundedRect(M, y, W - M * 2, 10, 2, 2, 'F');
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(7);
            pdf.setTextColor(...C.slate);
            pdf.text('Filtros aplicados:', M + 3, y + 4.5);
            pdf.setFont('helvetica', 'normal');
            pdf.setTextColor(...C.slateLt);
            pdf.text(filterTxt, M + 32, y + 4.5);
            pdf.setFont('helvetica', 'bold');
            pdf.setTextColor(...C.slate);
            pdf.text('Período:  ' + dateRange, W - M - 3, y + 4.5, { align: 'right' });
            y += 15;

            // ── SECCIÓN: KPIs ─────────────────────────────────────────────
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(8);
            pdf.setTextColor(...C.slate);
            pdf.text('INDICADORES CLAVE', M, y);
            pdf.setDrawColor(...C.blue);
            pdf.setLineWidth(0.5);
            pdf.line(M, y + 1, M + 38, y + 1);
            y += 5;

            // Recolectar valores KPI desde el DOM
            const kpiCards = document.querySelectorAll('.kpi-card');
            const kpiData  = [];

            // Valor RP del mini-indicator
            const rpVal = document.querySelector('.mini-value')?.textContent?.trim() || '$0';
            kpiData.push({ label: 'Valor Total RP', value: rpVal, color: C.navy });

            kpiCards.forEach(card => {
                const val   = card.querySelector('.kpi-value')?.textContent?.trim() || '0';
                const label = card.querySelector('.kpi-label')?.textContent?.trim() || '';
                let color   = C.blue;
                if (card.classList.contains('kpi-amber'))  color = C.amber;
                if (card.classList.contains('kpi-green'))  color = C.green;
                if (card.classList.contains('kpi-red'))    color = C.red;
                if (card.classList.contains('kpi-indigo')) color = C.indigo;
                if (card.classList.contains('kpi-teal'))   color = C.teal;
                if (label) kpiData.push({ label, value: val, color });
            });

            // Dibujar tarjetas KPI en una fila
            const kpiCount = Math.min(kpiData.length, 7);
            const kpiW     = (W - M * 2 - (kpiCount - 1) * 3) / kpiCount;
            const kpiH     = 26; // Aumentado de 20 para mejor proporción

            kpiData.slice(0, kpiCount).forEach((kpi, i) => {
                const kx = M + i * (kpiW + 3);
                const ky = y;

                // Fondo tarjeta
                pdf.setFillColor(...C.white);
                pdf.setDrawColor(...C.border);
                pdf.setLineWidth(0.15);
                pdf.roundedRect(kx, ky, kpiW, kpiH, 2, 2, 'FD');

                // Barra de color superior
                pdf.setFillColor(...kpi.color);
                pdf.roundedRect(kx, ky, kpiW, 1.8, 1, 1, 'F');
                pdf.rect(kx, ky + 1, kpiW, 0.8, 'F');

                // Valor
                pdf.setFont('helvetica', 'bold');
                const fontSize = kpi.value.length > 12 ? 8 : 11;
                pdf.setFontSize(fontSize);
                pdf.setTextColor(...kpi.color);
                pdf.text(kpi.value, kx + kpiW / 2, ky + 13, { align: 'center' }); // Centrado vertical mejorado

                // Label
                pdf.setFont('helvetica', 'normal');
                pdf.setFontSize(6.5);
                pdf.setTextColor(...C.slateLt);
                const labelLines = pdf.splitTextToSize(kpi.label, kpiW - 4);
                pdf.text(labelLines, kx + kpiW / 2, ky + 20, { align: 'center' });
            });
            y += kpiH + 10;

            // ── SECCIÓN: Gráficos (exportados como imagen desde ApexCharts) ──
            newPageIfNeeded(10);
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(8);
            pdf.setTextColor(...C.slate);
            pdf.text('ANÁLISIS GRÁFICO', M, y);
            pdf.setDrawColor(...C.indigo);
            pdf.setLineWidth(0.5);
            pdf.line(M, y + 1, M + 38, y + 1);
            y += 6;

            // Exportar gráficos como PNG desde ApexCharts respetando aspect ratio
            const getImageSize = (src) => new Promise(res => {
                const img = new Image();
                img.onload = () => res({ w: img.width, h: img.height });
                img.src = src;
            });

            async function addChartToPdf(chartInstance, label, x, cy, w, h) {
                if (!chartInstance) return;
                try {
                    const uri = await chartInstance.dataURI();
                    const imgSrc = uri.imgURI || uri;
                    if (imgSrc && imgSrc.startsWith('data:image')) {
                        // Marco contenedor
                        pdf.setFillColor(...C.white);
                        pdf.setDrawColor(...C.border);
                        pdf.setLineWidth(0.15);
                        pdf.roundedRect(x, cy, w, h + 8, 2, 2, 'FD');

                        // Etiqueta del gráfico
                        pdf.setFont('helvetica', 'bold');
                        pdf.setFontSize(7);
                        pdf.setTextColor(...C.slate);
                        pdf.text(label, x + 4, cy + 5);

                        // Calcular ajuste proporcional (Aspect Ratio)
                        const size = await getImageSize(imgSrc);
                        const ratio = size.w / size.h;
                        
                        let targetW = w - 8;
                        let targetH = targetW / ratio;

                        if (targetH > h) {
                            targetH = h;
                            targetW = targetH * ratio;
                        }

                        // Centrar imagen dentro del marco disponible
                        const offsetX = (w - targetW) / 2;
                        const offsetY = (h - targetH) / 2 + 6; // +6 por la etiqueta

                        pdf.addImage(imgSrc, 'PNG', x + offsetX, cy + offsetY, targetW, targetH, undefined, 'FAST');
                    }
                } catch (e) {
                    pdf.setFillColor(...C.bg);
                    pdf.setDrawColor(...C.border);
                    pdf.setLineWidth(0.15);
                    pdf.roundedRect(x, cy, w, h + 8, 2, 2, 'FD');
                    pdf.setFont('helvetica', 'italic');
                    pdf.setFontSize(7);
                    pdf.setTextColor(...C.slateLt);
                    pdf.text('Gráfico no disponible', x + w / 2, cy + (h + 8) / 2, { align: 'center' });
                }
            }

            const chartRowH = 58;
            const gutter    = 10; // Aumentado para más aire entre columnas
            const halfW     = (W - M * 2 - gutter) / 2;

            // Fila 1: Donut + Pipeline
            newPageIfNeeded(chartRowH + 15);
            await addChartToPdf(charts.donut,    'DISTRIBUCIÓN DE ESTADOS',         M,             y, halfW, chartRowH);
            await addChartToPdf(charts.gap,      'PIPELINE — CARGA POR ETAPA',      M + halfW + gutter, y, halfW, chartRowH);
            y += chartRowH + 18;

            // Fila 2: Demora + Timeline
            newPageIfNeeded(chartRowH + 15);
            await addChartToPdf(charts.delay,    'DEMORA PROMEDIO (HORAS)',         M,             y, halfW, chartRowH);
            await addChartToPdf(charts.timeline, 'ACTIVIDAD ÚLTIMOS 30 DÍAS',       M + halfW + gutter, y, halfW, chartRowH);
            y += chartRowH + 18;

            // ── SECCIÓN: Tabla de contratos ────────────────────────────────
            newPageIfNeeded(20);
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(8);
            pdf.setTextColor(...C.slate);
            pdf.text('DETALLE POR CONTRATO', M, y);
            pdf.setDrawColor(...C.green);
            pdf.setLineWidth(0.5);
            pdf.line(M, y + 1, M + 46, y + 1);
            y += 6;

            // Recolectar datos de la tabla del DOM
            const dtApi  = $('#alertTable').DataTable();
            const allRows = dtApi.rows({ search: 'applied' }).data().toArray();

            // Cabeceras visibles (excluir columna de botones toggle)
            const headers = ['N° Contrato', 'Contratista', 'Etapa Actual', 'Estado', 'Meta', 'Radicadas', 'Pendientes', 'Avance'];

            // Limpiar html de las celdas
            function cellText(raw) {
                if (raw === null || raw === undefined) return '';
                const str = String(raw);
                const tmp = document.createElement('div');
                tmp.innerHTML = str;
                return (tmp.textContent || tmp.innerText || '').trim();
            }

            const tableRows = allRows.map(row => {
                return Array.from({ length: 8 }, (_, i) => cellText(row[i]));
            });

            // Estilos de columna
            const colStyles = {
                0: { cellWidth: 28 },
                1: { cellWidth: 48 },
                2: { cellWidth: 35 },
                3: { cellWidth: 35 },
                4: { cellWidth: 20, halign: 'center' },
                5: { cellWidth: 22, halign: 'center' },
                6: { cellWidth: 22, halign: 'center' },
                7: { cellWidth: 28, halign: 'center' },
            };

            if (typeof pdf.autoTable === 'function') {
                pdf.autoTable({
                    startY:     y,
                    head:       [headers],
                    body:       tableRows,
                    margin:     { left: M, right: M },
                    styles: {
                        fontSize:    7,
                        cellPadding: 2.5,
                        lineColor:   C.border,
                        lineWidth:   0.15,
                        textColor:   C.slate,
                        font:        'helvetica',
                        overflow:    'ellipsize',
                    },
                    headStyles: {
                        fillColor:   C.navy,
                        textColor:   C.white,
                        fontStyle:   'bold',
                        halign:      'left',
                        fontSize:    7,
                    },
                    alternateRowStyles: { fillColor: C.bg },
                    columnStyles: colStyles,
                    didParseCell: function (data) {
                        // Colorear columna Pendientes si > 0
                        if (data.section === 'body' && data.column.index === 6) {
                            const val = parseInt(data.cell.raw) || 0;
                            if (val > 0) {
                                data.cell.styles.textColor = C.red;
                                data.cell.styles.fontStyle  = 'bold';
                            } else {
                                data.cell.styles.textColor = C.green;
                            }
                        }
                    },
                    didDrawPage: function () {
                        drawPageFooter();
                    }
                });
            } else {
                // Fallback simple si autoTable no está disponible
                pdf.setFontSize(8);
                pdf.setTextColor(...C.red);
                pdf.text('Instale jspdf-autotable para ver la tabla de contratos.', M, y + 5);
                y += 10;
            }

            // Footer de la última página
            drawPageFooter();

            pdf.save('Informe_Analitico_' + now.getTime() + '.pdf');

            btn.html(originalContent).prop('disabled', false);
            window.showSnackbar('Reporte PDF generado exitosamente', 'success');

        } catch (error) {
            console.error('Error exportando PDF:', error);
            Swal.fire('Error', 'No se pudo generar el PDF: ' + error.message, 'error');
            btn.html(originalContent).prop('disabled', false);
        }
    });

    // ==============================
    // EXCEL EXPORT
    // ==============================
    $('#btnExportExcel').on('click', function () {
        const table = $('#alertTable').DataTable();
        table.button('.buttons-excel').trigger();
    });

    // ==============================
    // LÓGICA DE OCULTAR COLUMNAS
    // ==============================
    let minimizedColumns = new Set();

    window.resetColumns = function () {
        minimizedColumns.clear();
        applyMinimizedColumns();
    };

    function toggleColumn(index) {
        if (minimizedColumns.has(index)) minimizedColumns.delete(index);
        else minimizedColumns.add(index);
        applyMinimizedColumns();
    }

    function applyMinimizedColumns() {
        const tableEl  = document.getElementById('alertTable');
        const resetBtn = document.getElementById('btnResetColumns');
        if (!tableEl) return;

        tableEl.querySelectorAll('.column-hidden').forEach(el => el.classList.remove('column-hidden'));

        minimizedColumns.forEach(index => {
            tableEl.querySelectorAll(`tr > *:nth-child(${index + 1})`).forEach(cell => {
                cell.classList.add('column-hidden');
            });
        });

        if (resetBtn) resetBtn.style.display = minimizedColumns.size > 0 ? 'inline-flex' : 'none';
    }

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
    // ANALITICA ESTADO PICKER
    // ==============================
    const picker       = document.getElementById('estadoPicker');
    const pickerBtn    = document.getElementById('estadoPickerBtn');
    const pickerMenu   = document.getElementById('estadoPickerMenu');
    const hiddenEstado = document.getElementById('hiddenSearchEstado');
    const estadoLabel  = document.getElementById('selectedEstadoLabel');

    if (picker && pickerBtn) {
        pickerBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            picker.classList.toggle('is-open');
        });

        if (pickerMenu) {
            pickerMenu.addEventListener('click', function (e) {
                const item        = e.target.closest('.analitica-picker-item');
                const groupHeader = e.target.closest('.analitica-picker-group-header');

                if (groupHeader) {
                    e.stopPropagation();
                    const group = groupHeader.closest('.analitica-picker-group');
                    if (group) group.classList.toggle('is-expanded');
                    return;
                }

                if (item) {
                    e.stopPropagation();
                    const value = item.dataset.value;
                    const label = item.dataset.label || item.textContent.trim();

                    if (hiddenEstado) hiddenEstado.value = value;
                    if (estadoLabel)  estadoLabel.textContent = label;

                    pickerMenu.querySelectorAll('.analitica-picker-item').forEach(el => el.classList.remove('is-active'));
                    item.classList.add('is-active');
                    picker.classList.remove('is-open');

                    refreshDashboard();
                }
            });
        }

        document.addEventListener('click', function (e) {
            if (!picker.contains(e.target)) picker.classList.remove('is-open');
        });
    }
});