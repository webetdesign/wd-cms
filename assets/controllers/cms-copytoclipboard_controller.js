import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

  time = null;

  initialize () {

  }

  async copy (e) {
    e.preventDefault();
    try {
      await navigator.clipboard.writeText(this.element.innerText);

      const txt = this.element.innerText;
      this.element.classList.remove('btn-info');
      this.element.classList.add('btn-success');
      this.element.innerText = 'Variable copiée';

      this.time = setTimeout(() => {
        this.element.classList.remove('btn-success');
        this.element.classList.add('btn-info');
        this.element.innerText = txt;
      }, 1000);

    }
    catch (err) {
      console.log(err);
    }
  }
}
