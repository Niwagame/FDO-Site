<?php
session_start();
require_once 'config.php'; // Assurez-vous que le chemin est correct
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Gestion des Casier BCSO</title>
    <link rel="stylesheet" href="../css/accueil.css">
</head>
<body>

<div class="header-bar">
    <div class="title">Gestion des Casier BCSO</div>
    <div class="nav-links">
        <a href="#code-radio">Code Radio</a>
        <a href="#escouade">Escouade</a>
        <a href="#juridiction">Juridiction</a>
        <a href="#droit">Droit</a>
        <a href="/car.php">Véhicule</a>
    </div>

    <!-- Bouton Login avec redirection conditionnelle -->
    <a href="<?php echo isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true ? '/tablette/casier/liste.php' : '/auth/login.php'; ?>" class="login-btn">Login</a>
</div>


<div class="container">
    <div id="code-radio" class="section-title">Code Radio</div>
    <table class="table-style">
        <tr><th>Code</th><th>Description</th><th>Code</th><th>Description</th></tr>
        <tr><td>10-0</td><td>Disparu</td><td class="red">10-80</td><td>Course à pied</td></tr>
        <tr><td>10-4</td><td>Affirmatif</td><td class="red">10-91</td><td>Braquages entrepot / Banques / bijouterie / Train</td></tr>
        <tr><td>10-5</td><td>Négatif</td><td>10-98</td><td>Retour en patrouille</td></tr>
        <tr><td>10-7</td><td>Pause de Service</td><td>10-420</td><td>Un incendie vient de se déclarer</td></tr>
        <tr><td>10-8</td><td>Prise de service</td><td>10-4012</td><td>Code binouze</td></tr>
        <tr><td>10-9</td><td>Répéter le dernier call</td><td class="green">CODE 0</td><td>Situations inexpliquable</td></tr>
        <tr><td>10-10</td><td>Fin de service</td><td class="green">CODE 1</td><td>En appel avec les hautes autorités</td></tr>
        <tr><td>10-12</td><td>En attente de dispatch</td><td class="yellow">CODE 2</td><td>Gyrophare sans avertisseur sonnore</td></tr>
        <tr><td>10-15</td><td>Transport de suspect</td><td class="yellow">CODE 3</td><td>Gyrophare avec avertisseur sonnore</td></tr>
        <tr><td>10-19</td><td>Retour au poste</td><td class="yellow">CODE 4</td><td>RAS / Situation stable</td></tr>
        <tr><td>10-20</td><td>Demande de position</td><td class="yellow">CODE 5</td><td>Situations Compliquée</td></tr>
        <tr><td>10-21</td><td>En route vers ...</td><td class="yellow">CODE 6</td><td>Situation Grave 10-35</td></tr>
        <tr><td>10-22</td><td>Ignorer le dernier call</td><td class="yellow">CODE 7</td><td>En surveillance</td></tr>
        <tr><td>10-27</td><td>Retour au poste avec un suspect</td><td class="yellow">CODE 8</td><td>Arrivée sur scene</td></tr>
        <tr><td class="red">10-31</td><td>Coups de feu</td><td class="yellow">CODE 9</td><td>Passage On Foot</td></tr>
        <tr><td class="red">10-35</td><td>Demande de renfort</td><td class="yellow">CODE 10</td><td>Off-Radio Procédure</td></tr>
        <tr><td>10-38</td><td>Controle routier</td><td class="yellow">CODE 11</td><td>Mise en place de Contrôle Frontière</td></tr>
        <tr><td class="red">10-40</td><td>Braquage de supérette/ATM/Coffre fort</td><td class="red">CODE 12</td><td>Alerte a la bombe</td></tr>
        <tr><td>10-41</td><td>Debut de patrouille</td><td class="red">CODE Noir</td><td>Agent à terre, pris en otage. Tous les agents sont demandés</td></tr>
        <tr><td>10-50</td><td>Accident</td></tr>
        <tr><td>10-52</td><td>Demande d'EMS - en attente de medecin</td></tr>
        <tr><td>10-56</td><td>Refus d'obtemperer / Course poursuite en cours</td></tr>
        <tr><td>10-57</td><td>Largage</td></tr>
        <tr><td>10-59</td><td>Vol de véhicule Luxueux</td></tr>
        <tr><td>10-60</td><td>Vente de drogue</td></tr>
        <tr><td>10-69</td><td>Cambriolage en cours</td></tr>
    </table>

    <div id="escouade" class="section-title">Équipe et Code d'escouade</div>
    <table class="table-style">
        <tr><th>Escouade</th><th>Rôle</th><th>Escouade</th><th>Rôle</th></tr>
        <tr><td>Lincoln XXX</td><td>Patrouille seul</td><td>Condor XXX</td><td>ASU</td></tr>
        <tr><td>Adam XXX</td><td>Patrouille à 2</td><td>Dolphin XXX</td><td>Marine</td></tr>
        <tr><td>Tango XXX</td><td>Patrouille à 3</td><td>Mary XXX</td><td>Moto</td></tr>
        <tr><td>X-Ray XXX</td><td>Patrouille à 4</td><td>HSU XXX</td><td>VIR</td></tr>
        <tr><td>Centrale</td><td>Chefs des patrouilles</td><td>Porsche XXX</td><td>Bike patrol</td></tr>
        <tr><td>Puma</td><td>Escouade SERT TERRESTRE</td><td>10-7</td><td>En pause</td></tr>
        <tr><td>Lynx</td><td>Escouade SERT TERRESTRE</td><td>10-12</td><td>En attente de dispatch</td></tr>
        <tr><td>Shark</td><td>Escouade SERT Maritime</td><td>Code P</td><td>Off radio paperasse</td></tr>
        <tr><td>Eagle</td><td>Escouade SERT Aérien</td><td>Au poste</td><td>Stand by au poste</td></tr>
        <tr><td>Phantom XXX</td><td>Escouade BCI</td><td>PPA</td><td>En PPA</td></tr>
        <tr><td>Recrutement</td><td>En Recrutement</td><td>Radio BCSO</td><td>4</td></tr>
        <tr><td>Formation</td><td>En formation</td><td>Radio LSPD</td><td>1</td></tr>
        <tr><td>RDV</td><td>En RDV</td><td>EMS</td><td>Aux EMS</td></tr>
    </table>
