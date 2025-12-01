document.addEventListener("DOMContentLoaded", function () {
    const pieLabels = window.chartData.pie.labels;
    const pieValues = window.chartData.pie.values;
    const pieColors = window.chartData.pie.colors;

    const lineLabels = window.chartData.line.labels;
    const lineValues = window.chartData.line.values;

    // Pie Chart
    new Chart(document.getElementById("myChart"), {
        type: "pie",
        data: {
            labels: pieLabels,
            datasets: [{
                backgroundColor: pieColors,
                data: pieValues
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: "Express Delivery All Data"
                }
            }
        }
    });

    // Line Chart
    new Chart(document.getElementById("newChart"), {
        type: "line",
        data: {
            labels: lineLabels,
            datasets: [{
                fill: false,
                pointRadius: 2,
                borderColor: "rgba(0,0,255,0.5)",
                data: lineValues
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: "y = sin(x)"
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'x'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'y'
                    }
                }
            }
        }
    });
});
