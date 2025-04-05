## [1.0.5] - 2025-04-03  
### Ajouté  
- Dans index.php ajout du tableau des catégories d'armes
- Dans intérrogatoire ajout d'une barre de chercher pour affichier les intérrogatoire qui ont le mot clés recherche.
- Dans index.php ajout du lien pour accéder a la page tenu
- Ajout d'une page pour voir les tenues par grade
- Ajout d'un bouton export dans la partit intérrogatoire, pour exporter l'intérrogatoire dans un google doc
- Ajout d'une pages des montée en grades 
- Ajout de la partit SA pour afficher la liste des effectif du BCSO, avec les unité, grade etc...
- Ajout d'une page details pour afficher le details de chaque agent
- Ajout d'une page d'ajout pour "Numeros de téléphone", "Photo d'identiter" et "Numéros de série"
- Ajout d'une page statistique pour voir le total d'amende mis par semaine.

### Modifié  
- Suppression de la table "Effectif"
- Correction de la page ajout et modifier dans "rapport" pour faire la recherche d'agent dans la table "sa_effectif"

### Corrigé  
- Bug où il étais possible de crée de doublons de casier, maintanant la vérification de fait bien (nom, prenom, date de naissance)
- Meilleur selection d'individue dans l'ajout de plainte + possibilité de changer le numéros de téléphone
- Permissions le mot étais "BCSO" pas "BCO"

## [1.0.4] - 2025-04-03  
### Ajouté  
- Création de la table `droit_miranda` pour enregistrer individuellement les droits lus (droit + heure).
- Possibilité d’ajouter dynamiquement plusieurs droits Miranda via l’interface utilisateur.
- Affichage des droits Miranda existants dans la page de modification d’un rapport.
- Ajout d’un champ “Heure de privation de liberté” dans les rapports.
- Affichage du numéro de série des armes saisies dans les logs Discord, au format `(N°: XXXXX)`.

### Modifié  
- Refonte du système de gestion des droits Miranda (ancien champ remplacé par des lignes en BDD).
- Mise à jour de la page `ajout.php` pour utiliser le nouveau format de saisie des droits Miranda (liste déroulante + heure).
- Mise à jour de la page `modifier.php` pour refléter la nouvelle logique (affichage dynamique et suppression possible).
- Mise à jour de la page `details.php` (présumée) pour afficher les droits Miranda associés au rapport.
- Les agents et les individus sont maintenant gérés via des champs dynamiques avec `Set()` JS et boutons de suppression.
- Amélioration de l'affichage du motif sélectionné avec un infobulle contenant l’article et les détails.
- Envoi Discord ajusté pour inclure les nouvelles données lors de la création et modification de rapports.
- Envoi Discord des saisies enrichi pour inclure le numéro de série des armes si renseigné.

### Corrigé  
- Bug où les droits Miranda n’étaient pas bien enregistrés (ancien format écrasé ou ignoré).
- Problème de doublons ou suppression incomplète des agents / individus dans `modifier.php`.

### Supprimé  
- Suppression des anciens champs `demandes_droits` et `heure_droits` devenus obsolètes.

## [1.0.3] - 2025-03-30  
### Ajouté  
- Vérification dynamique des numéros de série lors de la sortie des saisies (affichage "✅ Existe" ou "❌ Inexistant")  
- Suppression automatique des armes dans la BDD `s_armes` si le numéro de série est confirmé lors d'une sortie  
- Recherche de saisies par numéro de série dans la page de listing  
- Lien direct vers les armes filtrées via leur nom  
- Affichage du numéro de série dans les objets saisis (rapports, casiers, groupes)  

### Modifié  
- Refonte du design de la sortie de saisie pour correspondre au style de l’ajout de saisie  
- Séparation des rôles BCO, DOJ et Command Staff pour restreindre l'accès à certaines pages (ex. : `sortie.php`, `code_penal.php`, `details.php`)  
- Meilleure gestion des accès utilisateur via `$_SESSION` et `hasRole()`  

### Corrigé  
- Correction d’un bug empêchant la recherche correcte par numéro de série  
- Affichage nettoyé du statut de numéro de série (plus d’alignement ou d’espace cassé entre les boutons)

## [1.0.2] - 2025-03-28
### Ajouté
- Système complet de gestion des interrogatoires BCI (ajout, modification, suppression, détails, listing)
- Upload et affichage de fichiers médias liés aux interrogatoires
- Attribution automatique de l’agent interrogateur via le pseudo Discord
- Affichage des interrogatoires avec lien vers le casier de l’individu

### Corrigé
- Correction de l’heure d’interrogatoire grâce à `created_at`
- Suppression automatique des fichiers liés lors de la suppression d’un interrogatoire
- Résolution de l’erreur sur la table `agents` (désormais inutile)

---

## [1.0.1] - 2025-03-28
### Modifié
- Amélioration de l’envoi des messages Discord via webhook
