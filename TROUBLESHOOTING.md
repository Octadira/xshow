# 🔧 **Depanare Instalare XShow**

## 📋 **Pași pentru Diagnostic**

### 1. **Testează Componentele**
Accesează: `http://your-domain.com/xshow/test-install.php`

Acest script va verifica:
- ✅ Versiunea PHP
- ✅ Extensiile PHP necesare (PDO, SQLite, Session)
- ✅ Permisiunile directoarelor
- ✅ Scrierea fișierelor
- ✅ Funcționarea sesiunilor
- ✅ Conexiunea SQLite

### 2. **Verifică Erorile**
Dacă instalarea eșuează, verifică:
- **Logs PHP**: `/var/log/php/error.log` sau echivalentul
- **Permisiuni**: Directorul `config/` trebuie să fie writable (755)
- **Extensii PHP**: `pdo`, `pdo_sqlite` trebuie activate

### 3. **Probleme Comune**

#### **Butonul nu funcționează**
- Verifică consola browser-ului (F12) pentru erori JavaScript
- Asigură-te că toate câmpurile sunt completate corect
- Parola trebuie să aibă: 8+ caractere, majusculă, minusculă, număr, caracter special

#### **Redirect-ul nu funcționează**
- Verifică că `index.php` există în același director
- Pe unele servere, redirect-ul relativ nu funcționează - încearcă calea absolută

#### **Database error**
- Dacă SQLite nu funcționează, aplicația va folosi fișiere JSON
- Verifică că directorul `config/` are permisiuni de scriere

### 4. **Soluții Rapide**

#### **Pentru permisiuni**
```bash
chmod 755 /path/to/xshow/config
chmod 644 /path/to/xshow/config.php
```

#### **Pentru extensii PHP lipsă**
```bash
# Ubuntu/Debian
sudo apt install php-sqlite3

# CentOS/RHEL
sudo yum install php-pdo php-sqlite3
```

#### **Test manual**
Dacă test-install.php arată că totul e OK, încearcă să instalezi manual:

1. Creează fișierul `config/settings.php`:
```php
<?php
return [
    'installed_at' => '2024-01-01 12:00:00',
    'users' => [
        'admin' => '$2y$10$...' // parola hash-uită
    ]
];
```

2. Accesează direct `index.php`

### 5. **Debug Mode**
Pentru mai multe detalii, adaugă la începutul `install.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## 🚀 **După Instalare**
- Șterge `test-install.php` pentru securitate
- Verifică că poți accesa aplicația
- Testează upload-ul fișierelor și crearea folderelor

**Rulează test-install.php și spune-mi ce rezultate obții!** 🔍