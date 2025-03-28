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
