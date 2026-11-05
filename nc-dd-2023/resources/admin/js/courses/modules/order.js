document.addEventListener('DOMContentLoaded', () => {

    const onClick = event => {
        const element = event.target.closest('.js-course-module-order');

        if (!element) return;

        const id    = element.dataset.id;
        const order = document.getElementById(`course-module-order-${id}`).value;

        axios.put(`/courses/modules/${id}/order`, { order })
            .then((response) => {
                if (response.data.success) {
                    toastr.success(response.data.message);
                } else {
                    toastr.error(response.data.message);
                }
            })
            .catch(error => {
                toastr.error(error.response.data.message ?? error.message)
            })
    };

    document.addEventListener('click', onClick);

});
