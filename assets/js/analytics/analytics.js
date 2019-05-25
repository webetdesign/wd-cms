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

    var colors = null;

    if ( $("#client_key_api").length){
        api = $("#client_key_api").data('client-key');
        $("#client_key_api").remove();
    }

    if ( $("#colors").length){
        colors = $("#colors").data('colors');
        $("#colors").remove();
    }

    if (api != null && colors != null){
        /**
         * Authorize the user immediately if the user has already granted access.
         * If no access has been created, render an authorize button inside the
         * element with the ID "embed-api-auth-container".
         */
        gapi.analytics.auth.authorize({
            container: 'embed-api-auth-container',
            clientid: api,
            userInfoLabel : "Vous êtes connecté avec le compte : "
        });

        if ($('#active-users-container').length){
            /**
             * Create a new ActiveUsers instance to be rendered inside of an
             * element with the id "active-users-container" and poll for changes every
             * five seconds.
             */
            var activeUsers = new gapi.analytics.ext.ActiveUsers({
                container: 'active-users-container',
                pollingInterval: 5
            });

            /**
             * Add CSS animation to visually show the when users come and go.
             */
            activeUsers.once('success', function() {
                var element = this.container.firstChild;
                var timeout;

                this.on('change', function(data) {
                    var element = this.container.firstChild;
                    var animationClass = data.delta > 0 ? 'is-increasing' : 'is-decreasing';
                    element.className += (' ' + animationClass);

                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        element.className =
                            element.className.replace(/ is-(increasing|decreasing)/g, '');
                    }, 3000);
                });
            });
        }



        /**
         * Create a new ViewSelector2 instance to be rendered inside of an
         * element with the id "view-selector-container".
         */
        var viewSelector = new gapi.analytics.ext.ViewSelector2({
            container: 'view-selector-container'
        });

        // Render the view selector to the page.
        viewSelector.execute();


        /**
         * Update the activeUsers component, the Chartjs charts, and the dashboard
         * title whenever the user changes the view.
         */
        viewSelector.on('viewChange', function(data) {

            if ($("#active-users-container").length){
                activeUsers.set(data).execute();
            }

            if ($('#week-container').length){

                var week_colors = null;

                if ( $("#week_colors").length){
                    week_colors = $("#week_colors").data('week-colors');
                    $("#week_colors").remove();
                }

                renderWeekOverWeekChart(data.ids, week_colors);
            }

            if ($('#year-container').length){

                var year_colors = null;

                if ( $("#year_colors").length){
                    year_colors = $("#year_colors").data('year-colors');
                    $("#year_colors").remove();
                }

                renderYearOverYearChart(data.ids, year_colors);
            }

            if ($('#browsers-container').length){
                renderTopBrowsersChart(data.ids, colors);
            }

            if ($('#countries-container').length){

                if ( $("#map_key_api").length){

                    var map = $("#map_key_api").data('map-key');
                    $("#map_key_api").remove();

                    renderTopCountriesChart(data.ids, colors, map);
                }
            }

            if ($('#source-container').length){
                renderSourceChart(data.ids, colors);
            }

            if ($('#devices-container').length){
                renderDeviceChart(data.ids, colors);
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

    /*************************************************************************/

    /**
     * Draw the a chart.js line chart with data from the specified view that
     * overlays session data for the current week over session data for the
     * previous week.
     */
    function renderWeekOverWeekChart(ids, colors) {

        // Adjust `now` to experiment with different days, for testing only...
        var now = moment(); // .subtract(3, 'day');

        var thisWeek = query({
            'ids': ids,
            'dimensions': 'ga:date,ga:nthDay',
            'metrics': 'ga:sessions',
            'start-date': moment(now).subtract(1, 'day').day(0).format('YYYY-MM-DD'),
            'end-date': moment(now).format('YYYY-MM-DD')
        });

        var lastWeek = query({
            'ids': ids,
            'dimensions': 'ga:date,ga:nthDay',
            'metrics': 'ga:sessions',
            'start-date': moment(now).subtract(1, 'day').day(0).subtract(1, 'week')
            .format('YYYY-MM-DD'),
            'end-date': moment(now).subtract(1, 'day').day(6).subtract(1, 'week')
            .format('YYYY-MM-DD')
        });

        Promise.all([thisWeek, lastWeek]).then(function(results) {

            var data1 = results[0].rows.map(function(row) { return +row[2]; });
            var data2 = results[1].rows.map(function(row) { return +row[2]; });
            var labels = results[1].rows.map(function(row) { return +row[0]; });

            labels = labels.map(function(label) {
                return moment(label, 'YYYYMMDD').format('ddd');
            });

            var data = {
                labels : labels,
                datasets : [
                    {
                        label: 'Last Week',
                        borderColor : colors[0],
                        pointColor : colors[0],
                        backgroundColor: (colors[0]).substring(0,17) + ", 0.5)",
                        pointStrokeColor : '#fff',
                        data : data2
                    },
                    {
                        label: 'This Week',
                        borderColor : colors[1],
                        pointColor : colors[1],
                        backgroundColor: (colors[1]).substring(0,17) + ", 0.5)",
                        pointStrokeColor : '#fff',
                        data : data1
                    }
                ]
            };

            var  options = {};
            new Chart(makeCanvas('week-container'), {
                type: 'line',
                data: data,
                options: options
            });
            generateLegend('week-legend', data.datasets);
        });
    }


    /*************************************************************************/


    /**
     * Draw the a chart.js bar chart with data from the specified view that
     * overlays session data for the current year over session data for the
     * previous year, grouped by month.
     */
    function renderYearOverYearChart(ids, colors) {

        // Adjust `now` to experiment with different days, for testing only...
        var now = moment(); // .subtract(3, 'day');

        var thisYear = query({
            'ids': ids,
            'dimensions': 'ga:month,ga:nthMonth',
            'metrics': 'ga:users',
            'start-date': moment(now).date(1).month(0).format('YYYY-MM-DD'),
            'end-date': moment(now).format('YYYY-MM-DD')
        });

        var lastYear = query({
            'ids': ids,
            'dimensions': 'ga:month,ga:nthMonth',
            'metrics': 'ga:users',
            'start-date': moment(now).subtract(1, 'year').date(1).month(0)
            .format('YYYY-MM-DD'),
            'end-date': moment(now).date(1).month(0).subtract(1, 'day')
            .format('YYYY-MM-DD')
        });

        Promise.all([thisYear, lastYear]).then(function(results) {
            var data1 = results[0].rows.map(function(row) { return +row[2]; });
            var data2 = results[1].rows.map(function(row) { return +row[2]; });
            var labels = ['Jan','Feb','Mar','Apr','May','Jun',
                'Jul','Aug','Sep','Oct','Nov','Dec'];

            // Ensure the data arrays are at least as long as the labels array.
            // Chart.js bar charts don't (yet) accept sparse datasets.
            for (var i = 0, len = labels.length; i < len; i++) {
                if (data1[i] === undefined) data1[i] = null;
                if (data2[i] === undefined) data2[i] = null;
            }

            var data = {
                labels : labels,
                datasets : [
                    {
                        label: 'Last Year',
                        backgroundColor : colors[0],
                        data : data2
                    },
                    {
                        label: 'This Year',
                        backgroundColor : colors[1],
                        data : data1
                    }
                ]
            };

            var  options = {};
            new Chart(makeCanvas('year-container'), {
                type: 'bar',
                data: data,
                options: options
            });
            generateLegend('year-legend', data.datasets);
        })
        .catch(function(err) {
            console.error(err.stack);
        });
    }


    /*************************************************************************/


    /**
     * Draw the a chart.js doughnut chart with data from the specified view that
     * show the top 5 browsers over the past seven days.
     */
    function renderTopBrowsersChart(ids, colors) {

        query({
            'ids': ids,
            'dimensions': 'ga:browser',
            'metrics': 'ga:pageviews',
            'sort': '-ga:pageviews',
            'max-results': 5
        })
        .then(function(response) {

            var data = [];



            var values = [];
            var labels = [];
            var colors_chart = [];

            response.rows.forEach(function(row, i) {

                values.push(+row[1]);
                labels.push(row[0]);
                colors_chart.push(colors[i]);

            });

            data['datasets'] =  [];
            data['datasets'].push({
                "data": values,
                "backgroundColor" : colors_chart
            });

            data['labels'] =  labels;


            new Chart(makeCanvas('browsers-container'), {
                type: 'doughnut',
                data: data,
                options: {}
            });
            generateLegend('browsers-legend', data);
        });
    }


    /*************************************************************************/


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
     * show traffic sources over the past seven days.
     */
    function renderSourceChart(ids, colors) {

        query({
            'ids': ids,
            'metrics': 'ga:users',
            'dimensions': 'ga:date,ga:medium',
            'max-results': 21,

        })
        .then(function(response) {


            var dates = [];
            var types = [];


            response.rows.forEach(function(row, i) {


                if (jQuery.inArray(row[1], types) === -1){
                    types.push(row[1]);
                }

                if (jQuery.inArray(row[0], dates) === -1){
                    dates.push(row[0]);
                }

            });

            var datas_row = initDataRow(dates.length);
            var datas = [];

            $.each(types, function(i, type) {

                $.each(response.rows, function(k, row) {

                    if (row[1] === type)(

                        $.each(dates, function(j, date) {

                            if (row[0] === date) {
                                datas_row[j] = row[2];
                                return false;
                            }


                        })
                    );

                });

                datas.push({
                    label: type === '(none)' ? 'direct' : type,
                    backgroundColor: colors[i],
                    data: datas_row
                });

                datas_row = initDataRow(dates.length);


            });

            var barData = {
                labels: formatDateAPI(dates),
                datasets : datas
            }


            new Chart(makeCanvas('source-container'), {
                type: 'bar',
                data: barData,
                options: {
                    scales: {
                        xAxes: [{
                            stacked: true
                        }],
                        yAxes: [{
                            stacked: true
                        }]
                    }
                }
            });
            generateLegend('source-legend', barData);

        });




    }

    function initDataRow(nb_date){
        var data_row = [];

        for (let i = 0; i < nb_date; i++) {
            data_row.push(null);
        }

        return data_row;
    }

    function formatDateAPI(dates_old) {
        var dates = [];
        $.each(dates_old, function(i, date) {
            dates.push(
                date.substring(6, 8) + "/" + date.substring(4, 6)
            )
        })

        return dates;
    }


    /*************************************************************************/


    /**
     * Draw the a chart.js doughnut chart with data from the specified view that
     * show the top 5 browsers over the past seven days.
     */
    function renderDeviceChart(ids, colors) {

        query({
            'ids': ids,
            'dimensions': 'ga:deviceCategory',
            'metrics': 'ga:users',
            'max-results': 5
        })
        .then(function(response) {

            var data = [];


            var values = [];
            var labels = [];
            var colors_chart = [];
            var id_text = null;
            var total = 0;

            response.rows.forEach(function(row, i) {
                total += parseFloat(row[1]);
                values.push(row[1]);
                labels.push(formatDeviceName(row[0]));
                colors_chart.push(colors[i]);

            });

            $.each(labels, function(i, label) {
                id_text = "#text-" + labels[i];
                $(id_text).html(
                    getDescriptionDevice(values[i], label, total)
                );
            })

            data['datasets'] =  [];
            data['datasets'].push({
                "data": values,
                "backgroundColor" : colors_chart
            });

            data['labels'] =  labels;


            new Chart(makeCanvas('devices-container'), {
                type: 'doughnut',
                data: data,
                options: {}
            });

        });
    }

    function formatDeviceName(device) {
        switch (device) {
            case 'mobile':
                return 'Mobile';
            case 'desktop':
                return 'Ordinateur';
            case 'tablet':
                return 'Tablette';
            default:
                return device;
        }
    }

    function getDescriptionDevice(value, label, sum){
        var total = Math.round((value / sum) * 100);

        return '<span style="color: #A8ACAF;"> \n' +
         label  +   ' <br>\n' +
            '</span>\n' +
            '<span>  ' + total  + '% </span>';
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




    /**
     * Extend the Embed APIs `gapi.analytics.report.Data` component to
     * return a promise the is fulfilled with the value returned by the API.
     * @param {Object} params The request parameters.
     * @return {Promise} A promise.
     */
    function query(params) {
        return new Promise(function(resolve, reject) {
            var data = new gapi.analytics.report.Data({query: params});
            data.once('success', function(response) { resolve(response); })
            .once('error', function(response) { reject(response); })
            .execute();
        });
    }


    /**
     * Create a new canvas inside the specified element. Set it to be the width
     * and height of its container.
     * @param {string} id The id attribute of the element to host the canvas.
     * @return {RenderingContext} The 2D canvas context.
     */
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


    /**
     * Create a visual legend inside the specified element based off of a
     * Chart.js dataset.
     * @param {string} id The id attribute of the element to host the legend.
     * @param {Array.<Object>} items A list of labels and colors for the legend.
     */
    function generateLegend(id, items) {
        var legend = document.getElementById(id);
        legend.innerHTML = items.map(function(item) {
            var color = item.color || item.fillColor;
            var label = item.label;
            return '<li><i style="background:' + color + '"></i>' +
                escapeHtml(label) + '</li>';
        }).join('');
    }


    // Set some global Chart.js defaults.
    Chart.defaults.global.animationSteps = 60;
    Chart.defaults.global.animationEasing = 'easeInOutQuart';
    Chart.defaults.global.responsive = true;
    Chart.defaults.global.maintainAspectRatio = false;


    /**
     * Escapes a potentially unsafe HTML string.
     * @param {string} str An string that may contain HTML entities.
     * @return {string} The HTML-escaped string.
     */
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }


});
