<?php
declare(strict_types=1);

/**
 * Every editable site setting, grouped for the admin "Profile & media" and "Settings" screens.
 * field = [key, type, label, default, help]
 * types: text, email, textarea, markdown, image, checkbox
 */
final class SettingsDef
{
    public static function groups(): array
    {
        return [
            'profile' => ['label' => 'Profile & photos', 'icon' => '👤', 'fields' => [
                ['owner_name', 'text', 'Your name (with title)', 'Dr. [Your Name]', 'Shown in the header, About page and footer.'],
                ['owner_title', 'text', 'Headline', 'Atmospheric Scientist · Academic · Researcher', ''],
                ['owner_institution', 'text', 'Institution', 'University / Institution, Nigeria', ''],
                ['portrait', 'image', 'Portrait photo', '', 'Best: a portrait-orientation photo, at least 800 px wide. A placeholder is shown until you upload one.'],
                ['hero_image', 'image', 'Home page background image (optional)', '', 'Leave empty to keep the blue sky gradient.'],
                ['bio_short', 'textarea', 'Short bio (home page)', 'Replace this with two or three sentences introducing yourself, your research, and why people should learn with you.', ''],
                ['bio_full', 'markdown', 'Full biography (About page)', "Your story, qualifications, research interests (atmospheric physics, climate, the West African monsoon…), memberships and mission.\n\nWrite as much as you like. Use the buttons above the box for bold, italics, headings, lists and links.", ''],
                ['memberships', 'textarea', 'Memberships & affiliations', "Nigerian Meteorological Society\nAmerican Geophysical Union", 'One per line.'],
            ]],
            'home' => ['label' => 'Home page', 'icon' => '🏠', 'fields' => [
                ['hero_eyebrow', 'text', 'Small line above the headline', 'ATMOSPHERIC SCIENTIST · EDUCATOR · RESEARCHER', ''],
                ['hero_title', 'text', 'Headline', 'Understanding the sky. Mastering Physics.', ''],
                ['hero_text', 'textarea', 'Sub-headline', 'Learn with a working atmospheric scientist: university-level physics courses, science news, and research from Nigeria to the world.', ''],
                ['stat1_value', 'text', 'Number 1', '', 'Leave a number blank to hide it. Example: 40+'],
                ['stat1_label', 'text', 'Label 1', 'publications', ''],
                ['stat2_value', 'text', 'Number 2', '', ''],
                ['stat2_label', 'text', 'Label 2', 'years teaching', ''],
                ['stat3_value', 'text', 'Number 3', '', ''],
                ['stat3_label', 'text', 'Label 3', 'students taught', ''],
                ['stat4_value', 'text', 'Number 4', '', ''],
                ['stat4_label', 'text', 'Label 4', 'awards', ''],
                ['work_intro', 'textarea', 'Work-with-me introduction', 'Available for consulting, speaking, training workshops and postgraduate supervision.', ''],
            ]],
            'brand' => ['label' => 'Brand', 'icon' => '🎨', 'fields' => [
                ['site_name', 'text', 'Site / brand name', 'ClearSky Academy', 'Change once, updates everywhere.'],
                ['tagline', 'text', 'Tagline', 'Physics made clear', ''],
                ['logo', 'image', 'Logo (optional)', '', 'Wide PNG/SVG-like image on a transparent background, about 600 × 120 px. Until uploaded, a text logo is used.'],
                ['favicon', 'image', 'Favicon / site icon', '', 'Square image, at least 128 × 128 px.'],
                ['footer_text', 'text', 'Footer line', 'Science, taught clearly.', ''],
            ]],
            'contact' => ['label' => 'Contact & social', 'icon' => '✉', 'fields' => [
                ['contact_email', 'email', 'Public contact email', '', 'Shown on the Contact page.'],
                ['notify_email', 'email', 'Where to send enquiry alerts', '', 'You get an email here when someone fills the contact form.'],
                ['phone', 'text', 'Phone', '', ''],
                ['whatsapp', 'text', 'WhatsApp number (international format)', '', 'Example: 2348012345678 (no + or spaces)'],
                ['city', 'text', 'Location', 'Nigeria', ''],
                ['social_linkedin', 'text', 'LinkedIn URL', '', ''],
                ['social_x', 'text', 'X (Twitter) URL', '', ''],
                ['social_facebook', 'text', 'Facebook URL', '', ''],
                ['social_instagram', 'text', 'Instagram URL', '', ''],
                ['social_youtube', 'text', 'YouTube URL', '', ''],
                ['social_scholar', 'text', 'Google Scholar URL', '', ''],
                ['social_orcid', 'text', 'ORCID URL', '', ''],
                ['social_researchgate', 'text', 'ResearchGate URL', '', ''],
            ]],
            'promo' => ['label' => 'Speaking & media kit', 'icon' => '🎙', 'fields' => [
                ['speaker_intro', 'textarea', 'Speaking page introduction', 'Invite me to speak at your conference, school, company or event. I give talks on weather, climate and physics that audiences can understand and use.', 'Shown at the top of the Speaking page.'],
                ['media_bio_short', 'textarea', 'Media bio, short (about 50 words)', '', 'Event organisers and journalists copy this. Leave empty to use your short bio from Profile & photos.'],
                ['media_bio_long', 'textarea', 'Media bio, long (about 150 words)', '', 'Leave empty to use your full biography.'],
                ['speaker_requirements', 'textarea', 'What organisers should know', "Projector and microphone\nA 15-minute sound check before the session\nTravel and accommodation arranged by the host for events outside my city", 'One per line. Shown on the Speaking page.'],
                ['speaker_fee_note', 'text', 'Fees note', 'Speaking fees depend on the event. Send the details and I will reply with a quote. Schools and student societies: ask about reduced rates.', ''],
                ['speaker_lead_time', 'text', 'Notice needed', 'Please contact me at least 4 weeks before the event.', ''],
            ]],
            'security' => ['label' => 'Security', 'icon' => '🔐', 'fields' => [
                ['require_2fa_admin', 'checkbox', 'Require two-step verification for administrators', '0', 'When ticked, an administrator without it can only open the security page until it is set up.'],
                ['security_alert_emails', 'checkbox', 'Email people about security events (new device sign-in, password or 2FA changes)', '1', ''],
            ]],
            'modules' => ['label' => 'Site sections', 'icon' => '🧩', 'fields' => [
                ['enable_learn', 'checkbox', 'Learn (courses) is live', '0', 'Switched on automatically when the courses phase is installed.'],
                ['enable_shop', 'checkbox', 'Shop is live', '0', ''],
                ['enable_news', 'checkbox', 'News is live', '0', ''],
            ]],
            'news' => ['label' => 'News rules', 'icon' => '📰', 'fields' => [
                ['news_paused', 'checkbox', 'PAUSE ALL NEWS (kill-switch)', '0', 'Tick to stop fetching and hide the whole News section and ticker until you untick it.'],
                ['news_digest', 'checkbox', 'Email me a daily summary of what was published, flagged and hidden', '1', 'Sent to your “enquiry alerts” address.'],
                ['news_breaking_window_hours', 'text', 'Breaking: corroboration window (hours)', '2', 'Independent sources must report the same story within this time.'],
                ['news_breaking_min_sources', 'text', 'Breaking: minimum independent Tier 1/2 sources', '2', 'Used when no Tier 1 authority has reported the story itself.'],
                ['news_interval_hours', 'text', 'Update the news automatically every (hours)', '2', 'From 1 to 24. Runs by itself when visitors are on the site, and exactly on time if you also schedule cron/fetch_news.php (see Admin → Science news).'],
                ['news_ticker_hours', 'text', 'Ticker: show breaking stories for (hours)', '12', ''],
                ['news_ticker_latest', 'checkbox', 'When nothing is breaking, scroll the latest headlines in the bar instead of hiding it', '1', ''],
                ['news_probation_hours', 'text', 'New sources: probation (hours)', '48', 'During probation a source’s stories show as “New” and are never marked breaking.'],
                ['news_recheck_hours', 'text', 'Re-check published stories for (hours)', '48', 'The original page is re-checked every 6 hours in this period; removed or substantially changed stories are hidden.'],
                ['news_max_age_days', 'text', 'Ignore stories older than (days)', '7', ''],
                ['news_flood_limit', 'text', 'Auto-pause a source that suddenly sends more than this many new stories at once', '60', 'A sudden flood can mean a hijacked or broken feed.'],
                ['news_keywords_science', 'textarea', 'Science-topic words (for general news feeds)', "science\nscientist\nresearcher\nresearch\nphysics\nphysicist\nchemistry\nbiology\nastronomy\nastronaut\nspacecraft\nspace station\nspace agency\nouter space\nnasrda\nnasa\nsatellite\nrocket\ntelescope\nplanet\ngalaxy\nclimate\nweather\nrainfall\ndrought\nharmattan\natmosphere\nozone\nglobal warming\nnimet\nmeteorolog\nearthquake\nvolcano\nnobel\nlaborator\nquantum\nscience and technology", 'One word or phrase per line. Matches the start of words (so “meteorolog” also matches “meteorology”). A story from a source marked “general news feed” must contain at least one.'],
                ['news_keywords_breaking', 'textarea', 'Breaking-news signal words', "breaking\ndiscover\nbreakthrough\nnobel prize\nawarded\nannounce\nfirst-ever\nfirst time\nrecord-breaking\nrecord high\nrecord low\nearthquake\ntsunami\neruption\nvolcano erupt\nhurricane\ncyclone\ntyphoon\nlaunch\nlands\nlanding\ndetect\nconfirm\nemergency\nalert\nwarning", 'A story can only become BREAKING if its headline contains one of these AND it meets the source rules (Tier 1 authority, or enough independent sources).'],
            ]],
            'shop' => ['label' => 'Shop settings', 'icon' => '🛒', 'fields' => [
                ['shop_intro', 'text', 'Shop banner line', 'Original physics and atmosphere T-shirts. Wear the science.', ''],
                ['dispatch_text', 'text', 'Dispatch promise', 'Dispatched within 2 working days', 'Shown on product pages and at checkout.'],
                ['default_shipping_fee', 'text', 'Default delivery fee (₦)', '3500', 'Used for any state without its own fee (set state fees under Shop → Delivery fees).'],
                ['free_shipping_over', 'text', 'Free delivery on orders over (₦)', '0', '0 means no free delivery.'],
                ['pickup_enabled', 'checkbox', 'Offer pickup (no delivery fee)', '1', ''],
                ['pickup_address', 'textarea', 'Pickup address and hours', 'Add your pickup address and opening hours here.', 'Shown to customers who choose pickup.'],
                ['order_prefix', 'text', 'Order number prefix', 'CSA', 'Letters only. Order numbers look like CSA-1043. Changing it affects new orders only.'],
                ['size_guide', 'markdown', 'Size guide', "Placeholder measurements: replace them with your real ones.

- S: chest 96 cm, length 68 cm
- M: chest 102 cm, length 70 cm
- L: chest 108 cm, length 72 cm
- XL: chest 114 cm, length 74 cm
- XXL: chest 120 cm, length 76 cm

Measure a T-shirt you like, lying flat, and compare.", 'Shown on every product page.'],
            ]],
            'seo' => ['label' => 'Search & sharing', 'icon' => '🔎', 'fields' => [
                ['meta_description', 'textarea', 'Site description for Google', 'ClearSky Academy: university-level physics courses, science news and research from an atmospheric scientist in Nigeria.', 'About 150 characters.'],
                ['og_image', 'image', 'Image shown when links are shared', '', '1200 × 630 px works best.'],
            ]],
            'payments' => ['label' => 'Bank details (for the shop)', 'icon' => '🏦', 'fields' => [
                ['bank_name', 'text', 'Bank', '[Your Bank]', 'Shown to customers at checkout once the shop goes live (a later phase).'],
                ['bank_account_name', 'text', 'Account name', '[Your Name]', ''],
                ['bank_account_number', 'text', 'Account number', '0123456789', ''],
                ['order_hold_hours', 'text', 'Hold unpaid orders for (hours)', '24', ''],
            ]],
        ];
    }

    public static function defaults(): array
    {
        static $d = null;
        if ($d === null) {
            $d = [];
            foreach (self::groups() as $g) {
                foreach ($g['fields'] as $f) {
                    $d[$f[0]] = $f[3];
                }
            }
        }
        return $d;
    }
}
