<?php
include 'config.php';
include 'includes/header.php';

$role_bco = $roles['bcso'] ?? null;
if (!isset($_SESSION['user_authenticated']) || !hasRole($role_bco)) {
    echo "<p style='color: red; text-align: center;'>Accès refusé.</p>";
    exit();
}
?>

<link rel="stylesheet" href="css/styles.css">
<style>
    .grades-container {
        max-width: 900px;
        margin: auto;
        padding: 30px;
        background-color: #1e1e1e;
        border-radius: 10px;
        font-family: 'Segoe UI', sans-serif;
        color: #f5f5f5;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
    }
    .grades-container img {
        display: block;
        margin: auto;
        width: 140px;
    }
    h1 {
        text-align: center;
        margin: 10px 0;
        font-size: 1.6em;
        color: #d6b25d;
    }
    hr.separator {
        border: none;
        border-top: 3px solid #d6b25d;
        margin: 30px 0;
    }
    .important {
        font-weight: bold;
        font-size: 1.05em;
    }
    .important p {
        margin: 5px 0;
    }
    .important .attention {
        color: #ff6666;
        font-weight: bold;
    }
    h2 {
        color: #d6b25d;
        border-bottom: 2px solid #444;
        padding-bottom: 5px;
        margin-top: 40px;
    }
    h3 {
        color: #f0ad4e;
        margin-bottom: 5px;
    }
    .section-title {
        color: #f5f5f5;
        font-weight: bold;
        text-decoration: underline;
        margin-top: 15px;
    }
    .confidential {
        color: #ff4d4d;
        font-weight: bold;
        font-size: 1.2em;
        text-align: center;
        margin-top: 30px;
    }
    .footer-note {
        font-size: 0.9em;
        color: #90ee90;
        text-align: center;
        margin-top: 40px;
    }
</style>


<div class="grades-container">
    <img src="assets/site/BCSO.png" alt="Logo BCSO">
    <h1>MONTÉES EN GRADE (Formation, Spécialisation, etc.)</h1>

    <hr class="separator">

    <div class="important">
        <p><u>Information importante :</u></p>
        <p>Dans ce document, vous trouverez toutes les possibilités que vous avez à votre grade.</p>
        <p class="attention">Attention :</p>
        <p>Si certaines choses ne sont pas ajoutées au grade au-dessus, c’est que c’est accessible depuis le précédent grade.</p>
        <p><strong>Exemple :</strong> l’Unité Park Ranger est marquée dans les Informations complémentaires de Deputy 2 mais pas Deputy 3 : c’est accessible aux Deputy 3 également mais inutile à renoter.</p>
    </div>

    <hr class="separator">

    <h2>🔹 DEPUTY</h2>

    <h3>Junior</h3>
    <p><strong>Durée :</strong> 3j minimum de service</p>
    <p><strong>Formation :</strong> Test Junior → Deputy I</p>
    <p><strong>Équipement :</strong> Tazer / Matraque / Radio</p>

    <hr>

    <h3>Deputy I</h3>
    <p><strong>Durée :</strong> 1 semaine</p>
    <p><strong>Formation :</strong> Formation de base + Lincoln, PPA1</p>
    <p><strong>Équipement :</strong> Glock / Tazer / Matraque / Radio</p>

    <hr>

    <h3>Deputy II</h3>
    <p class="section-title">Informations générales</p>
    <p><strong>Durée :</strong> 2 semaines</p>
    <p><strong>Formation :</strong> Se former sur une spécialité CNU</p>
    <p><strong>Syndicat :</strong> Peut se syndiquer</p>
    <p><strong>Équipement :</strong> Glock / Tazer / Matraque / Radio</p>

    <p class="section-title">Informations complémentaires</p>
    <p><strong>Sheriff Academy :</strong> Peut rejoindre la SA</p>
    <p><strong>Divisions :</strong> Doit rejoindre la BCI ou le SERT ou demander à rester Patrol (voir CS)</p>
    <p><strong>Spécialisations :</strong> Négociation ou Terrain</p>
    <p><strong>Unités :</strong> Park Ranger, Traffic Unit (MARY et HIGH SPEED), Media Relation Unit</p>

    <hr>

    <h3>Deputy III</h3>
    <p class="section-title">Informations générales</p>
    <p><strong>Durée :</strong> 2,5 semaines</p>
    <p><strong>Formation :</strong> Se former sur l’autre spécialité CNU</p>
    <p><strong>Équipement :</strong> Glock / Tazer / Matraque / Radio / MP5</p>

    <p class="section-title">Informations complémentaires</p>
    <p><strong>Divisions :</strong> Peut être co-lead de division et/ou d’unités</p>
    <p><strong>Spécialisations :</strong> Celle non choisie précédemment</p>
    <p><strong>Unités :</strong> Traffic Unit (MARINE & AIR SUPPORT)</p>

    <hr>

    <h3>Senior Deputy</h3>
    <p class="section-title">Informations générales</p>
    <p><strong>Durée :</strong> 2,5 semaines</p>
    <p><strong>Formation :</strong> Centrale</p>
    <p><strong>Équipement :</strong> Glock / Tazer / Matraque / Radio / Remington ou G36C / Bean Bag</p>

    <p class="section-title">Informations complémentaires</p>
    <p><strong>Divisions :</strong> Peut être lead de division et/ou d’unités</p>
    <p><strong>Formateur :</strong> Possibilité de devenir Formateur CNU</p>

    <hr>

    <h2 style="background-color:rgb(34, 33, 31); display: inline-block; padding: 6px 12px;">SUPERVISOR TEAM</h2>
    <p class="confidential">INFORMATIONS CONFIDENTIELLES</p>

    <div class="footer-note">
        <p>NB : Passer des formations, vous spécialiser ou vous investir dans une division/unité est préférable en vue d'une promotion.</p>
        <p>Ce n’est pas sûr que vous allez monter si vous ne le méritez pas ou que vous n’êtes pas irréprochables.</p>
        <br>
        <p>NB 2 : Les durées sont indicatives, vous monterez probablement plus vite si vous le méritez.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
