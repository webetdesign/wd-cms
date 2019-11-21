import Chart from "chart.js"


document.addEventListener('DOMContentLoaded', function(){
    var colors = null;

    if ( document.getElementById("colors")){
        colors = document.getElementById("colors").dataset.colors;
        colors = JSON.parse(colors);
        document.getElementById("colors").remove();
    }

    if (document.getElementById("browsers-container") != null){
        var browsers = document.getElementById("data-browsers").dataset.values;
        renderTopBrowsersChart(JSON.parse(browsers), colors);
    }

    if (document.getElementById('week-container') != null){

        var week_colors = {
            0 : null,
            1 : null
        };

        if ( document.getElementById("week_colors") != null){
            week_colors = document.getElementById("week_colors").dataset.weekcolors;
            week_colors = JSON.parse(week_colors);
            document.getElementById("week_colors").remove();
        }

        var weeks = document.getElementById("data-userWeek").dataset.values;

        renderWeekOverWeekChart(JSON.parse(weeks), week_colors);
    }
}, false);

function renderTopBrowsersChart(browsers, colors) {
    var data = [];
    var colors_chart = [];
    var values = [];
    var labels = [];

    for (let i = 0; i < browsers.labels.length; i++) {
        values.push(browsers.values[i]);
        labels.push(browsers.labels[i]);
        colors_chart.push(colors[i]);
    }

    data['datasets'] =  [];
    data['datasets'].push({
        "data": values,
        "backgroundColor" : colors_chart
    });

    data['labels'] =  labels;


    var chart = new Chart(makeCanvas('browsers-container'), {
        type: 'doughnut',
        data: data,
        options: {}
    });

    generateLegend('browsers-legend', data);
}

/**
 * Draw the a chart.js line chart with data from the specified view that
 * overlays session data for the current week over session data for the
 * previous week.
 */
function renderWeekOverWeekChart(data, colors) {

    var values = {
        labels : data.labels,
        datasets : [
            {
                label: 'Last Week',
                borderColor : colors[0],
                pointColor : colors[0],
                backgroundColor: (colors[0]).substring(0,17) + ", 0.5)",
                pointStrokeColor : '#fff',
                data :  data.values.last_week
            },
            {
                label: 'This Week',
                borderColor : colors[1],
                pointColor : colors[1],
                backgroundColor: (colors[1]).substring(0,17) + ", 0.5)",
                pointStrokeColor : '#fff',
                data : data.values.this_week
            }
        ]
    };

    var  options = {};
    new Chart(makeCanvas('week-container'), {
        type: 'line',
        data: values,
        options: options
    });
    generateLegend('week-legend', data.datasets);
}

function makeCanvas(id) {
    var container = document.getElementById(id);
    var canvas = document.createElement('canvas');
    var ctx = canvas.getContext('2d');

    container.innerHTML = '';
    canvas.width = container.offsetWidth;
    canvas.height = container.offsetHeight;
    container.appendChild(canvas);

    return ctx;
}

function generateLegend(id, items) {
    var legend = document.getElementById(id);
    legend.innerHTML = items.map(function(item) {
        var color = item.color || item.fillColor;
        var label = item.label;
        return '<li><i style="background:' + color + '"></i>' +
            escapeHtml(label) + '</li>';
    }).join('');
}
