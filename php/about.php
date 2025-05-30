<?php
// about.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σχετικά με το Ροή μου</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/accordion.css">
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <h2>Σχετικά με την Υπηρεσία μας</h2>

        <div class="accordion">
            <div class="accordion-item">
                <button class="accordion-header">Σκοπός του Ιστοτόπου</button>
                <div class="accordion-content">
                    <p>Ο ιστότοπος "Ροή μου" σας επιτρέπει να δημιουργείτε και να διαχειρίζεστε προσωπικές λίστες αναπαραγωγής με περιεχόμενο ροής (βίντεο) από το YouTube. Μπορείτε να οργανώσετε τα αγαπημένα σας βίντεο, να τα μοιραστείτε με άλλους (αν το επιθυμείτε) και να παρακολουθείτε τις δημόσιες λίστες άλλων χρηστών.</p>
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">Πώς να Εγγραφείτε</button>
                <div class="accordion-content">
                    <p>Για να εγγραφείτε, απλά κάντε κλικ στον σύνδεσμο "Εγγραφή" που θα βρείτε στο μενού πλοήγησης. Θα σας ζητηθεί να συμπληρώσετε μια φόρμα με τα βασικά σας στοιχεία: όνομα, επώνυμο, ένα μοναδικό όνομα χρήστη (username), τον κωδικό πρόσβασής σας και το email σας. Όλα τα πεδία είναι υποχρεωτικά.</p>
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">Γιατί να Εγγραφείτε;</button>
                <div class="accordion-content">
                    <p>Με την εγγραφή σας αποκτάτε πρόσβαση στις παρακάτω δυνατότητες:</p>
                    <ul>
                        <li>Δημιουργία προσωπικών λιστών περιεχομένου.</li>
                        <li>Επιλογή αν οι λίστες σας θα είναι ιδιωτικές (ορατές μόνο σε εσάς) ή δημόσιες.</li>
                        <li>Δυνατότητα να "ακολουθείτε" άλλους χρήστες και να βλέπετε τις δημόσιες λίστες τους.</li>
                        <li>Αναπαραγωγή των βίντεο απευθείας μέσα από την πλατφόρμα.</li>
                        <li>Προσωποποιημένη εμπειρία και εύκολη διαχείριση του αγαπημένου σας περιεχομένου.</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <?php include 'php/partials/footer.php'; ?>

    <script src="js/accordion.js"></script>
    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script> 
</body>
</html>