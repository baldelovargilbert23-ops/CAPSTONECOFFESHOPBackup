// resources/js/charts.js

import ApexCharts from 'apexcharts';
import Chart from 'chart.js/auto';

window.ApexCharts = ApexCharts;
window.Chart = Chart;

window.addEventListener('load', () => {
    (function () {
        const el = document.querySelector('#hs-single-area-chart');
        if (!el) return;

        function varToColor(v) {
            if (!v) return '#888';
            const isVar = v.startsWith('--');
            return isVar ? getComputedStyle(document.documentElement).getPropertyValue(v).trim() : v;
        }

        function buildTooltip(props, opts) {
            const { series, seriesIndex, dataPointIndex, w } = props;
            const value = w.globals.series[seriesIndex][dataPointIndex];
            return `
                <div class="p-2 ${opts.wrapperExtClasses || ''}">
                    <span class="text-xs text-gray-500">${opts.title || ''}</span>
                    <div class="font-semibold text-gray-800 dark:text-neutral-200">
                        ${opts.valuePrefix || ''}${value}
                    </div>
                </div>
            `;
        }

        function buildChart(selector, mode) {
            const chartEl = document.querySelector(selector);
            if (!chartEl) return;

            const isDark = document.documentElement.classList.contains('dark');

            const options = {
                chart: { height: 300, type: 'area', toolbar: { show: false }, zoom: { enabled: false } },
                series: [{ name: 'Visitors', data: [180, 51, 60, 38, 88, 50, 40, 52, 88, 80, 60, 70] }],
                legend: { show: false },
                dataLabels: { enabled: false },
                stroke: { curve: 'straight', width: 2 },
                grid: {
                    strokeDashArray: 2,
                    borderColor: isDark ? varToColor('--color-neutral-700') : varToColor('--color-gray-200')
                },
                colors: [isDark ? '#ffffff' : '#1f2937'],
                fill: {
                    type: 'gradient',
                    gradient: { type: 'vertical', shadeIntensity: 1, opacityFrom: 0.1, opacityTo: 0.8 }
                },
                xaxis: {
                    type: 'category',
                    tickPlacement: 'on',
                    categories: [
                        '25 January 2023','26 January 2023','27 January 2023',
                        '28 January 2023','29 January 2023','30 January 2023',
                        '31 January 2023','1 February 2023','2 February 2023',
                        '3 February 2023','4 February 2023','5 February 2023'
                    ],
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    crosshairs: { stroke: { dashArray: 0 }, dropShadow: { show: false } },
                    tooltip: { enabled: false },
                    labels: {
                        style: {
                            colors: varToColor('--color-gray-400'),
                            fontSize: '13px',
                            fontFamily: 'Inter, ui-sans-serif',
                            fontWeight: 400
                        },
                        formatter: (title) => {
                            let t = title;
                            if (t) {
                                const newT = t.split(' ');
                                t = `${newT[0]} ${newT[1].slice(0, 3)}`;
                            }
                            return t;
                        }
                    }
                },
                yaxis: {
                    labels: {
                        align: 'left',
                        minWidth: 0,
                        maxWidth: 140,
                        style: {
                            colors: varToColor('--color-gray-400'),
                            fontSize: '13px',
                            fontFamily: 'Inter, ui-sans-serif',
                            fontWeight: 400
                        },
                        formatter: (value) => value >= 1000 ? `${value / 1000}k` : value
                    }
                },
                tooltip: {
                    x: { format: 'MMMM yyyy' },
                    y: { formatter: (value) => `${value >= 1000 ? `${value / 1000}k` : value}` },
                    custom: function (props) {
                        const { categories } = props.ctx.opts.xaxis;
                        const { dataPointIndex } = props;
                        const title = categories[dataPointIndex].split(' ');
                        const newTitle = `${title[0]} ${title[1]}`;
                        return buildTooltip(props, {
                            title: newTitle,
                            mode,
                            valuePrefix: '',
                            hasTextLabel: true,
                            wrapperExtClasses: 'min-w-28'
                        });
                    }
                },
                responsive: [{
                    breakpoint: 568,
                    options: {
                        chart: { height: 300 },
                        labels: {
                            style: {
                                colors: varToColor('--color-gray-400'),
                                fontSize: '11px',
                                fontFamily: 'Inter, ui-sans-serif',
                                fontWeight: 400
                            },
                            offsetX: -2,
                            formatter: (title) => title.slice(0, 3)
                        }
                    }
                }]
            };

            new ApexCharts(chartEl, options).render();
        }

        buildChart('#hs-single-area-chart', 'light');
    })();
});