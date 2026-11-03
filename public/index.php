<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

/* Not installed yet? Send the owner to the installer. */
if (!is_file(ROOT . '/storage/install.lock') && !is_file(ROOT . '/config/.env')) {
    header('Location: ' . BASE_PATH . '/install.php');
    exit;
}

/* New files copied over an older database? Show a friendly page to visitors; the admin area stays open so the update can be applied. */
if (!str_starts_with(request_path(), '/admin') && Migrator::pendingCached()) {
    http_response_code(503);
    header('Retry-After: 300');
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Updating</title>'
        . '<body style="font-family:Segoe UI,Arial,sans-serif;max-width:34rem;margin:15vh auto;padding:0 1.2rem;line-height:1.6;color:#0b1f3a">'
        . '<h1>We are updating the site</h1><p>Please check back in a few minutes.</p>'
        . '<p style="color:#5b6b82;font-size:.9rem">Site owner: a database update is waiting. Sign in at <a href="' . htmlspecialchars(url('/admin')) . '">the admin area</a> and press “Apply update now” (or run <code>php tools/migrate.php</code>).</p></body>';
    exit;
}

$router = new Router();

/* Public site */
$router->get('/', [SiteController::class, 'home']);
$router->get('/about', [SiteController::class, 'about']);
$router->get('/research', [SiteController::class, 'research']);
$router->get('/blog/feed.xml', [SiteController::class, 'feed']);
$router->get('/blog', [SiteController::class, 'blog']);
$router->get('/blog/{slug}', [SiteController::class, 'post']);
$router->get('/contact', [SiteController::class, 'contact']);
$router->post('/contact', [SiteController::class, 'contactSend']);
$router->post('/subscribe', [SiteController::class, 'subscribe']);
$router->get('/newsletter/confirm/{token}', [SiteController::class, 'confirmSubscription']);
$router->get('/newsletter/unsubscribe/{token}', [SiteController::class, 'unsubscribe']);
$router->get('/learn', [LearnController::class, 'index']);
$router->get('/learn/video/{id:\d+}', [LearnController::class, 'video']);
$router->get('/learn/file/{id:\d+}', [LearnController::class, 'file']);
$router->get('/learn/{slug}', [LearnController::class, 'course']);
$router->post('/learn/{slug}/enrol', [LearnController::class, 'enrol']);
$router->post('/learn/{slug}/review', [LearnController::class, 'review']);
$router->get('/learn/{slug}/chat', [CourseChatController::class, 'show']);
$router->post('/learn/{slug}/chat', [CourseChatController::class, 'post']);
$router->get('/learn/{slug}/chat/poll', [CourseChatController::class, 'poll']);
$router->post('/learn/{slug}/chat/{id:\d+}/delete', [CourseChatController::class, 'delete']);
$router->post('/learn/{slug}/chat/mute/{uid:\d+}', [CourseChatController::class, 'mute']);
$router->post('/learn/{slug}/lesson/{id:\d+}/ask', [LearnController::class, 'ask']);
$router->get('/learn/{slug}/lesson/{id:\d+}', [LearnController::class, 'lesson']);
$router->post('/learn/{slug}/lesson/{id:\d+}/complete', [LearnController::class, 'complete']);
$router->get('/learn/{slug}/lesson/{id:\d+}/check', [LearnController::class, 'check']);
$router->post('/learn/{slug}/lesson/{id:\d+}/check', [LearnController::class, 'checkSubmit']);
$router->get('/learn/{slug}/module/{mid:\d+}', [LearnController::class, 'module']);
$router->get('/learn/{slug}/module/{mid:\d+}/pdf', [LearnController::class, 'modulePdf']);
$router->get('/learn/{slug}/module/{mid:\d+}/notes', [LearnController::class, 'moduleNotes']);
$router->get('/learn/{slug}/quiz/{id:\d+}', [LearnController::class, 'quiz']);
$router->post('/learn/{slug}/quiz/{id:\d+}/start', [LearnController::class, 'quizStart']);
$router->get('/learn/{slug}/quiz/{id:\d+}/attempt/{aid:\d+}', [LearnController::class, 'attempt']);
$router->post('/learn/{slug}/quiz/{id:\d+}/attempt/{aid:\d+}/submit', [LearnController::class, 'submit']);
$router->get('/learn/{slug}/quiz/{id:\d+}/result/{aid:\d+}', [LearnController::class, 'result']);
$router->get('/enrolment/{no}', [LearnController::class, 'enrolment']);
$router->post('/enrolment/{no}/receipt', [LearnController::class, 'receipt']);
$router->post('/enrolment/{no}/cancel', [LearnController::class, 'cancelEnrolment']);
$router->get('/account/courses', [LearnController::class, 'myCourses']);
$router->get('/certificate/{code}', [LearnController::class, 'certificate']);
$router->get('/certificate/{code}/print', [LearnController::class, 'certificatePrint']);
$router->get('/shop', [ShopController::class, 'index']);
$router->get('/shop/category/{slug}', [ShopController::class, 'category']);
$router->get('/shop/{slug}', [ShopController::class, 'product']);
$router->post('/shop/{slug}/notify', [ShopController::class, 'notify']);
$router->get('/cart', [ShopController::class, 'cart']);
$router->post('/cart/add', [ShopController::class, 'cartAdd']);
$router->post('/cart/update', [ShopController::class, 'cartUpdate']);
$router->post('/cart/remove', [ShopController::class, 'cartRemove']);
$router->post('/cart/coupon', [ShopController::class, 'couponApply']);
$router->post('/cart/coupon/remove', [ShopController::class, 'couponRemove']);
$router->get('/checkout', [ShopController::class, 'checkout']);
$router->post('/checkout', [ShopController::class, 'place']);
$router->get('/order/{no}/{token}', [ShopController::class, 'order']);
$router->post('/order/{no}/{token}/receipt', [ShopController::class, 'receipt']);
$router->post('/order/{no}/{token}/cancel', [ShopController::class, 'cancel']);
$router->get('/sales-terms', [SiteController::class, 'legal']);
$router->get('/services', [SiteController::class, 'services']);
$router->get('/speaking', [SiteController::class, 'speaking']);
$router->get('/press', [SiteController::class, 'press']);
$router->get('/events', [SiteController::class, 'events']);
$router->get('/links', [SiteController::class, 'links']);
$router->get('/resources', [SiteController::class, 'resources']);
$router->get('/toolbox', [ToolboxController::class, 'index']);
$router->get('/toolbox/go/{id:\d+}', [ToolboxController::class, 'go']);
$router->get('/toolbox/{id:\d+}', [ToolboxController::class, 'show']);
$router->post('/toolbox/suggest', [ToolboxController::class, 'suggest']);
$router->get('/resources/{id:\d+}/download', [SiteController::class, 'resourceDownload']);
$router->get('/resources/{id:\d+}/report', [SiteController::class, 'resourceReportForm']);
$router->post('/resources/{id:\d+}/report', [SiteController::class, 'resourceReport']);
$router->get('/copyright', [SiteController::class, 'legal']);

