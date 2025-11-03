# XShow File Manager - Configurare și Funcționalitate

## Funcționalitate Principală

XShow File Manager gestionează **folderul home** (directorul părinte al aplicației), permițând:
- ✅ Vizualizarea și gestionarea tuturor fișierelor și folderelor din directorul home
- ✅ Navigarea prin subfoldere
- ✅ Upload, creare, editare și ștergere fișiere/foldere
- ✅ Căutare recursivă în toate subdirectoarele

## Protecție Integrată

Aplicația **protejează automat** următoarele directoare/fișiere:
- 🔒 **xshow** - Folderul aplicației (NU poate fi accesat sau șters)
- 🔒 **.git** - Directorul Git
- 🔒 **.htaccess** - Fișiere de configurare
- 🔒 **node_modules** - Dependențe Node.js
- 🔒 **vendor** - Dependențe PHP

## Structura Directoarelor

```
home/                          # Directorul principal gestionat
├── xshow/                     # PROTEJAT - Aplicația XShow
│   ├── index.php
│   ├── view.php               # Script pentru afișare securizată fișiere
│   ├── config.php
│   └── ...
├── alte_foldere/              # Accesibile și gestionabile
├── fisiere.txt                # Accesibile și gestionabile
└── ...
```

## Funcții de Securitate

1. **Protecție împotriva ștergerii accidentale**: Folderul `xshow` nu poate fi accesat sau șters
2. **Izolare completă**: Nu se poate naviga în folderul aplicației
3. **Verificare path**: Toate căile sunt verificate pentru a preveni accesul neautorizat
4. **Afișare securizată**: Fișierele sunt servite prin `view.php` cu validare completă

## Cum Funcționează

### Vizualizare Fișiere
- Fișierele sunt afișate prin scriptul `view.php`
- Imagini, PDF-uri, video, audio → vizualizare în browser
- Alte tipuri de fișiere → download automat

### Navigare
- Click pe folder → intră în acel folder
- Breadcrumb (Home / folder1 / folder2) → navigare rapidă
- Folderul `xshow` nu apare în listare

### Editare
- Fișiere text (txt, md, html, css, js, php, json, xml, csv) pot fi editate direct
- Click pe ✏️ → deschide editorul
- Modificările sunt salvate instant

## Instalare și Utilizare

1. **Instalare**: Accesează `install.php`
2. **Login**: Folosește credentialele de admin create
3. **Gestionare**: Toate fișierele din `home` sunt disponibile (except `xshow`)

## Notă Importantă

⚠️ **Folderul `xshow` este invizibil și inaccesibil** din aplicație pentru a preveni:
- Ștergerea accidentală a aplicației
- Modificarea fișierelor critice
- Compromise de securitate

Dacă ai nevoie să modifici aplicația, fă-o direct prin FTP/SSH, nu prin interfața web.
