import Chart from "chart.js"
import './gapi.js';

document.addEventListener('DOMContentLoaded', function(){
    var colors = null;

    if ( document.getElementById("colors")){
        colors = document.getElementById("colors").dataset.colors;
        colors = JSON.parse(colors);
    }

    if (document.getElementById("browsers-container") != null){
        var browsers = document.getElementById("data-browsers").dataset.values;
        renderDoughnut(JSON.parse(browsers), colors, "browsers");
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

    if (document.getElementById('year-container') != null){

        var year_colors = {
            0 : null,
            1 : null
        };

        if ( document.getElementById("year_colors") != null){
            year_colors = document.getElementById("year_colors").dataset.yearcolors;
            year_colors = JSON.parse(year_colors);
            document.getElementById("year_colors").remove();
        }

        var years = document.getElementById("data-userYear").dataset.values;

        renderYearOverYearChart(JSON.parse(years), year_colors);
    }

    if (document.getElementById("sources-container") != null){
        var sources = document.getElementById("data-sources").dataset.values;
        renderDoughnut(JSON.parse(sources), colors, "sources");
    }

    if (document.getElementById("devices-container") != null){
        var devices = document.getElementById("data-devices").dataset.values;
        renderDoughnut(JSON.parse(devices), colors, "devices");
    }

    if (document.getElementById("countries-container") != null){
        var countries = document.getElementById("data-countries").dataset.values;
        var map = document.getElementById("map_key").dataset.mapkey;
        var map_color = document.getElementById("map_color").dataset.mapcolor;
        document.getElementById("map_key").remove();
        renderCountries(JSON.parse(countries), map_color    , map);
    }

    if (document.getElementById("users-container") != null){
        var users_color = document.getElementById("users_color").dataset.userscolor;
        var users = document.getElementById("data-users").dataset.values;
        renderUsers(JSON.parse(users), users_color, "users");
    }

}, false);

function renderDoughnut(response, colors, name) {
    var data = [];
    var colors_chart = [];
    var values = [];
    var labels = [];

    for (let i = 0; i < response.labels.length; i++) {
        values.push(response.values[i]);
        labels.push(response.labels[i]);
        colors_chart.push(colors[i]);
    }

    data['datasets'] =  [];
    data['datasets'].push({
        "data": values,
        "backgroundColor" : colors_chart
    });

    data['labels'] =  labels;


    var chart = new Chart(makeCanvas(name + '-container'), {
        type: 'doughnut',
        data: data,
        options: {}
    });

}

function renderWeekOverWeekChart(data, colors) {

    var values = {
        labels : data.labels,
        datasets : [
            {
                label: 'Semaine dernière',
                borderColor : colors[0],
                pointColor : colors[0],
                backgroundColor: (colors[0]).substring(0,17) + ", 0.5)",
                pointStrokeColor : '#fff',
                data :  data.values.last_week
            },
            {
                label: 'Cette semaine',
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
}

function renderYearOverYearChart(data, colors) {

    var values = {
        labels : data.labels,
        datasets : [
            {
                label: 'Année dernière',
                backgroundColor : colors[0],
                data : data.values.last_year
            },
            {
                label: 'Cette année',
                backgroundColor : colors[1],
                data : data.values.this_year
            }
        ]
    };

    var  options = {};
    new Chart(makeCanvas('year-container'), {
        type: 'bar',
        data: values,
        options: options
    });
}

function renderCountries(data, color, mapKey){
    google.charts.load('current', {
        'packages':['geochart'],
        'mapsApiKey': mapKey,
    });

    setTimeout(function() {
        google.charts.setOnLoadCallback(drawMap(data, color));
    }, 2000)

}

function renderUsers(data, color){
    var visits = data["values"];
    var max = data["max"];

    $.each(visits, function(i, row) {
        var id = "row-" + i ;
        $("#users-container").append('<div class="row " id="'+id+'">\n' +
            '\n' +
            '</div>'
        )
        $.each(row, function(j, value) {
            var colorDiv = getColorUser(max, value, color);
            $("#"+id).append('<div ' +
                'class="col-xs-1 m-1 " ' +
                'style="background-color: '+ colorDiv +'; height: 12px; border: 2px solid white" ' +
                'rel=\'tooltip\' data-original-title=\'' +
                '<span style=" color: #A6ACAF;">' + getDay(j) + ' ' + i + 'h' + '</span>' +
                '<br>' +
                '<span style="font-size: 1.6rem; color: white;">'+ value +'</span>' +
                '<br>' +
                '<span style=" color: #A6ACAF;">' + (value < 2 ? 'Utilisateur' : 'Utilisateurs') +'</span>' +
                '\'' +
                '>\n</div>'
            );
        })

    })

    $("#users-container").append('<div class="row" id="row-date">\n' +
        '\n' +
        '</div>'
    )
    for (var i = 0; i < 7; i++) {
        $("#row-date").append('<div class="col-xs-1 m-1 text-center" style=" height: 10px; border: 1px solid inherit; left: -5px;  font-size: 1.2rem; color: #A6ACAF;" >'+ getDay(i)  + '</div>');
    }

    $("[rel=tooltip]").tooltip({html:true});
}

function getColorUser(max, value, color){
    if (value === 0) return "#dfdfdf";
    var prct = value / max;
    return color.substring(0, 17) + ", " + (prct * 2) + ")";

}

function getDay(day) {
    switch (day) {
        case 0:
        case 'Monday':
            return 'lun.';
        case 1:
        case 'Tuesday':
            return 'mar.';
        case 2:
        case 'Wednesday':
            return 'mer.';
        case 3:
        case 'Thursday':
            return 'jeu.';
        case 4:
        case 'Friday':
            return 'ven.';
        case 5:
        case 'Saturday':
            return 'sam.';
        case 6:
        case 'Sunday':
            return 'dim.';
        default:
            return day;
    }
}

function drawMap(values, color){
    var data = google.visualization.arrayToDataTable(values);

    var options = {
        colors: [color],
        keepAspectRatio: true,
    };

    var chart = new google.visualization.GeoChart(document.getElementById('countries-container'));

    chart.draw(data, options);
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

