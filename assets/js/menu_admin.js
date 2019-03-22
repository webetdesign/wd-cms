import $ from 'jquery'
import {Sortable, Plugins} from '@shopify/draggable';
import axios from 'axios'
import queryString from 'query-string'

var time = 100;
$(document).ready(function () {
  let query = queryString.parse(location.search)

  if (query.in_edit != undefined) {
    let item = $('.StackedListItem[data-id=' + query.in_edit + ']')
    let data = item.data()
    setTimeout(function () {
      $('.spinbox').show();

      $.get(data.editUrl, function (response) {
        $('.spinbox').hide();
        $('.edit-container').empty().append(response);
        $('.edit-container').fadeIn();
      })
    }, time)
  }

  $('.cms-menu-item__btn-edit').on('click', function () {
    $('.StackedListItem').removeClass('active');
    let item = $(this).closest('.item-data')
    item.addClass('active');

    $('.edit-container').fadeOut(time);
    var data = item.data();
    setTimeout(function () {
      $('.spinbox').show();

      $.get(data.editUrl, function (response) {
        $('.spinbox').hide();
        $('.edit-container').empty().append(response);
        $('.edit-container').fadeIn();
      })
    }, time)
  })

  $('.cms-menu-item__btn-add').on('click', function () {
    $('.StackedListItem').removeClass('active');
    let item = $(this).closest('.item-data')
    console.log(item)

    $('.edit-container').fadeOut(time);
    var data = item.data();
    setTimeout(function () {
      $('.spinbox').show();

      $.get(data.addUrl, function (response) {
        $('.spinbox').hide();
        $('.edit-container').empty().append(response);
        $('.edit-container').fadeIn();
      })
    }, time)
  })

  $('.sonata-action-element').on('click', function (e) {
    e.preventDefault();
    $('.cms-menu-item').removeClass('active');
    $('.edit-container').fadeOut(time);
    var url = $(this).prop('href');
    setTimeout(function () {
      $('.spinbox').show();

      $.get(url, function (response) {
        $('.spinbox').hide();
        $('.edit-container').empty().append(response);
        $('.edit-container').fadeIn();
      })
    }, time)
  })

  const containers = document.querySelectorAll('.StackedList');

  const moveUrl = $('.cmsMenuAdmin').data('move-url');

  const sortable = new Sortable(containers, {
    draggable: '.StackedListItem',
    handle: '.glyphicon-move',
    mirror: {
      constrainDimensions: true,
    },
    plugins: [Plugins.ResizeMirror],
  });

  sortable.on('sortable:start', evt => {
    console.log(evt)
    $('.cmsMenuAdmin').addClass('dragging')
  });
  // sortable.on('sortable:sort', evt => console.log(evt));
  // sortable.on('sortable:sorted', evt => console.log(evt));
  sortable.on('sortable:stop', evt => {
    $('.cmsMenuAdmin').removeClass('dragging')

    setTimeout(function () {
      console.log(evt)
      const el = evt.data.dragEvent.originalSource
      let prev = document.querySelector(`li[data-id="${el.dataset.id}"]`).previousElementSibling
      const parent = document.querySelector(`li[data-id="${el.dataset.id}"]`).parentElement

      let data = new FormData()
      data.append('source', el.dataset.id)
      if (prev != null && !prev.classList.contains('StackedListGhost')) {
        console.log('id ' + prev.dataset.id)
        data.append('target', prev.dataset.id)
        data.append('moveMode', 'persistAsNextSiblingOf')
        axios.post(moveUrl, data)
      } else {
        console.log('parentId ' + evt.data.newContainer.dataset.parentId)
        data.append('target', evt.data.newContainer.dataset.parentId)
        data.append('moveMode', 'persistAsFirstChildOf')
        axios.post(moveUrl, data)
      }
    }, 50)
  });

})
