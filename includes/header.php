<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles.css">
    <title>Gestion des Casiers BCSO</title>
</head>
<body>
    <header>
        <div class="header-content">
            <img src="/assets/site/BCSO.png" alt="BCSO Logo" class="logo">
            <h1><a href="/index.php" class="header-link">Gestion des Casier BCSO</a></h1>
        </div>
        <nav>
            <div class="dropdown">
                <button class="dropbtn">TABLETTE</button>
                <div class="dropdown-content">
                    <a href="/tablette/casier/liste.php">Casiers</a>
                    <a href="/tablette/rapport/liste.php">Rapports</a>
                    <a href="/tablette/saisie/liste.php">Saisie</a>
                    <a href="/tablette/plaintes/liste.php">Plaintes</a>
                    <a href="/convocation/liste_convocations.php">Convocation</a>
                </div>
            </div>
            <div class="dropdown">
                <button class="dropbtn">INFO</button>
                <div class="dropdown-content">
                    <a href="/info/effectif.php">Effectif</a>
                    <a href="/info/entreprise/liste.php">Entreprise</a>
                    <a href="/info/group/liste.php">Groupe</a>
                    <a href="/info/code_penal.php">Code Penal</a>
                </div>
            </div>
            <div class="dropdown">
                <button class="dropbtn">B.C.I</button>
                <div class="dropdown-content">
                    <a href="/bci/rapport.php">Rapport</a>
                </div>
            </div>
        </nav>
    </header>

    <script>
        document.querySelectorAll('.dropdown-content a').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                if (href !== "#") {
                    window.location.href = href;
                }
            });
        });
    </script>
</body>
</html>
