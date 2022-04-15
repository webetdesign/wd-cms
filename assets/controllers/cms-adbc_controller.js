import { Controller } from '@hotwired/stimulus';

/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="hello" attribute will cause
 * this controller to be executed. The name "hello" comes from the filename:
 * hello_controller.js -> "hello"
 *
 * Delete this file or adapt it for your use!
 */
export default class extends Controller {
  static values = {
    prototypes: String,
    prototypeNames: String,
    max: Number,
  };

  static targets = ['collection', 'item', 'blockSelector', 'positionField', 'addButton'];


  connect() {
    this.number = this.itemTargets.length;
    if (this.prototypesValue !== undefined && this.prototypesValue !== '') {
      this.prototypes = JSON.parse(this.prototypesValue);
    }

    if (this.prototypeNamesValue !== undefined && this.prototypeNamesValue !== '') {
      this.prototypeNames = JSON.parse(this.prototypeNamesValue);
    }
  }

  add(e) {
    e.preventDefault();
    if (this.blockSelectorTarget.value === null || this.blockSelectorTarget.value === '') return;

    const proto = this.prototypes[this.blockSelectorTarget.value]
      .replaceAll(this.prototypeNames[this.blockSelectorTarget.value] + 'label__', 'Nouveau block')
      .replaceAll(this.prototypeNames[this.blockSelectorTarget.value], this.number);

    this.collectionTarget.insertAdjacentHTML('beforeend', proto);

    Admin.setup_select2(this.collectionTarget);
    Admin.setup_icheck(this.collectionTarget);
    this.number++;
    this.computePosition();
  }

  del(e) {
    e.preventDefault();
    const item = e.currentTarget.closest('[data-cms-adbc-target="item"]');
    item.remove();

    this.toggleAddButton();
  }

  moveUp(e) {
    const line = e.currentTarget.closest('[data-cms-adbc-target="item"]');
    const previousLine = line.previousElementSibling;
    this.persistCkEditorDataBeforeMove(line);
    previousLine.insertAdjacentElement('beforebegin', line);
    this.computePosition();
  }

  moveDown(e) {
    const line = e.currentTarget.closest('[data-cms-adbc-target="item"]');
    const nextLine = line.nextElementSibling;
    this.persistCkEditorDataBeforeMove(line);
    nextLine.insertAdjacentElement('afterend', line);
    this.computePosition();
  }

  computePosition() {
    this.itemTargets.forEach((item, key) => {
      const linePosition = item.querySelector('[data-cms-adbc-target="positionField"]');
      linePosition.value = key;
    });

    this.toggleAddButton();
  }

  toggleAddButton() {
    if (this.maxValue > 0 && this.maxValue === this.itemTargets.length) {
      this.addButtonTarget.setAttribute('disabled', 'disabled');
    } else {
      this.addButtonTarget.removeAttribute('disabled');
    }
  }

  persistCkEditorDataBeforeMove(line) {
    line.querySelectorAll('[data-controller="ckeditor"]')
      .forEach(item => {
        let textarea_id = item.querySelector('textarea').id;
        item.querySelector('textarea').value = CKEDITOR.instances[textarea_id].getData();
      });
  }
}
