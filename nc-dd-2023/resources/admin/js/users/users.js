import 'inputmask';

$(document).ready(function(){
    if ($('.mobile-number').length) {
        let im = new Inputmask("+7 (999)-999-99-99");

        im.mask('.mobile-number');
    }
});
