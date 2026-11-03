<?php
declare(strict_types=1);

/**
 * Definitions for the admin list/add/edit screens of every simple content type.
 * field = ['name', 'type', 'Label', [options]]  options: required, help, options (select), default, rows, max
 * types: text, textarea, markdown, number, date, datetime, select, checkbox, url, image, pdf
 */
final class Entities
{
    public static function slugs(): string
    {
        return implode('|', array_keys(self::all()));
    }

    public static function get(string $slug): array
    {
        $all = self::all();
        if (!isset($all[$slug])) {
            abort(404);
        }
        return ['slug' => $slug] + $all[$slug];
    }

    public static function normaliseTags(string $tags): string
    {
        $parts = array_unique(array_filter(array_map(fn($t) => mb_substr(trim($t), 0, 40), explode(',', $tags))));
        return implode(', ', $parts);
    }

    public static function all(): array
    {
        $visible = ['is_visible', 'checkbox', 'Show on the website', ['default' => '1']];
        $order = ['sort_order', 'number', 'Display order', ['default' => '0', 'help' => 'Lower numbers appear first.']];

        return [
            'publications' => [
                'table' => 'publications', 'label' => 'Publication', 'plural' => 'Publications', 'icon' => '📄',
                'help' => 'Newest first on the website. Use “Import” to add many at once from a DOI, ORCID, BibTeX or CSV.',
                'order' => 'year DESC, id DESC',
                'list' => ['title' => 'Title', 'venue' => 'Journal / venue', 'year' => 'Year', 'type' => 'Type'],
                'fields' => [
                    ['title', 'text', 'Title', ['required' => true, 'max' => 500]],
                    ['authors', 'text', 'Authors', ['help' => 'Example: A. B. Adeyemi, C. Okafor, D. Musa', 'max' => 1000]],
                    ['venue', 'text', 'Journal / conference / publisher', ['max' => 300]],
                    ['year', 'number', 'Year', ['min' => 1900, 'max' => 2100]],
                    ['type', 'select', 'Type', ['options' => Importer::TYPES, 'default' => 'journal']],
                    ['doi', 'text', 'DOI', ['help' => 'Example: 10.1029/2020JD033411 (just the DOI, not the link)', 'max' => 200]],
                    ['url', 'url', 'Link (if no DOI)', ['max' => 500]],
                    ['abstract', 'textarea', 'Abstract', ['rows' => 6]],
                    ['pdf_file', 'pdf', 'PDF of the paper (optional)', ['help' => 'Only upload papers you have the right to share.']],
                    ['tags', 'text', 'Topic tags', ['help' => 'Comma-separated, e.g. monsoon, aerosols', 'max' => 300]],
                    ['is_featured', 'checkbox', 'Feature on the home page', []],
                    $order, $visible,
                ],
            ],
            'awards' => [
                'table' => 'awards', 'label' => 'Award', 'plural' => 'Awards', 'icon' => '🏅',
                'help' => 'Awards, fellowships and grants shown on the About page.',
                'order' => 'sort_order, year DESC, id DESC',
                'list' => ['title' => 'Award', 'org' => 'Awarded by', 'year' => 'Year'],
                'fields' => [
                    ['title', 'text', 'Award / honour', ['required' => true, 'max' => 300]],
                    ['org', 'text', 'Awarded by', ['max' => 300]],
                    ['year', 'number', 'Year', ['min' => 1900, 'max' => 2100]],
                    ['description', 'textarea', 'Details (optional)', ['rows' => 3]],
                    $order, $visible,
                ],
            ],
            'talks' => [
                'table' => 'talks', 'label' => 'Talk or media item', 'plural' => 'Talks & media', 'icon' => '🎤',
                'help' => 'Conference talks, keynotes, interviews, podcasts and videos.',
                'order' => 'talk_date DESC, id DESC',
                'list' => ['title' => 'Title', 'event' => 'Event / outlet', 'talk_date' => 'Date', 'kind' => 'Kind'],
                'fields' => [
                    ['title', 'text', 'Title', ['required' => true, 'max' => 300]],
                    ['event', 'text', 'Event or outlet', ['max' => 300]],
                    ['location', 'text', 'Location', ['max' => 200]],
                    ['talk_date', 'date', 'Date', []],
                    ['kind', 'select', 'Kind', ['options' => ['talk' => 'Talk', 'keynote' => 'Keynote', 'media' => 'Media appearance', 'interview' => 'Interview', 'podcast' => 'Podcast', 'video' => 'Video'], 'default' => 'talk']],
                    ['url', 'url', 'Link (video, slides, article)', ['max' => 500]],
                    $order, $visible,
                ],
            ],
            'projects' => [
                'table' => 'projects', 'label' => 'Project or research area', 'plural' => 'Projects & research areas', 'icon' => '🔬',
                'help' => 'Shown on the Research page.',
                'order' => 'sort_order, id',
                'list' => ['title' => 'Title', 'status' => 'Status'],
                'fields' => [
                    ['title', 'text', 'Title', ['required' => true, 'max' => 300]],
                    ['summary', 'textarea', 'Summary', ['rows' => 4]],
                    ['status', 'select', 'Status', ['options' => ['ongoing' => 'Ongoing', 'completed' => 'Completed', 'planned' => 'Planned'], 'default' => 'ongoing']],
                    ['url', 'url', 'More information link', ['max' => 500]],
                    ['image', 'image', 'Image (optional)', []],
                    $order, $visible,
                ],
            ],
            'testimonials' => [
                'table' => 'testimonials', 'label' => 'Testimonial', 'plural' => 'Testimonials', 'icon' => '💬',
                'help' => 'Shown on the home page. Only add quotes you have permission to publish.',
                'order' => 'sort_order, id',
                'list' => ['name' => 'Name', 'role' => 'Role'],
                'fields' => [
                    ['name', 'text', 'Name', ['required' => true, 'max' => 150]],
                    ['role', 'text', 'Role / level', ['help' => 'Example: 300L Physics student', 'max' => 200]],
                    ['quote', 'textarea', 'Quote', ['required' => true, 'rows' => 4]],
                    $order, $visible,
                ],
            ],
            'timeline' => [
                'table' => 'timeline', 'label' => 'Timeline entry', 'plural' => 'Biography timeline', 'icon' => '🕘',
                'help' => 'Education and career milestones on the About page.',
                'order' => 'sort_order, id',
                'list' => ['year' => 'When', 'title' => 'Milestone'],
                'fields' => [
                    ['year', 'text', 'When', ['required' => true, 'help' => 'Example: 2018 or 2014–2018', 'max' => 40]],
                    ['title', 'text', 'Milestone', ['required' => true, 'max' => 300]],
                    ['detail', 'text', 'Detail (optional)', ['max' => 500]],
                    ['sort_order', 'number', 'Display order', ['default' => '0', 'help' => 'Lower numbers appear first. Use 10, 20, 30… so you can insert items later.']],
                    $visible,
                ],
            ],
            'posts' => [
                'table' => 'posts', 'label' => 'Blog post', 'plural' => 'Blog posts', 'icon' => '📝',
                'help' => 'Articles, explainers and opinion pieces. Posts marked “Published” appear on the Blog page.',
                'order' => 'COALESCE(published_at, created_at) DESC, id DESC',
                'list' => ['title' => 'Title', 'author_name' => 'Author', 'status' => 'Status', 'published_at' => 'Date'],
                'fields' => [
                    ['title', 'text', 'Title', ['required' => true, 'max' => 250]],
                    ['slug', 'text', 'Web address ending', ['help' => 'Leave empty to create from the title.', 'max' => 200]],
                    ['excerpt', 'textarea', 'Short summary', ['rows' => 3, 'max' => 400, 'help' => 'Shown in lists and when the post is shared.']],
                    ['body', 'markdown', 'Article', ['required' => true, 'rows' => 18]],
                    ['cover_image', 'image', 'Cover image (optional)', []],
                    ['tags', 'text', 'Tags', ['help' => 'Comma-separated, e.g. climate, physics education', 'max' => 300]],
                    ['status', 'select', 'Status', ['options' => ['draft' => 'Draft (hidden)', 'published' => 'Published', 'pending' => 'Waiting for approval (guest article)', 'rejected' => 'Sent back to the author'], 'default' => 'draft']],
                    ['author_name', 'text', 'Author name shown on the post', ['max' => 150, 'help' => 'Leave empty to use your own name from Profile & photos. For guest articles this is the author\'s registered name.']],
                    ['author_email', 'text', 'Author contact email shown on the post', ['max' => 190, 'help' => 'Leave empty to use your contact email. Clear it to show no email at all by typing a single dash (-).']],
                    ['published_at', 'datetime', 'Publish date', ['help' => 'Leave empty to publish now. A future date schedules the post.']],
                ],
                'no_visible' => true,
            ],
            'categories' => [
                'table' => 'shop_categories', 'label' => 'Shop category', 'plural' => 'Shop categories', 'icon' => '🗂',
                'help' => 'Sections of your shop, for example T-shirts, Books, Mugs, Teaching aids. Customers can browse each category on its own page. A category appears in the shop once it has at least one product on sale. Choose the category when you add or edit a product.',
                'order' => 'sort_order, name', 'no_sample' => true, 'tab_of' => 'shop', 'tab' => 'categories',
                'list' => ['name' => 'Category', 'slug' => 'Web address', 'sort_order' => 'Order'],
                'fields' => [
                    ['name', 'text', 'Category name', ['required' => true, 'max' => 80]],
                    ['slug', 'text', 'Web address ending', ['help' => 'Leave empty to create from the name, e.g. teaching-aids.', 'max' => 90]],
                    ['description', 'textarea', 'Short description (shown at the top of the category page)', ['rows' => 3, 'max' => 600]],
                    ['image', 'image', 'Banner image (optional)', []],
                    $order, $visible,
                ],
            ],
            'services' => [
                'table' => 'services', 'label' => 'Service', 'plural' => 'Services', 'icon' => '💼',
                'help' => 'What you offer: consulting, workshops, supervision and so on. Shown on the Services page and the home page. Each service has an Enquire button that opens the contact form with the right topic selected.',
                'order' => 'sort_order, id',
                'list' => ['title' => 'Service', 'price_note' => 'Price note'],
                'fields' => [
                    ['title', 'text', 'Service name', ['required' => true, 'max' => 150]],
                    ['icon', 'text', 'Icon (one emoji, optional)', ['help' => 'For example 📊 or 🎓', 'max' => 8]],
                    ['summary', 'text', 'One-line summary', ['max' => 400]],
                    ['details', 'markdown', 'Details (what is included, who it is for)', ['rows' => 10]],
                    ['price_note', 'text', 'Price note (optional)', ['help' => 'For example “From ₦50,000”, “Fee on request”.', 'max' => 120]],
                    ['enquiry_type', 'select', 'Contact-form topic', ['options' => SiteController::ENQUIRY_TYPES, 'default' => 'general']],
                    $order, $visible,
                ],
            ],
            'topics' => [
                'table' => 'speaking_topics', 'label' => 'Speaking topic', 'plural' => 'Speaking topics', 'icon' => '🎙',
                'help' => 'Talks you offer. They appear on the Speaking page, which also holds your media kit and booking button.',
                'order' => 'sort_order, id',
                'list' => ['title' => 'Topic', 'audience' => 'Audience', 'duration' => 'Length'],
                'fields' => [
                    ['title', 'text', 'Talk title', ['required' => true, 'max' => 200]],
                    ['summary', 'textarea', 'Abstract', ['rows' => 5]],
                    ['audience', 'text', 'Best for', ['help' => 'For example “Secondary schools, undergraduates”.', 'max' => 200]],
                    ['duration', 'text', 'Length', ['help' => 'For example “45 min”.', 'max' => 60]],
                    $order, $visible,
                ],
            ],
            'press' => [
                'table' => 'press_items', 'label' => 'Press or media mention', 'plural' => 'In the media', 'icon' => '📰',
                'help' => 'Newspaper articles, interviews, radio, TV and podcasts that feature you. Shown on the Press page.',
                'order' => 'pub_date DESC, id DESC',
                'list' => ['title' => 'Title', 'outlet' => 'Outlet', 'pub_date' => 'Date', 'kind' => 'Kind'],
                'fields' => [
                    ['outlet', 'text', 'Outlet', ['required' => true, 'max' => 200, 'help' => 'For example “Punch”, “Channels TV”, “BBC Focus on Africa”.']],
                    ['title', 'text', 'Headline or title', ['required' => true, 'max' => 300]],
                    ['kind', 'select', 'Kind', ['options' => ['article' => 'Article', 'interview' => 'Interview', 'radio' => 'Radio', 'tv' => 'TV', 'podcast' => 'Podcast', 'feature' => 'Feature / profile'], 'default' => 'article']],
                    ['pub_date', 'date', 'Date', []],
                    ['url', 'url', 'Link', ['max' => 500]],
                    ['quote', 'textarea', 'Pull quote (optional)', ['rows' => 3, 'help' => 'Only a short line from the piece.']],
                    $order, $visible,
                ],
            ],
            'events' => [
                'table' => 'events', 'label' => 'Event', 'plural' => 'Events', 'icon' => '📅',
                'help' => 'Talks, workshops, conferences and public lectures. Upcoming events appear first on the Events page; past ones move to the archive by themselves.',
                'order' => 'start_date DESC, id DESC',
                'list' => ['title' => 'Event', 'start_date' => 'Date', 'location' => 'Where', 'kind' => 'Kind'],
                'fields' => [
                    ['title', 'text', 'Event title', ['required' => true, 'max' => 250]],
                    ['start_date', 'date', 'Date (first day)', ['required' => true]],
                    ['end_date', 'date', 'Last day (if more than one day)', []],
                    ['time_text', 'text', 'Time (optional)', ['help' => 'For example “10:00–12:00 WAT”.', 'max' => 80]],
                    ['location', 'text', 'Place or “Online”', ['max' => 200]],
                    ['kind', 'select', 'Kind', ['options' => ['talk' => 'Talk', 'keynote' => 'Keynote', 'workshop' => 'Workshop', 'conference' => 'Conference', 'webinar' => 'Webinar', 'course' => 'Course start'], 'default' => 'talk']],
                    ['summary', 'textarea', 'About the event', ['rows' => 4]],
                    ['url', 'url', 'Link (programme or registration)', ['max' => 500]],
                    $order, $visible,
                ],
            ],
            'links' => [
                'table' => 'link_items', 'label' => 'Link', 'plural' => 'Link page', 'icon' => '🔗',
                'help' => 'The page at /links is a single page to put in your social-media profiles (like a “link in bio”). Add the links you want people to tap, in order. If you add none, it lists your main pages and social profiles automatically.',
                'order' => 'sort_order, id', 'no_sample' => true,
                'list' => ['label' => 'Label', 'url' => 'Address'],
                'fields' => [
                    ['label', 'text', 'Button text', ['required' => true, 'max' => 150]],
                    ['url', 'text', 'Address', ['required' => true, 'max' => 500, 'help' => 'A full link (https://…) or a page of this site such as /shop.']],
                    ['icon', 'text', 'Icon (one emoji, optional)', ['max' => 8]],
                    $order, $visible,
                ],
            ],
            'resources' => [
                'table' => 'resources', 'label' => 'Free resource', 'plural' => 'Free resources', 'icon' => '📥',
                'help' => 'Free downloads for students and teachers: formula sheets, problem sets, lecture notes, posters. Upload a PDF, or link to a file elsewhere. Downloads are counted. Share only what is yours, openly licensed, in the public domain, or that you have written permission to share: choose the licence honestly and credit the author. Never upload pirated books or papers; if you only want to point to someone else\'s page, use Free tools (links) instead.',
                'order' => 'sort_order, id DESC',
                'list' => ['title' => 'Title', 'topic' => 'Topic', 'downloads' => 'Downloads'],
                'fields' => [
                    ['title', 'text', 'Title', ['required' => true, 'max' => 200]],
                    ['description', 'text', 'Short description', ['max' => 500]],
                    ['topic', 'text', 'Topic', ['help' => 'For example Mechanics, Weather, Maths. Used to group the list.', 'max' => 80]],
                    ['pdf_file', 'pdf', 'PDF file', []],
                    ['url', 'url', 'Or a link to the file', ['max' => 500, 'help' => 'Used only if no PDF is uploaded.']],
                    ['license', 'select', 'Licence (what readers may do with it)', ['options' => Rights::LICENSES, 'default' => 'own']],
                    ['author', 'text', 'Author or rights holder (credit line)', ['max' => 200, 'help' => 'Your name for your own work. Required for Creative Commons and permission items.']],
                    ['source_url', 'url', 'Where the licence or permission can be checked (optional)', ['max' => 500, 'help' => 'For example the Creative Commons page of the original, or the owner\'s page.']],
                    ['rights_confirmed', 'checkbox', 'I confirm I wrote this, or it is openly licensed or public domain, or I have written permission to share it', []],
                    $order, $visible,
                ],
            ],
            'toolbox' => [
                'table' => 'toolbox_links', 'label' => 'Free tool or link', 'plural' => 'Free tools', 'icon' => '🧰',
                'help' => 'The directory at /toolbox: free video lessons, open textbooks, software, data and tutorials. List only legitimate sources (the owner\'s own site, open-access or openly licensed material, software its authors give away), never pirated books or cracked software. YouTube links to a single video show a thumbnail automatically. A link marked broken after a check is hidden from visitors until you fix it.',
                'order' => 'is_featured DESC, sort_order, title',
                'list' => ['title' => 'Title', 'subject' => 'Subject', 'kind' => 'Type', 'link_status' => 'Link', 'clicks' => 'Clicks'],
                'fields' => [
                    ['title', 'text', 'Title', ['required' => true, 'max' => 200]],
                    ['url', 'url', 'Link (web address of the resource)', ['required' => true, 'max' => 600, 'help' => 'Use the official page. For a YouTube video paste the video link; for a whole channel or playlist paste that link.']],
                    ['kind', 'select', 'Type', ['options' => Toolbox::KINDS, 'default' => 'tutorial']],
                    ['subject', 'text', 'Subject', ['required' => true, 'max' => 60, 'help' => 'For example Physics, Mathematics, Meteorology & climate, Computing & programming, Research skills. Type a new one to create a new section.']],
                    ['audience', 'select', 'Best for', ['options' => Toolbox::AUDIENCES, 'default' => 'all']],
                    ['provider', 'text', 'Provided by', ['max' => 120, 'help' => 'For example NCAR, MIT, Khan Academy.']],
                    ['description', 'text', 'One-line description', ['max' => 500]],
                    ['license', 'select', 'Cost / licence', ['options' => Toolbox::LICENSES, 'default' => 'free']],
                    ['notes', 'markdown', 'Notes: how to install or start (optional)', ['rows' => 12, 'help' => 'When filled in, the card shows “Read the notes”. Good for step-by-step install tips for models like WRF.']],
                    ['is_featured', 'checkbox', 'Show near the top', []],
                    $order, $visible,
                ],
            ],
            'coupons' => [
                'table' => 'coupons', 'label' => 'Coupon', 'plural' => 'Coupons', 'icon' => '🎟',
                'help' => 'Discount codes customers enter in the cart. The discount applies to the items, not to delivery.',
                'order' => 'id DESC', 'no_visible' => true, 'no_sample' => true, 'tab_of' => 'shop', 'tab' => 'coupons',
                'list' => ['code' => 'Code', 'type' => 'Type', 'value' => 'Value', 'used_count' => 'Times used', 'expires_on' => 'Expires', 'is_active' => 'Active'],
                'fields' => [
                    ['code', 'text', 'Code', ['required' => true, 'max' => 40, 'help' => 'Letters and numbers, e.g. WELCOME10. Customers type it in the cart.']],
                    ['type', 'select', 'Discount type', ['options' => ['percent' => 'Percentage off', 'fixed' => 'Fixed amount off (₦)'], 'default' => 'percent']],
                    ['value', 'number', 'Value', ['required' => true, 'min' => 1, 'max' => 1000000, 'help' => 'A percentage (1–100) or a Naira amount.']],
                    ['min_order', 'number', 'Minimum order (₦)', ['min' => 0, 'default' => '0', 'help' => '0 means no minimum.']],
                    ['max_uses', 'number', 'Maximum number of uses', ['min' => 0, 'default' => '0', 'help' => '0 means unlimited.']],
                    ['expires_on', 'date', 'Expires on (optional)', []],
                    ['is_active', 'checkbox', 'Active', ['default' => '1']],
                ],
            ],
            'pages' => [
                'table' => 'pages', 'label' => 'Page', 'plural' => 'Legal & info pages', 'icon' => '📃',
                'help' => 'Privacy policy and terms. These are starting templates: please review them for your situation.',
                'order' => 'id', 'can_create' => false, 'can_delete' => false, 'no_visible' => true,
                'list' => ['title' => 'Page', 'updated_at' => 'Last updated'],
                'fields' => [
                    ['title', 'text', 'Title', ['required' => true, 'max' => 200]],
                    ['body', 'markdown', 'Content', ['required' => true, 'rows' => 20]],
                ],
            ],
        ];
    }
}
