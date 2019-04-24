import './gapi.js';
import './date-range-selector.js';
import './view-selector2.js';
import './utils.js';

import Chart from "chart.js"

// == NOTE ==
// This code uses ES6 promises. If you want to use this code in a browser
// that doesn't supporting promises natively, you'll have to include a polyfill.

gapi.analytics.ready(function() {

    /**
     * Authorize the user immediately if the user has already granted access.
     * If no access has been created, render an authorize button inside the
     * element with the ID "embed-api-auth-container".
     */
    gapi.analytics.auth.authorize({
        container: 'embed-api-auth-container',
        clientid: '278406372079-p9vg0oof8vuk208r0n85n14p814p5ntp.apps.googleusercontent.com',
        userInfoLabel : "Vous êtes connecté avec le compte : "
    });



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
    viewSelector.on('change', function(data) {
        var title = document.getElementById('view-name');
        //title.textContent = data.property.name + ' (' + data.view.name + ')';

        // Start tracking active users for this view.
        //activeUsers.set(data).execute();

        // Render all the of charts for this view.
        renderWeekOverWeekChart(data);
        renderYearOverYearChart(data);
        renderTopBrowsersChart(data);
        renderTopCountriesChart(data);

        test(data);


    });


    /**
     * Draw the a chart.js line chart with data from the specified view that
     * overlays session data for the current week over session data for the
     * previous week.
     */
    function renderWeekOverWeekChart(ids) {

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
                        borderColor : 'rgba(220,220,220,1)',
                        backgroundColor : 'rgba(220,220,220,0.3)',
                        pointColor : 'rgba(220,220,220,1)',
                        pointStrokeColor : '#fff',
                        data : data2
                    },
                    {
                        label: 'This Week',
                        borderColor : 'rgba(151,187,205,1)',
                        backgroundColor : 'rgba(151,187,205,0.3)',
                        pointColor : 'rgba(151,187,205,1)',
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


    /**
     * Draw the a chart.js bar chart with data from the specified view that
     * overlays session data for the current year over session data for the
     * previous year, grouped by month.
     */
    function renderYearOverYearChart(ids) {

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
                        fillColor : 'rgba(220,220,220,0.5)',
                        backgroundColor : 'rgba(220,220,220,1)',
                        data : data2
                    },
                    {
                        label: 'This Year',
                        fillColor : 'rgba(151,187,205,0.5)',
                        backgroundColor : 'rgba(151,187,205,1)',
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


    /**
     * Draw the a chart.js doughnut chart with data from the specified view that
     * show the top 5 browsers over the past seven days.
     */
    function renderTopBrowsersChart(ids) {

        query({
            'ids': ids,
            'dimensions': 'ga:browser',
            'metrics': 'ga:pageviews',
            'sort': '-ga:pageviews',
            'max-results': 5
        })
        .then(function(response) {

            var data = [];
            var colors = ['#4D5360','#949FB1','#D4CCC5','#E2EAE9','#F7464A'];


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


    /**
     * Draw the a chart.js doughnut chart with data from the specified view that
     * compares sessions from mobile, desktop, and tablet over the past seven
     * days.
     */
    function renderTopCountriesChart(ids) {
        query({
            'ids': ids,
            'dimensions': 'ga:country',
            'metrics': 'ga:sessions',
            'sort': '-ga:sessions',
            'max-results': 5
        })
        .then(function(response) {

            var data = [];
            var colors = ['#4D5360','#949FB1','#D4CCC5','#E2EAE9','#F7464A'];

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

            new Chart(makeCanvas('countries-container'), {
                type: 'doughnut',
                data: data,
                options: {}
            });
            generateLegend('countries-legend', data);
        });
    }

    function test(ids) {
        console.log("passe test");

        query({
            'ids': ids,
            'metrics': 'ga:users',
            'dimensions': 'ga:source',
        })
        .then(function(response) {



            response.rows.forEach(function(row, i) {

                var data = [];
                var colors = ['#4D5360','#949FB1','#D4CCC5','#E2EAE9','#F7464A'];


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


                new Chart(makeCanvas('source-container'), {
                    type: 'doughnut',
                    data: data,
                    options: {}
                });
                generateLegend('source-legend', data);

            });


        });
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
