// js/main.js
document.addEventListener('DOMContentLoaded', function() {
    // Αυτόματη ενημέρωση έτους στο footer
    const yearSpan = document.getElementById('year');
    if (yearSpan) {
        yearSpan.textContent = new Date().getFullYear();
    }
});