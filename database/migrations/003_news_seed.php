<?php
declare(strict_types=1);

/**
 * Phase 2 seed: the approved starter source list, a starter block-list, and switches the News section on.
 * Feed addresses were checked on 8 Oct 2026. Sources that could not be reached are added PAUSED with a note,
 * so you can fix the address in Admin → News sources and press Resume.
 */
return function (PDO $pdo): void {
    // [name, site, feed, domains, tier, kind, region, topic, official, can_break, all_breaking, topic_filter, images, status, note]
    $S = [
        // Tier 1: primary / official
        ['NASA', 'https://www.nasa.gov', 'https://www.nasa.gov/feed/', 'nasa.gov', 1, 'primary', 'world', 'space', 1, 1, 0, 0, 1, 'active', ''],
        ['NASA Earth Observatory', 'https://earthobservatory.nasa.gov', 'https://earthobservatory.nasa.gov/feeds/earth-observatory.rss', 'nasa.gov', 1, 'primary', 'world', 'climate', 1, 0, 0, 0, 1, 'active', ''],
        ['ESA: Space Science', 'https://www.esa.int', 'https://www.esa.int/rssfeed/Our_Activities/Space_Science', 'esa.int', 1, 'primary', 'world', 'space', 1, 1, 0, 0, 0, 'active', ''],
        ['ESA: Observing the Earth', 'https://www.esa.int', 'https://www.esa.int/rssfeed/Our_Activities/Observing_the_Earth', 'esa.int', 1, 'primary', 'world', 'climate', 1, 0, 0, 0, 0, 'active', ''],
        ['NOAA', 'https://www.noaa.gov', 'https://www.noaa.gov/rss.xml', 'noaa.gov', 1, 'primary', 'world', 'climate', 1, 1, 0, 0, 0, 'active', ''],
        ['USGS: significant earthquakes', 'https://earthquake.usgs.gov', 'https://earthquake.usgs.gov/earthquakes/feed/v1.0/summary/significant_week.atom', 'usgs.gov', 1, 'primary', 'world', 'earth', 1, 1, 1, 0, 0, 'active', 'Every item in this feed is a significant earthquake, so each one counts as breaking.'],
        ['NobelPrize.org', 'https://www.nobelprize.org', 'https://www.nobelprize.org/feed/', 'nobelprize.org', 1, 'primary', 'world', 'nobel', 1, 1, 0, 0, 0, 'active', ''],
        ['Nature (research papers)', 'https://www.nature.com', 'https://www.nature.com/nature.rss', 'nature.com', 1, 'primary', 'world', 'other', 1, 0, 0, 0, 0, 'active', 'Primary research from the journal; never marked breaking by itself.'],
        ['WMO (World Meteorological Organization)', 'https://wmo.int', 'https://wmo.int/news/rss', 'wmo.int', 1, 'primary', 'world', 'climate', 1, 1, 0, 0, 0, 'paused', 'The feed address was not found when this site was set up. Find the current RSS link on wmo.int, paste it here and press Resume.'],
        ['NiMet (Nigerian Meteorological Agency)', 'https://nimet.gov.ng', 'https://nimet.gov.ng/rss', 'nimet.gov.ng', 1, 'primary', 'nigeria', 'climate', 1, 1, 0, 0, 0, 'paused', 'NiMet did not offer a reachable feed when this site was set up. Check nimet.gov.ng for an RSS link, or leave paused.'],
        // Tier 2: reputable press
        ['Science (AAAS) News', 'https://www.science.org', 'https://www.science.org/rss/news_current.xml', 'science.org', 2, 'news', 'world', 'other', 0, 0, 0, 0, 0, 'active', ''],
        ['New Scientist', 'https://www.newscientist.com', 'https://www.newscientist.com/feed/home/', 'newscientist.com', 2, 'news', 'world', 'other', 0, 0, 0, 0, 0, 'active', ''],
        ['Science News', 'https://www.sciencenews.org', 'https://www.sciencenews.org/feed', 'sciencenews.org', 2, 'news', 'world', 'other', 0, 0, 0, 0, 0, 'active', ''],
        ['Scientific American', 'https://www.scientificamerican.com', 'https://www.scientificamerican.com/platform/syndication/rss/', 'scientificamerican.com', 2, 'news', 'world', 'other', 0, 0, 0, 0, 0, 'active', ''],
        ['BBC Science & Environment', 'https://www.bbc.com/news/science_and_environment', 'https://feeds.bbci.co.uk/news/science_and_environment/rss.xml', 'bbc.co.uk,bbc.com', 2, 'news', 'world', 'other', 0, 0, 0, 0, 0, 'active', ''],
        ['The Guardian: Science', 'https://www.theguardian.com/science', 'https://www.theguardian.com/science/rss', 'theguardian.com', 2, 'news', 'world', 'other', 0, 0, 0, 0, 0, 'active', ''],
        ['Mongabay', 'https://news.mongabay.com', 'https://news.mongabay.com/feed/', 'mongabay.com', 2, 'news', 'world', 'climate', 0, 0, 0, 1, 0, 'active', 'Environment news; only science and climate stories are kept.'],
        ['Premium Times (Nigeria)', 'https://www.premiumtimesng.com', 'https://www.premiumtimesng.com/feed', 'premiumtimesng.com', 2, 'news', 'nigeria', 'other', 0, 0, 0, 1, 0, 'active', 'General news feed: only stories that pass the science-topic check are kept.'],
        ['Punch (Nigeria)', 'https://punchng.com', 'https://punchng.com/feed/', 'punchng.com', 2, 'news', 'nigeria', 'other', 0, 0, 0, 1, 0, 'active', 'General news feed: only stories that pass the science-topic check are kept.'],
        ['TheCable (Nigeria)', 'https://www.thecable.ng', 'https://www.thecable.ng/feed', 'thecable.ng', 2, 'news', 'nigeria', 'other', 0, 0, 0, 1, 0, 'paused', 'The site blocks automated readers (bot protection), so the feed could not be fetched. Try again later, or ask the publisher for feed access.'],
        ['The Guardian Nigeria', 'https://guardian.ng', 'https://guardian.ng/feed/', 'guardian.ng', 2, 'news', 'nigeria', 'other', 0, 0, 0, 1, 0, 'paused', 'The site blocks automated readers, so the feed could not be fetched.'],
        ['Reuters: Science', 'https://www.reuters.com/science/', 'https://www.reuters.com/arc/outboundfeeds/v3/all/category/science/?outputType=xml', 'reuters.com', 2, 'news', 'world', 'other', 0, 0, 0, 0, 0, 'paused', 'Reuters no longer publishes a free public RSS feed. Leave paused unless you have a licensed feed address.'],
        ['SciDev.Net', 'https://www.scidev.net', 'https://www.scidev.net/global/feed/', 'scidev.net', 2, 'news', 'africa', 'other', 0, 0, 0, 0, 0, 'paused', 'The address returned a web page instead of a feed. Look for the current RSS link on scidev.net.'],
        ['Nigerian Academy of Science', 'https://www.nas.edu.ng', 'https://www.nas.edu.ng/feed/', 'nas.edu.ng', 2, 'news', 'nigeria', 'other', 0, 0, 0, 0, 0, 'paused', 'The site could not be reached when this was set up. Check the address and press Resume.'],
        // Tier 3: press-release wires (labelled as such)
        ['Phys.org', 'https://phys.org', 'https://phys.org/rss-feed/', 'phys.org', 3, 'press_release', 'world', 'other', 0, 0, 0, 0, 0, 'active', 'Mostly university press releases.'],
        ['ScienceDaily', 'https://www.sciencedaily.com', 'https://www.sciencedaily.com/rss/top/science.xml', 'sciencedaily.com', 3, 'press_release', 'world', 'other', 0, 0, 0, 0, 0, 'active', 'Mostly university press releases.'],
        ['EurekAlert!', 'https://www.eurekalert.org', 'https://www.eurekalert.org/rss.xml', 'eurekalert.org', 3, 'press_release', 'world', 'other', 0, 0, 0, 0, 0, 'paused', 'The feed address returned “not found”. Find the current RSS link on eurekalert.org and press Resume.'],
    ];

    $ins = $pdo->prepare('INSERT INTO news_sources (name, site_url, feed_url, domains, tier, kind, region, default_topic, is_official, can_break, all_breaking, topic_filter, show_images, status, paused_reason, notes, is_seed)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)');
    if ((int)$pdo->query('SELECT COUNT(*) FROM news_sources')->fetchColumn() === 0) {
        foreach ($S as $r) {
            $paused = $r[13] === 'paused';
            $ins->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7], $r[8], $r[9], $r[10], $r[11], $r[12], $r[13], $paused ? 'Paused at install: ' . $r[14] : '', $paused ? '' : $r[14]]);
        }
    }

    $terms = ['sponsored', 'advertorial', 'paid post', 'promoted content', 'miracle cure', "you won't believe", 'shocking', 'doctors hate', 'conspiracy', 'flat earth', 'chemtrail', 'horoscope', 'astrology', 'psychic', 'weight loss', 'get rich', 'click here', 'giveaway'];
    $t = $pdo->prepare('INSERT IGNORE INTO news_blocked_terms (term) VALUES (?)');
    foreach ($terms as $term) {
        $t->execute([$term]);
    }

    $pdo->prepare("INSERT INTO settings (skey, svalue) VALUES ('enable_news', '1') ON DUPLICATE KEY UPDATE svalue = '1'")->execute();
};
