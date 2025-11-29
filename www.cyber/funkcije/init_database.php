<?php
// funkcije/init_database.php
require_once __DIR__ . "/veza_do_baze.php";

function initCyberDatabase($veza)
{
    try {
        // Users table
        $veza->exec("CREATE TABLE IF NOT EXISTS cyber_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255),
            email VARCHAR(100),
            total_xp INT DEFAULT 0,
            level INT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_xp (total_xp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Categories table
        $veza->exec("CREATE TABLE IF NOT EXISTS cyber_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50),
            color VARCHAR(20),
            description TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Quiz questions table
        $veza->exec("CREATE TABLE IF NOT EXISTS cyber_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
            xp_reward INT DEFAULT 10,
            FOREIGN KEY (category_id) REFERENCES cyber_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Scenarios table
        $veza->exec("CREATE TABLE IF NOT EXISTS cyber_scenarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
            xp_reward INT DEFAULT 50,
            FOREIGN KEY (category_id) REFERENCES cyber_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Scenario steps table
        $veza->exec("CREATE TABLE IF NOT EXISTS cyber_scenario_steps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            scenario_id INT NOT NULL,
            step_number INT NOT NULL,
            story_text TEXT NOT NULL,
            FOREIGN KEY (scenario_id) REFERENCES cyber_scenarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Scenario choices table
        $veza->exec("CREATE TABLE IF NOT EXISTS cyber_scenario_choices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            step_id INT NOT NULL,
            choice_text TEXT NOT NULL,
            is_correct BOOLEAN DEFAULT FALSE,
            feedback TEXT,
            next_step_id INT,
            FOREIGN KEY (step_id) REFERENCES cyber_scenario_steps(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Training modules table
        $veza->exec("CREATE TABLE IF NOT EXISTS cyber_modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            duration_minutes INT DEFAULT 10,
            xp_reward INT DEFAULT 25,
            module_order INT DEFAULT 0,
            FOREIGN KEY (category_id) REFERENCES cyber_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // User progress table
        $veza->exec("CREATE TABLE IF NOT EXISTS cyber_user_progress (
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
            UNIQUE KEY unique_user_category (user_id, category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Achievements/Badges table
        $veza->exec("CREATE TABLE IF NOT EXISTS cyber_achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            badge_type VARCHAR(50) NOT NULL,
            badge_name VARCHAR(100) NOT NULL,
            earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES cyber_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Insert default categories
        $stmt = $veza->query("SELECT COUNT(*) FROM cyber_categories");
        if ($stmt->fetchColumn() == 0) {
            $veza->exec("INSERT INTO cyber_categories (name, icon, color, description) VALUES
                ('Phishing', '🎣', '#3b82f6', 'Learn to detect and prevent phishing attacks'),
                ('Passwords', '🔐', '#10b981', 'Master password security and best practices'),
                ('Malware', '🦠', '#ef4444', 'Understand malware threats and protection')
            ");
        }

        // Insert sample quiz questions
        $stmt = $veza->query("SELECT COUNT(*) FROM cyber_questions");
        if ($stmt->fetchColumn() == 0) {
            $veza->exec("INSERT INTO cyber_questions (category_id, question, answer, difficulty, xp_reward) VALUES
                (1, 'Šta je phishing napad?', 'Phishing je vrsta cyber napada gdje napadač pokušava prevariti žrtvu da otkrije osjetljive informacije poput lozinki ili brojeva kreditnih kartica kroz lažne email poruke ili web stranice.', 'easy', 10),
                (1, 'Kako prepoznati phishing email?', 'Obratite pažnju na: pogrešnu gramatiku, sumnjive linkove, neočekivane priloge, hitne zahtjeve za akciju, nesigledne email adrese pošiljaoca.', 'medium', 15),
                (2, 'Koliko znakova bi trebala imati sigurna lozinka?', 'Sigurna lozinka bi trebala imati najmanje 12-16 znakova, kombinaciju velikih i malih slova, brojeva i specijalnih znakova.', 'easy', 10),
                (2, 'Šta je two-factor authentication (2FA)?', '2FA je dodatni sloj sigurnosti koji zahtijeva dva različita načina verifikacije identiteta - nešto što znate (lozinka) i nešto što imate (telefon, token).', 'medium', 15),
                (3, 'Šta je ransomware?', 'Ransomware je vrsta malwarea koji enkriptuje podatke žrtve i zahtijeva otkupninu za dešifrovanje.', 'easy', 10),
                (3, 'Kako se zaštititi od malwarea?', 'Koristite antivirus softver, redovno ažurirajte sistem, ne otvarajte sumnjive priloge, pravite backup podataka, koristite firewall.', 'medium', 15)
            ");
        }

        // Insert sample scenarios
        $stmt = $veza->query("SELECT COUNT(*) FROM cyber_scenarios");
        if ($stmt->fetchColumn() == 0) {
            $veza->exec("INSERT INTO cyber_scenarios (category_id, title, description, difficulty, xp_reward) VALUES
                (1, 'Sumnjivi Email od Banke', 'Primili ste email koji navodno dolazi od vaše banke. Trebate odlučiti kako postupiti.', 'easy', 50),
                (2, 'Kompromitovana Lozinka', 'Saznali ste da je vaša lozinka procurila u data brechu. Šta ćete učiniti?', 'medium', 75),
                (3, 'Ransomware Napad', 'Vaš računar je zaražen ransomwareom. Kako ćete reagovati?', 'hard', 100)
            ");

            // Add scenario steps and choices for first scenario
            $veza->exec("INSERT INTO cyber_scenario_steps (scenario_id, step_number, story_text) VALUES
                (1, 1, 'Otvorili ste email koji izgleda kao da dolazi od vaše banke. U emailu piše da je vaš račun suspendovan i da morate hitno kliknuti na link da biste ga reaktivirali. Email ima logo banke i izgleda profesionalno.')
            ");

            $veza->exec("INSERT INTO cyber_scenario_choices (step_id, choice_text, is_correct, feedback) VALUES
                (1, 'Kliknuti na link i unijeti svoje podatke', FALSE, 'Ovo je pogrešna odluka! Nikada ne klikćite na linkove u sumnjivim emailovima. Kontaktirajte banku direktno preko njihovog zvaničnog broja telefona.'),
                (1, 'Ignorisati email i kontaktirati banku direktno', TRUE, 'Odlično! Ovo je najsigurnija opcija. Uvijek kontaktirajte instituciju direktno preko zvaničnih kanala kada dobijete sumnjive poruke.'),
                (1, 'Proslijediti email prijateljima da ih upozorite', FALSE, 'Iako je dobra namera, prosljeđivanje phishing emaila može dovesti do širenja prijetnje. Bolje je prijaviti email kao spam i obrisati ga.')
            ");
        }

        // Insert sample training modules
        $stmt = $veza->query("SELECT COUNT(*) FROM cyber_modules");
        if ($stmt->fetchColumn() == 0) {
            $veza->exec("INSERT INTO cyber_modules (category_id, title, content, duration_minutes, xp_reward, module_order) VALUES
                (1, 'Uvod u Phishing', 'Phishing je jedna od najčešćih vrsta cyber napada. U ovom modulu naučit ćete osnovne karakteristike phishing napada i kako ih prepoznati.\n\nKljučne točke:\n- Phishing koristi socijalnu manipulaciju\n- Napadači se predstavljaju kao pouzdane institucije\n- Cilj je ukrasti lične podatke ili novac\n\nPrimjeri phishing napada:\n1. Email prevare\n2. SMS poruke (smishing)\n3. Lažni pozivi (vishing)\n4. Lažne web stranice', 10, 25, 1),
                (1, 'Prepoznavanje Phishing Emailova', 'Naučite kako prepoznati phishing email prije nego što postanete žrtva.\n\nCrvene zastavice:\n- Hitnost i prijetnje\n- Loša gramatika i pravopis\n- Generički pozdrav (npr. \"Poštovani klijente\")\n- Sumnjive email adrese\n- Neočekivani prilozi\n- Sumnjivi linkovi\n\nPro savjet: Prebacite miš preko linka (bez klikanja) da vidite pravu destinaciju.', 15, 30, 2),
                (2, 'Osnove Sigurnih Lozinki', 'Lozinke su prva linija odbrane vašeg digitalnog identiteta.\n\nKarakteristike jake lozinke:\n- Minimum 12-16 znakova\n- Kombinacija velikih i malih slova\n- Brojevi i specijalni znakovi\n- Bez ličnih informacija\n- Jedinstvena za svaki account\n\nNE koristite:\n- Riječi iz rječnika\n- Datume rođenja\n- Imena članova porodice\n- Jednostavne sekvence (123456)', 10, 25, 1),
                (3, 'Vrste Malwarea', 'Malware je zlonamjerni softver dizajniran da ošteti ili iskoristi računarski sistem.\n\nVrste malwarea:\n1. Virusi - kopiraju se i šire\n2. Worms - samostalno se šire mrežom\n3. Trojanci - skrivaju se u legitimnom softveru\n4. Ransomware - enkriptuje podatke\n5. Spyware - krade informacije\n6. Adware - prikazuje neželjene reklame\n\nZaštita: antivirus, firewall, redovna ažuriranja', 15, 30, 1)
            ");
        }

        return true;
    } catch (PDOException $e) {
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}

// Initialize database
initCyberDatabase($veza);
?>