/* Customer accounts */
$router->get('/login', [AccountController::class, 'loginForm']);
$router->post('/login', [AccountController::class, 'login']);
$router->get('/register', [AccountController::class, 'registerForm']);
$router->post('/register', [AccountController::class, 'register']);
$router->post('/logout', [AccountController::class, 'logout']);
$router->get('/forgot-password', [AccountController::class, 'forgotForm']);
$router->post('/forgot-password', [AccountController::class, 'forgot']);
$router->get('/reset-password/{token}', [AccountController::class, 'resetForm']);
$router->post('/reset-password/{token}', [AccountController::class, 'reset']);
$router->get('/verify-email', [VerifyController::class, 'form']);
$router->post('/verify-email', [VerifyController::class, 'verify']);
$router->post('/verify-email/resend', [VerifyController::class, 'resend']);
$router->post('/verify-email/cancel', [VerifyController::class, 'cancel']);
$router->get('/account/verify', [VerifyController::class, 'start']);
$router->get('/login/2fa', [TwoFactorController::class, 'form']);
$router->post('/login/2fa', [TwoFactorController::class, 'verify']);
$router->get('/account/security', [AccountSecurityController::class, 'index']);
$router->post('/account/security/2fa/start', [AccountSecurityController::class, 'start']);
$router->post('/account/security/2fa/cancel', [AccountSecurityController::class, 'cancel']);
$router->post('/account/security/2fa/confirm', [AccountSecurityController::class, 'confirm']);
$router->post('/account/security/2fa/disable', [AccountSecurityController::class, 'disable']);
$router->post('/account/security/2fa/recovery', [AccountSecurityController::class, 'regenerate']);
$router->post('/account/security/signout-all', [AccountSecurityController::class, 'signoutAll']);
$router->get('/.well-known/security.txt', [SiteController::class, 'securityTxt']);
$router->get('/account/articles', [ArticlesController::class, 'index']);
$router->get('/account/articles/new', [ArticlesController::class, 'form']);
$router->post('/account/articles/new', [ArticlesController::class, 'save']);
$router->post('/account/articles/preview', [ArticlesController::class, 'preview']);
$router->get('/account/articles/{id:\d+}', [ArticlesController::class, 'form']);
$router->post('/account/articles/{id:\d+}', [ArticlesController::class, 'save']);
$router->post('/account/articles/{id:\d+}/withdraw', [ArticlesController::class, 'withdraw']);
$router->post('/account/articles/{id:\d+}/delete', [ArticlesController::class, 'delete']);
$router->get('/account', [AccountController::class, 'dashboard']);
$router->get('/account/orders', [AccountController::class, 'orders']);
$router->get('/account/profile', [AccountController::class, 'profile']);
$router->post('/account/profile', [AccountController::class, 'profileSave']);
$router->post('/account/password', [AccountController::class, 'passwordSave']);
$router->post('/account/delete', [AccountController::class, 'delete']);
$router->get('/news', [NewsController::class, 'hub']);
$router->get('/news/tick', [NewsController::class, 'tick']);
$router->get('/news/nobel', [NewsController::class, 'nobel']);
$router->get('/news/sources', [NewsController::class, 'sources']);
$router->post('/news/report', [NewsController::class, 'report']);
$router->get('/privacy', [SiteController::class, 'legal']);
$router->get('/terms', [SiteController::class, 'legal']);
$router->get('/sitemap.xml', [SiteController::class, 'sitemap']);
$router->get('/robots.txt', [SiteController::class, 'robots']);
$router->get('/{key:[a-f0-9]+}.txt', [SiteController::class, 'indexNowKey']);

