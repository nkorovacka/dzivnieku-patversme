# dzivnieku-patversme, sobrid instrukcija meitenem kā uzstādīt
Majaslapa dzivnieku patversmei
1. solis: Nepieciešamo rīku uzstādīšana
Lai strādātu ar šo projektu, katrai no jums jāpārliecinās, ka viņas datorā ir instalēti šie rīki:

Git: Versiju kontroles sistēma.

Composer: PHP atkarību pārvaldnieks.

XAMPP:lokālais serveris ar PHP un MySQL.

Kā pārbaudīt, vai rīki ir instalēti:

Atveriet komandrindu (Windows) vai termināli (macOS) un ierakstiet šīs komandas. Ja parādās versijas numurs, rīks ir instalēts.

Bash

git --version
composer -v

Ja kāds no rīkiem nav instalēts, lūk, saites, kur tos lejupielādēt:
Git: https://git-scm.com/downloads

Composer: https://getcomposer.org/download/

XAMPP: https://www.apachefriends.org/index.html

2. solis: Projekta klonēšana no GitHub
Pēc tam, kad visi rīki ir uzstādīti, katrai no jums jāklonē projekts uz sava datora.

Atveriet komandrindu (vai termināli).

Dodies uz mapi, kurā vēlies glabāt projektu (piemēram, C:\xampp\htdocs\ Windowsā vai ~/ macOSā).

Ieraksti klonēšanas komandu
Bash

git clone https://github.com/nkorovacka/dzivnieku-patversme.git


Kad tiks lūgts ievadīt paroli, ievadi GitHub Personal Access Token, nevis savu GitHub konta paroli.
3. solis: Lokālās datubāzes izveide
Lai projekts darbotos, katrai no jums jāizveido sava datubāze savā datorā.
Startējiet XAMPP. Startējiet Apache un MySQL.

Atveriet phpMyAdmin. Savā pārlūkā atveriet http://localhost/phpmyadmin

Izveidojiet jaunu datubāzi. Kreisajā pusē nospiediet New (Jauns), nosauciet to par dzivnieku_patversme un nospiediet Create (Izveidot).

Izpildiet SQL kodu. Atveriet failu database.sql, nokopējiet visu saturu, pārlūkā atveriet cilni SQL un izpildiet kodu. Tas izveidos visas nepieciešamās tabulas.

4. solis: Savienojuma konfigurācija
Šis ir pēdējais solis, lai projekts būtu gatavs darbam.

Atrodiet failu .env.example projekta mapē un pārdēvē to par .env.

Atveriet .env failu jebkurā teksta redaktorā un ievadiet savus datus. Ja izmantojat XAMPP, visticamāk, lietotājvārds būs root un parole būs tukša.

DB_SERVER=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=dzivnieku_patversme


Saglabājiet izmaiņas.
5. solis: Atkarību instalēšana
Atveriet komandrindu (vai termināli), dodies uz projekta mapi un palaidiet šo komandu, lai instalētu Composer atkarības.
Bash

composer install


Pēc šīs komandas izpildīšanas tiks izveidota mape vendor, kas saturēs visus projekta failus. Tagad viss ir gatavs darbam!
Kā notiks kopdarbs ar datubāzi?
Šis ir pats svarīgākais princips: jūs visas strādājat ar vienu koda bāzi, bet ar atsevišķām, lokālām datubāzēm.
Kods (.php faili, .css faili u.c.) tiek koplietots, izmantojot Git un GitHub. Man ir galvenā kontrole pār to, kāds kods tiek pievienots, un man ir jāapstiprina Pull Request no jums.

Datubāzes struktūra (tabulas, kolonnas) arī tiek koplietota caur database.sql failu. Ja kāda no jums pievieno jaunu kolonnu, viņa atjaunina šo failu, un pārējām jāsinhronizē sava datubāze.

Datubāzes dati (piemēram, pievienotie dzīvnieki) paliek katras datorā. Tas nozīmē, ka katra no jums var veikt savus eksperimentus un testa pievienojumus, neietekmējot citu meiteņu darbu.
