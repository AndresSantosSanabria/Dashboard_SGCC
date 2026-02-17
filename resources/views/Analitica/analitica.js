document.addEventListener('DOMContentLoaded', function () {
    const chartData = window.chartData || {};
    const avanceGlobal = window.avanceGlobal || 0;

    // Paleta de colores premium
    const P = {
        blue: '#004884',
        blueLt: '#0070cc',
        blueUltra: '#3b82f6',
        indigo: '#4f46e5',
        teal: '#0d9488',
        green: '#0a8754',
        greenLt: '#10b981',
        amber: '#d97706',
        yellow: '#F6CA1F',
        red: '#D60D0D',
        redLt: '#ef4444',
        gray: '#64748b',
        grayLt: '#94a3b8',
        surface: '#f1f5f9',
        muted: '#e2e8f0',
    };

    const baseFont = { fontFamily: 'Inter, sans-serif' };
    const noToolbar = { show: false };

    // ==============================
    // 1. GAUGE – Eficiencia Global
    // ==============================
    new ApexCharts(document.querySelector("#gaugeChart"), {
        series: [Math.min(avanceGlobal, 100)],
        chart: { height: 280, type: 'radialBar' },
        plotOptions: {
            radialBar: {
                startAngle: -135,
                endAngle: 135,
                hollow: { size: '68%', background: 'transparent' },
                track: { background: P.muted, strokeWidth: '100%' },
                dataLabels: {
                    name: { fontSize: '12px', color: P.gray, offsetY: 85, fontWeight: 600 },
                    value: {
                        offsetY: 45,
                        fontSize: '2.2rem',
                        fontWeight: '800',
                        color: P.blue,
                        ...baseFont,
                        formatter: val => parseFloat(val).toFixed(1) + "%"
                    }
                }
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                type: 'horizontal',
                shadeIntensity: 0.5,
                gradientToColors: [P.teal],
                stops: [0, 100]
            }
        },
        stroke: { lineCap: 'round' },
        labels: ['EFICIENCIA GLOBAL'],
    }).render();

    // ==============================
    // 2. GAP – Cumplimiento de Metas
    // ==============================
    if (chartData.gap_chart && chartData.gap_chart.labels.length > 0) {
        new ApexCharts(document.querySelector("#gapChart"), {
            series: [{
                name: 'Radicadas',
                data: chartData.gap_chart.reales
            }, {
                name: 'Faltante',
                data: chartData.gap_chart.metas.map((m, i) => Math.max(0, m - chartData.gap_chart.reales[i]))
            }],
            chart: { type: 'bar', height: 320, stacked: true, toolbar: noToolbar, ...baseFont },
            plotOptions: {
                bar: { horizontal: false, columnWidth: '55%', borderRadius: 6, borderRadiusApplication: 'end' },
            },
            colors: [P.blue, P.muted],
            xaxis: {
                categories: chartData.gap_chart.labels,
                labels: { rotate: -45, style: { fontSize: '10px', colors: P.grayLt, ...baseFont } }
            },
            yaxis: {
                title: { text: 'N° de Cuentas', style: { fontSize: '11px', color: P.gray, ...baseFont } },
                labels: { formatter: val => Math.round(val), style: { colors: P.grayLt } }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '11px',
                markers: { radius: 3 },
                itemMargin: { horizontal: 10 }
            },
            tooltip: {
                y: {
                    formatter: function (val, { seriesIndex, dataPointIndex }) {
                        if (seriesIndex === 0) return val + " radicadas";
                        const meta = chartData.gap_chart.metas[dataPointIndex];
                        return "Faltan " + val + " para llegar a " + meta;
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val, { seriesIndex, dataPointIndex }) {
                    if (seriesIndex === 1) return chartData.gap_chart.metas[dataPointIndex];
                    return val > 0 ? val : '';
                },
                style: { colors: ['#fff', P.gray], fontSize: '10px', fontWeight: 700 }
            },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
        }).render();
    }

    // ==============================
    // 3. DONUT – Distribución
    // ==============================
    new ApexCharts(document.querySelector("#donutChart"), {
        series: chartData.estado_anillos.series,
        chart: { type: 'donut', height: 300, ...baseFont },
        labels: chartData.estado_anillos.labels,
        colors: [P.amber, P.blue, P.redLt],
        legend: { position: 'bottom', fontSize: '11px', markers: { radius: 3 } },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: '13px',
                            fontWeight: 700,
                            color: P.gray,
                            formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                        }
                    }
                }
            }
        },
        dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700 } },
        stroke: { width: 3, colors: ['#fff'] },
    }).render();

    // ==============================
    // 4. DELAY – Demora por Etapa
    // ==============================
    if (chartData.demora_bloques && chartData.demora_bloques.length > 0) {
        new ApexCharts(document.querySelector("#delayChart"), {
            series: [{
                name: 'Promedio (h)',
                data: chartData.demora_bloques.map(d => d.promedio_horas)
            }],
            chart: { type: 'bar', height: 350, toolbar: noToolbar, ...baseFont },
            plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '55%' } },
            colors: [P.amber],
            fill: {
                type: 'gradient',
                gradient: { shade: 'light', type: 'horizontal', shadeIntensity: 0.2, gradientToColors: [P.yellow], stops: [0, 100] }
            },
            xaxis: {
                categories: chartData.demora_bloques.map(d => d.bloque),
                labels: { style: { fontSize: '11px', colors: P.grayLt } }
            },
            yaxis: { labels: { style: { fontSize: '11px', colors: P.gray, fontWeight: 600 } } },
            tooltip: { y: { formatter: val => val + " horas promedio" } },
            dataLabels: {
                enabled: true,
                formatter: val => val + "h",
                style: { fontSize: '10px', fontWeight: 700, colors: ['#fff'] },
                offsetX: 5
            },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
        }).render();
    } else {
        document.querySelector("#delayChart").innerHTML = '<div class="empty-chart-state"><i class="bi bi-inbox"></i>Sin datos de demora</div>';
    }

    // ==============================
    // 5. STATES – Estados Frecuentes
    // ==============================
    if (chartData.estados_uso && chartData.estados_uso.length > 0) {
        new ApexCharts(document.querySelector("#statesChart"), {
            series: chartData.estados_uso.map(d => d.cantidad),
            chart: { type: 'polarArea', height: 350, ...baseFont },
            labels: chartData.estados_uso.map(d => d.estado),
            fill: { opacity: 0.85 },
            stroke: { width: 2, colors: ['#fff'] },
            colors: [P.blue, P.blueLt, P.teal, P.green, P.amber, P.indigo, P.grayLt, P.redLt],
            legend: { position: 'bottom', fontSize: '10px', markers: { radius: 3 } },
            plotOptions: { polarArea: { rings: { strokeWidth: 1, strokeColor: '#f1f5f9' } } },
            yaxis: { show: false },
        }).render();
    } else {
        document.querySelector("#statesChart").innerHTML = '<div class="empty-chart-state"><i class="bi bi-inbox"></i>Sin datos de estados</div>';
    }

    // ==============================
    // 6. HEATMAP – Carga por Responsable
    // ==============================
    if (chartData.heatmap && chartData.heatmap.length > 0) {
        new ApexCharts(document.querySelector("#heatmapChart"), {
            series: [{
                name: 'En Trámite',
                data: chartData.heatmap.map(d => ({ x: d.name, y: d.tramite }))
            }, {
                name: 'Devueltas',
                data: chartData.heatmap.map(d => ({ x: d.name, y: d.devueltas }))
            }, {
                name: 'Finalizadas',
                data: chartData.heatmap.map(d => ({ x: d.name, y: d.finalizadas }))
            }],
            chart: { height: 350, type: 'heatmap', toolbar: noToolbar, ...baseFont },
            dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700 } },
            plotOptions: {
                heatmap: {
                    radius: 4,
                    colorScale: {
                        ranges: [
                            { from: 0, to: 0, color: '#f8fafc', name: 'Ninguna' },
                            { from: 1, to: 2, color: '#bfdbfe', name: 'Baja' },
                            { from: 3, to: 5, color: '#60a5fa', name: 'Media' },
                            { from: 6, to: 100, color: P.blue, name: 'Alta' }
                        ]
                    }
                }
            },
            stroke: { width: 2, colors: ['#fff'] },
            xaxis: { labels: { style: { fontSize: '10px' } } },
        }).render();
    } else {
        document.querySelector("#heatmapChart").innerHTML = '<div class="empty-chart-state"><i class="bi bi-inbox"></i>Sin datos de carga</div>';
    }

    // ==============================
    // 7. BAR DIFERENCIA – Contratos con Mayor Brecha
    // ==============================
    if (chartData.diferencia_barras && chartData.diferencia_barras.length > 0) {
        new ApexCharts(document.querySelector("#barChartDiferencia"), {
            series: [{
                name: 'Brecha',
                data: chartData.diferencia_barras.map(d => d.diferencia)
            }],
            chart: { type: 'bar', height: 350, toolbar: noToolbar, ...baseFont },
            plotOptions: { bar: { borderRadius: 6, horizontal: true, barHeight: '65%' } },
            colors: [P.redLt],
            fill: {
                type: 'gradient',
                gradient: { shade: 'dark', type: 'horizontal', shadeIntensity: 0.3, gradientToColors: [P.red], stops: [0, 100] }
            },
            dataLabels: {
                enabled: true,
                formatter: val => val > 0 ? val + " pend." : "OK",
                style: { fontSize: '10px', fontWeight: 700, colors: ['#fff'] }
            },
            xaxis: {
                categories: chartData.diferencia_barras.map(d => d.contrato),
                labels: { style: { fontSize: '10px', colors: P.grayLt } }
            },
            yaxis: { labels: { style: { fontSize: '11px', colors: P.gray, fontWeight: 600 } } },
            tooltip: {
                y: { formatter: val => "Faltan " + val + " facturas por radicar" }
            },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
        }).render();
    }

    // ==============================
    // 8. TIMELINE – Actividad últimos 30 días
    // ==============================
    if (chartData.timeline && chartData.timeline.labels.length > 0) {
        new ApexCharts(document.querySelector("#timelineChart"), {
            series: [{
                name: 'Transiciones',
                data: chartData.timeline.series
            }],
            chart: {
                type: 'area',
                height: 350,
                toolbar: noToolbar,
                ...baseFont,
                sparkline: { enabled: false }
            },
            colors: [P.indigo],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.05,
                    stops: [0, 95]
                }
            },
            stroke: { curve: 'smooth', width: 2.5 },
            xaxis: {
                categories: chartData.timeline.labels,
                labels: { rotate: -45, style: { fontSize: '9px', colors: P.grayLt } }
            },
            yaxis: {
                title: { text: 'Transiciones', style: { fontSize: '11px', color: P.gray } },
                labels: { formatter: val => Math.round(val), style: { colors: P.grayLt } }
            },
            dataLabels: { enabled: false },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            markers: { size: 3, colors: [P.indigo], strokeWidth: 0, hover: { size: 6 } },
            tooltip: { y: { formatter: val => val + " transiciones" } }
        }).render();
    } else {
        document.querySelector("#timelineChart").innerHTML = '<div class="empty-chart-state"><i class="bi bi-activity"></i>Sin actividad reciente (30 días)</div>';
    }

    // ==============================
    // 9. SLA COMPLIANCE
    // ==============================
    if (chartData.sla_compliance && chartData.sla_compliance.length > 0) {
        new ApexCharts(document.querySelector("#slaChart"), {
            series: [{
                name: 'Cumple SLA',
                data: chartData.sla_compliance.map(d => d.cumple)
            }, {
                name: 'Excede SLA',
                data: chartData.sla_compliance.map(d => d.no_cumple)
            }],
            chart: { type: 'bar', height: 350, stacked: true, toolbar: noToolbar, ...baseFont },
            plotOptions: { bar: { horizontal: false, columnWidth: '50%', borderRadius: 6, borderRadiusApplication: 'end' } },
            colors: [P.green, P.redLt],
            xaxis: {
                categories: chartData.sla_compliance.map(d => d.bloque),
                labels: { style: { fontSize: '10px', colors: P.grayLt } }
            },
            yaxis: {
                title: { text: 'Transiciones', style: { fontSize: '11px', color: P.gray } },
                labels: { style: { colors: P.grayLt } }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '11px',
                markers: { radius: 3 }
            },
            dataLabels: {
                enabled: true,
                style: { fontSize: '10px', fontWeight: 700, colors: ['#fff'] }
            },
            tooltip: {
                shared: true,
                y: { formatter: val => val + " transiciones" }
            },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            annotations: {
                yaxis: chartData.sla_compliance.map(d => ({
                    y: 0,
                    borderColor: 'transparent',
                    label: {
                        text: d.porcentaje + '% cumpl.',
                        position: 'front',
                        style: { fontSize: '9px', color: P.green, background: 'transparent' }
                    }
                }))
            }
        }).render();
    } else {
        document.querySelector("#slaChart").innerHTML = '<div class="empty-chart-state"><i class="bi bi-shield-x"></i>Sin datos de SLA configurados</div>';
    }

    // ==============================
    // 10. SUPERVISOR PERFORMANCE
    // ==============================
    if (chartData.supervisor_perf && chartData.supervisor_perf.length > 0) {
        new ApexCharts(document.querySelector("#supervisorChart"), {
            series: [{
                name: 'Finalizadas',
                data: chartData.supervisor_perf.map(d => d.finalizadas)
            }, {
                name: 'Pendientes',
                data: chartData.supervisor_perf.map(d => d.pendientes)
            }],
            chart: { type: 'bar', height: 350, stacked: true, toolbar: noToolbar, ...baseFont },
            plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: '55%' } },
            colors: [P.teal, P.muted],
            xaxis: {
                categories: chartData.supervisor_perf.map(d => d.supervisor),
                labels: { style: { fontSize: '10px', colors: P.grayLt } }
            },
            yaxis: { labels: { style: { fontSize: '10px', colors: P.gray, fontWeight: 600 } } },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '11px',
                markers: { radius: 3 }
            },
            dataLabels: {
                enabled: true,
                style: { fontSize: '10px', fontWeight: 700 }
            },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            tooltip: {
                y: { formatter: (val, { seriesIndex }) => val + (seriesIndex === 0 ? " completadas" : " pendientes") }
            }
        }).render();
    } else {
        document.querySelector("#supervisorChart").innerHTML = '<div class="empty-chart-state"><i class="bi bi-person"></i>Sin datos de supervisores</div>';
    }

    // ==============================
    // 11. BLOQUE DISTRIBUTION (Treemap)
    // ==============================
    if (chartData.bloque_distribucion && chartData.bloque_distribucion.length > 0) {
        new ApexCharts(document.querySelector("#bloqueChart"), {
            series: [{
                data: chartData.bloque_distribucion.map(d => ({
                    x: d.bloque,
                    y: d.cantidad
                }))
            }],
            chart: { type: 'treemap', height: 350, toolbar: noToolbar, ...baseFont },
            colors: [P.blue, P.blueLt, P.teal, P.green, P.indigo, P.amber],
            plotOptions: {
                treemap: {
                    distributed: true,
                    enableShades: false,
                    borderRadius: 4,
                }
            },
            dataLabels: {
                enabled: true,
                style: { fontSize: '12px', fontWeight: 700 },
                formatter: (text, op) => [text, op.value + ' cuentas'],
                offsetY: -2
            },
            legend: { show: false },
        }).render();
    } else {
        document.querySelector("#bloqueChart").innerHTML = '<div class="empty-chart-state"><i class="bi bi-layers"></i>Sin datos de bloques</div>';
    }

    // ==============================
    // BOOTSTRAP TOOLTIPS
    // ==============================
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(el => {
        new bootstrap.Tooltip(el);
    });

    // ==============================
    // RANGE SLIDER (NoUiSlider)
    // ==============================
    var slider = document.getElementById('rangeSlider');
    if (slider) {
        var minValInput = document.getElementById('minVal');
        var maxValInput = document.getElementById('maxVal');

        noUiSlider.create(slider, {
            start: [parseInt(minValInput.value), parseInt(maxValInput.value)],
            connect: true,
            range: { 'min': 0, 'max': 100 },
            step: 1,
            tooltips: true,
            format: {
                to: val => Math.round(val),
                from: val => val
            }
        });

        slider.noUiSlider.on('update', function (values) {
            minValInput.value = values[0];
            maxValInput.value = values[1];
        });
    }

    // ==============================
    // DATATABLES
    // ==============================
    var table = $('#alertTable').DataTable({
        pageLength: 10,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: 'Excel', className: 'd-none', filename: 'BI_Report_2026' }
        ],
        order: [[6, 'desc']] // Ordenar por diferencia descendente
    });

    $('#btnExportExcel').on('click', () => table.button('.buttons-excel').trigger());

    // ==============================
    // PDF EXPORT
    // ==============================
    $('#btnExportPDF').on('click', function () {
        const btn = $(this);
        const originalContent = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Generando...');
        btn.prop('disabled', true);

        const element = document.getElementById('captureArea');

        setTimeout(() => {
            html2canvas(element, {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: "#f1f5f9",
                scrollY: -window.scrollY,
                windowWidth: document.documentElement.offsetWidth,
                windowHeight: element.scrollHeight + 100
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;

                const pdf = new jsPDF('p', 'mm', 'a4');
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

                let heightLeft = pdfHeight;
                let position = 0;
                const pageHeight = pdf.internal.pageSize.getHeight();

                pdf.addImage(imgData, 'PNG', 0, position, pdfWidth, pdfHeight);
                heightLeft -= pageHeight;

                while (heightLeft >= 0) {
                    position = heightLeft - pdfHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, pdfWidth, pdfHeight);
                    heightLeft -= pageHeight;
                }

                pdf.save(`Reporte_Analitica_${new Date().toISOString().split('T')[0]}.pdf`);

                btn.html(originalContent);
                btn.prop('disabled', false);
            }).catch(err => {
                console.error('Error PDF:', err);
                alert('No se pudo generar el PDF.');
                btn.html(originalContent);
                btn.prop('disabled', false);
            });
        }, 500);
    });
});
