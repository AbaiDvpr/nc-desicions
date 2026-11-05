$(document).ready(function(){
    $(document).on('click', '.set-paid-status', function (e) {
        e.preventDefault()

        const element = $(this);
        const id = element.attr('data-id');

        axios.put(`/modules/request-refill/${id}`)
            .then((response) => {
                if (response.data.isPaid) {
                    element.html('Да').removeClass('text-danger').addClass('text-success')
                } else {
                    element.html('Нет').removeClass('text-success').addClass('text-danger')
                }

                toastr.success(response.data.message)
            })
            .catch((error) => {
                toastr.error(error.response.data.message ?? error.message)
            })
    });
});
