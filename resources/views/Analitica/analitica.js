/**
 * MOTOR DE VISUALIZACIÓN BI (ApexCharts)
 *
 * Este script gestiona la interactividad del tablero analítico.
 * Sigue un patrón de "Data-Driven UI", donde los gráficos se destruyen y
 * recrean dinámicamente según la respuesta del servidor (AJAX).
 */
document.addEventListener('DOMContentLoaded', function () {
    function formatMinutosLabel(val, full = false) {
        const h = Math.floor(val / 60);
        const m = Math.round(val % 60);
        if (full) return `${h}h ${m}m en total`;
        return `${h}h ${m}m`;
    }
    let chartData = window.chartData || {};
    let charts = {};
    const slider      = document.getElementById('rangeSlider');
    const filterForm  = document.getElementById('filterForm');
    const captureArea = document.getElementById('captureArea');
    const filterEtapaEl = document.getElementById('filterEtapa');
    const delayLabelEl = document.getElementById('delayDrilldownLabel');
    const delayBadgeEl = document.getElementById('delayDrilldownBadge');
    const delayBackBtn = document.getElementById('btnToggleView');
    let refreshTimer = null;
    let activeRequest = null;

    const dashboardState = {
        selectedBlock: '',
        selectedBlockData: null,
        delayMode: 'blocks'
    };

    function setDelayState(mode, blockData = null) {
        dashboardState.delayMode = mode;
        dashboardState.selectedBlock = blockData?.etapa || '';
        dashboardState.selectedBlockData = blockData;
        window.currentDelayView = mode;

        if (delayLabelEl) {
            if (mode === 'states' && blockData) {
                delayLabelEl.textContent = `Detalle del bloque: ${blockData.etapa}`;
            } else {
                delayLabelEl.textContent = 'Vista general por bloques';
            }
        }

        if (delayBadgeEl) {
            if (mode === 'states' && blockData) {
                delayBadgeEl.textContent = 'Drill-down activo';
                delayBadgeEl.classList.remove('d-none');
            } else {
                delayBadgeEl.classList.add('d-none');
                delayBadgeEl.textContent = '';
            }
        }

        if (delayBackBtn) {
            delayBackBtn.classList.toggle('d-none', mode !== 'states');
        }
    }

    function getSelectedEtapa() {
        return filterEtapaEl ? (filterEtapaEl.value || '') : '';
    }

    function scheduleRefresh(delay = 120) {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => refreshDashboard(), delay);
    }

    // PALETA DE COLORES INSTITUCIONAL
    const P = {
        primary:  '#0f172a',
        accent:   '#6366f1',
        success:  '#10b981',
        warning:  '#f59e0b',
        danger:   '#ef4444',
        info:     '#3b82f6',
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

    function resolveDelayHierarchy(delayData) {
        const blocks = Array.isArray(delayData?.bloques) && delayData.bloques.length
            ? delayData.bloques
            : (Array.isArray(delayData?.tiempoEquipo) ? delayData.tiempoEquipo : []);

        const selectedBlockName = getSelectedEtapa();
        const selectedBlock = selectedBlockName
            ? blocks.find(block => String(block.etapa) === String(selectedBlockName))
            : null;

        if (selectedBlock) {
            return {
                mode: 'states',
                blocks,
                selectedBlock,
                categories: (selectedBlock.estados || []).map(item => item.nombre),
                series: [{
                    name: `Estados de ${selectedBlock.etapa}`,
                    data: (selectedBlock.estados || []).map(item => item.minutos)
                }]
            };
        }

        return {
            mode: 'blocks',
            blocks,
            selectedBlock: null,
            categories: blocks.map(block => block.etapa),
            series: [{
                name: 'Tiempo total por bloque',
                data: blocks.map(block => block.minutos_totales)
            }]
        };
    }

    function updateDelayDrilldownUI(mode, selectedBlock = null) {
        setDelayState(mode, selectedBlock);
    }

    // ==============================
    // CHARTS
    // ==============================
    function initCharts(data) {
        const isMobile = window.innerWidth <= 991;
        const chartTheme = isMobile ? 'dark' : 'light';
        const labelColor = isMobile ? '#94a3b8' : P.slateLt;
        const titleColor = isMobile ? '#f1f5f9' : P.primary;
        const gridColor  = isMobile ? 'rgba(255,255,255,0.05)' : '#f8fafc';

        // 1. DISTRIBUCIÓN POR ESTADO (Donut)
        if (data.estado_anillos) {
            if (charts.donut) charts.donut.destroy();
            charts.donut = new ApexCharts(document.querySelector('#donutChart'), {
                series: data.estado_anillos.series,
                chart: { 
                    type: 'donut', 
                    height: isMobile ? 260 : 320, 
                    ...baseFont,
                    theme: { mode: chartTheme }
                },
                labels: data.estado_anillos.labels,
                colors: [P.accent, '#ef4444'], // Blue for Process, Red for Return/Gap
                legend: {
                    show: !isMobile,
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
                                    label: 'total',
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    color: labelColor,
                                    formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                },
                                value: {
                                    fontSize: isMobile ? '1.6rem' : '2.2rem',
                                    fontWeight: 800,
                                    color: titleColor,
                                    offsetY: 5
                                }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false },
                stroke: { width: isMobile ? 2 : 5, colors: [isMobile ? 'transparent' : '#fff'] }
            });
            charts.donut.render();

            // Populate Mobile Legend
            if (isMobile) {
                const legendEl = document.getElementById('mobileDonutLegend');
                if (legendEl) {
                    legendEl.innerHTML = data.estado_anillos.labels.map((label, i) => `
                        <div class="m-legend-item">
                            <span class="m-legend-dot" style="background: ${[P.accent, P.danger][i]}"></span>
                            <span class="m-legend-label">${label}</span>
                            <span class="m-legend-value">${data.estado_anillos.series[i]}</span>
                        </div>
                    `).join('');
                }
            }
            // 1.5 DELAY – Jerarquía Bloque -> Estados
            const delayData = data.demora_usuario_etapa;
            const delayHierarchy = resolveDelayHierarchy(delayData);
            const hasDelayData = delayHierarchy.blocks.length > 0;

            if (hasDelayData) {
                updateDelayDrilldownUI(delayHierarchy.mode, delayHierarchy.selectedBlock);

                if (charts.delay) charts.delay.destroy();
                charts.delay = new ApexCharts(document.querySelector('#delayChart'), {
                    series: delayHierarchy.series,
                    chart: {
                        type: 'bar',
                        height: isMobile ? 300 : 380,
                        toolbar: noToolbar,
                        ...baseFont,
                        animations: { enabled: true, easing: 'easeinout', speed: 800 },
                        theme: { mode: chartTheme },
                        events: {
                            dataPointSelection: function (_event, _chartContext, config) {
                                if (delayHierarchy.mode !== 'blocks') return;
                                const selected = delayHierarchy.blocks[config.dataPointIndex];
                                if (!selected) return;

                                if (filterEtapaEl) {
                                    filterEtapaEl.value = selected.etapa;
                                }

                                updateDelayDrilldownUI('states', selected);
                                scheduleRefresh();
                            }
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 6,
                            barHeight: delayHierarchy.mode === 'states' ? '70%' : '55%',
                            distributed: delayHierarchy.mode === 'blocks',
                            dataLabels: { position: 'top' }
                        }
                    },
                    colors: [P.accent, P.teal, P.indigo, P.amber, P.orange, P.red, P.green],
                    xaxis: {
                        categories: delayHierarchy.categories,
                        labels: {
                            style: { fontSize: '11px', colors: labelColor, fontWeight: 500 },
                            formatter: val => formatMinutosLabel(val)
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: { labels: { style: { fontSize: '11px', fontWeight: 600, colors: labelColor } } },
                    tooltip: {
                        theme: 'dark',
                        shared: false,
                        intersect: true,
                        custom: function({ seriesIndex, dataPointIndex }) {
                            const minutes = delayHierarchy.series[seriesIndex]?.data?.[dataPointIndex] ?? 0;
                            if (delayHierarchy.mode === 'states' && delayHierarchy.selectedBlock) {
                                const state = delayHierarchy.selectedBlock.estados?.[dataPointIndex];
                                const title = state ? `${delayHierarchy.selectedBlock.etapa} > ${state.nombre}` : delayHierarchy.selectedBlock.etapa;
                                return `<div class="apexcharts-tooltip-title" style="padding:8px 10px;font-weight:700">${title}</div><div class="apexcharts-tooltip-series-group" style="padding:0 10px 8px"><span class="apexcharts-tooltip-text">${formatMinutosLabel(minutes, true)}</span></div>`;
                            }

                            const block = delayHierarchy.blocks[dataPointIndex];
                            const title = block ? block.etapa : 'Bloque';
                            return `<div class="apexcharts-tooltip-title" style="padding:8px 10px;font-weight:700">${title}</div><div class="apexcharts-tooltip-series-group" style="padding:0 10px 8px"><span class="apexcharts-tooltip-text">${formatMinutosLabel(minutes, true)}</span></div>`;
                        },
                        y: {
                            formatter: val => formatMinutosLabel(val, true)
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val) {
                            return formatMinutosLabel(val);
                        },
                        style: { fontSize: '10px', fontWeight: 700, colors: [titleColor] },
                        offsetX: 45
                    },
                    grid: { borderColor: gridColor, strokeDashArray: 4 },
                    legend: { show: false }
                });
                charts.delay.render();
                
                if (delayData.kpis) {
                    document.getElementById('kpi-general').textContent = delayData.kpis.general;
                    document.getElementById('kpi-lenta').textContent = delayData.kpis.lenta;
                    document.getElementById('kpi-rapida').textContent = delayData.kpis.rapida;

                    if (isMobile) {
                        const mKpiTotal = document.getElementById('m-kpi-total');
                        const mKpiLenta = document.getElementById('m-kpi-lenta');
                        const mKpiLentaSub = document.getElementById('m-kpi-lenta-sub');
                        const mKpiRapida = document.getElementById('m-kpi-rapida');
                        const mKpiRapidaSub = document.getElementById('m-kpi-rapida-sub');

                        if (mKpiTotal) mKpiTotal.textContent = delayData.kpis.general;
                        
                        if (mKpiLenta) {
                            const lentaParts = delayData.kpis.lenta.split(' En ');
                            mKpiLenta.textContent = lentaParts[0];
                            if (mKpiLentaSub && lentaParts[1]) mKpiLentaSub.textContent = 'En ' + lentaParts[1];
                        }

                        if (mKpiRapida) {
                            const rapidaParts = delayData.kpis.rapida.split(' ');
                            mKpiRapida.textContent = rapidaParts[0];
                            if (mKpiRapidaSub && rapidaParts[1]) mKpiRapidaSub.textContent = rapidaParts.slice(1).join(' ');
                        }
                    }
                }
            } else {
                updateDelayDrilldownUI('blocks', null);
                const el = document.querySelector('#delayChart');
                if (el) el.innerHTML = '<div class="empty-chart-state"><i class="bi bi-clock"></i>Sin datos de demora configurados</div>';
            }
        }

        // 2. PIPELINE – Carga por Etapa
        if (data.gap_chart && data.gap_chart.labels.length > 0) {
            if (charts.gap) charts.gap.destroy();
            charts.gap = new ApexCharts(document.querySelector('#gapChart'), {
                series: [{ name: 'Contratos', data: data.gap_chart.series }],
                chart: { 
                    type: 'bar', 
                    height: isMobile ? 260 : 320, 
                    toolbar: noToolbar, 
                    ...baseFont, 
                    animations: { enabled: true, speed: 800 },
                    theme: { mode: chartTheme }
                },
                plotOptions: { bar: { horizontal: true, borderRadius: 10, barHeight: '55%', distributed: true } },
                colors: [P.blue, P.indigo, P.teal, P.green, P.amber, P.red],
                xaxis: {
                    categories: data.gap_chart.labels,
                    labels: { style: { fontSize: '11px', colors: labelColor } },
                    axisBorder: { show: false }
                },
                yaxis: { labels: { style: { fontSize: '11px', fontWeight: 600, colors: labelColor } } },
                legend: { show: false },
                tooltip: { theme: 'dark', y: { formatter: val => val + ' contratos' } },
                dataLabels: {
                    enabled: true,
                    textAnchor: 'start',
                    style: { colors: ['#fff'], fontSize: '12px', fontWeight: 700 },
                    offsetX: 10
                },
                grid: { borderColor: gridColor, strokeDashArray: 4 }
            });
            charts.gap.render();

            // Populate Mobile Pipeline Bars
            if (isMobile) {
                const pipeEl = document.getElementById('mobilePipelineBars');
                if (pipeEl) {
                    const maxVal = Math.max(...data.gap_chart.series, 1);
                    pipeEl.innerHTML = data.gap_chart.labels.map((label, i) => `
                        <div class="m-pipeline-item">
                            <div class="m-pipeline-header">
                                <span>${label}</span>
                                <span>${data.gap_chart.series[i]}</span>
                            </div>
                            <div class="m-pipeline-bar-bg">
                                <div class="m-pipeline-bar-fill" style="width: ${(data.gap_chart.series[i] / maxVal) * 100}%"></div>
                            </div>
                        </div>
                    `).join('');
                }
            }
        } else {
            const el = document.querySelector('#gapChart');
            if (el) el.innerHTML = '<div class="empty-chart-state"><i class="bi bi-bar-chart"></i>Sin datos en el pipeline</div>';
        }

        // 3. TIMELINE – Actividad últimos 30 días
        if (data.timeline && data.timeline.labels.length > 0) {
            if (charts.timeline) charts.timeline.destroy();
            charts.timeline = new ApexCharts(document.querySelector('#timelineChart'), {
                series: [{ name: 'Transiciones', data: data.timeline.series }],
                chart: { 
                    type: 'area', 
                    height: isMobile ? 220 : 280, 
                    toolbar: noToolbar, 
                    ...baseFont,
                    theme: { mode: chartTheme }
                },
                colors: [P.accent],
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.0, stops: [0, 100] }
                },
                stroke: { curve: 'smooth', width: 3 },
                xaxis: {
                    categories: data.timeline.labels,
                    labels: { rotate: -45, style: { fontSize: '10px', colors: labelColor } },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: { labels: { formatter: val => Math.round(val), style: { colors: labelColor } } },
                dataLabels: { enabled: false },
                grid: { borderColor: gridColor, strokeDashArray: 4 },
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

        // Update Mobile Global Progress
        const mGlobalProgressVal = document.getElementById('mobileGlobalProgressVal');
        const mGlobalProgressBar = document.getElementById('mobileGlobalProgressBar');
        if (mGlobalProgressVal) mGlobalProgressVal.textContent = fmtDec.format(data.avanceGlobal) + '%';
        if (mGlobalProgressBar) mGlobalProgressBar.style.width = Math.min(data.avanceGlobal, 100) + '%';
    }

    function updateTable(html, mobileHtml = null) {
        const table = $('#alertTable').DataTable();
        table.destroy();
        document.getElementById('tableBody').innerHTML = html;
        initDataTable();
        applyMinimizedColumns();

        // Update Mobile List if provided
        if (mobileHtml) {
            const mobileList = document.getElementById('mobileContractList');
            if (mobileList) mobileList.innerHTML = mobileHtml;
        }
    }

    // ==============================
    // REACTIVITY & LOADING ENGINE
    // ==============================
    function getChartSkeleton() {
        return `<div class="skeleton-chart animate-pulse"></div>`;
    }

    function setGlobalLoading(isLoading) {
        const containers = [
            '#donutChart', '#gapChart', '#delayChart', '#timelineChart', 
            '#timeTableContainer', '#tableBody', '#bottleneckContainer'
        ];

        if (isLoading) {
            if (captureArea) captureArea.classList.add('is-updating');
            
            // Inyectar skeletons en contenedores críticos para evitar colapso de altura
            document.querySelectorAll('.card-premium .card-body').forEach(body => {
                const chart = body.querySelector('[id$="Chart"]');
                if (chart) {
                    chart.style.opacity = '0.3';
                    // No vaciamos para mantener la altura, solo bajamos opacidad
                }
            });

            // Skeletons específicos para tablas y listas
            const tableBody = document.getElementById('tableBody');
            if (tableBody) tableBody.style.opacity = '0.5';
            
        } else {
            if (captureArea) captureArea.classList.remove('is-updating');
            document.querySelectorAll('.card-premium .card-body [id$="Chart"]').forEach(chart => {
                chart.style.opacity = '1';
            });
            const tableBody = document.getElementById('tableBody');
            if (tableBody) tableBody.style.opacity = '1';
        }
    }

    async function refreshDashboard() {
        if (activeRequest) {
            activeRequest.abort();
        }
        activeRequest = new AbortController();

        const formData = new FormData(filterForm);
        const params   = new URLSearchParams(formData);

        const filterEtapa = document.getElementById('filterEtapa');
        const filterUsuario = document.getElementById('filterUsuario');
        const filterResponsableEtapa = document.getElementById('filterResponsableEtapa');
        const filterEstadoEtapa = document.getElementById('filterEstadoEtapa');
        const filterActividadResponsable = document.getElementById('filterActividadResponsable');
        const filterActividadEstado = document.getElementById('filterActividadEstado');
        
        if (filterEtapa && filterEtapa.value) params.append('f_etapa', filterEtapa.value);
        if (filterUsuario && filterUsuario.value) params.append('f_usuario', filterUsuario.value);
        if (filterResponsableEtapa && filterResponsableEtapa.value) params.append('f_responsable_etapa', filterResponsableEtapa.value);
        if (filterEstadoEtapa && filterEstadoEtapa.value) params.append('f_estado_etapa', filterEstadoEtapa.value);
        if (filterActividadResponsable && filterActividadResponsable.value) params.append('f_act_responsable', filterActividadResponsable.value);
        if (filterActividadEstado && filterActividadEstado.value) params.append('f_act_estado', filterActividadEstado.value);

        // Estado inicial de carga
        setGlobalLoading(true);

        try {
            const response = await window.apiFetch(filterForm.action + '?' + params.toString(), {
                signal: activeRequest.signal
            });
            if (!response.ok) throw new Error('Error en la respuesta del servidor');
            
            const data = await response.json();

            // 1. Actualizar KPIs Numéricos (Global)
            updateKPIs(data);

            // 2. Re-renderizar Gráficos con nuevas transiciones
            initCharts(data.chartData);

            // 3. Actualizar Listados y Tablas
            updateTable(data.tableHtml, data.mobileTableHtml);

            // 4. Actualizar Componentes Premium (Tiempos desglosados)
            const timeTable = document.getElementById('timeTableContainer');
            if (timeTable && data.timeTableHtml) {
                timeTable.innerHTML = data.timeTableHtml;
            }

            const bottleneck = document.getElementById('bottleneckContainer');
            if (bottleneck && data.bottleneckHtml) {
                bottleneck.innerHTML = data.bottleneckHtml;
            }

            // 5. KPIs de Demora
            if (data.chartData.demora_usuario_etapa) {
                const du = data.chartData.demora_usuario_etapa;
                const kpiGen = document.getElementById('kpi-general');
                const kpiLen = document.getElementById('kpi-lenta');
                const kpiRap = document.getElementById('kpi-rapida');
                
                if (kpiGen) kpiGen.textContent = du.kpis.general;
                if (kpiLen) kpiLen.textContent = du.kpis.lenta;
                if (kpiRap) kpiRap.textContent = du.kpis.rapida;
            }

            // Actualizar URL sin recargar para mantener historial
            const newUrl = window.location.pathname + '?' + params.toString();
            window.history.pushState({ path: newUrl }, '', newUrl);

        } catch (error) {
            if (error && error.name === 'AbortError') {
                return;
            }
            console.error('Dashboard Update Error:', error);
            window.showSnackbar('No se pudieron sincronizar los datos. Reintente.', 'error');
        } finally {
            activeRequest = null;
            // Finalizar carga con un pequeño delay para suavizar la transición
            setTimeout(() => setGlobalLoading(false), 300);
        }
    }

    // ==============================
    // INITIALIZATION
    // ==============================
    if (captureArea) captureArea.classList.remove('loading');
    setDelayState(getSelectedEtapa() ? 'states' : 'blocks', null);
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
    const filterEstadoEtapaEl = document.getElementById('filterEstadoEtapa');

    if (filterEtapaEl && filterEstadoEtapaEl) {
        filterEtapaEl.addEventListener('change', function() {
            const selectedEtapa = this.value;
            const currentEstado = filterEstadoEtapaEl.value;
            let foundCurrent = false;

            Array.from(filterEstadoEtapaEl.options).forEach(opt => {
                if (!opt.value) {
                    opt.style.display = ''; 
                    return;
                }
                const optBloque = opt.getAttribute('data-bloque');
                if (!selectedEtapa || optBloque === selectedEtapa) {
                    opt.style.display = '';
                    if (opt.value === currentEstado) foundCurrent = true;
                } else {
                    opt.style.display = 'none';
                }
            });

            if (!foundCurrent && currentEstado !== "") {
                filterEstadoEtapaEl.value = "";
            }
        });
    }

    ['filterEtapa', 'filterEstadoEtapa', 'filterResponsableEtapa', 'filterActividadResponsable', 'filterActividadEstado'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => scheduleRefresh());
    });

    const globalQuickTimeFilter = document.getElementById('globalQuickTimeFilter');
    if (globalQuickTimeFilter) {
        globalQuickTimeFilter.addEventListener('change', function() {
            const days = this.value;
            const fechaDesdeEl = document.getElementById('fecha_desde');
            const fechaHastaEl = document.getElementById('fecha_hasta');
            const dateDisplay = document.getElementById('dateDisplay');
            const fp = document.getElementById('dateRangePicker')?._flatpickr;

            if (days) {
                const end = new Date();
                const start = new Date();
                start.setDate(end.getDate() - parseInt(days));

                const format = (d) => {
                    const y = d.getFullYear();
                    const m = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${y}-${m}-${day}`;
                };
                
                fechaDesdeEl.value = format(start);
                fechaHastaEl.value = format(end);

                if (dateDisplay) {
                    const options = { day: 'numeric', month: 'long', year: 'numeric' };
                    dateDisplay.textContent = `${start.toLocaleDateString('es-ES', options)} - ${end.toLocaleDateString('es-ES', options)}`;
                }

                if (fp) {
                    fp.setDate([start, end], false);
                }
            } else {
                fechaDesdeEl.value = '';
                fechaHastaEl.value = '';
                if (dateDisplay) {
                    dateDisplay.textContent = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
                }
                if (fp) fp.clear();
            }

            scheduleRefresh();
        });
    }
    document.getElementById('filterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        scheduleRefresh();
    });

    // Auto-refresh on select change
    document.querySelectorAll('#filterForm select').forEach(sel => {
        sel.addEventListener('change', () => scheduleRefresh());
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

        const qf = document.getElementById('globalQuickTimeFilter');
        if (qf) qf.value = "";

        if (slider && slider.noUiSlider) slider.noUiSlider.set([0, 100]);

        scheduleRefresh();
    });

    $('#filterForm select').on('change', function () {
        scheduleRefresh();
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

            slider.noUiSlider.on('change', function () { scheduleRefresh(); });
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

                    const qf = document.getElementById('globalQuickTimeFilter');
                    if (qf) qf.value = "";

                    scheduleRefresh();
                }
            }
        });
    }

    // ==============================
    // EVENT LISTENERS PARA MÉTRICAS
    // ==============================
    const btnToggleView = document.getElementById('btnToggleView');
    if (btnToggleView) {
        btnToggleView.addEventListener('click', function() {
            if (filterEtapaEl) {
                filterEtapaEl.value = '';
            }
            setDelayState('blocks', null);
            scheduleRefresh();
        });
    }

    // Se eliminó la lógica de administración manual por solicitud del usuario

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

                    scheduleRefresh();
                }
            });
        }

        document.addEventListener('click', function (e) {
            if (!picker.contains(e.target)) picker.classList.remove('is-open');
        });
    }
});
