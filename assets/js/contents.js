require('../sass/content_collapse.scss');

document.addEventListener('DOMContentLoaded', () => {
  const collapseGlobal = document.querySelector('#contents_collapse');
  if (collapseGlobal != null && collapseGlobal !== undefined) {
    const collapses = collapseGlobal.querySelectorAll('.collapse');

    collapses.forEach((collapse) => {
      $(collapse).on('shown.bs.collapse', () => {
        Admin.setup_sticky_elements(document);
      });
    });
  }
});
