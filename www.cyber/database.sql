CREATE DATABASE IF NOT EXISTS cyberguard_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cyberguard_db;
CREATE TABLE IF NOT EXISTS cyber_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255),
    email VARCHAR(100),
    total_xp INT DEFAULT 0,
    level INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    INDEX idx_xp (total_xp),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE IF NOT EXISTS cyber_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50),
    color VARCHAR(20),
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert categories
INSERT INTO cyber_categories (name, icon, color, description) VALUES
('Phishing', '🎣', '#3b82f6', 'Learn to detect and prevent phishing attacks'),
('Passwords', '🔐', '#10b981', 'Master password security and best practices'),
('Malware', '🦠', '#ef4444', 'Understand malware threats and protection'),
('Social Engineering', '🎭', '#f59e0b', 'Recognize manipulation tactics and defend against them'),
('Network Security', '🌐', '#8b5cf6', 'Secure networks and understand vulnerabilities'),
('Kriptografija i Enkripcija', '🔐', '#8b5cf6', 'Učite o kriptografiji, enkripciji i digitalnim potpisima'),
('VPN i Privatnost', '🌐', '#6366f1', 'Master VPN tehnologije i zaštitu privatnosti'),
('Digitalna Sigurnost', '🔑', '#0284c7', 'Osigurajte svoje digitalne identitete i podatke'),
('E-mail Sigurnost', '📧', '#0284c7', 'Zaštita od phishing i e-mail prevara'),
('Blockchain i Kripto', '⛓', '#f59e0b', 'Razumijevanje blockchain tehnologije i kriptovaluta'),
('Aplikacijska Sigurnost (DevSecOps)', '💻', '#2563eb', 'Sigurno programiranje i DevOps prakse'),
('Fizička Sigurnost', '🛡️', '#dc2626', 'Zaštita fizičkih prostora i osoba'),
('Operacijske Sigurnosti', '🚨', '#dc2626', 'Sigurnost operacija i incident management'),
('Preventivne Mjere', '🛡️', '#4ade80', 'Proaktivne mjere zaštite od napada'),
('Forenzika', '🔍', '#fbbf24', 'Digitalna forenzika i analiza tragova');

-- Quiz questions table
CREATE TABLE IF NOT EXISTS cyber_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    xp_reward INT DEFAULT 10,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES cyber_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_difficulty (difficulty)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample questions
INSERT INTO cyber_questions (category_id, question, answer, difficulty, xp_reward) VALUES
-- Phishing questions (category_id = 1)
(1, 'Šta je phishing napad?', 'Phishing je vrsta cyber napada gdje napadač pokušava prevariti žrtvu da otkrije osjetljive informacije poput lozinki ili brojeva kreditnih kartica kroz lažne email poruke ili web stranice koje izgledaju kao legitimne institucije.', 'easy', 10),
(1, 'Kako prepoznati phishing email?', 'Obratite pažnju na: pogrešnu gramatiku i pravopis, sumnjive linkove koji ne odgovaraju domeni, neočekivane priloge, hitne zahtjeve za akciju, nepoznate email adrese pošiljaoca, generičke pozdrave umjesto vašeg imena.', 'medium', 15),
(1, 'Šta je spear phishing?', 'Spear phishing je ciljan phishing napad usmjeren na specifičnu osobu ili organizaciju. Napadači prikupljaju informacije o žrtvi i kreiraju personalizovane poruke koje izgledaju veoma uvjerljivo, što ih čini opasnijim od običnog phishinga.', 'hard', 20),
(1, 'Šta trebate učiniti ako primite sumnjiv email?', 'Ne klikćite na linkove niti otvarajte priloge. Provjerite email adresu pošiljaoca, kontaktirajte organizaciju direktno preko njihovog zvaničnog broja telefona ili web stranice, prijavite email kao spam i obrišite ga. Nikada ne odgovarajte na sumnjive emailove.', 'easy', 10),
(1, 'Koje su najčešće karakteristike phishing web stranica?', 'Phishing web stranice često imaju: neobične URL adrese sa pravopisnim greškama, loš dizajn ili kopiju legitimne stranice, zahtjeve za hitnim unošenjem ličnih podataka, nedostatak HTTPS sigurnosti, sumnjive pop-up prozore.', 'medium', 15),

-- Password questions (category_id = 2)
(2, 'Koliko znakova bi trebala imati sigurna lozinka?', 'Sigurna lozinka bi trebala imati najmanje 12-16 znakova, kombinaciju velikih i malih slova, brojeva i specijalnih znakova. Duže lozinke su eksponencijalno teže za probijanje nego kraće.', 'easy', 10),
(2, 'Šta je two-factor authentication (2FA)?', '2FA je dodatni sloj sigurnosti koji zahtijeva dva različita načina verifikacije identiteta - nešto što znate (lozinka) i nešto što imate (telefon sa kodom, biometrija). Ovo drastično povećava sigurnost jer napadač mora kompromitovati oba faktora.', 'medium', 15),
(2, 'Zašto ne bi trebalo koristiti istu lozinku na više accounta?', 'Ako jedan account bude kompromitovan u data brechu, napadači mogu koristiti tu istu lozinku da pristupe svim vašim drugim accountima. Ovo se naziva "credential stuffing" napad. Jedinstvene lozinke za svaki account izoluju rizik.', 'easy', 10),
(2, 'Šta je password manager i zašto ga koristiti?', 'Password manager je aplikacija koja sigurno skladišti sve vaše lozinke u enkriptovanoj bazi. Omogućava vam da koristite jake, jedinstvene lozinke za svaki account bez potrebe da ih pamtite. Primjeri su LastPass, 1Password, Bitwarden.', 'medium', 15),
(2, 'Šta je brute force napad na lozinku?', 'Brute force je metoda probijanja lozinke gdje napadač sistematski pokušava sve moguće kombinacije znakova dok ne pronađe ispravnu. Duže i kompleksnije lozinke eksponencijalno povećavaju vrijeme potrebno za ovakav napad.', 'hard', 20),

