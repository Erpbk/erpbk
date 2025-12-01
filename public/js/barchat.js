    var xValues = ["Vendors", "Customer", "Riders", "Bikes", "Sims"];
    var yValues = [55, 49, 44, 24, 15];
    var barColors = [
      "#0760d3",
      "#5c98e5",
      "#211c1d",
      "#706c7e",
      "#94baec"
    ];

    var ctx = document.getElementById("myChart").getContext("2d");

    new Chart(ctx, {
      type: "pie",
      data: {
        labels: xValues,
        datasets: [{
          backgroundColor: barColors,
          data: yValues
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

    var xsValues = [];
  var ysValues = [];
  generateData("Math.sin(x)", 0, 10, 0.5);

  new Chart("newChart", {
    type: "line",
    data: {
      labels: xsValues,
      datasets: [{
        fill: false,
        pointRadius: 2,
        borderColor: "rgba(0,0,255,0.5)",
        data: ysValues
      }]
    },    
    options: {
      legend: {display: false},
      title: {
        display: true,
        text: "y = sin(x)",
        fontSize: 16
      }
    }
  });
  function generateData(value, i1, i2, step = 1) {
    for (let x = i1; x <= i2; x += step) {
      ysValues.push(eval(value));
      xsValues.push(x);
    }
  }