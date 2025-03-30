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
