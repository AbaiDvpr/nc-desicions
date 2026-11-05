import Modal from "../vendor/modal";

document.addEventListener('DOMContentLoaded', () => {

    if (Modal) {
        const modal = new Modal();

        modal.initialize();
    }

});
