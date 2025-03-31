document.addEventListener('DOMContentLoaded', function() {
    function initFooterSwiper() {
        if (document.querySelector('.swiper-button-next-footer') && document.querySelector('.swiper-button-prev-footer')) {
            console.log("Footer Swiper Initialized");

            new Swiper('.hello-bar-slider-footer', {
                loop: true,
                autoplay: { delay: 30000 },
                slidesPerView: 1,
                navigation: {
                    nextEl: '.swiper-button-next-footer',
                    prevEl: '.swiper-button-prev-footer',
                },
            });
        } else {
            console.warn("Footer Swiper buttons not found! Retrying in 500ms...");
            setTimeout(initFooterSwiper, 500);
        }
    }

    new Swiper('.hello-bar-slider-top', {
        loop: true,
        autoplay: { delay: 30000 },
        slidesPerView: 1,
        navigation: {
            nextEl: '.swiper-button-next-top',
            prevEl: '.swiper-button-prev-top',
        },
    });

    initFooterSwiper();
});
