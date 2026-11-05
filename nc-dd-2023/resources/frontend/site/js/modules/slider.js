import {tns} from "tiny-slider";

document.addEventListener('DOMContentLoaded', () => {

    const reviewSlider = document.querySelector('#reviewSlider');

    if (reviewSlider) {
         tns({
            container: '#reviewSlider .slider__body',
            items: 3,
            slideBy: '1',
            autoplay: true,
            mouseDrag: true,
            nav: false,
            controls: true,
            loop: true,
            autoplayButtonOutput: false,
            controlsContainer: '#reviewSlider .slider__nav',
            speed: 700,
            responsive: {
                320: {
                    items: 1
                },
                768: {
                    items: 2
                },
                1024: {
                    items: 3
                }
            }
        });
    }

    const priceSlider = document.querySelector('#priceSlider');

    if (priceSlider) {
        const options = {
            items: 3,
            slideBy: '1',
            autoplay: true,
            mouseDrag: true,
            nav: false,
            controls: false,
            loop: true,
            autoplayButtonOutput: false,
            speed: 700,
            responsive: {
                320: {
                    items: 1
                },
                768: {
                    items: 2
                },
                1024: {
                    items: 3
                }
            }
        };

        tns({
            container: '#pricesMonthly',
            ...options
        });

        tns({
            container: '#pricesAnnual',
            ...options
        });
    }

    const partnersSlider = document.querySelector('#partnersSlider');

    if (partnersSlider) {
        tns({
            container: '#partnersSlider .partners__list',
            items: 5,
            slideBy: '1',
            gutter: 16,
            autoplay: true,
            mouseDrag: true,
            nav: false,
            controls: true,
            loop: true,
            autoplayButtonOutput: false,
            controlsContainer: '#partnersSlider .slider__nav',
            speed: 700,
            responsive: {
                320: {
                    items: 1
                },
                768: {
                    items: 2
                },
                992: {
                    items: 3
                },
                1200: {
                    items: 4
                },
                1920: {
                    items: 5
                }
            }
        });
    }

    const newsSlider = document.querySelector('#newsSlider');

    if (newsSlider) {
        tns({
            container: '#newsSlider .news__list',
            items: 3,
            slideBy: '1',
            autoplay: true,
            mouseDrag: true,
            nav: false,
            controls: false,
            loop: true,
            autoplayButtonOutput: false,
            speed: 700,
            responsive: {
                320: {
                    items: 1
                },
                768: {
                    items: 2
                },
                1200: {
                    items: 3
                }
            }
        });
    }

});
