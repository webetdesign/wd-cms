import axios from 'axios';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

document.addEventListener("DOMContentLoaded",function(){
  const items = document.querySelectorAll('.treeMoveAction')
  const modal = document.querySelector('#tree_move_modal')

  items.forEach(item => {
    item.addEventListener('click', e => {
      e.preventDefault();

      axios.get(item.href)
        .then(response => {
          console.log(response.data.modalContent)
          modal.querySelector('.modal-title').innerText = 'Déplacé ' + response.data.label;
          modal.querySelector('.modal-body').innerHTML = response.data.modalContent;

          Admin.setup_select2(modal)

          $(modal).modal('show');
        })


    })
  })

})
