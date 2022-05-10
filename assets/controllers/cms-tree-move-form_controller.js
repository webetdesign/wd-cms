import { Controller } from '@hotwired/stimulus';
import Choices from 'choices.js';

export default class extends Controller {
  static values = {};

  static targets = ['radio', 'select'];

  connect() {
    console.log('la');
    const opts = this.selectTarget.querySelector('[value="' + this.selectTarget.value + '"]');
    this.toggleRadio(opts.dataset.customProperties == 1);
  }

  change(e) {
    const opts = this.selectTarget.querySelector('[value="' + e.currentTarget.value + '"]');
    this.toggleRadio(opts.dataset.customProperties == 1);
  }

  toggleRadio(lock) {
    if (lock) {
      this.radioTargets.forEach(radio => {
        if (radio.dataset.hasOwnProperty('disallowRoot')) {
          radio.setAttribute('disabled', 'disabled');
          if (radio.checked) {
            radio.checked = false;
            this.radioTargets[0].checked = true;
          }
        }

      });
    } else {
      this.radioTargets.forEach(radio => {
        if (radio.dataset.hasOwnProperty('disallowRoot')) {
          radio.removeAttribute('disabled');
        }
      });

    }
  }
}
