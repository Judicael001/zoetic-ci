# CAS D'UTILISATION - Gestion du Logo

## 👤 Acteur: Administrateur du Site

---

## 📌 Cas d'Utilisation 1: Télécharger un Logo Initial

### Scénario: Premier Logo
1. Admin accède à Configuration → Logo du Site
2. Voit "Aucun logo téléchargé"
3. Glisse-dépose une image PNG (150x50px)
4. Aperçu s'affiche automatiquement
5. Clique "Enregistrer les modifications"
6. Logo téléchargé et sauvegardé
7. Visite le site → Le logo s'affiche dans le header
8. Action enregistrée dans les logs

### Résultat
✅ Logo du site changé
✅ Affichage en temps réel
✅ Ancien "Z" remplacé

---

## 📌 Cas d'Utilisation 2: Remplacer un Logo Existant

### Scénario: Logo Actuel + Nouveau
1. Admin voit le logo actuel affiché
2. Clique sur la zone d'upload
3. Sélectionne un nouveau logo WebP
4. Aperçu du nouveau logo s'affiche
5. Clique "Enregistrer"
6. Ancien logo supprimé automatiquement
7. Nouveau logo affiché sur le site
8. Ancien fichier supprimé du serveur
9. Action enregistrée

### Résultat
✅ Logo remplacé sans fichiers orphelins
✅ Ancien logo complètement supprimé
✅ Historique enregistré

---

## 📌 Cas d'Utilisation 3: Supprimer le Logo

### Scénario: Revenir au Logo par Défaut
1. Admin voit le logo actuel
2. Clique le bouton "Supprimer" (poubelle rouge)
3. Confirme "Êtes-vous sûr?"
4. Logo supprimé du serveur
5. Supprimé de la base de données
6. Logo "Z" par défaut réapparaît sur le site
7. Confirmation affichée
8. Action enregistrée

### Résultat
✅ Logo entièrement supprimé
✅ Retour au fallback
✅ Aucun fichier orphelin

---

## 📌 Cas d'Utilisation 4: Validation du Format

### Scénario: Fichier Invalide
1. Admin tente d'upload un PDF
2. Système détecte le type MIME
3. Message d'erreur: "Format non autorisé"
4. Aperçu n'apparaît pas
5. Admin ne peut pas télécharger

### Résultat
✅ Protection contre les mauvais formats
✅ Feedback utilisateur clair

---

## 📌 Cas d'Utilisation 5: Limite de Taille

### Scénario: Fichier Trop Volumineux
1. Admin upload une image de 10MB
2. Système détecte le dépassement
3. Message: "Taille dépasse 5MB"
4. Upload bloqué

### Résultat
✅ Limitation de taille appliquée
✅ Protection serveur

---

## 📌 Cas d'Utilisation 6: Drag & Drop

### Scénario: Interface Intuitive
1. Admin a un logo sur son bureau
2. Va à Configuration → Logo
3. Glisse l'image dans la zone pointillée
4. Aperçu s'affiche immédiatement
5. Enregistre

### Résultat
✅ UX améliorée
✅ Adoption rapide

---

## 📌 Cas d'Utilisation 7: Affichage du Logo

### Scénario: Visite du Site
1. Visiteur accède au site
2. Header affiche le logo personnalisé
3. Logo s'affiche correctement sur:
   - Desktop (full-size)
   - Tablette (responsive)
   - Mobile (adapté)

### Résultat
✅ Logo visible partout
✅ Design cohérent

---

## 📌 Cas d'Utilisation 8: Journalisation

### Scénario: Audit des Modifications
1. Admin upload un logo
2. Action enregistrée dans logs:
   - Qui: Admin ID + Nom
   - Quand: Timestamp
   - Quoi: "Changement du logo du site"
   - Où: IP address
3. Super Admin consulte les logs d'activité
4. Voit l'historique complet des changements

### Résultat
✅ Audit trail complet
✅ Responsabilité

---

## 🔄 Flux Complet

```
┌─────────────────┐
│  Admin Login    │
└────────┬────────┘
         │
         v
┌──────────────────────┐
│ Configuration Page   │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│ Section Logo du Site │
└────────┬─────────────┘
         │
    ┌────┴────┐
    │          │
    v          v
┌─────────┐  ┌──────────┐
│  Upload │  │ Supprimer│
└────┬────┘  └────┬─────┘
     │            │
     v            v
┌──────────────────┐
│ Enregistrer      │
└────┬─────────────┘
     │
    ┌┴─────────────────┐
    │                  │
    v                  v
┌─────────────┐  ┌─────────────┐
│ Logo Saved  │  │ Logo Deleted│
└────┬────────┘  └────┬────────┘
     │                │
     v                v
┌────────────────────────────┐
│ Logs Admin Activities      │
│ (Journalisation)           │
└───────┬────────────────────┘
        │
        v
┌────────────────────┐
│ Site Homepage      │
│ Logo Displayed     │
└────────────────────┘
```

---

## 🎯 Objectifs Atteints

✅ Admin peut gérer le logo facilement
✅ Aucune connaissance technique requise
✅ Upload sécurisé et validé
✅ Affichage automatique sur le site
✅ Historique complet gardé
✅ Possibilité de revenir au défaut
✅ UX intuitive et responsive

---

## 📊 Statistiques

- **Formats Support:** 4 (PNG, JPEG, WebP, SVG)
- **Taille Max:** 5MB
- **Validations:** 3 (MIME, Size, Upload)
- **Actions Journalisées:** 2 (Upload, Delete)
- **Fallback:** Logo "Z" par défaut

---

**Status:** ✅ Tous les cas d'utilisation supportés
**Version:** 1.0
**Production:** Ready
