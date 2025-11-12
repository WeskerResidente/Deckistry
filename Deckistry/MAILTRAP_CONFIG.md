# 📧 Configuration Mailtrap - Guide Complet

## 🎯 Deux types de comptes Mailtrap

Mailtrap propose deux services différents :

### 1️⃣ **Sandbox** (Recommandé pour développement)
- Pour tester les emails en développement
- Les emails ne sont JAMAIS envoyés aux vrais destinataires
- Vous les voyez dans votre inbox Mailtrap

### 2️⃣ **Transactional Stream** (Pour production)
- Envoie de VRAIS emails aux destinataires
- Nécessite un API Token

---

## 📝 Comment obtenir vos identifiants

### Pour Sandbox (DEV) :

1. Allez sur https://mailtrap.io/
2. Cliquez sur **"Email Testing"** (ou "Sandbox")
3. Sélectionnez ou créez un **Inbox**
4. Cliquez sur **"SMTP Settings"**
5. Choisissez l'intégration **"Symfony"** ou **"SMTP"**
6. Copiez les identifiants :
   - **Host** : `sandbox.smtp.mailtrap.io`
   - **Port** : `2525`
   - **Username** : (un code unique, genre `488d9ca9027f63`)
   - **Password** : (un autre code unique, genre `f464896b929d38`)

**Configuration dans `.env` :**
```env
MAILER_DSN=smtp://USERNAME:PASSWORD@sandbox.smtp.mailtrap.io:2525
```

**Exemple :**
```env
MAILER_DSN=smtp://488d9ca9027f63:f464896b929d38@sandbox.smtp.mailtrap.io:2525
```

---

### Pour Transactional Stream (PRODUCTION) :

1. Allez sur https://mailtrap.io/
2. Cliquez sur **"Email Sending"** (ou "Email API/SMTP")
3. Sélectionnez votre **Stream**
4. Allez dans **"SMTP/API Settings"**
5. Générez un **API Token** si vous n'en avez pas
6. Utilisez :
   - **Host** : `live.smtp.mailtrap.io`
   - **Port** : `587` (recommandé) ou `2525`
   - **Username** : `api` OU `smtp@mailtrap.io`
   - **Password** : Votre **API Token**

**Configuration dans `.env` :**
```env
MAILER_DSN=smtp://api:YOUR_API_TOKEN@live.smtp.mailtrap.io:587
```

---

## 🧪 Tester votre configuration

### Méthode 1 : Route de test
Accédez à :
```
http://127.0.0.1:8000/debug/test-email/votre@email.com
```

### Méthode 2 : Console Symfony
```bash
php bin/console cache:clear
```

Puis testez le formulaire de mot de passe oublié :
```
http://127.0.0.1:8000/mot-de-passe-oublie
```

### Méthode 3 : Vérifier les logs
```bash
tail -f var/log/dev.log
```

---

## ✅ Configuration actuelle

Votre fichier `.env` est configuré pour **Sandbox** :
```env
MAILER_DSN=smtp://488d9ca9027f63:f464896b929d38@sandbox.smtp.mailtrap.io:2525
```

### Si ça ne fonctionne pas :

1. **Vérifiez vos identifiants Mailtrap** :
   - Inbox → SMTP Settings → Vérifiez username/password

2. **Essayez l'alternative** :
   ```env
   MAILER_DSN=smtp://488d9ca9027f63:f464896b929d38@smtp2.mailtrap.io:2525
   ```

3. **Mode null (sans email)** :
   ```env
   MAILER_DSN=null://null
   ```
   Les emails seront visibles dans le **Symfony Profiler** (barre de debug en bas de page).

---

## 🚀 Test rapide MAINTENANT

1. Votre configuration actuelle devrait fonctionner
2. Allez sur : **http://127.0.0.1:8000/debug/test-email/test@example.com**
3. Si succès → Vérifiez votre inbox sur https://mailtrap.io/
4. Si erreur → Lisez le message d'erreur affiché

---

## 🔧 En cas de problème

### Erreur "Authentication failed"
→ Vos identifiants sont incorrects. Allez sur Mailtrap et copiez à nouveau username/password

### Erreur "Connection timeout"
→ Vérifiez votre connexion internet ou essayez un autre port (2525, 587, 465)

### Aucune erreur mais pas d'email
→ Vérifiez le bon inbox sur Mailtrap (Demo inbox par défaut)

### Vous voulez juste que ça marche ?
→ Utilisez la route de debug sans email :
```
http://127.0.0.1:8000/debug/reset-link/normanbelaid@gmail.com
```

