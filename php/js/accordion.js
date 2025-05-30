// js/accordion.js
document.addEventListener('DOMContentLoaded', function () {
    const accordionItems = document.querySelectorAll('.accordion-item');

    accordionItems.forEach(item => {
        const header = item.querySelector('.accordion-header');
        const content = item.querySelector('.accordion-content');

        header.addEventListener('click', () => {
            // Close all other active items
            accordionItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.querySelector('.accordion-header').classList.remove('active');
                    otherItem.querySelector('.accordion-content').classList.remove('active');
                    otherItem.querySelector('.accordion-content').style.maxHeight = null;
                    otherItem.querySelector('.accordion-content').style.paddingTop = null;
                    otherItem.querySelector('.accordion-content').style.paddingBottom = null;
                }
            });

            // Toggle current item
            header.classList.toggle('active');
            content.classList.toggle('active');

            if (content.classList.contains('active')) {
                content.style.maxHeight = content.scrollHeight + "px";
                content.style.paddingTop = "15px"; // Match CSS
                content.style.paddingBottom = "15px"; // Match CSS
            } else {
                content.style.maxHeight = null;
                content.style.paddingTop = null;
                content.style.paddingBottom = null;
            }
        });
    });
});