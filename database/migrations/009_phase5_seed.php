<?php
declare(strict_types=1);

/**
 * Phase 5 seed: turns the existing free-text product categories into real shop categories, and adds placeholder
 * services, speaking topics, press items and events (marked as samples; they vanish once you add your own).
 */
return function (PDO $pdo): void {
    $slugify = function (string $s): string {
        $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
        return $s !== '' ? $s : 'category';
    };

    $names = $pdo->query("SELECT DISTINCT category FROM products WHERE category <> ''")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('T-shirts', $names, true)) {
        array_unshift($names, 'T-shirts');
    }
    $insC = $pdo->prepare('INSERT INTO shop_categories (name, slug, description, sort_order) VALUES (?, ?, ?, ?)');
    $upd = $pdo->prepare('UPDATE products SET category_id = ? WHERE category = ? AND category_id IS NULL');
    foreach (array_values($names) as $i => $name) {
        $desc = $name === 'T-shirts' ? 'Original physics and atmosphere designs. Wear the science.' : '';
        $insC->execute([$name, $slugify($name), $desc, ($i + 1) * 10]);
        $upd->execute([(int)$pdo->lastInsertId(), $name]);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM services')->fetchColumn() === 0) {
        $s = $pdo->prepare('INSERT INTO services (title, icon, summary, details, price_note, enquiry_type, sort_order, is_sample) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
        $note = "*Sample text: replace it in Admin → Services.*\n\n";
        foreach ([
            ['Consulting', '📊', 'Weather, climate and atmospheric data analysis for organisations.', $note . "What I can help with:\n\n- Interpreting weather and climate data for planning and risk\n- Reviewing and designing observation or monitoring programmes\n- Technical reports and independent expert opinion", 'Quoted per project', 'consulting', 10],
            ['Speaking and keynotes', '🎤', 'Talks on climate, weather and physics for conferences, schools and media.', $note . "Talks can be 20 minutes to a full day, in person or online. See the Speaking page for topics and the media kit.", 'Fee on request', 'speaking', 20],
            ['Training and workshops', '🧑‍🏫', 'Hands-on workshops for teachers, students and professionals.', $note . "Examples: teaching physics with everyday examples, introduction to weather data, research methods for graduate students.", 'From a half-day session', 'training', 30],
            ['Supervision and mentoring', '🎓', 'Mentoring for postgraduate students and early-career researchers.', $note . "Project and thesis supervision, proposal and manuscript feedback, career guidance in the atmospheric sciences.", 'By arrangement', 'supervision', 40],
        ] as $r) {
            $s->execute($r);
        }
    }
    if ((int)$pdo->query('SELECT COUNT(*) FROM speaking_topics')->fetchColumn() === 0) {
        $t = $pdo->prepare('INSERT INTO speaking_topics (title, summary, audience, duration, sort_order, is_sample) VALUES (?, ?, ?, ?, ?, 1)');
        foreach ([
            ['Why West Africa\'s rains are changing', 'Sample topic: how the West African monsoon works and what a warming climate means for farmers and cities.', 'General audience, policy makers', '30–45 min', 10],
            ['Physics you can feel: the science of everyday weather', 'Sample topic: a lively talk that explains weather with simple demonstrations.', 'Secondary schools, undergraduates', '45 min', 20],
            ['From data to decisions: using climate information', 'Sample topic: turning forecasts and observations into practical planning.', 'Organisations, NGOs, government', '60 min', 30],
        ] as $r) {
            $t->execute($r);
        }
    }
    if ((int)$pdo->query('SELECT COUNT(*) FROM press_items')->fetchColumn() === 0) {
        $p = $pdo->prepare('INSERT INTO press_items (outlet, title, url, pub_date, kind, quote, sort_order, is_sample) VALUES (?, ?, \'\', ?, ?, ?, ?, 1)');
        $p->execute(['Sample Newspaper', 'Sample: interview on the coming rainy season', date('Y-m-d', strtotime('-60 days')), 'interview', 'Sample quote: replace with a line from the real article.', 10]);
        $p->execute(['Sample Radio', 'Sample: explaining the harmattan on morning radio', date('Y-m-d', strtotime('-200 days')), 'radio', '', 20]);
    }
    if ((int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn() === 0) {
        $e = $pdo->prepare('INSERT INTO events (title, start_date, location, summary, kind, sort_order, is_sample) VALUES (?, ?, ?, ?, ?, ?, 1)');
        $e->execute(['Sample: public lecture on climate and weather', date('Y-m-d', strtotime('+45 days')), 'Lagos, Nigeria (sample)', 'Sample event. Replace it in Admin → Events.', 'talk', 10]);
        $e->execute(['Sample: workshop for physics teachers', date('Y-m-d', strtotime('+90 days')), 'Online (sample)', 'Sample event.', 'workshop', 20]);
        $e->execute(['Sample: past conference keynote', date('Y-m-d', strtotime('-120 days')), 'Abuja, Nigeria (sample)', 'Sample past event.', 'keynote', 30]);
    }
};