-- Malware questions (category_id = 3)
(3, 'Šta je ransomware?', 'Ransomware je vrsta malwarea koji enkriptuje podatke žrtve i zahtijeva otkupninu (obično u kriptovaluti) za dešifrovanje. Moderne verzije ransomwarea često kradu podatke prije enkriptovanja, prijetnjom objavljivanja osjetljivih informacija.', 'easy', 10),
(3, 'Kako se zaštititi od malwarea?', 'Koristite ažuran antivirus softver, redovno ažurirajte operativni sistem i sve aplikacije, ne otvarajte sumnjive priloge ili linkove, pravite redovne backup-e važnih podataka, koristite firewall, izbjegavajte preuzimanje softvera sa nepouzdanih izvora.', 'medium', 15),
(3, 'Šta je trojan malware?', 'Trojan je malware koji se maskira kao legitiman softver kako bi prevario korisnika da ga instalira. Nakon instalacije, može otvoriti "backdoor" za daljinski pristup, krasti podatke, instalirati dodatni malware ili oštetiti sistem.', 'easy', 10),
(3, 'Koja je razlika između virusa i worma?', 'Virus se mora priključiti postojećem programu i zahtijeva korisničku akciju za širenje (npr. otvaranje fajla). Worm je samostalan program koji se automatski replcira i širi mrežom bez potrebe za korisničkom interakcijom.', 'hard', 20),
(3, 'Šta je spyware?', 'Spyware je malware koji tajno prati aktivnosti korisnika i krade lične informacije kao što su lozinke, brojevi kreditnih kartica, pretraživačka historija. Često dolazi sa besplatnim softverom i radi u pozadini bez znanja korisnika.', 'medium', 15),

-- Social Engineering questions (category_id = 4)
(4, 'Šta je social engineering?', 'Social engineering je manipulacija ljudi da otkriju povjerljive informacije ili izvrše određene akcije. Umjesto tehničkog hakovanja sistema, napadači "hakuju" ljudsku psihologiju koristeći prevare, manipulaciju, lažno predstavljanje.', 'easy', 10),
(4, 'Koje su najčešće social engineering tehnike?', 'Pretexting (lažno predstavljanje), baiting (mamac sa besplatnim stvarima), quid pro quo (ponuda usluge za informacije), tailgating (fizički pristup praćenjem autorizovane osobe), pretnja autoriteta (predstavljanje kao šef ili policija).', 'medium', 15),
(4, 'Šta je vishing?', 'Vishing (voice phishing) je social engineering napad preko telefonskog poziva. Napadač se predstavlja kao zaposlenik banke, tech support ili vladina agencija i pokušava izvući lične informacije ili novac od žrtve koristeći hitnost i pritisak.', 'medium', 15),
(4, 'Kako prepoznati social engineering napad?', 'Budite oprezni kod: hitnih zahtjeva za akciju, poziva koji traže lične ili finansijske informacije, ponuda koje zvuče previše dobro, neočekivanih zahtjeva za pristup ili informacije, pritiska da zaobiđete uobičajene sigurnosne procedure.', 'hard', 20),
(4, 'Šta je pretexting?', 'Pretexting je kreiranje fabriciranog scenarija (preteksta) kako bi se izvukle informacije od žrtve. Napadač se predstavlja kao neko od poverenja (IT support, HR, dobavljač) i koristi taj identitet da legitimiše svoj zahtjev za osjetljive podatke.', 'medium', 15),

-- Network Security questions (category_id = 5)
(5, 'Šta je firewall i kako funkcioniše?', 'Firewall je sigurnosni sistem koji kontroliše dolazni i odlazni mrežni saobraćaj na osnovu određenih sigurnosnih pravila. Djeluje kao barijera između pouzdane unutrašnje mreže i nepouzdanog interneta, blokirajući neautorizovan pristup.', 'easy', 10),
(5, 'Zašto je važno koristiti VPN na javnim WiFi mrežama?', 'Javne WiFi mreže su često nesigurne. VPN (Virtual Private Network) enkriptuje vaš internet saobraćaj i skriva vašu IP adresu, štiteći vas od presretanja podataka, man-in-the-middle napada i praćenja vaših online aktivnosti.', 'medium', 15),
(5, 'Šta je man-in-the-middle (MITM) napad?', 'MITM napad se dešava kada napadač tajno presreće i potencijalno mijenja komunikaciju između dvije strane koje misle da direktno komuniciraju. Napadač može ukrasti podatke poput lozinki ili kreditnih kartica koji prolaze kroz mrežu.', 'hard', 20),
(5, 'Šta znači HTTPS i zašto je važno?', 'HTTPS (Hypertext Transfer Protocol Secure) je sigurna verzija HTTP-a koja koristi SSL/TLS enkripciju za zaštitu podataka koji se prenose između browsera i web servera. Sprečava prisluškivanje i manipulaciju podacima tokom prijenosa.', 'easy', 10),
(5, 'Šta je DDoS napad?', 'Distributed Denial of Service (DDoS) napad pokušava učiniti online servis nedostupnim preopterećenjem servera, mreže ili aplikacije ogromnom količinom saobraćaja iz mnoštva kompromitovanih računara (botnet-a).', 'medium', 15);

-- Scenarios table
CREATE TABLE IF NOT EXISTS cyber_scenarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    xp_reward INT DEFAULT 50,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES cyber_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert scenarios
INSERT INTO cyber_scenarios (category_id, title, description, difficulty, xp_reward) VALUES
(1, 'Sumnjivi Email od Banke', 'Primili ste email koji navodno dolazi od vaše banke sa hitnim zahtjevom. Kako ćete postupiti?', 'easy', 50),
(2, 'Kompromitovana Lozinka', 'Saznali ste da je vaša lozinka procurila u data brechu. Potrebno je hitno djelovati.', 'medium', 75),
(3, 'Ransomware Napad', 'Vaš računar je zaražen ransomwareom koji je enkriptovao sve fajlove. Šta ćete učiniti?', 'hard', 100),
(4, 'Sumnjiv Telefonski Poziv', 'Primili ste poziv od nekoga ko tvrdi da je iz IT odjela i traži vašu lozinku.', 'easy', 50),
(5, 'Nezaštićena WiFi Mreža', 'Trebate hitno pristupiti bankovnom accountu, ali ste na javnoj WiFi mreži.', 'medium', 75);

