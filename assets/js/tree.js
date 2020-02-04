import axios from 'axios';
import _ from 'lodash';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

let toggleSubItems = (item)=>{
  item.classList.toggle('is-toggled');

  let closedItemStored = [];
  const storageItems = JSON.parse(localStorage.getItem('adminMenuClosed'));
  if(storageItems ){
    closedItemStored = [].concat(storageItems);
  }
  const id = item.getAttribute('data-id');
  if (item.classList.contains('is-toggled')){
    closedItemStored = closedItemStored.filter(item => item!== id);
  }else{
    closedItemStored.push(id)
  }
  localStorage.setItem('adminMenuClosed', JSON.stringify(closedItemStored));

};

let searchTree = function(){
  document.querySelector('.js-search-result').innerHTML = '';
  const input = document.querySelector('.content-header .sticky-wrapper .js-search-tree input');
  if (input.value.length > 2){
    document.querySelector('.pages-box').classList.add('hidden');
   const searchValue = input.value.toLowerCase();
  let results = [];
   const slugTxt = document.querySelectorAll('.text-muted, .page-tree__item__edit');
   slugTxt.forEach(slug => {
     const cleanSlug = slug.innerHTML.toLowerCase();
     if(cleanSlug.includes(searchValue)){
        let div = slug.parentNode.cloneNode(true);
        div.classList.add('page-tree__item');
        if (div.querySelector('.declinations')){
          div.querySelector('.declinations').remove();
        }
        results.push(div);
     }
   })
    const unique = Array.from(new Set(results.map(a => a.innerText)))
      .map(innerText => {
        return results.find(a => a.innerText === innerText)
      })

    unique.forEach(result => {
     document.querySelector('.js-search-result').append(result);
   })
    document.querySelector('.js-search-result').classList.remove('hidden');

  }else{
    document.querySelector('.js-search-result').classList.add('hidden');
    document.querySelector('.pages-box').classList.remove('hidden');
  }
};

let createSearchbar = () => {
  const header = document.querySelector('.content-header .sticky-wrapper');
  let searchDiv = document.createElement('div');
  searchDiv.classList.add('js-search-tree');
  searchDiv.innerHTML = '<div class="search-container"><input type="search" placeholder="Recherche"></div><div class="js-search-result hidden"></div>'
  if(header){
    header.append(searchDiv);
    header.querySelector('.js-search-tree input').addEventListener('input', _.debounce(()=>{searchTree()},300));
  }
};

document.addEventListener("DOMContentLoaded",function(){
  const treeItems = document.querySelectorAll('.page-tree__item');
  const treeMove = document.querySelectorAll('.treeMoveAction');
  const declinations = document.querySelectorAll('.declination-toggle');
  const modal = document.querySelector('#tree_move_modal');
  const storageItems = JSON.parse(localStorage.getItem('adminMenuClosed'));
  createSearchbar();
  treeItems.forEach(item => {
    const id = item.getAttribute('data-id');
    if (storageItems.includes(id)){
      toggleSubItems(item);
    }
    const next = item.nextElementSibling;
    if(next !== null && next.tagName === 'UL'){
      const caret = item.querySelector('.fa-caret-right');
      if (caret){
        caret.addEventListener('click', e =>{
          toggleSubItems(item);
        })
      }
    }
  })
  treeMove.forEach(item => {
    item.addEventListener('click', e => {
      e.preventDefault();

      axios.get(item.href)
        .then(response => {
          modal.querySelector('.modal-title').innerText = 'Déplacé ' + response.data.label;
          modal.querySelector('.modal-body').innerHTML = response.data.modalContent;

          let script = modal.querySelector('.modal-body').querySelector('script');
          eval(script.innerText);

          Admin.setup_select2(modal);

          $(modal).modal('show');
        })
    })
  })
  declinations.forEach( item => {
    item.addEventListener('click', e => {
      const declination = item.parentNode.querySelector('.declinations');
      declination.classList.toggle('oppened');
    })
  })
});