</div>

<div id="juridiction" class="section-title">Juridiction</div>
<div class="juridiction-content">
    <p>La juridiction couvre les zones frontalières telles que montrées ci-dessous :</p>
    <img src="/assets/site/carte_frontiere.png" alt="Carte de la Juridiction BCSO">
</div>

<div id="droit" class="section-title">Droits Miranda</div>
<div class="droit-content">
    <p><strong>Monsieur/Madame (X),</strong></p>
    <p>
        Nous sommes le [jour] [mois] [années], il est actuellement [heure] H et [minutes] Min.
    </p>
    <p>
        Vous êtes à ce jour placé en état d'arrestation pour les faits suivants : 
        [Exemple : Braquage de Fleeca]
    </p>
    <p>
        A savoir que cette liste n'est pas exhaustive. 
    </p>
    <p>
        Vous avez le droit de garder le silence ; si vous renoncez à ce droit, tout ce que vous direz pourra être et sera retenu contre vous devant une cour de justice. Durant chaque interrogatoire, vous pouvez décider à n'importe quel moment d'exercer les droits suivants, de ne répondre à aucune question ou de ne faire aucune déposition.
    </p>
    <p>
        Vous avez le droit à un avocat et d'avoir un avocat présent lors de votre interrogatoire. Si vous n'en avez pas les moyens, un avocat vous sera commis d’office.
    </p>
    <p>
        Vous avez également le droit à une assistance médicale, un interprète, ainsi qu'à de l'eau, de la nourriture et de contacter un proche ou un employeur.
    </p>
    <p><strong>Avez-vous bien compris vos droits, Monsieur/Madame ?</strong></p>
    <p><strong>Désirez-vous utiliser un de ces droits ?</strong></p>
    
    <div class="important-note">
        ⬆️ CES DROITS DOIVENT IMPÉRATIVEMENT ÊTRE DITS LORS DE TOUTES ARRESTATIONS ⬆️
    </div>

    <div class="procedure-title">PROCÉDURES LORS D'UNE ARRESTATION</div>
    <ul class="procedure-list">
        <li>Placer devant le capot du véhicule de fonction</li>
        <li>Menottes + palpation de sécurité</li>
        <li>Retrait complet des armes, moyens de communication</li>
        <li>Prendre sa carte d'identité</li>
        <li>Vérifier les besoins : Médecins, avocats, nourriture</li>
        <li>Le fouiller complètement à l'arrivée au poste de police (retirer les plus petites choses -> argent, drogue, pochons...)</li>
        <li>Lire les droits Miranda</li>
        <li>Remplir le casier judiciaire</li>
    </ul>
</div>


<footer>
    <div class="footer-text">© 2025 BCSO (1.0.4) - Tous droits réservés - Niwagame</div>
</footer>

</body>
</html>
