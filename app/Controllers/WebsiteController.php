<?php
require_once ROOT_DIR . '/core/Controller.php';
require_once ROOT_DIR . '/app/Services/WebsiteContent.php';

/**
 * The school's public website — the marketing pages a visitor sees before signing in.
 *
 * Every page is gated on tenants.website_enabled so a School Admin can take the whole
 * site offline from the Website module. Its text, photos, news, gallery and team are
 * edited there too (WebsiteAdminController / WebsiteContent). When it is off, "/" behaves exactly as it did
 * before the website existed (straight to the login page) and every other website
 * URL redirects there too, so no half-published page is ever reachable.
 */
class WebsiteController extends Controller {
    private ?array $tenant;

    public function __construct() {
        parent::__construct();
        $this->tenant = $this->resolveTenant();
    }

    // Same lookup as AuthController/AdmissionController: a visitor is not logged in,
    // so the school is resolved from the domain, or the single active school.
    private function resolveTenant(): ?array {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE domain = ? AND status = 'active' LIMIT 1", [$host]);
        if (!$tenant) {
            $activeTenants = $this->db->fetchAll("SELECT * FROM tenants WHERE status = 'active'");
            if (count($activeTenants) === 1) {
                $tenant = $activeTenants[0];
            }
        }
        return $tenant ?: null;
    }

    /** Whether the public website is switched on for the resolved school. */
    public static function isEnabled(?array $tenant): bool {
        return !empty($tenant['website_enabled']);
    }

    private function page(string $view, string $title, string $description, string $active, array $extra = []): void {
        if (!self::isEnabled($this->tenant)) {
            $this->redirect('/login');
        }
        $tid = isset($this->tenant['id']) ? (int)$this->tenant['id'] : null;
        $this->view('website/' . $view, $extra + [
            'tenant'          => $this->tenant,
            'content'         => WebsiteContent::load($this->db, $tid),
            // Uploaded photos replace the built-in gallery once the school adds any.
            'galleryPhotos'   => $tid ? WebsiteContent::rows($this->db,
                "SELECT image_url, caption FROM website_gallery WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, id", [$tid]) : [],
            'pageTitle'       => $title,
            'pageDescription' => $description,
            'activeNav'       => $active,
            // /login already forwards a signed-in user to their own dashboard, so the
            // header's portal button can point there either way; only its label changes.
            'isLoggedIn'      => isset($_SESSION['user_id']),
        ]);
    }

    /** Published posts, newest first. */
    private function posts(int $limit): array {
        if (empty($this->tenant['id'])) { return []; }
        return WebsiteContent::rows($this->db,
            "SELECT * FROM website_posts WHERE tenant_id=? AND is_published=1 AND published_on <= CURDATE()
              ORDER BY published_on DESC, id DESC LIMIT " . (int)$limit, [(int)$this->tenant['id']]);
    }

    public function home(): void {
        $this->page('home', 'Home', 'CELDI Academy is a K-12 school in Ben Town, Margibi County, Liberia, changing Liberia one child at a time through Christ-centered, technological and vocational education.', 'home', ['posts' => $this->posts(3)]);
    }

    public function about(): void {
        $this->page('about', 'About Us', 'Our story, vision, mission and the core values that shape every CELDI Academy student.', 'about');
    }

    public function leadership(): void {
        $this->page('leadership', 'Our Leadership', 'How CELDI Academy is led: board, administration, faculty and parents working together.', 'about', [
            'leaders' => empty($this->tenant['id']) ? [] : WebsiteContent::rows($this->db,
                "SELECT * FROM website_leaders WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, id", [(int)$this->tenant['id']]),
        ]);
    }

    public function studentLife(): void {
        $this->page('student_life', 'Student Life', 'Mentorship, leadership, community and the Third Place Initiative at CELDI Academy.', 'about');
    }

    public function divisions(): void {
        $this->page('divisions', 'Our Divisions', 'Early Childhood & Daycare, Elementary, Junior High and Senior High at CELDI Academy.', 'divisions');
    }

    public function earlyChildhood(): void {
        $this->page('early_childhood', 'Early Childhood & Daycare', 'A nurturing, play-rich foundation for character and intellect at CELDI Academy.', 'divisions');
    }

    public function elementary(): void {
        $this->page('elementary', 'Elementary', 'Grades 1-6 at CELDI Academy: the bridge between early discovery and academic mastery.', 'divisions');
    }

    public function juniorHigh(): void {
        $this->page('junior_high', 'Junior High', 'Grades 7-9 at CELDI Academy: advanced critical thinking, specialized skills and servant leadership.', 'divisions');
    }

    public function seniorHigh(): void {
        $this->page('senior_high', 'Senior High', 'Grades 10-12 at CELDI Academy: graduating purposeful, technically proficient young leaders.', 'divisions');
    }

    public function admissions(): void {
        $this->page('admissions', 'Admissions', 'Enroll your child at CELDI Academy. Start the application online and complete it on campus.', 'admissions');
    }

    public function news(): void {
        $this->page('news', 'News & Events', 'The CELDI Academy academic calendar, events and highlights from campus.', 'news', ['posts' => $this->posts(24)]);
    }

    public function newsPost(string $id): void {
        $post = null;
        if (!empty($this->tenant['id'])) {
            $post = WebsiteContent::rows($this->db,
                "SELECT * FROM website_posts WHERE id=? AND tenant_id=? AND is_published=1 AND published_on <= CURDATE()",
                [$id, (int)$this->tenant['id']])[0] ?? null;
        }
        if (!$post) {
            $this->redirect('/academy-news');
        }
        $excerpt = $post['excerpt'] ?: mb_substr(trim(strip_tags((string)$post['body'])), 0, 160);
        $this->page('news_post', $post['title'], $excerpt, 'news', [
            'post'  => $post,
            'more'  => array_values(array_filter($this->posts(4), fn($p) => (int)$p['id'] !== (int)$post['id'])),
        ]);
    }
}
