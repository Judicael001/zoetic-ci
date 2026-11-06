# Guide - Gestion du Logo du Site

## Description
Vous pouvez maintenant télécharger et gérer le logo de votre site ZoeTIC depuis la section Configuration de l'espace administrateur.

## Accès
1. Connectez-vous à l'espace administrateur
2. Cliquez sur **Configuration** dans le menu latéral (icône engrenage)
3. Accédez à la section **Logo du Site** en haut de la page

## Fonctionnalités

### 1. Télécharger un Logo
- **Glissez-déposez** votre fichier directement dans la zone prévue
- **Ou cliquez** pour parcourir et sélectionner un fichier
- Le système affiche automatiquement un **aperçu** avant le téléchargement
- Cliquez sur **"Enregistrer les modifications"** pour finaliser

### 2. Formats Acceptés
- PNG (recommandé pour la transparence)
- JPEG
- WebP
- SVG

### 3. Limite de Taille
- Maximum **5 Mo** par fichier

### 4. Affichage du Logo
- Le logo s'affiche automatiquement dans le **header du site**
- Remplace le logo par défaut (lettre "Z")
- S'adapte responsive sur mobile et desktop
- Maintient l'aspect ratio original

### 5. Supprimer le Logo
- Cliquez sur le bouton **"Supprimer"** (icône corbeille)
- Confirmez la suppression
- Le site revient au logo par défaut (lettre "Z")

## Conseils d'Optimisation

### Dimensions Recommandées
- Hauteur: 40-50px (le logo s'affichera à h-10)
- Ratio: Préférez 2:1 ou 3:1 (par exemple 150x50px)

### Optimisation des Images
- Compressez votre image avant le téléchargement
- Utilisez PNG si vous avez de la transparence
- Utilisez JPEG pour les images plus légères
- Pour des logos simples, SVG est idéal

### Bonnes Pratiques
1. **Testez le rendu** sur mobile et desktop
2. **Vérifiez la lisibilité** sur le fond blanc du header
3. **Utilisez un format** qui s'affiche nettement à petite taille
4. **Sauvegardez l'original** avant de compresser

## Dépannage

### Le logo ne s'affiche pas
1. Rafraîchissez la page avec Ctrl+F5
2. Videz le cache du navigateur
3. Vérifiez que le fichier n'a pas été supprimé manuellement

### L'upload échoue
1. Vérifiez la taille du fichier (max 5MB)
2. Vérifiez le format (PNG, JPEG, WebP, SVG)
3. Vérifiez les permissions du dossier `uploads/logos`

### Le logo s'affiche déformé
1. Utilisez un fichier avec un meilleur ratio d'aspect
2. Testez avec PNG au lieu de JPEG
3. Vérifiez que l'image n'est pas trop grande

## Détails Techniques

### Stockage
- Les logos sont stockés dans: `uploads/logos/`
- Noms de fichiers: Générés aléatoirement pour la sécurité
- Configuration: Enregistrée dans la base de données (table `settings`)

### Sécurité
- Validation MIME type stricte
- Limite de taille appliquée
- Suppression automatique de l'ancien logo
- Journalisation de toutes les actions

### Historique
- Chaque changement de logo est enregistré dans les logs d'activité
- Consultable dans **Logs d'activité** (section Équipe Admin)
