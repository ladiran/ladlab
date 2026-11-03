<?php
declare(strict_types=1);

/** Every admin screen (except login) requires a signed-in user. POST handlers must call verify(). */
abstract class AdminBase
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    protected function page(string $template, array $vars = []): void
    {
        view($template, $vars, 'layout/admin');
    }

    protected function verify(): void
    {
        csrf_check();
    }

    /** Menu structure shared by the admin layout. */
    public static function menu(): array
    {
        $g = SettingsDef::groups();
        return [
            ['Dashboard', 'admin', '🏠'],
            ['Profile & photos', 'admin/settings/profile', $g['profile']['icon']],
            ['CV', 'admin/cv', '📄'],
            ['Publications', 'admin/publications', '📚'],
            ['Awards', 'admin/awards', '🏅'],
            ['Talks & media', 'admin/talks', '🎤'],
            ['Projects', 'admin/projects', '🔬'],
            ['Biography timeline', 'admin/timeline', '🕘'],
            ['Services', 'admin/services', '💼'],
            ['Speaking topics', 'admin/topics', '🎙'],
            ['Speaking & media kit', 'admin/settings/promo', '🧳'],
            ['In the media', 'admin/press', '📰'],
            ['Events', 'admin/events', '📅'],
            ['Free tools (links)', 'admin/toolbox', '🧰'],
            ['Free resources', 'admin/resources', '📥'],
            ['Link page', 'admin/links', '🔗'],
            ['Testimonials', 'admin/testimonials', '💬'],
            ['Blog posts', 'admin/posts', '📝'],
            ['Article submissions', 'admin/articles', '✍'],
            ['Shop orders', 'admin/orders', '🛒'],
            ['Products & stock', 'admin/shop/products', '👕'],
            ['Shop categories', 'admin/categories', '🗂'],
            ['Coupons', 'admin/coupons', '🎟'],
            ['Customers', 'admin/customers', '👥'],
            ['Courses', 'admin/courses', '🎓'],
            ['Student questions', 'admin/qa', '🙋'],
            ['Course reviews', 'admin/reviews', '⭐'],
            ['Students & payments', 'admin/enrolments', '🧑‍🎓'],
            ['Science news', 'admin/news', '📰'],
            ['Enquiries', 'admin/enquiries', '✉'],
            ['Subscribers', 'admin/subscribers', '📬'],
            ['Media library', 'admin/media', '🖼'],
            ['Home page text', 'admin/settings/home', $g['home']['icon']],
            ['Brand', 'admin/settings/brand', $g['brand']['icon']],
            ['Contact & social', 'admin/settings/contact', $g['contact']['icon']],
            ['Site sections', 'admin/settings/modules', $g['modules']['icon']],
            ['Search & sharing', 'admin/settings/seo', $g['seo']['icon']],
            ['Search engines (SEO)', 'admin/seo', '🔎'],
            ['Bank details', 'admin/settings/payments', $g['payments']['icon']],
            ['Legal pages', 'admin/pages', '📃'],
            ['My account', 'admin/account', '🔑'],
            ['Security', 'admin/security', '🔐'],
            ['Activity log', 'admin/audit', '🕵'],
        ];
    }
}
