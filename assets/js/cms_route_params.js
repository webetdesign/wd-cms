import $ from "jquery"

//parse le champ pour trouver tous les params '{params}'
function parsePath ($el) {
  let val = $el.val();

  const regex = /\{\w+\}/gm;
  let m;
  let vars = []
  while ((m = regex.exec(val)) !== null) {
    // This is necessary to avoid infinite loops with zero-width matches
    if (m.index === regex.lastIndex) {
      regex.lastIndex++;
    }

    // The result can be accessed through the `m`-variable.
    m.forEach((match, groupIndex) => {
      vars.push(match.replace(/[\{\}]/g, ''))
    });
  }

  buildTable($el, vars)
}

function buildTable ($el, params) {
  let defaults = JSON.parse($('.cms_route_default_input').val())
  let requirements = JSON.parse($('.cms_route_requirements_input').val())

  $.each(defaults, param => {
    if (!params.includes(param)) {
      delete defaults[param]
    }
  })
  $('.cms_route_default_input').val(JSON.stringify(defaults))
  $.each(requirements, param => {
    if (!params.includes(param)) {
      delete requirements[param]
    }
  })
  $('.cms_route_requirements_input').val(JSON.stringify(requirements))

  let div = $('<div>', {
    class: 'form-group path_params_container'
  })
  let table = $('<table>', {class: 'table table-bordered'});
  let thead = $('<thead></thead>')
  let thead_tr = $('<tr></tr>')
  thead.append(thead_tr)
  thead_tr.append('<th>Paramètre</th><th>Défaut</th><th>Critère</th>')
  let tbody = $('<tbody>')

  params.forEach(params => {
    let tr = $('<tr>')
    tr.append($('<td>' + params + '</td>'))
    let inputDefault = $('<input>', {
      type: 'text',
      class: 'form-control'
    })
    let inputRequirement = $('<input>', {
      type: 'text',
      class: 'form-control'
    })

    inputDefault.on('change', function () {
      defaults[params] = $(this).val()
      $('.cms_route_default_input').val(JSON.stringify(defaults))
    })

    inputRequirement.on('change', function () {
      requirements[params] = $(this).val()
      $('.cms_route_requirements_input').val(JSON.stringify(requirements))
    })

    if (defaults.hasOwnProperty(params)) {
      inputDefault.val(defaults[params])
    }
    if (requirements.hasOwnProperty(params)) {
      inputRequirement.val(requirements[params])
    }

    tr.append($('<td></td>').append(inputDefault))
    tr.append($('<td></td>').append(inputRequirement))
    tbody.append(tr)
  })
  table.append(thead, tbody)
  $('.path_params_container').remove()
  div.append(table)
  $el.parent().parent().after(div)
}

$(document).ready(function () {
  $.each($('.cms_route_path_input'), function () {
    $(this).on('change blur', function () {
      parsePath($(this));
    })
    parsePath($(this));
  })
});