/* Admin */
$router->get('/admin/login', [AdminAuthController::class, 'loginForm']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout']);
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->post('/admin/samples/clear', [AdminController::class, 'clearSamples']);
$router->post('/admin/markdown-preview', [AdminController::class, 'markdownPreview']);
$router->get('/admin/account', [AdminController::class, 'account']);
$router->post('/admin/account', [AdminController::class, 'accountSave']);
$router->get('/admin/settings/{group}', [AdminSettingsController::class, 'edit']);
$router->post('/admin/settings/{group}', [AdminSettingsController::class, 'save']);
$router->get('/admin/cv', [AdminCvController::class, 'index']);
$router->post('/admin/cv', [AdminCvController::class, 'upload']);
$router->post('/admin/cv/{id:\d+}/activate', [AdminCvController::class, 'activate']);
$router->post('/admin/cv/{id:\d+}/delete', [AdminCvController::class, 'delete']);
$router->get('/admin/publications/import', [AdminImportController::class, 'form']);
$router->post('/admin/publications/import', [AdminImportController::class, 'run']);
$router->get('/admin/publications/template.csv', [AdminImportController::class, 'template']);
$router->get('/admin/enquiries', [AdminInboxController::class, 'enquiries']);
$router->get('/admin/enquiries/{id:\d+}', [AdminInboxController::class, 'enquiry']);
$router->post('/admin/enquiries/{id:\d+}/status', [AdminInboxController::class, 'enquiryStatus']);
$router->post('/admin/enquiries/{id:\d+}/delete', [AdminInboxController::class, 'enquiryDelete']);
$router->get('/admin/subscribers', [AdminInboxController::class, 'subscribers']);
$router->get('/admin/subscribers/export.csv', [AdminInboxController::class, 'subscribersExport']);
$router->post('/admin/subscribers/{id:\d+}/delete', [AdminInboxController::class, 'subscriberDelete']);
$router->get('/admin/media', [AdminMediaController::class, 'index']);
$router->post('/admin/media', [AdminMediaController::class, 'upload']);
$router->post('/admin/media/{id:\d+}/delete', [AdminMediaController::class, 'delete']);
$router->get('/admin/audit', [AdminController::class, 'audit']);
$router->post('/admin/update', [AdminController::class, 'update']);
$router->post('/admin/mail-test', [AdminController::class, 'mailTest']);

/* Admin: shop */
$router->get('/admin/orders', [AdminOrdersController::class, 'index']);
$router->get('/admin/orders/pick-list', [AdminOrdersController::class, 'pickList']);
$router->get('/admin/orders/export.csv', [AdminOrdersController::class, 'export']);
$router->get('/admin/orders/{id:\d+}', [AdminOrdersController::class, 'show']);
$router->post('/admin/orders/{id:\d+}/action', [AdminOrdersController::class, 'action']);
$router->post('/admin/orders/{id:\d+}/note', [AdminOrdersController::class, 'note']);
$router->get('/admin/orders/{id:\d+}/receipt', [AdminOrdersController::class, 'receipt']);
$router->get('/admin/orders/{id:\d+}/print/{type}', [AdminOrdersController::class, 'printOne']);
$router->get('/admin/customers', [AdminOrdersController::class, 'customers']);
$router->get('/admin/shop/products', [AdminProductsController::class, 'index']);
$router->get('/admin/shop/products/new', [AdminProductsController::class, 'form']);
$router->post('/admin/shop/products/new', [AdminProductsController::class, 'save']);
$router->get('/admin/shop/products/{id:\d+}', [AdminProductsController::class, 'form']);
$router->post('/admin/shop/products/{id:\d+}', [AdminProductsController::class, 'save']);
$router->post('/admin/shop/products/{id:\d+}/delete', [AdminProductsController::class, 'delete']);
$router->get('/admin/shop/stock', [AdminStockController::class, 'index']);
$router->post('/admin/shop/stock', [AdminStockController::class, 'save']);
$router->get('/admin/shop/stock/log', [AdminStockController::class, 'log']);
$router->get('/admin/shop/shipping', [AdminShippingController::class, 'index']);
$router->post('/admin/shop/shipping', [AdminShippingController::class, 'save']);

/* Admin: courses */
$router->get('/admin/courses', [AdminCoursesController::class, 'index']);
$router->get('/admin/courses/new', [AdminCoursesController::class, 'form']);
$router->post('/admin/courses/new', [AdminCoursesController::class, 'save']);
$router->get('/admin/courses/{id:\d+}', [AdminCoursesController::class, 'form']);
$router->post('/admin/courses/{id:\d+}', [AdminCoursesController::class, 'save']);
$router->post('/admin/courses/{id:\d+}/delete', [AdminCoursesController::class, 'delete']);
$router->get('/admin/courses/{id:\d+}/curriculum', [AdminCoursesController::class, 'curriculum']);
$router->post('/admin/courses/{id:\d+}/modules', [AdminCoursesController::class, 'moduleAdd']);
$router->post('/admin/modules/{mid:\d+}', [AdminCoursesController::class, 'moduleSave']);
$router->post('/admin/modules/{mid:\d+}/move', [AdminCoursesController::class, 'moduleMove']);
$router->post('/admin/modules/{mid:\d+}/delete', [AdminCoursesController::class, 'moduleDelete']);
$router->post('/admin/modules/{mid:\d+}/pdf', [AdminCoursesController::class, 'modulePdf']);
$router->get('/admin/courses/{id:\d+}/lessons/new', [AdminCoursesController::class, 'lessonForm']);
$router->post('/admin/courses/{id:\d+}/lessons/new', [AdminCoursesController::class, 'lessonSave']);
$router->get('/admin/lessons/{lid:\d+}', [AdminCoursesController::class, 'lessonEdit']);
$router->post('/admin/lessons/{lid:\d+}', [AdminCoursesController::class, 'lessonUpdate']);
$router->post('/admin/lessons/{lid:\d+}/move', [AdminCoursesController::class, 'lessonMove']);
$router->post('/admin/lessons/{lid:\d+}/delete', [AdminCoursesController::class, 'lessonDelete']);
$router->post('/admin/lessons/{lid:\d+}/bank', [AdminCoursesController::class, 'bankOpen']);
$router->get('/admin/courses/{id:\d+}/quizzes/new', [AdminCoursesController::class, 'quizForm']);
$router->post('/admin/courses/{id:\d+}/quizzes/new', [AdminCoursesController::class, 'quizSave']);
$router->get('/admin/quizzes/{qid:\d+}', [AdminCoursesController::class, 'quizEdit']);
$router->post('/admin/quizzes/{qid:\d+}', [AdminCoursesController::class, 'quizUpdate']);
$router->post('/admin/quizzes/{qid:\d+}/delete', [AdminCoursesController::class, 'quizDelete']);
$router->get('/admin/quizzes/{qid:\d+}/questions/new', [AdminCoursesController::class, 'questionForm']);
$router->post('/admin/quizzes/{qid:\d+}/questions/new', [AdminCoursesController::class, 'questionSave']);
$router->get('/admin/questions/{id:\d+}', [AdminCoursesController::class, 'questionEdit']);
$router->post('/admin/questions/{id:\d+}', [AdminCoursesController::class, 'questionUpdate']);
$router->post('/admin/questions/{id:\d+}/move', [AdminCoursesController::class, 'questionMove']);
$router->post('/admin/questions/{id:\d+}/delete', [AdminCoursesController::class, 'questionDelete']);
$router->get('/admin/enrolments', [AdminEnrolmentsController::class, 'index']);
$router->post('/admin/enrolments/grant', [AdminEnrolmentsController::class, 'grant']);
$router->get('/admin/enrolments/{id:\d+}', [AdminEnrolmentsController::class, 'show']);
$router->post('/admin/enrolments/{id:\d+}/action', [AdminEnrolmentsController::class, 'action']);
$router->get('/admin/enrolments/{id:\d+}/receipt', [AdminEnrolmentsController::class, 'receipt']);

/* Admin: free tools */
$router->get('/admin/toolbox/bulk', [AdminToolboxController::class, 'bulkForm']);
$router->post('/admin/toolbox/bulk', [AdminToolboxController::class, 'bulkSave']);
$router->post('/admin/toolbox/check', [AdminToolboxController::class, 'check']);

/* Admin: article submissions */
$router->get('/admin/articles', [AdminArticlesController::class, 'index']);
$router->get('/admin/articles/{id:\d+}', [AdminArticlesController::class, 'show']);
$router->post('/admin/articles/{id:\d+}/approve', [AdminArticlesController::class, 'approve']);
$router->post('/admin/articles/{id:\d+}/reject', [AdminArticlesController::class, 'reject']);
$router->post('/admin/articles/{id:\d+}/delete', [AdminArticlesController::class, 'delete']);

/* Admin: search engines */
$router->get('/admin/seo', [AdminSeoController::class, 'index']);
$router->post('/admin/seo/save', [AdminSeoController::class, 'save']);
$router->post('/admin/seo/notify', [AdminSeoController::class, 'notify']);

/* Admin: security */
$router->get('/admin/security', [AdminSecurityController::class, 'index']);
$router->post('/admin/security/2fa/start', [AdminSecurityController::class, 'start']);
$router->post('/admin/security/2fa/cancel', [AdminSecurityController::class, 'cancel']);
$router->post('/admin/security/2fa/confirm', [AdminSecurityController::class, 'confirm']);
$router->post('/admin/security/2fa/disable', [AdminSecurityController::class, 'disable']);
$router->post('/admin/security/2fa/recovery', [AdminSecurityController::class, 'regenerate']);
$router->post('/admin/security/signout-all', [AdminSecurityController::class, 'signoutAll']);
$router->post('/admin/security/settings', [AdminSecurityController::class, 'settings']);
$router->post('/admin/security/encrypt', [AdminSecurityController::class, 'encrypt']);
$router->post('/admin/security/delete-installer', [AdminSecurityController::class, 'deleteInstaller']);
$router->post('/admin/security/reset-2fa/{id:\d+}', [AdminSecurityController::class, 'resetUser']);

/* Admin: course community */
$router->get('/admin/courses/{id:\d+}/announcements', [AdminCommunityController::class, 'announcements']);
$router->post('/admin/courses/{id:\d+}/announcements', [AdminCommunityController::class, 'announcementSave']);
$router->post('/admin/announcements/{aid:\d+}/delete', [AdminCommunityController::class, 'announcementDelete']);
$router->get('/admin/qa', [AdminCommunityController::class, 'questions']);
$router->post('/admin/qa/{qid:\d+}', [AdminCommunityController::class, 'questionAnswer']);
$router->get('/admin/reviews', [AdminCommunityController::class, 'reviews']);
$router->post('/admin/reviews/{rid:\d+}', [AdminCommunityController::class, 'reviewAction']);
$router->post('/admin/shop/products/{id:\d+}/duplicate', [AdminProductsController::class, 'duplicate']);

/* Admin: news */
$router->get('/admin/news', [AdminNewsController::class, 'index']);
$router->post('/admin/news/fetch', [AdminNewsController::class, 'fetchNow']);
$router->post('/admin/news/pause', [AdminNewsController::class, 'pause']);
$router->post('/admin/news/items/{id:\d+}/hide', [AdminNewsController::class, 'hide']);
$router->post('/admin/news/items/{id:\d+}/restore', [AdminNewsController::class, 'restore']);
$router->get('/admin/news/reports', [AdminNewsController::class, 'reports']);
$router->post('/admin/news/reports/{id:\d+}/resolve', [AdminNewsController::class, 'reportResolve']);
$router->post('/admin/news/reports/{id:\d+}/dismiss', [AdminNewsController::class, 'reportDismiss']);
$router->get('/admin/news/rules', [AdminNewsController::class, 'rules']);
$router->post('/admin/news/terms', [AdminNewsController::class, 'termAdd']);
$router->post('/admin/news/terms/{id:\d+}/delete', [AdminNewsController::class, 'termDelete']);
$router->get('/admin/news/log', [AdminNewsController::class, 'log']);
$router->get('/admin/news/sources', [AdminNewsSourcesController::class, 'index']);
$router->get('/admin/news/sources/new', [AdminNewsSourcesController::class, 'form']);
$router->post('/admin/news/sources/new', [AdminNewsSourcesController::class, 'save']);
$router->post('/admin/news/sources/test', [AdminNewsSourcesController::class, 'test']);
$router->get('/admin/news/sources/{id:\d+}', [AdminNewsSourcesController::class, 'form']);
$router->post('/admin/news/sources/{id:\d+}', [AdminNewsSourcesController::class, 'save']);
$router->post('/admin/news/sources/{id:\d+}/pause', [AdminNewsSourcesController::class, 'pause']);
$router->post('/admin/news/sources/{id:\d+}/resume', [AdminNewsSourcesController::class, 'resume']);
$router->post('/admin/news/sources/{id:\d+}/delete', [AdminNewsSourcesController::class, 'delete']);

/* Generic content screens: publications, awards, talks, projects, testimonials, timeline, posts, pages */
$e = Entities::slugs();
$router->get("/admin/{entity:$e}", [AdminCrudController::class, 'index']);
$router->get("/admin/{entity:$e}/new", [AdminCrudController::class, 'form']);
$router->post("/admin/{entity:$e}/new", [AdminCrudController::class, 'store']);
$router->get("/admin/{entity:$e}/{id:\\d+}", [AdminCrudController::class, 'form']);
$router->post("/admin/{entity:$e}/{id:\\d+}", [AdminCrudController::class, 'update']);
$router->post("/admin/{entity:$e}/{id:\\d+}/delete", [AdminCrudController::class, 'destroy']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
