<?php
// help.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Βοήθεια - Ροή μου</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/accordion.css">
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <h2>Βασική Βοήθεια Χρήσης</h2>

        <div class="accordion">
            <div class="accordion-item">
                <button class="accordion-header">Πώς δημιουργώ μια νέα λίστα;</button>
                <div class="accordion-content">
                    <p>Αφού συνδεθείτε, θα βρείτε στο προφίλ σας ή στο μενού την επιλογή "Οι Λίστες μου" και από εκεί το κουμπί "+ Δημιουργία Νέας Λίστας". Πατώντας εκεί, θα μπορείτε να δώσετε ένα όνομα στη λίστα σας και να επιλέξετε αν θα είναι δημόσια ή ιδιωτική.</p>
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">Πώς προσθέτω βίντεο σε μια λίστα;</button>
                <div class="accordion-content">
                    <p>Όταν προβάλλετε μια λίστα σας (από "Οι Λίστες μου" -> "Προβολή"), αν είστε ο ιδιοκτήτης, θα υπάρχει η επιλογή "Αναζήτηση & Προσθήκη Βίντεο". Εκεί, θα μπορείτε να αναζητήσετε βίντεο από το YouTube και να τα προσθέσετε απευθείας στη λίστα σας, αφού πρώτα δώσετε άδεια στην εφαρμογή να έχει πρόσβαση στον λογαριασμό σας Google για την αναζήτηση.</p>
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">Πώς ακολουθώ άλλους χρήστες;</button>
                <div class="accordion-content">
                    <p>Μπορείτε να βρείτε άλλους χρήστες μέσω της σελίδας "Αναζήτηση" (ψάχνοντας για περιεχόμενο που έχουν δημιουργήσει) ή αν δείτε το όνομά τους ως δημιουργούς λίστας ή περιεχομένου. Αν το όνομα χρήστη είναι σύνδεσμος, μπορείτε να πατήσετε για να δείτε το δημόσιο προφίλ τους. Εκεί, αν δεν τους ακολουθείτε ήδη, θα υπάρχει το κουμπί "Follow".</p>
                </div>
            </div>

             <div class="accordion-item">
                <button class="accordion-header">Πώς αλλάζω το θέμα εμφάνισης (Light/Dark);</button>
                <div class="accordion-content">
                    <p>Στην πάνω δεξιά γωνία κάθε σελίδας υπάρχει ένα κουμπί "Εναλλαγή Θέματος". Κάνοντας κλικ σε αυτό, μπορείτε να αλλάξετε μεταξύ φωτεινού (light) και σκοτεινού (dark) θέματος. Η προτίμησή σας αποθηκεύεται για τις επόμενες επισκέψεις σας.</p>
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