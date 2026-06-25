import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import html2canvas from 'html2canvas';
window.jspdf = { jsPDF };
document.addEventListener('DOMContentLoaded', function () {
    function formatMinutosLabel(val, full = false) {
        const h = Math.floor(val / 60);
        const m = Math.round(val % 60);
        if (full) return `${h}h ${m}m en total`;
        return `${h}h ${m}m`;
    }
    let chartData = window.chartData || {};
    let charts = {};
    const filterForm = document.getElementById('filterForm');
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
            delayLabelEl.textContent = mode === 'states' && blockData
                ? `Detalle del bloque: ${blockData.etapa}`
                : 'Vista general por bloques';
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
        if (delayBackBtn) delayBackBtn.classList.toggle('d-none', mode !== 'states');
    }

    function getSelectedEtapa() {
        return filterEtapaEl ? (filterEtapaEl.value || '') : '';
    }

    function scheduleRefresh(delay = 120) {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => refreshDashboard(), delay);
    }

    const P = {
        primary: '#0F172A', accent: '#6366f1', success: '#10b981',
        warning: '#f59e0b', danger: '#ef4444', info: '#3b82f6',
        slate: '#475569', slateLt: '#94A3B8', muted: '#f1f5f9',
        blue: '#0F172A', blueLt: '#3b82f6', indigo: '#6366f1',
        teal: '#14b8a6', green: '#10b981', greenLt: '#34d399',
        amber: '#f59e0b', orange: '#f97316', red: '#ef4444',
        ruby: '#BE123C', emerald: '#047857', sapphire: '#1D4ED8',
        gray: '#475569', grayLt: '#94A3B8'
    };

    const baseFont = { fontFamily: 'Inter, sans-serif' };
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
                mode: 'states', blocks, selectedBlock,
                categories: (selectedBlock.estados || []).map(item => item.nombre),
                series: [{ name: `Estados de ${selectedBlock.etapa}`, data: (selectedBlock.estados || []).map(item => item.minutos) }]
            };
        }
        return {
            mode: 'blocks', blocks, selectedBlock: null,
            categories: blocks.map(block => block.etapa),
            series: [{ name: 'Tiempo total por bloque', data: blocks.map(block => block.minutos_totales) }]
        };
    }

    function updateDelayDrilldownUI(mode, selectedBlock = null) {
        setDelayState(mode, selectedBlock);
    }

    function renderTimelineHtml(events) {
        const container = document.getElementById('timelineHtml');
        if (!container) return;
        if (!events || events.length === 0) {
            container.innerHTML = '<div class="tl-empty"><i class="bi bi-activity"></i>Sin actividad reciente</div>';
            return;
        }
        container.innerHTML = events.map(e => {
            let tlClass = 'tl-progress';
            if (e.type === 'approved' || e.type === 'finalized') tlClass = 'tl-approved';
            else if (e.type === 'returned' || e.type === 'rejected') tlClass = 'tl-returned';
            else if (e.type === 'critical') tlClass = 'tl-critical';
            return `<div class="tl-item ${tlClass}">
                <div class="tl-content">
                    <div class="tl-main">
                        <div class="tl-event">${e.evento}</div>
                        <div class="tl-meta">${e.usuario} &middot; ${e.cuenta} ${e.monto ? '&middot; $' + e.monto : ''}</div>
                    </div>
                    <span class="tl-time">${e.tiempo}</span>
                </div>
            </div>`;
        }).join('');
    }

    function formatTimeAgo(dateStr) {
        if (!dateStr) return '';
        const now = new Date();
        const d = new Date(dateStr);
        const diffMs = now - d;
        const diffMin = Math.floor(diffMs / 60000);
        if (diffMin < 1) return 'Ahora';
        if (diffMin < 60) return `Hace ${diffMin} min`;
        const diffH = Math.floor(diffMin / 60);
        if (diffH < 24) return `Hace ${diffH}h ${diffMin % 60}m`;
        const diffD = Math.floor(diffH / 24);
        return `Hace ${diffD}d`;
    }

    function buildTimelineFromData(data) {
        if (!data || !data.length) return [];
        return data.slice(0, 15).map(item => ({
            evento: item.evento || item.estado || 'Transición',
            usuario: item.usuario || item.usuario_nombre || 'Sistema',
            cuenta: item.cuenta || item.numero_cuenta || '',
            monto: item.monto ? new Intl.NumberFormat('es-CO').format(item.monto) : null,
            tiempo: formatTimeAgo(item.fecha || item.fecha_evento || item.created_at),
            type: item.type || item.tipo || 'progress'
        }));
    }

    function initCharts(data) {
        const isMobile = window.innerWidth <= 991;
        const chartTheme = isMobile ? 'dark' : 'light';
        const labelColor = isMobile ? '#94a3b8' : P.slateLt;
        const titleColor = isMobile ? '#f1f5f9' : P.primary;
        const gridColor = isMobile ? 'rgba(255,255,255,0.05)' : '#f8fafc';

        // 1. SEMI-DONUT
        if (data.estado_anillos) {
            if (charts.donut) charts.donut.destroy();
            const total = data.estado_anillos.series.reduce((a, b) => a + b, 0);
            charts.donut = new ApexCharts(document.querySelector('#donutChart'), {
                series: data.estado_anillos.series,
                chart: { type: 'donut', height: 260, ...baseFont, theme: { mode: chartTheme } },
                labels: data.estado_anillos.labels,
                colors: [P.sapphire, P.ruby],
                legend: { show: false },
                plotOptions: {
                    pie: {
                        startAngle: -90,
                        endAngle: 90,
                        offsetY: 0,
                        donut: {
                            size: '78%',
                            labels: {
                                show: true,
                                total: { show: true, label: 'Total', fontSize: '11px', fontWeight: 500, color: labelColor, formatter: () => total },
                                value: { fontSize: isMobile ? '1.4rem' : '1.8rem', fontWeight: 700, color: titleColor, offsetY: 0 }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false },
                stroke: { width: 0 }
            });
            charts.donut.render();

            const legendEl = document.getElementById('donutLegend');
            if (legendEl) {
                const pcts = data.estado_anillos.series.map(s => total > 0 ? ((s / total) * 100).toFixed(1) : '0');
                legendEl.innerHTML = data.estado_anillos.labels.map((label, i) => `
                    <div class="d-flex justify-content-between align-items-center py-1 px-1">
                        <div class="d-flex align-items-center gap-2">
                            <span style="width:8px;height:8px;border-radius:50%;background:${[P.sapphire, P.ruby][i]};display:inline-block;"></span>
                            <span style="font-size:0.8125rem;color:var(--text-secondary)">${label}</span>
                        </div>
                        <span style="font-size:0.8125rem;font-weight:700;color:var(--text-primary)">${data.estado_anillos.series[i]} (${pcts[i]}%)</span>
                    </div>
                `).join('');
            }
        }

        // 1.5 DELAY CHART
        const delayData = data.demora_usuario_etapa;
        const delayHierarchy = resolveDelayHierarchy(delayData);
        const hasDelayData = delayHierarchy.blocks.length > 0;

        if (hasDelayData) {
            updateDelayDrilldownUI(delayHierarchy.mode, delayHierarchy.selectedBlock);
            if (charts.delay) charts.delay.destroy();

            const isDistributed = delayHierarchy.mode === 'blocks';
            const barColors = isDistributed
                ? [P.sapphire, P.teal, P.indigo, P.amber, P.orange, P.ruby, P.emerald]
                : [P.sapphire];

            charts.delay = new ApexCharts(document.querySelector('#delayChart'), {
                series: delayHierarchy.series,
                chart: {
                    type: 'bar', height: isMobile ? 300 : 360, toolbar: noToolbar,
                    ...baseFont, animations: { enabled: true, easing: 'easeinout', speed: 800 },
                    theme: { mode: chartTheme },
                    events: {
                        dataPointSelection: function (_event, _chartContext, config) {
                            if (delayHierarchy.mode !== 'blocks') return;
                            const selected = delayHierarchy.blocks[config.dataPointIndex];
                            if (!selected) return;
                            if (filterEtapaEl) filterEtapaEl.value = selected.etapa;
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
                        distributed: isDistributed,
                        dataLabels: { position: 'top' }
                    }
                },

                colors: barColors,
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
                    theme: 'light', style: { fontSize: '12px', fontFamily: 'Inter, sans-serif' },
                    shared: false, intersect: true,
                    custom: function ({ seriesIndex, dataPointIndex }) {
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
                    y: { formatter: val => formatMinutosLabel(val, true) }
                },
                dataLabels: {
                    enabled: true,
                    formatter: val => formatMinutosLabel(val),
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
                    const els = {
                        total: document.getElementById('m-kpi-total'),
                        lenta: document.getElementById('m-kpi-lenta'),
                        lentaSub: document.getElementById('m-kpi-lenta-sub'),
                        rapida: document.getElementById('m-kpi-rapida'),
                        rapidaSub: document.getElementById('m-kpi-rapida-sub')
                    };
                    if (els.total) els.total.textContent = delayData.kpis.general;
                    if (els.lenta) {
                        const parts = delayData.kpis.lenta.split(' En ');
                        els.lenta.textContent = parts[0];
                        if (els.lentaSub && parts[1]) els.lentaSub.textContent = 'En ' + parts[1];
                    }
                    if (els.rapida) {
                        const parts = delayData.kpis.rapida.split(' ');
                        els.rapida.textContent = parts[0];
                        if (els.rapidaSub && parts[1]) els.rapidaSub.textContent = parts.slice(1).join(' ');
                    }
                }
            }
        } else {
            updateDelayDrilldownUI('blocks', null);
            const el = document.querySelector('#delayChart');
            if (el) el.innerHTML = '<div class="empty-state"><i class="bi bi-clock"></i>Sin datos de demora configurados</div>';
        }

        // 2. PIPELINE
        if (data.gap_chart && data.gap_chart.labels.length > 0) {
            if (charts.gap) charts.gap.destroy();
            charts.gap = new ApexCharts(document.querySelector('#gapChart'), {
                series: [{ name: 'Contratos', data: data.gap_chart.series }],
                chart: { type: 'bar', height: isMobile ? 260 : 300, toolbar: noToolbar, ...baseFont, animations: { enabled: true, speed: 800 }, theme: { mode: chartTheme } },
                plotOptions: { bar: { horizontal: true, borderRadius: 8, barHeight: '55%', distributed: true } },
                colors: [P.sapphire, P.indigo, P.teal, P.emerald, P.amber, P.ruby],

                xaxis: {
                    categories: data.gap_chart.labels,
                    labels: { style: { fontSize: '11px', colors: labelColor } },
                    axisBorder: { show: false }
                },
                yaxis: { labels: { style: { fontSize: '11px', fontWeight: 500, colors: labelColor } } },
                legend: { show: false },
                tooltip: { theme: 'light', style: { fontSize: '12px' }, y: { formatter: val => val + ' contratos' } },
                dataLabels: { enabled: true, textAnchor: 'start', style: { colors: ['#fff'], fontSize: '11px', fontWeight: 700 }, offsetX: 8 },
                grid: { borderColor: gridColor, strokeDashArray: 4 }
            });
            charts.gap.render();

            if (isMobile) {
                const pipeEl = document.getElementById('mobilePipelineBars');
                if (pipeEl) {
                    const maxVal = Math.max(...data.gap_chart.series, 1);
                    pipeEl.innerHTML = data.gap_chart.labels.map((label, i) => `
                        <div class="m-pipeline-item">
                            <div class="m-pipeline-header">
                                <span>${label}</span><span>${data.gap_chart.series[i]}</span>
                            </div>
                            <div class="m-pipeline-bar-bg">
                                <div class="m-pipeline-bar-fill" style="width:${(data.gap_chart.series[i] / maxVal) * 100}%"></div>
                            </div>
                        </div>
                    `).join('');
                }
            }
        } else {
            const el = document.querySelector('#gapChart');
            if (el) el.innerHTML = '<div class="empty-state"><i class="bi bi-bar-chart"></i>Sin datos en el pipeline</div>';
        }

        // 3. TIMELINE (HTML vertical, not ApexCharts area)
        if (data.timeline_events && data.timeline_events.length > 0) {
            renderTimelineHtml(buildTimelineFromData(data.timeline_events));
        } else if (data.timeline_raw && data.timeline_raw.length > 0) {
            renderTimelineHtml(buildTimelineFromData(data.timeline_raw));
        } else {
            const container = document.getElementById('timelineHtml');
            if (container) container.innerHTML = '<div class="tl-empty"><i class="bi bi-activity"></i>Sin actividad reciente (30 días)</div>';
        }

        // 4. SPARKLINES — usan la data de timeline para mostrar tendencia real filtrada
        const renderSparkline = (id, dataPoints, color) => {
            const el = document.querySelector(`#${id}`);
            if (!el) return;
            if (charts[id]) charts[id].destroy();
            if (!dataPoints || dataPoints.length === 0) {
                el.innerHTML = '';
                return;
            }
            charts[id] = new ApexCharts(el, {
                series: [{ data: dataPoints }],
                chart: { type: 'area', width: '100%', height: 36, sparkline: { enabled: true } },
                stroke: { curve: 'smooth', width: 1.5 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0, stops: [0, 100] } },
                colors: [color],
                tooltip: { enabled: false }
            });
            charts[id].render();
        };

        // Sacamos 6 muestras de la serie real de timeline para los sparklines
        const tlSeries = data.timeline?.series || [];
        const sparkSample = tlSeries.length > 6
            ? tlSeries.filter((_, i) => i % Math.ceil(tlSeries.length / 6) === 0).slice(-6)
            : tlSeries;

        renderSparkline('sparkline-1', sparkSample, P.sapphire);
        renderSparkline('sparkline-2', sparkSample, P.amber);
        renderSparkline('sparkline-3', sparkSample, P.emerald);
        renderSparkline('sparkline-4', sparkSample, P.ruby);
        renderSparkline('sparkline-5', sparkSample, P.indigo);
        renderSparkline('sparkline-6', sparkSample, P.teal);

        // 5. ACTIVITY TIMELINE (Area chart)
        const tlData = data.timeline;
        const tlContainer = document.querySelector('#timelineChart');
        if (tlContainer && tlData && tlData.labels && tlData.labels.length > 0) {
            if (charts.timeline) charts.timeline.destroy();
            charts.timeline = new ApexCharts(tlContainer, {
                series: [{
                    name: 'Transacciones',
                    data: tlData.series
                }],
                chart: {
                    type: 'area',
                    height: isMobile ? 140 : 160,
                    toolbar: { show: false },
                    sparkline: { enabled: false },
                    ...baseFont,
                    theme: { mode: chartTheme },
                    animations: { enabled: true, easing: 'easeinout', speed: 600 }
                },
                colors: [P.sapphire],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.05,
                        stops: [0, 95, 100]
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2,
                    colors: [P.sapphire]
                },
                markers: {
                    size: isMobile ? 2 : 3,
                    colors: [P.sapphire],
                    strokeColors: '#fff',
                    strokeWidth: 2,
                    hover: { size: 5 }
                },
                xaxis: {
                    type: 'datetime',
                    categories: tlData.labels,
                    labels: {
                        format: 'dd MMM',
                        style: { fontSize: '9px', colors: labelColor, fontWeight: 500 },
                        rotate: 0,
                        hideOverlappingLabels: true,
                        trim: true
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    show: false,
                    min: 0,
                    forceNiceScale: true
                },
                grid: { show: false },
                tooltip: {
                    theme: 'light',
                    style: { fontSize: '11px', fontFamily: 'Inter, sans-serif' },
                    x: {
                        format: 'dd MMM yyyy'
                    },
                    y: {
                        formatter: val => val + ' transacciones'
                    }
                },
                dataLabels: { enabled: false },
                legend: { show: false }
            });
            charts.timeline.render();
        } else {
            if (tlContainer) {
                tlContainer.innerHTML = '<div style="text-align:center;padding:20px 10px;color:var(--text-muted);font-size:0.75rem;"><i class="bi bi-bar-chart-line" style="font-size:1.5rem;opacity:0.3;display:block;margin-bottom:6px;"></i>Sin actividad en el período</div>';
            }
        }
    }

    function updateKPIs(data) {
        const fmt = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });
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
        document.querySelectorAll('.kpi-card').forEach(card => {
            const val = card.querySelector('.kpi-value');
            if (!val) return;
            val.style.opacity = '0';
            setTimeout(() => {
                if (card.classList.contains('kpi-sapphire')) val.textContent = fmt.format(data.contratistasUnicos);
                else if (card.classList.contains('kpi-amber')) val.textContent = fmt.format(data.cuentasTramite);
                else if (card.classList.contains('kpi-emerald')) val.textContent = fmt.format(data.cuentasRadicadas);
                else if (card.classList.contains('kpi-ruby')) val.textContent = fmt.format(Math.max(0, data.pagosTotales - data.cuentasRadicadas));
                else if (card.classList.contains('kpi-indigo')) val.textContent = fmt.format(data.pagosTotales);
                else if (card.classList.contains('kpi-teal')) val.textContent = fmtDec.format(data.avanceGlobal) + '%';
                val.style.transition = 'opacity 0.5s ease';
                val.style.opacity = '1';
            }, 300);
        });
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
        if (mobileHtml) {
            const mobileList = document.getElementById('mobileContractList');
            if (mobileList) mobileList.innerHTML = mobileHtml;
        }
    }

    function updateActiveFiltersChips() {
        const container = document.getElementById('activeFilters');
        if (!container) return;
        const chips = [];
        const fd = document.getElementById('fecha_desde')?.value;
        const fh = document.getElementById('fecha_hasta')?.value;
        if (fd && fh) {
            const d1 = new Date(fd + 'T00:00:00').toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
            const d2 = new Date(fh + 'T00:00:00').toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
            chips.push({ label: `${d1} - ${d2}`, field: 'fecha' });
        }
        const sup = document.querySelector('[name="supervisor"] option:checked');
        if (sup && sup.value) chips.push({ label: 'Sup: ' + sup.textContent.trim().substring(0, 20), field: 'supervisor' });
        const resp = document.querySelector('[name="responsable"] option:checked');
        if (resp && resp.value) chips.push({ label: 'Resp: ' + resp.textContent.trim().substring(0, 20), field: 'responsable' });
        const est = document.querySelector('[name="estado"] option:checked');
        if (est && est.value) chips.push({ label: 'Estado: ' + est.textContent.trim().substring(0, 20), field: 'estado' });
        container.innerHTML = chips.map(c => `
            <span class="active-filter-chip" data-field="${c.field}">
                ${c.label}
                <span class="chip-remove" data-field="${c.field}"><i class="bi bi-x"></i></span>
            </span>
        `).join('');
        container.querySelectorAll('.chip-remove').forEach(el => {
            el.addEventListener('click', function () {
                const field = this.dataset.field;
                if (field === 'fecha') {
                    document.getElementById('fecha_desde').value = '';
                    document.getElementById('fecha_hasta').value = '';
                    const fp = document.getElementById('dateRangePicker')?._flatpickr;
                    if (fp) fp.clear();
                    const dd = document.getElementById('dateDisplay');
                    if (dd) dd.textContent = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
                } else {
                    const sel = document.querySelector(`[name="${field}"]`);
                    if (sel) sel.value = '';
                }
                scheduleRefresh();
            });
        });
    }

    function setGlobalLoading(isLoading) {
        if (isLoading) {
            if (captureArea) captureArea.classList.add('is-updating');
            document.querySelectorAll('.card-premium .card-body [id$="Chart"]').forEach(ch => ch.style.opacity = '0.3');
            const tb = document.getElementById('tableBody');
            if (tb) tb.style.opacity = '0.5';
        } else {
            if (captureArea) captureArea.classList.remove('is-updating');
            document.querySelectorAll('.card-premium .card-body [id$="Chart"]').forEach(ch => ch.style.opacity = '1');
            const tb = document.getElementById('tableBody');
            if (tb) tb.style.opacity = '1';
        }
    }

    async function refreshDashboard() {
        if (activeRequest) activeRequest.abort();
        activeRequest = new AbortController();
        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);

        const filterParams = {
            filterEtapa: 'f_etapa',
            filterUsuario: 'f_usuario',
            filterResponsableEtapa: 'f_responsable_etapa',
            filterEstadoEtapa: 'f_estado_etapa',
            filterActividadResponsable: 'f_act_responsable',
            filterActividadEstado: 'f_act_estado'
        };
        Object.entries(filterParams).forEach(([id, param]) => {
            const el = document.getElementById(id);
            if (el && el.value) params.append(param, el.value);
        });

        setGlobalLoading(true);
        try {
            const response = await window.apiFetch(filterForm.action + '?' + params.toString(), { signal: activeRequest.signal });
            if (!response.ok) throw new Error('Error en la respuesta del servidor');
            const data = await response.json();
            updateKPIs(data);
            initCharts(data.chartData);
            updateTable(data.tableHtml, data.mobileTableHtml);
            const timeTable = document.getElementById('timeTableContainer');
            if (timeTable && data.timeTableHtml) timeTable.innerHTML = data.timeTableHtml;
            const bottleneck = document.getElementById('bottleneckContainer');
            if (bottleneck && data.bottleneckHtml) bottleneck.innerHTML = data.bottleneckHtml;
            if (data.chartData.demora_usuario_etapa) {
                const du = data.chartData.demora_usuario_etapa;
                ['kpi-general', 'kpi-lenta', 'kpi-rapida'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = du.kpis[id.replace('kpi-', '')];
                });
            }
            updateActiveFiltersChips();
            const newUrl = window.location.pathname + '?' + params.toString();
            window.history.pushState({ path: newUrl }, '', newUrl);
        } catch (error) {
            if (error?.name === 'AbortError') return;
            console.error('Dashboard Update Error:', error);
            window.showSnackbar('No se pudieron sincronizar los datos. Reintente.', 'error');
        } finally {
            activeRequest = null;
            setTimeout(() => setGlobalLoading(false), 300);
        }
    }

    function initDataTable() {
        return $('#alertTable').DataTable({
            pageLength: 10,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
            dom: 'Bfrtip',
            buttons: [{ extend: 'excelHtml5', text: 'Excel', className: 'd-none', filename: 'BI_Report_SGCC', exportOptions: { columns: ':visible' } }],
            order: [[6, 'desc']]
        });
    }

    // ===== SMART DROPDOWNS =====
    document.querySelectorAll('.smart-dropdown').forEach(dd => {
        const trigger = dd.querySelector('.smart-dropdown-trigger');
        const menu = dd.querySelector('.smart-dropdown-menu');
        const search = dd.querySelector('.smart-dropdown-search');
        const items = dd.querySelectorAll('.smart-dropdown-item');
        const label = dd.querySelector('.trigger-label');

        if (trigger && menu) {
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.smart-dropdown.is-open').forEach(d => {
                    if (d !== dd) d.classList.remove('is-open');
                });
                dd.classList.toggle('is-open');
                if (search) setTimeout(() => search.focus(), 50);
            });

            items.forEach(item => {
                item.addEventListener('click', function () {
                    const val = this.dataset.value;
                    const text = this.textContent.trim();
                    items.forEach(i => i.classList.remove('is-active'));
                    this.classList.add('is-active');
                    if (label) label.textContent = text || 'Seleccionar...';
                    dd.classList.remove('is-open');

                    const fieldName = menu.dataset.field;
                    if (fieldName) {
                        let hidden = dd.querySelector('input[type="hidden"]');
                        if (!hidden) {
                            hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = fieldName;
                            dd.appendChild(hidden);
                        }
                        hidden.value = val;
                    }
                    scheduleRefresh();
                });
            });

            if (search) {
                search.addEventListener('input', function () {
                    const q = this.value.toLowerCase().trim();
                    items.forEach(item => {
                        if (item.classList.contains('no-results')) return;
                        const txt = item.textContent.toLowerCase();
                        item.style.display = txt.includes(q) ? '' : 'none';
                    });
                });
                search.addEventListener('click', function (e) { e.stopPropagation(); });
            }
        }
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.smart-dropdown.is-open').forEach(d => d.classList.remove('is-open'));
    });

    // ===== INIT =====
    if (captureArea) captureArea.classList.remove('loading');
    setDelayState(getSelectedEtapa() ? 'states' : 'blocks', null);

    // Build timeline from initial chartData if available
    if (chartData.timeline_events) {
        renderTimelineHtml(buildTimelineFromData(chartData.timeline_events));
    } else if (chartData.timeline_raw) {
        renderTimelineHtml(buildTimelineFromData(chartData.timeline_raw));
    } else if (chartData.timeline && chartData.timeline.labels) {
        const container = document.getElementById('timelineHtml');
        if (container) container.innerHTML = '<div class="tl-empty"><i class="bi bi-activity"></i>Sin actividad reciente (30 días)</div>';
    }

    initCharts(chartData);
    initDataTable();
    updateActiveFiltersChips();

    // ===== EVENT LISTENERS =====
    ['filterEtapa', 'filterEstadoEtapa', 'filterResponsableEtapa', 'filterActividadResponsable', 'filterActividadEstado'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => scheduleRefresh());
    });

    document.getElementById('filterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        scheduleRefresh();
    });

    document.querySelectorAll('#filterForm select').forEach(sel => {
        sel.addEventListener('change', () => scheduleRefresh());
    });

    $('#btnReset').on('click', function () {
        if (filterForm) filterForm.reset();
        if (filterForm) $(filterForm).find('input[type="hidden"]').val('');
        const dateDisplay = document.getElementById('dateDisplay');
        if (dateDisplay) dateDisplay.textContent = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
        const fp = document.getElementById('dateRangePicker')?._flatpickr;
        if (fp) fp.clear();
        document.querySelectorAll('.smart-dropdown').forEach(dd => {
            const label = dd.querySelector('.trigger-label');
            const firstItem = dd.querySelector('.smart-dropdown-item');
            if (label && firstItem) {
                dd.querySelectorAll('.smart-dropdown-item').forEach(i => i.classList.remove('is-active'));
                firstItem.classList.add('is-active');
                label.textContent = firstItem.textContent.trim();
            }
            const hidden = dd.querySelector('input[type="hidden"]');
            if (hidden) hidden.value = '';
        });
        scheduleRefresh();
    });

    document.getElementById('btnToggleView')?.addEventListener('click', function () {
        if (filterEtapaEl) filterEtapaEl.value = '';
        setDelayState('blocks', null);
        scheduleRefresh();
    });

    // Flatpickr
    const dateRangePicker = document.getElementById('dateRangePicker');
    if (dateRangePicker) {
        flatpickr(dateRangePicker, {
            mode: 'range', dateFormat: 'Y-m-d', locale: 'es',
            defaultDate: [document.getElementById('fecha_desde').value, document.getElementById('fecha_hasta').value],
            onChange: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    document.getElementById('fecha_desde').value = instance.formatDate(selectedDates[0], 'Y-m-d');
                    document.getElementById('fecha_hasta').value = instance.formatDate(selectedDates[1], 'Y-m-d');
                    document.getElementById('dateDisplay').textContent =
                        instance.formatDate(selectedDates[0], 'd M, Y') + ' - ' + instance.formatDate(selectedDates[1], 'd M, Y');
                    scheduleRefresh();
                }
            }
        });
    }

    // ===== EXPORTS =====
    $(document).on('click', '#dropdownExportar', function () {
        console.log('===== CLIC EN TOGGLE PRINCIPAL EXPORTAR (#dropdownExportar) =====');
    });

    $(document).on('click', '#btnExportPDF', async function (e) {
        console.log('===== CLIC EN BOTÓN PDF (#btnExportPDF) =====');
        e.preventDefault();
        const btn = $(this);
        const originalContent = btn.html();
        let captureAreaRef = null;

        try {
            // Verificar que html2canvas existe
            if (typeof html2canvas === 'undefined') {
                throw new Error('Librería html2canvas no cargada. Verifica que esté importada en el HTML.');
            }

            // Obtener constructor de jsPDF de forma más robusta
            const jsPDFConstructor = window.jsPDF || (window.jspdf && window.jspdf.jsPDF);
            if (!jsPDFConstructor) {
                throw new Error('Librería jsPDF no cargada. Verifica que esté importada en el HTML.');
            }

            btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Generando PDF...');
            btn.prop('disabled', true);

            captureAreaRef = document.getElementById('captureArea');
            if (!captureAreaRef) throw new Error('No se encontró el área a capturar (captureArea).');

            console.log('1. Añadiendo clase is-exporting...');
            // Agregar clase para estilos de impresión
            captureAreaRef.classList.add('is-exporting');

            console.log('2. Esperando render del DOM...');
            // Permitir que el DOM se actualice
            await new Promise(resolve => setTimeout(resolve, 500));

            console.log('3. Llamando a html2canvas...');
            // Capturar el canvas con configuración optimizada
            const canvas = await html2canvas(captureAreaRef, {
                scale: 1.5, // Resolución moderada para no saturar memoria
                useCORS: true,
                logging: true, // HABILITADO LOGGING PARA DEBUG
                backgroundColor: '#F8FAFC',
                allowTaint: false, // NO USAR ALLOW TAINT PORQUE BLOQUEA toDataURL
                scrollY: -window.scrollY,
                scrollX: -window.scrollX,
                windowHeight: captureAreaRef.scrollHeight || captureAreaRef.offsetHeight
            });

            console.log('4. html2canvas terminó. Extrayendo imagen...');
            captureAreaRef.classList.remove('is-exporting');

            const imgData = canvas.toDataURL('image/png', 0.95);
            console.log('5. Imagen extraída. Inicializando jsPDF...');

            // Crear PDF con tamaño A4
            const pdf = new jsPDFConstructor({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });

            const pdfWidth = pdf.internal.pageSize.getWidth(); // 210 mm
            const pdfHeight = pdf.internal.pageSize.getHeight(); // 297 mm
            const margin = 10; // Margen de 10mm
            const contentWidth = pdfWidth - (margin * 2);

            // Calcular altura de la imagen basada en el ancho disponible
            const imgWidth = contentWidth;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            let currentPosition = margin;
            let remainingHeight = imgHeight;

            console.log('6. Añadiendo imagen a las páginas...');
            // Agregar imagen a la primera página
            pdf.addImage(imgData, 'PNG', margin, currentPosition, imgWidth, imgHeight);
            remainingHeight -= (pdfHeight - margin * 2);

            // Agregar páginas adicionales si es necesario
            while (remainingHeight > 0) {
                pdf.addPage();
                currentPosition = -remainingHeight + margin;
                pdf.addImage(imgData, 'PNG', margin, currentPosition, imgWidth, imgHeight);
                remainingHeight -= (pdfHeight - margin * 2);
            }

            // Generar nombre del archivo con timestamp
            const now = new Date();
            const dateStr = now.toISOString().split('T')[0]; // YYYY-MM-DD
            const timeStr = now.getHours().toString().padStart(2, '0') +
                now.getMinutes().toString().padStart(2, '0');
            const filename = `Tablero_Analitico_${dateStr}_${timeStr}.pdf`;

            // Descargar el PDF
            pdf.save(filename);

            btn.html(originalContent).prop('disabled', false);

            // Mostrar mensaje de éxito
            if (typeof window.showSnackbar === 'function') {
                window.showSnackbar('Reporte PDF generado exitosamente: ' + filename, 'success');
            } else if (typeof Swal !== 'undefined') {
                Swal.fire('Éxito', 'Reporte PDF generado exitosamente', 'success');
            } else {
                alert('Reporte PDF generado exitosamente');
            }

        } catch (error) {
            console.error('Error exportando PDF:', error);
            if (captureAreaRef) captureAreaRef.classList.remove('is-exporting');

            btn.html(originalContent).prop('disabled', false);

            const errorMsg = error.message || 'Error desconocido al generar PDF';

            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se pudo generar el PDF: ' + errorMsg, 'error');
            } else {
                alert('No se pudo generar el PDF: ' + errorMsg);
            }
        }
    });

    $('#btnExportExcel').on('click', function (e) {
        e.preventDefault();
        try {
            $('#alertTable').DataTable().button('.buttons-excel').trigger();
        } catch (error) {
            console.error('Error exportando Excel:', error);
            alert('Para exportar a Excel, asegúrate de que la extensión de botones de DataTables esté cargada.');
        }
    });
});