require('./sass/app.scss');

import $ from 'jquery';
import "bootstrap/js/tooltip";
import 'moment/moment';

import './js/cms_route_params';
import './js/menu_admin';
import './js/tree';
import './js/contents';
import './stimulus';

$(function () {
  $('[data-toggle="tooltip"]').tooltip();
})

$(document).ready(function() {
  // show active tab on reload
  //   if (location.hash !== '' && location.hash.substring(1, 5) == 'tab_') {
  //     var currentTabNumber = location.hash.substring(5);
  //     var adminUniqid = '{{ admin.uniqid }}';
  //
  //     $('a[href="#tab_' + adminUniqid + '_' + currentTabNumber + '"]').tab('show');
  //   }
  //
  function modifyURLQuery(url, param) {
    var value = {};

    var query = String(url).split('?');

    if (query[1]) {
      var part = query[1].split('&');

      for (var i = 0; i < part.length; i++) {
        var data = part[i].split('=');

        if (data[0] && data[1]) {
          value[data[0]] = data[1];
        }
      }
    }

    value = $.extend(value, param);

    // Remove empty value
    for (i in value) {
      if (!value[i]) {
        delete value[i];
      }
    }

    // Return url with modified parameter
    if (value) {
      return query[0] + '?' + $.param(value);
    } else {
      return query[0];
    }
  }

  $('a[data-toggle="tab"]').on('click', function(e) {
    var tabName = $(e.target).attr('href').substring(1);

    var form = $('form:not([class])');
    var action = modifyURLQuery(form.attr('action'), { _tab: tabName });
    form.attr('action', action);

  });

  function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
      results = regex.exec(url);
    if (!results) {
      return null;
    }
    if (!results[2]) {
      return '';
    }
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
  }

  var tabName = getParameterByName('_tab', location.href);
  if (tabName) {
    var form = $('form:not([class])');
    var action = modifyURLQuery(form.attr('action'), { _tab: tabName });
    form.attr('action', action);
  }
});