-- Scenario steps table
CREATE TABLE IF NOT EXISTS cyber_scenario_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scenario_id INT NOT NULL,
    step_number INT NOT NULL,
    story_text TEXT NOT NULL,
    FOREIGN KEY (scenario_id) REFERENCES cyber_scenarios(id) ON DELETE CASCADE,
    INDEX idx_scenario (scenario_id, step_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert scenario steps for "Sumnjivi Email od Banke"
INSERT INTO cyber_scenario_steps (scenario_id, step_number, story_text) VALUES
(1, 1, 'Upravo ste otvorili svoj email i pronašli poruku koja izgleda kao da dolazi od vaše banke. Email ima zvanični logo banke i profesionalan izgled. U poruci piše: "HITNO: Vaš račun će biti suspendovan za 24 sata zbog sumnjive aktivnosti. Molimo kliknite ovdje da verifikujete svoj identitet."'),
(1, 2, 'Odlučili ste da detaljnije ispitate email prije bilo kakve akcije. Šta ćete provjeriti prvo?'),
(1, 3, 'Nakon pažljive analize, primjetili ste nekoliko sumljivih elemenata. Email adresa pošiljaoca je "support@bank-security.net" umjesto zvaničnog "@vašabanka.com" domena. Link u emailu vodi na stranicu koja ima sličan URL ali sa malim pravopisnim greškama.');

-- Scenario choices table
CREATE TABLE IF NOT EXISTS cyber_scenario_choices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    step_id INT NOT NULL,
    choice_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    feedback TEXT,
    next_step_id INT,
    FOREIGN KEY (step_id) REFERENCES cyber_scenario_steps(id) ON DELETE CASCADE,
    INDEX idx_step (step_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert choices for scenario 1, step 1
INSERT INTO cyber_scenario_choices (step_id, choice_text, is_correct, feedback, next_step_id) VALUES
(1, 'Odmah kliknuti na link i unijeti svoje podatke da izbjegnem suspenziju accounta', FALSE, 'Ovo je pogrešna odluka! Nikada ne klikćite na linkove u sumnjivim emailovima. Hitnost je klasična phishing taktika. Legitime banke nikada neće tražiti lične podatke preko emaila.', 2),
(1, 'Ignorisati email i kontaktirati banku direktno preko njihovog zvaničnog broja telefona', TRUE, 'Odlično! Ovo je najsigurnija opcija. Uvijek kontaktirajte instituciju direktno preko zvaničnih kanala kada dobijete sumnjive poruke. Banka će vam reći da li je email legitiman.', 2),
(1, 'Proslijediti email prijateljima da ih upozorim na prevaru', FALSE, 'Iako je dobra namjera upozoriti druge, prosljeđivanje phishing emaila može dovesti do širenja prijetnje. Bolje je prijaviti email kao spam banci i provajderu emaila, a zatim ga obrisati.', 2);

-- Insert choices for scenario 1, step 2
INSERT INTO cyber_scenario_choices (step_id, choice_text, is_correct, feedback, next_step_id) VALUES
(2, 'Provjeriti email adresu pošiljaoca', TRUE, 'Odličan prvi korak! Email adresa je često najbolji indikator phishing pokušaja. Legitiman email bi došao sa zvaničnog domena banke.', 3),
(2, 'Hover-ovati mišem preko linka (bez klikanja) da vidim gdje zapravo vodi', TRUE, 'Izvrsno! Ovo je sigurna metoda da vidite pravu destinaciju linka bez rizika. Phishing linkovi često vode na lažne stranice sa sličnim URL-om.', 3),
(2, 'Provjeriti gramatiku i pravopis u emailu', TRUE, 'Dobar potez! Phishing emailovi često sadrže gramatičke greške i pravopisne probleme jer dolaze od neizvornih govornika ili automatizovanih sistema.', 3);

-- Insert choices for scenario 1, step 3
INSERT INTO cyber_scenario_choices (step_id, choice_text, is_correct, feedback, next_step_id) VALUES
(3, 'Prijaviti email kao phishing banci i email provideru, zatim ga obrisati', TRUE, 'Savršeno! Ovo je ispravan završni korak. Prijavljivanje phishing pokušaja pomaže banci da zaštiti druge korisnike i pomaže u borbi protiv cyber kriminala.', NULL),
(3, 'Samo obrisati email i zaboraviti na njega', FALSE, 'Brisanje je dobro, ali prijava phishing pokušaja je također važna. To pomaže banci da upozori druge korisnike i preduzme akciju protiv napadača.', NULL),
(3, 'Odgovoriti na email govoreći im da znam da je prevara', FALSE, 'Nikada ne odgovarajte na phishing emailove! To potvrđuje napadačima da je vaša email adresa aktivna, što može dovesti do još više spam-a i phishing pokušaja.', NULL);

-- Training modules table
CREATE TABLE IF NOT EXISTS cyber_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    duration_minutes INT DEFAULT 10,
    xp_reward INT DEFAULT 25,
    module_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES cyber_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id, module_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert training modules
INSERT INTO cyber_modules (category_id, title, content, duration_minutes, xp_reward, module_order) VALUES
(1, 'Uvod u Phishing',
'Phishing je jedna od najčešćih i najopasnijih vrsta cyber napada u današnje vrijeme. Ovaj modul će vas naučiti osnovama prepoznavanja i odbrane od phishing napada.

**Šta je Phishing?**
Phishing je metoda gdje napadači koriste lažne emailove, poruke ili web stranice da bi vas prevarili da otkrijete osjetljive informacije poput:
- Lozinki i korisničkih imena
- Brojeva kreditnih kartica
- Podataka o bankovnom accountu
- Ličnih informacija

**Kako Phishing Funkcioniše?**
1. Napadač šalje email ili poruku koja izgleda kao da dolazi od legitimne organizacije
2. Poruka obično sadrži hitnu situaciju koja zahtijeva vašu pažnju
3. Link vodi na lažnu web stranicu koja izgleda identično originalnoj
4. Kada unesete podatke, napadač ih prikuplja

**Vrste Phishing Napada:**
- **Email Phishing** - Masovni emailovi poslani hiljadama korisnika
- **Spear Phishing** - Ciljani napadi na specifične osobe ili organizacije
- **Whaling** - Napadi usmjereni na top menadžment kompanije
- **Smishing** - Phishing preko SMS poruka
- **Vishing** - Phishing preko telefonskih poziva

**Zašto je Phishing Efikasan?**
Phishing napadi su uspješni jer:
- Koriste socijalnu manipulaciju i psihologiju
- Stvaraju osjećaj hitnosti i straha
- Imitiraju poznate i pouzdane brendove
- Eksploatišu ljudsku prirodu da pomogne ili brzo reaguje

**Statistika:**
- 90% cyber napada počinje sa phishing emailom
- Prosječno kompanija gubi $1.6 miliona godišnje zbog phishing napada
- 1 od 4000 emailova je phishing pokušaj

**Zaključak:**
Razumijevanje phishing napada je prvi korak u zaštiti. U narednim modulima naučit ćete konkretne tehnike prepoznavanja i odbrane.',
15, 30, 1),

(1, 'Prepoznavanje Phishing Emailova',
'U ovom modulu naučit ćete praktične tehnike za identifikaciju phishing emailova prije nego što postanete žrtva.

**Crvene Zastavice - Glavni Znakovi Upozorenja:**

**1. Email Adresa Pošiljaoca**
- Provjerite tačan domen (@companija.com vs @c0mpanija.com)
- Sumnjivi domeni često imaju pravopisne greške
- Besplatni email servisi (gmail, yahoo) za "zvanične" poruke
- Čudne kombinacije slova i brojeva

**2. Generički Pozdrav**
- "Poštovani klijente" umjesto vašeg imena
- Legitimne kompanije obično koriste vaše ime
- Nedostatak personalizacije

**3. Gramatika i Pravopis**
- Pravopisne greške i čudne formulacije
- Loš prevod sa drugih jezika
- Neprirodan jezik

**4. Osjećaj Hitnosti**
- "Vaš account će biti zatvoren za 24h!"
- "Hitna akcija potrebna!"
- "Ograničena ponuda - djelujte sada!"
- Prijetnje ili zastrašivanje

**5. Sumnjivi Linkovi**
- Hover preko linka (bez klikanja) da vidite pravu destinaciju
- URL koji ne odgovara kompaniji
- Skraćeni linkovi (bit.ly, tinyurl)
- IP adrese umjesto imena domena

**6. Neočekivani Prilozi**
- .exe, .zip, .scr fajlovi
- Dokumenti sa makroima
- Prilozi od nepoznatih pošiljaoca

**7. Zahtjevi za Lične Informacije**
- Banke NIKADA ne traže lozinke preko emaila
- Zahtjevi za brojeve kreditnih kartica
- Molbe za verifikaciju accounta

**Provjerite Ove Detalje:**
✓ Pažljivo pročitajte email adresu
✓ Provjerite sve linkove prije klikanja
✓ Potražite personalizaciju
✓ Analizirajte ton poruke
✓ Verifikujte preko zvaničnih kanala

**Praktični Savjeti:**
- Kada ste u nedoumici, kontaktirajte kompaniju direktno
- Koristite bookmark-e za važne web stranice
- Nikada ne otvarajte sumnjive priloge
- Prijavite phishing pokušaje

**Vježba:**
Analizirajte svaki email koji dobijete sa kritičkim okom. Pitajte se: "Da li je ovo legitimno?"',
20, 35, 2),

(2, 'Osnove Sigurnih Lozinki',
'Lozinke su prva i najvažnija linija odbrane vašeg digitalnog identiteta. Ovaj modul će vas naučiti kako kreirati i održavati sigurne lozinke.

**Zašto su Jake Lozinke Važne?**
- Prosječna osoba ima preko 100 online accounta
- Hakovane lozinke su odgovorne za 81% data breach-eva
- Slabe lozinke mogu biti probijene za manje od sekundi

**Karakteristike Jake Lozinke:**

**1. Dužina**
- Minimum 12-16 znakova
- Svaki dodatni znak eksponencijalno povećava sigurnost
- Fraza je bolja od riječi

**2. Kompleksnost**
- Kombinacija velikih slova (A-Z)
- Kombinacija malih slova (a-z)
- Brojevi (0-9)
- Specijalni znakovi (!@#$%^&*)

**3. Jedinstvenost**
- Različita lozinka za svaki account
- Nikada ne reciklirajte stare lozinke
- Ne koristite male varijacije iste lozinke

**4. Nepredvidljivost**
- Bez ličnih informacija (ime, datum rođenja)
- Bez riječi iz rječnika
- Bez prostih sekvenci (123456, qwerty)

**Loše Lozinke - IZBJEGAVAJTE:**
❌ password123
❌ 123456
❌ qwerty
❌ ImeNekoga2000
❌ ZimnaCetvrt!

**Dobre Lozinke - Primjeri Strukture:**
✓ Koral$Plutao7Kroz&More!
✓ Moj%P5s-Je#Velik@
✓ 2Jablka+4Narandže=6Voća

**Metode Kreiranja Lozinki:**

**1. Passphrase Metoda**
Koristite rečenicu i transformišite je:
"Volim piti kafu svako jutro u 7!"
→ VpKsJu7!

**2. Diceware Metoda**
Random kombinacija riječi:
correct-horse-battery-staple

**3. Prvi Slova Metoda**
Uzmite prvu slovu svake riječi iz fraze:
"Moja baka ima 3 mačke i 2 psa od 2020!"
→ Mbi3mi2pod2020!

**Šta NIKADA ne koristiti:**
- Vaše ime ili ime članova porodice
- Datum rođenja
- Adresa
- Broj telefona
- Ime ljubimca
- Omiljeni sportski tim
- Bilo šta povezano sa vama na društvenim mrežama

**Password Strength:**
- 8 znakova = Probijeno za sate
- 12 znakova = Probijeno za godine
- 16+ znakova = Probijeno za vijekove

**Zaključak:**
Uložite vrijeme u kreiranje jakih lozinki. To je najjeftinija i najefikasnija sigurnosna mjera koju možete preduzeti.',
15, 30, 1),

(2, 'Password Manageri i 2FA',
'Naučite kako koristiti password managere i two-factor authentication za maksimalnu sigurnost.

**Password Manageri**

**Šta je Password Manager?**
Password manager je aplikacija koja:
- Sigurno skladišti sve vaše lozinke
- Kriptuje ih master lozinkom
- Automatski popunjava forme za prijavu
- Generiše jake random lozinke
- Sinhronizuje između uređaja

**Prednosti:**
✓ Morate pamtiti samo jednu master lozinku
✓ Korištenje unikatnih jakih lozinki za sve accounte
✓ Zaštita od keylogger-a (auto-fill)
✓ Sigurna dijeljenja lozinki
✓ Upozorenja o kompromitovanim lozinkama

**Popularni Password Manageri:**
1. **Bitwarden** - Open source, besplatan
2. **1Password** - User-friendly, plaćen
3. **LastPass** - Besplatna opcija dostupna
4. **Dashlane** - Premium features
5. **KeePass** - Offline, potpuna kontrola

**Kako Početi:**
1. Izaberite password manager
2. Kreirajte jaku master lozinku (nikad je ne zaboravite!)
3. Dodajte postojeće lozinke
4. Postepeno promijenite slabe lozinke
5. Omogućite sinhronizaciju i backup

**Two-Factor Authentication (2FA)**

**Šta je 2FA?**
2FA dodaje drugi sloj verifikacije:
1. Nešto što ZNATE (lozinka)
2. Nešto što IMATE (telefon, token)

**Tipovi 2FA:**

**1. SMS Kodovi**
- Kod poslan tekstom
- Lako za setup
- Ranjiv na SIM swapping

**2. Authenticator Apps**
- Google Authenticator, Authy, Microsoft Authenticator
- Generiše rotacione kodove
- Sigurniji od SMS-a
3. Hardware Keys

YubiKey, Titan Security Key
Fizički uređaj
Najsigurnija opcija

4. Biometrija

Fingerprint, Face ID
Brzo i zgodno
Zahtijeva hardware support

Koje Accounte Zaštititi 2FA:
🔒 Email (najvažnije!)
🔒 Bankovni accounti
🔒 Social media
🔒 Cloud storage
🔒 Password manager
🔒 Crypto walleti
Backup Kodovi:

Snimite backup kodove na sigurno mjesto
Koristite ih ako izgubite pristup 2FA uređaju
Držite ih offline ili u password manageru

Best Practices:
✓ Omogućite 2FA na svim kritičnim accountima
✓ Koristite authenticator app umjesto SMS-a kad god je moguće
✓ Čuvajte backup kodove
✓ Ne dijelite 2FA kodove ni sa kim
✓ Provjeravajte listu ovlaštenih uređaja redovno
Zaključak:
Password manager + 2FA = Zlatni standard sigurnosti. Implementacija ove kombinacije dramatično smanjuje rizik od hakovanja.',
20, 35, 2),
(3, 'Vrste Malwarea',
'Malware (malicious software) je opći termin za bilo koji softver dizajniran da ošteti ili iskoristi računarski sistem. Naučite o različitim vrstama.
Glavne Vrste Malwarea:
1. VIRUSI

Priključuju se legitimnim programima
Aktiviraju se kada pokrenete inficirani program
Replicitaju se na druge fajlove
Mogu oštetiti ili obrisati podatke
Primjer: Boot sector virusi

2. WORMS (Crvi)

Samostalni programi koji se šire mrežom
Ne trebaju host program
Automatska replikacija
Mogu preopteretiti mrežu
Primjer: WannaCry, Stuxnet

3. TROJANCI (Trojans)

Maskiraju se kao legitiman softver
Otvaraju "backdoor" za napadače
Ne repliciraju se sami
Kradu podatke ili daju daljinski pristup
Primjer: Zeus, Emotet

4. RANSOMWARE

Enkriptuje vaše podatke
Zahtijeva otkupninu za dekripciju
Često širi kroz phishing
Može blokirati cijeli sistem
Primjer: WannaCry, Ryuk

5. SPYWARE

Tajno prati vaše aktivnosti
Krade lozinke i finansijske podatke
Prikuplja browser historiju
Može snimati tastere (keylogger)
Primjer: Pegasus

6. ADWARE

Prikazuje neželjene reklame
Prati pretraživačke navike
Usporava sistem
Često dolazi sa besplatnim softverom
Manje opasan ali iritantan

7. ROOTKITS

Skriva se duboko u sistemu
Teško ga je detektovati
Daje administratorski pristup napadaču
Može modifikovati OS
Vrlo opasan i uporian

8. BOTNET

Mreža inficiranih računara
Kontrolisana od strane napadača
Koristi se za DDoS napade
Slanje spam-a
Kriptominiranje

Kako se Zaraze Malwareom:
🦠 Phishing emailovi i prilozi
🦠 Sumnjive web stranice
🦠 Zaraženi USB uređaji
🦠 Piratirani softver
🦠 Exploit-i ranjivosti sistema
🦠 Malicious reklame (malvertising)
🦠 P2P file sharing
Znakovi Infekcije:
⚠️ Sporost sistema
⚠️ Česte crashevi
⚠️ Neobične pop-up prozore
⚠️ Programi se sami pokreću
⚠️ Povećan mrežni saobraćaj
⚠️ Nedostajući fajlovi
⚠️ Promjene u postavkama
⚠️ Novi toolbari u browseru
Zaključak:
Razumijevanje različitih vrsta malwarea je ključno za prepoznavanje prijetnji i poduzimanje odgovarajućih mjera zaštite.',
20, 35, 1),
(3, 'Zaštita od Malwarea',
'Naučite kako zaštititi svoj sistem od malware infekcija i šta učiniti ako ste zaraženi.
Preventivne Mjere:
1. Antivirus i Anti-Malware Softver
Preporučeni:

Windows Defender (ugrađen u Windows)
Malwarebytes
Bitdefender
Kaspersky
Norton

Funkcije:

Real-time zaštita
Automatsko skeniranje
Quarantine sumljivih fajlova
Web zaštita

2. Redovni Upd ati
✓ Operativni sistem (Windows Update)
✓ Aplikacije i programi
✓ Browser-i
✓ Antivirus definicije
✓ Firmware uređaja
3. Firewall

Aktivirajte Windows Firewall
Kontroliše dolazni/odlazni saobraćaj
Blokira neautorizovane konekcije

4. Sigurno Pretraživanje
❌ Izbjegavajte:

Piratirani softver
Crack-ove i keygens
Torrent stranice
Sumnjive download linkove
Previše dobre ponude

5. Email Sigurnost

Ne otvarajte priloge od nepoznatih
Provjeravajte pošiljaoca
Izbjegavajte linkove u emailovima
Koristite spam filter

6. Backup Strategija
VAŽNO! Redovni backup-i su najbolja odbrana:

3-2-1 Pravilo:

3 kopije podataka
2 različita medija
1 off-site lokacija



Opcije:

External hard drive
Cloud storage (Google Drive, Dropbox)
NAS (Network Attached Storage)

7. Principi Sigurne Upotrebe
✓ Ne davajte admin prava svima
✓ Koristite standardan user account
✓ Budite oprezni sa USB uređajima
✓ Scanirajte sve downloadove
✓ Budite skeptični prema pop-ups
Ako Ste Zaraženi:
Immediate Steps:

Diskonektujte Internet

Spriječava širenje i komunikaciju sa C&C serverom


Boot u Safe Mode

Windows: F8 tokom boot-a
Ograničava malware funkcionalnost


Pokrenite Full System Scan

Koristite antivirus
Pokrenite Malwarebytes
Uklonite detektovane prijetnje


Promijenite Lozinke

SA DRUGOG čistog uređaja
Svi važni accounti


Provjerite:

Startup programe
Browser extensione
Zakazane taskove
Registry entries (napredni korisnici)



Nakon Čišćenja:
✓ Ažurirajte sve softver
✓ Promijenite lozinke
✓ Omogućite 2FA
✓ Monitor account aktivnosti
✓ Razmislite o fresh OS instalaciji (za ozbiljne infekcije)
Ransomware Specifično:

NE PLAĆAJTE otkupninu
Kontaktirajte profesionalce
Restore iz backup-a
Prijavite policiji
Provjerite NoMoreRansom.org za dekriptore

Alati za Malware Removal:

Malwarebytes
HitmanPro
AdwCleaner
CCleaner
Kaspersky Virus Removal Tool

Zaključak:
Prevencija je najbolja zaštita. Kombinacija antivirus softvera, redovnih update-a, sigurnih navika browsing-a i backup strategije daje sveobuhvatnu zaštitu od malwarea.',
25, 40, 2);
-- User progress table
CREATE TABLE IF NOT EXISTS cyber_user_progress (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
category_id INT NOT NULL,
questions_answered INT DEFAULT 0,
questions_correct INT DEFAULT 0,
scenarios_completed INT DEFAULT 0,
modules_completed INT DEFAULT 0,
category_xp INT DEFAULT 0,
last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
FOREIGN KEY (category_id) REFERENCES cyber_categories(id) ON DELETE CASCADE,
UNIQUE KEY unique_user_category (user_id, category_id),
INDEX idx_user (user_id),
INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Achievements table
CREATE TABLE IF NOT EXISTS cyber_achievements (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
badge_type VARCHAR(50) NOT NULL,
badge_name VARCHAR(100) NOT NULL,
badge_description TEXT,
earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Quiz sessions table
CREATE TABLE IF NOT EXISTS cyber_quiz_sessions (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
correct INT NOT NULL,
incorrect INT NOT NULL,
total_xp INT NOT NULL,
category VARCHAR(50),
difficulty VARCHAR(20),
completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
INDEX idx_user (user_id),
INDEX idx_completed (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Scenario completions table
CREATE TABLE IF NOT EXISTS cyber_scenario_completions (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
scenario_id INT NOT NULL,
score INT NOT NULL,
xp_earned INT NOT NULL,
completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
FOREIGN KEY (scenario_id) REFERENCES cyber_scenarios(id) ON DELETE CASCADE,
INDEX idx_user (user_id),
INDEX idx_scenario (scenario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Module completions table
CREATE TABLE IF NOT EXISTS cyber_module_completions (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
module_id INT NOT NULL,
completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
FOREIGN KEY (module_id) REFERENCES cyber_modules(id) ON DELETE CASCADE,
UNIQUE KEY unique_completion (user_id, module_id),
INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily challenges table
CREATE TABLE IF NOT EXISTS cyber_daily_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    challenge_type ENUM('quiz', 'scenario', 'interactive', 'simulation') NOT NULL,
    category_id INT,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    xp_reward INT DEFAULT 30,
    date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES cyber_categories(id) ON DELETE CASCADE,
    INDEX idx_date (date),
    INDEX idx_type (challenge_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User streaks table
CREATE TABLE IF NOT EXISTS cyber_user_streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_activity_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rewards/Gamification table
CREATE TABLE IF NOT EXISTS cyber_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    reward_type ENUM('badge', 'avatar', 'theme', 'title') NOT NULL,
    icon VARCHAR(50),
    color VARCHAR(20),
    requirement_type ENUM('level', 'streak', 'achievement', 'xp') NOT NULL,
    requirement_value INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (reward_type),
    INDEX idx_requirement (requirement_type, requirement_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User rewards table
CREATE TABLE IF NOT EXISTS cyber_user_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES cyber_rewards(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_reward (user_id, reward_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Interactive elements table
CREATE TABLE IF NOT EXISTS cyber_interactive_elements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    element_type ENUM('drag_drop', 'simulation', 'memory_game', 'code_challenge', 'quiz') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    content JSON,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    xp_reward INT DEFAULT 30,
    time_limit INT DEFAULT 300,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES cyber_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_type (element_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Interactive element completions table
CREATE TABLE IF NOT EXISTS cyber_interactive_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    element_id INT NOT NULL,
    score INT NOT NULL,
    xp_earned INT NOT NULL,
    completion_time INT NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
    FOREIGN KEY (element_id) REFERENCES cyber_interactive_elements(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_element (element_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily challenge completions table
CREATE TABLE IF NOT EXISTS cyber_daily_challenge_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    challenge_id INT NOT NULL,
    xp_earned INT NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES cyber_daily_challenges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_challenge (user_id, challenge_id),
    INDEX idx_user (user_id),
    INDEX idx_challenge (challenge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User activity log table
CREATE TABLE IF NOT EXISTS cyber_user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    details TEXT,
    xp_earned INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_activity (activity_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User preferences table
CREATE TABLE IF NOT EXISTS cyber_user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(50) NOT NULL,
    preference_value TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_key),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Level system is preserved but leaderboard view is removed


-- Create sample admin user
-- Insert sample rewards
INSERT INTO cyber_rewards (name, description, reward_type, icon, color, requirement_type, requirement_value) VALUES
('Newcomer', 'Completed your first quiz', 'badge', '🌟', '#10b981', 'achievement', 1),
('Knowledge Seeker', 'Answered 50 questions', 'badge', '📚', '#3b82f6', 'achievement', 50),
('Expert Learner', 'Reached level 10', 'badge', '🏆', '#fbbf24', 'level', 10),
('Cyber Defender', 'Completed all scenarios', 'badge', '🛡️', '#8b5cf6', 'achievement', 1),
('Week Warrior', '7 day streak', 'badge', '🔥', '#ef4444', 'streak', 7),
('Phoenix', '30 day streak', 'badge', '🔥', '#ef4444', 'streak', 30),
('Cyber Ninja', 'Completed 100 interactive elements', 'badge', '🥷', '#000000', 'achievement', 100),
('Cyber Master', 'Reached level 20', 'badge', '👑', '#fbbf24', 'level', 20);

-- Insert sample avatars
INSERT INTO cyber_rewards (name, description, reward_type, icon, color, requirement_type, requirement_value) VALUES
('Hacker', 'Anonymous hacker avatar', 'avatar', '👨‍💻', '#000000', 'level', 3),
('Security Expert', 'Professional security expert', 'avatar', '👨‍💼', '#3b82f6', 'level', 5),
('Cyber Agent', 'Secret agent avatar', 'avatar', '🕵️', '#8b5cf6', 'level', 7),
('Ninja', 'Stealthy ninja avatar', 'avatar', '🥷', '#000000', 'level', 10),
('Wizard', 'Cyber wizard avatar', 'avatar', '🧙', '#8b5cf6', 'level', 15);

-- Insert sample themes
INSERT INTO cyber_rewards (name, description, reward_type, icon, color, requirement_type, requirement_value) VALUES
('Dark Mode', 'Dark theme for night owls', 'theme', '🌙', '#1f2937', 'level', 2),
('Matrix', 'Green matrix rain theme', 'theme', '💻', '#00d4ff', 'level', 5),
('Neon', 'Bright neon colors theme', 'theme', '🌈', '#ec4899', 'level', 8),
('Retro', 'Classic terminal theme', 'theme', '🖥️', '#10b981', 'level', 10);

-- Insert daily challenges sample data
INSERT INTO cyber_daily_challenges (title, description, challenge_type, category_id, difficulty, xp_reward, date) VALUES
('Phishing First Aid', 'Answer 5 phishing questions correctly', 'quiz', 1, 'easy', 30, CURDATE()),
('Password Strength Challenge', 'Complete a password strength simulation', 'interactive', 2, 'medium', 40, CURDATE()),
('Malware Investigation', 'Solve a malware scenario', 'scenario', 3, 'hard', 50, CURDATE()),
('Social Engineering Quiz', 'Answer 7 social engineering questions', 'quiz', 4, 'medium', 35, CURDATE() + INTERVAL 1 DAY),
('Network Security Simulation', 'Complete a network security interactive element', 'interactive', 5, 'hard', 45, CURDATE() + INTERVAL 1 DAY);

-- Insert sample interactive elements
INSERT INTO cyber_interactive_elements (category_id, element_type, title, description, content, difficulty, xp_reward, time_limit) VALUES
(1, 'drag_drop', 'Identify Phishing Emails', 'Drag the suspicious elements from phishing emails', '{"elements": [{"id": "suspicious_link", "name": "Suspicious Link"}, {"id": "grammar_error", "name": "Grammar Error"}, {"id": "urgent_action", "name": "Urgent Action"}, {"id": "spoofed_brand", "name": "Spoofed Brand"}], "scenarios": [{"id": 1, "image": "phishing1.jpg", "correct_elements": ["suspicious_link", "urgent_action"]}, {"id": 2, "image": "phishing2.jpg", "correct_elements": ["grammar_error", "spoofed_brand"]}]}', 'medium', 30, 300),
(2, 'simulation', 'Password Strength Simulator', 'Create and test different password combinations to see their strength', '{"min_length": 8, "complexity_requirements": ["uppercase", "lowercase", "number", "special"], "levels": [{"min_score": 20, "feedback": "Very weak"}, {"min_score": 40, "feedback": "Weak"}, {"min_score": 60, "feedback": "Medium"}, {"min_score": 80, "feedback": "Strong"}, {"min_score": 100, "feedback": "Very strong"}]}', 'easy', 25, 180),
(3, 'memory_game', 'Malware Types Memory', 'Match malware types with their descriptions in this memory game', '{"cards": [{"id": 1, "type": "malware", "name": "Ransomware", "description": "Encrypts files and demands payment"}, {"id": 2, "type": "malware", "name": "Trojan", "description": "Disguises itself as legitimate software"}, {"id": 3, "type": "malware", "name": "Spyware", "description": "Secretly monitors user activity"}, {"id": 4, "type": "malware", "name": "Worm", "description": "Self-replicating malware that spreads across networks"}], "pairs": 4, "flip_time": 1000}', 'easy', 20, 120),

(6, 'drag_drop', 'Phishing Detection', 'Identify phishing elements in suspicious emails', '{"elements": [{"id": "suspicious_link", "name": "Sumnjiv link"}, {"id": "grammar_error", "name": "Gramatička greška"}, {"id": "urgent_action", "name": "Hitna akcija"}, {"id": "spoofed_brand", "name": "Lažni brend"}], "scenarios": [{"id": 1, "image": "phishing1.jpg", "correct_elements": ["suspicious_link", "urgent_action"]}, {"id": 2, "image": "phishing2.jpg", "correct_elements": ["grammar_error", "spoofed_brand"]}]}', 'medium', 30, 180),

(7, 'drag_drop', 'Digitalni Otisak', 'Pronađite slabosti u digitalnom otisku', '{"elements": [{"id": "ridge", "name": "Grbice uzorci"}, {"id": "whorl", "name": "Uzorci vrtloga"}, {"id": "loop", "name": "Kružni uzorci"}, {"id": "arch", "name": "Lukovi"}, {"id": "endpoint", "name": "Krajnje tačke"}], "scenarios": [{"id": 1, "image": "fingerprint1.jpg", "correct_elements": ["ridge", "whorl", "arch", "loop"]}, {"id": 2, "image": "fingerprint2.jpg", "correct_elements": ["ridge", "whorl", "arch"]}]}', 'medium', 35, 240),

(8, 'drag_drop', 'VPN Konfiguracija', 'Postavite sigurnu VPN konfiguraciju', '{"elements": [{"id": "protocol", "name": "Protokol"}, {"id": "encryption", "name": "Enkripcija"}, {"id": "kill_switch", "name": "Kill Switch"}], "scenarios": [{"id": 1, "image": "vpn-config.jpg", "correct_elements": ["protocol", "encryption", "kill_switch"]}, {"id": 2, "image": "vpn-mobile.jpg", "correct_elements": ["protocol", "encryption"]}]}', 'hard', 40, 300),

(9, 'simulation', 'Kriptografska Simulacija', 'Simulirajte kriptografske algoritme', '{"algorithms": ["AES", "RSA", "DES", "Caesar"], "plaintext": "Ovo je tajna poruka za enkripciju", "key_length": 256}', 'hard', 45, 400),

(10, 'simulation', 'VPN Testiranje', 'Testirajte VPN sigurnost', '{"scenarios": [{"id": 1, "name": "IP Utajavanje", "description": "Test da li VPN skriva vašu IP adresu"}, {"id": 2, "name": "DNS Protekcija", "description": "Proverite da li VPN štiti od DNS propuštanja"}], "requirements": ["dns_leak", "kill_switch"], "results_display": "graphical"}', 'medium', 35, 300),

(11, 'code_challenge', 'Firewall Pravila', 'Analizirajte firewall konfiguraciju', '{"challenge": "Analizirajte ovaj iptables konfiguracioni fajl i pronađite sigurnosne propuste.", "solution": ["Nedostaje INPUT pravilo za SSH", "Nedostaje pravilo za odgovarajuće pakete", "Nedostaje logging"]}', 'medium', 25, 300),
(4, 'code_challenge', 'Social Engineering Detection', 'Analyze code snippet to identify social engineering tactics', '{"challenge": "Examine this JavaScript code that simulates a fake login form. Identify 3 security issues.", "solution": ["Missing form validation", "No HTTPS enforcement", "Direct credential submission without encryption"], "hints": ["Look for validation methods", "Check if form uses secure protocol", "Examine how data is submitted"]}', 'hard', 40, 600),
(5, 'drag_drop', 'Network Security Elements', 'Build a secure network by placing security elements in correct positions', '{"elements": [{"id": "firewall", "name": "Firewall"}, {"id": "ids", "name": "IDS/IPS"}, {"id": "router", "name": "Router"}, {"id": "server", "name": "Server"}], "correct_placement": {"firewall": "perimeter", "ids": "internal", "router": "boundary", "server": "protected"}}', 'medium', 35, 240),
(6, 'drag_drop', 'VPN Konfiguracija', 'Postavite sigurnu VPN konfiguraciju za različite scenarije', '{"elements": [{"id": "protocol", "name": "Protokol"}, {"id": "encryption", "name": "Enkripcija"}, {"id": "kill_switch", "name": "Kill Switch"}], "scenarios": [{"id": 1, "name": "Remote Work", "required_protocols": ["OpenVPN", "WireGuard"], "firewall_rules": true}, {"id": 2, "name": "Public WiFi", "required_protocols": ["OpenVPN"], "kill_switch": true}]}', 'hard', 40, 300),
(7, 'simulation', 'VPN Testiranje', 'Testirajte VPN sigurnost', '{"scenarios": [{"id": 1, "name": "IP Utajavanje", "description": "Test da li VPN skriva vašu IP adresu"}, {"id": 2, "name": "DNS Zaštita", "description": "Proverite da li VPN štiti od DNS propuštanja"}], "requirements": ["dns_leak", "kill_switch"], "results_display": "graphical"}', 'medium', 35, 300),
(8, 'simulation', 'VPN Trafik Analiza', 'Analizirajte VPN promet', '{"packets": [{"id": 1, "name": "HTTP", "description": "Nekriptovan web saobraćaj"}, {"id": 2, "name": "DNS", "description": "DNS upiti"}, {"id": 3, "name": "UDP", "description": "Video stream"}]}', 'medium', 30, 180),
(9, 'code_challenge', 'Firewall Pravila', 'Analizirajte firewall konfiguraciju', '{"challenge": "Analizirajte ovaj iptables konfiguracioni fajl i pronađite sigurnosne propuste.", "solution": ["Neophodno INPUT pravilo za SSH", "Neophodno pravilo za dozvoljene pakete", "Omogućiti logovanje"]}', 'medium', 25, 300),
(10, 'simulation', 'Mrežni Skener', 'Skenirajte mrežu za ranjivosti', '{"tools": ["nmap", "masscan", "nikto"], "targets": ["192.168.1.0/24", "example.com"], "vulnerabilities": ["SQLi", "XSS", "CVE-2023-1234"]}', 'hard', 45, 400),
(11, 'drag_drop', 'Digitalni Otisak', 'Pronađite karakteristike digitalnog otiska', '{"elements": [{"id": "ridge", "name": "Grbice uzorci"}, {"id": "whorl", "name": "Uzorci vrtloga"}, {"id": "loop", "name": "Kružni uzorci"}, {"id": "arch", "name": "Lukovi"}, {"id": "endpoint", "name": "Krajevne tačke"}], "scenarios": [{"id": 1, "image": "fingerprint1.jpg", "correct_elements": ["ridge", "whorl", "arch", "loop"], "description": "Visoki kvalitet otiska"}], "scenarios": [{"id": 2, "image": "fingerprint2.jpg", "correct_elements": ["ridge", "whorl", "arch", "loop"], "description": "Srednji kvalitet otiska"}]}', 'medium', 35, 240),
(12, 'code_challenge', 'Digitalni Otisak', 'Analizirajte digitalni otisak', '{"challenge": "Pronađite karakteristike digitalnog otiska u sledećem kodu i identifikujte sigurnosne propuste.", "code": "biometricData = new BiometricAPI();\\nconst result = biometricData.verifyFingerprint(userId, fingerprint);\\nif (!result.success) {\\n  return error;\\n}\\nreturn success;", "solution": ["Nedostaje enkripcija", "Nedostaje validacija unosa", "Pohranjivanje bez autorizacije"]}', 'hard', 40, 600);
(13, 'drag_drop', 'VPN Konfiguracija', 'Postavite sigurnu VPN konfiguraciju', '{"elements": [{"id": "protocol", "name": "Protokol"}, {"id": "encryption", "name": "Enkripcija"}, {"id": "kill_switch", "name": "Kill Switch"}], "scenarios": [{"id": 1, "name": "Remote Work", "required_protocols": ["OpenVPN", "WireGuard"], "firewall_rules": true}, {"id": 2, "name": "Public WiFi", "required_protocols": ["OpenVPN"], "kill_switch": true}]}', 'hard', 40, 300),
(14, 'drag_drop', 'Mrežni Paketi', 'Analizirajte mrežne pakete', '{"elements": [{"id": "tcp", "name": "TCP"}, {"id": "udp", "name": "UDP"}, {"id": "icmp", "name": "ICMP"}, {"id": "arp", "name": "ARP"}], "scenarios": [{"id": 1, "name": "DoS napad", "description": "Prepoznajte DoS paketni napad"}, {"id": 2, "name": "Port skeniranje", "description": "Identifikacija otvorenih portova"}, {"id": 3, "name": "VPN saobraćaj", "description": "Analizirajte VPN promet"}]}', 'hard', 45, 300),
(15, 'simulation', 'VPN Simulacija', 'Simulirajte VPN konekciju', '{"endpoints": ["server1.vpn.com", "server2.vpn.com"], "protocols": ["OpenVPN", "WireGuard"], "encryption": ["AES-256", "ChaCha20"], "features": ["multi-hop", "obfuscation"]}', 'hard', 50, 600);
(16, 'drag_drop', 'VPN Konfiguracija', 'Postavite sigurnu VPN konfiguraciju', '{"elements": [{"id": "protocol", "name": "Protokol"}, {"id": "encryption", "name": "Enkripcija"}, {"id": "kill_switch", "name": "Kill Switch"}], "scenarios": [{"id": 1, "name": "Remote Work", "required_protocols": ["OpenVPN", "WireGuard"], "firewall_rules": true}, {"id": 2, "name": "Public WiFi", "required_protocols": ["OpenVPN"], "kill_switch": true}]}', 'hard', 40, 300);
