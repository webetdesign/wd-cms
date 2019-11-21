import './gapi.js';
import './date-range-selector.js';
import './view-selector2.js';
import './utils.js';
import './active-users.js';

import Chart from "chart.js"

// == NOTE ==
// This code uses ES6 promises. If you want to use this code in a browser
// that doesn't supporting promises natively, you'll have to include a polyfill.

gapi.analytics.ready(function() {

    var api = null;



    if (api != null && colors != null){


        /**
         * Update the activeUsers component, the Chartjs charts, and the dashboard
         * title whenever the user changes the view.
         */
        viewSelector.on('viewChange', function(data) {




            if ($('#countries-container').length){

                if ( $("#map_key_api").length){

                    var map = $("#map_key_api").data('map-key');
                    $("#map_key_api").remove();

                    renderTopCountriesChart(data.ids, colors, map);
                }
            }

            if ($('#users-container').length){

                if ( $("#users_color").length){
                    var users_color = $("#users_color").data('users-color');
                    $("#users_color").remove();
                }

                renderUsersChart(data.ids, users_color);
            }



        });
    }


    /**
     * Draw the a chart.js doughnut chart with data from the specified view that
     * compares sessions from mobile, desktop, and tablet over the past seven
     * days.
     */
    function renderTopCountriesChart(ids, colors, map) {

        google.charts.load('current', {
            'packages':['geochart'],
            'mapsApiKey': map
        });

        query({
            'ids': ids,
            'dimensions': 'ga:country',
            'metrics': 'ga:users'
        })
        .then(function(response) {

            var values = [];
            values.push(['Country', 'Popularité']);

            response.rows.forEach(function(row, i) {

                values.push([
                    row[0], parseInt(row[1])
                ]);
            });

            google.charts.setOnLoadCallback(drawRegionsMap(values, colors[0]));

        });
    }

    function drawRegionsMap(values, color) {
        var data = google.visualization.arrayToDataTable(values);

        var options = {
            colors: [color],
            keepAspectRatio: true,
        };

        var chart = new google.visualization.GeoChart(document.getElementById('countries-container'));

        chart.draw(data, options);
    }

    /*************************************************************************/


    /**
     * Draw the a chart.js bar chart with data from the specified view that
     * show users over the past day.
     */
    function renderUsersChart(ids, color) {
        var now = moment();

        var response1 = null;

        query({
            'ids': ids,
            'metrics': 'ga:sessions',
            'dimensions': 'ga:hour, ga:dayOfWeekName, ga:day',
            'start-date': moment(now).subtract(4, 'day').format('YYYY-MM-DD'),
            'end-date': moment(now).subtract(1, 'day').format('YYYY-MM-DD'),
            'sort' : 'ga:day'

        })
        .then(function(response) {

            response1 = response.rows;
            // console.log(response.rows);
            query({
                'ids': ids,
                'metrics': 'ga:sessions',
                'dimensions': 'ga:hour, ga:dayOfWeekName, ga:day',
                'start-date': moment(now).subtract(7, 'day').format('YYYY-MM-DD'),
                'end-date': moment(now).subtract(5, 'day').format('YYYY-MM-DD'),
                'sort' : 'ga:day'

            })
            .then(function(response) {

                var res = formatDatasUser(response.rows, response1);
                var datas = res[0];
                var max = res[1];
                $.each(datas, function(i, row) {
                    var id = "row-" + i ;
                    $("#users-container").append('<div class="row " id="'+id+'">\n' +
                        '\n' +
                        '</div>'
                    )
                    $.each(row, function(j, value) {
                        var colorDiv = getColorUser(max, value[3], color);
                        $("#"+id).append('<div ' +
                            'class="col-xs-1 m-1 " ' +
                            'style="background-color: '+ colorDiv +'; height: 10px; border: 1px solid lightgrey" ' +
                            'rel=\'tooltip\' data-original-title=\'' +
                            '<span style=" color: #A6ACAF;">' + getDay(value[1]) + ' ' + value[0] + 'h' + '</span>' +
                            '<br>' +
                            '<span style="font-size: 1.6rem; color: black;">'+ value[3] +'</span>' +
                            '<br>' +
                            '<span style=" color: #A6ACAF;">'+ (parseFloat(value[3]) < 2 ? 'Utilisateur' : 'Utilisateurs') +'</span>' +
                            '\'' +
                            '>\n</div>'
                        );
                    })

                    if (row[0][0] % 2 === 0){
                        $("#"+id).append('<div class="col-xs-1 m-1 text-center" style=" height: 10px; border: 1px solid inherit; color: #A6ACAF; left: -5px;  font-size: 1.3rem" >'+ (row[0][0]) + '</div>');

                    }

                })

                $("#users-container").append('<div class="row" id="row-date">\n' +
                    '\n' +
                    '</div>'
                )
                for (var i = 0; i < datas[0].length; i++) {
                    $("#row-date").append('<div class="col-xs-1 m-1 text-center" style=" height: 10px; border: 1px solid inherit; left: -5px;  font-size: 1.2rem; color: #A6ACAF;" >'+ getDay((datas[0][i][1]))  + '</div>');

                }

                $("[rel=tooltip]").tooltip({html:true});

            });

        });


    }

    function formatDatasUser(r2, r1) {
        var r = [];
        var row = [];
        var max = 0;
        for (let i = 0; i < 24; i++) {
            $.each(r2, function(j, value) {
                if (parseInt(i) === parseInt(value[0])){
                    row.push(value);
                    if (parseInt(value[3]) > max){
                        max = parseInt(value[3]);
                    }
                }
            })

            $.each(r1, function(j, value) {
                if (parseInt(i) === parseInt(value[0])){
                    row.push(value);
                    if (parseInt(value[3]) > max){
                        max = parseInt(value[3]);
                    }
                }
            })

            r.push(row);
            row = [];
        }

        return [r, max];
    }

    function getColorUser(max, value, color){
        var prct = value / max;
        return color.substring(0, 17) + ", " + prct + ")";

    }

    function getDay(day) {
        switch (day) {
            case 'Friday':
                return 'ven.';
            case 'Saturday':
                return 'sam.';
            case 'Thursday':
                return 'jeu.';
            case 'Monday':
                return 'lun.';
            case 'Sunday':
                return 'dim.';
            case 'Tuesday':
                return 'mar.';
            case 'Wednesday':
                return 'mer.';
            default:
                return day;
        }
    }
});
