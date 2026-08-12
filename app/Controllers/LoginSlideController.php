<?php
require_once ROOT_DIR . '/core/Controller.php';

/**
 * Login page carousel — the school's own announcements, shown before sign-in.
 *
 * This is the one screen every parent, student and teacher sees whether or not
 * they have an account, so the content is deliberately school-owned rather than
 * hard-coded marketing copy. Images are uploaded to the server rather than
 * hot-linked, so the page still renders on a school network with no outside
 * internet access.
 */
class LoginSlideController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->requirePermission(['settings.manage']);
        $this->tid = $this->tenantId() ?? 0;
    }

    /**
     * The slides a visitor should actually see right now.
     *
     * Static so AuthController can call it without instantiating this controller
     * (which would demand settings.manage from a logged-out visitor). Returns []
     * when the tenant has none, which the view treats as "use the built-ins".
     */
    public static function activeSlides(Database $db, ?int $tenantId): array {
        if (!$tenantId) { return []; }
        try {
            return $db->fetchAll(
                "SELECT * FROM login_slides
                  WHERE tenant_id = ? AND is_active = 1
                    AND (starts_on IS NULL OR starts_on <= CURDATE())
                    AND (ends_on   IS NULL OR ends_on   >= CURDATE())
                  ORDER BY sort_order, id",
                [$tenantId]
            );
        } catch (\Throwable $e) {
            // The login page must render even if this table is missing (an install
            // that hasn't run the migration yet) — fall back to the built-in slides.
            error_log('Login slides unavailable: ' . $e->getMessage());
            return [];
        }
    }

    public function index(): void {
        $slides = $this->db->fetchAll(
            "SELECT * FROM login_slides WHERE tenant_id=? ORDER BY sort_order, id", [$this->tid]
        );
        $this->view('school/highschool/settings/login_slides', [
            'pageTitle' => 'Login Carousel', 'panelType' => 'school',
            'slides' => $slides,
            'liveCount' => count(self::activeSlides($this->db, $this->tid)),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void {
        $errors = $this->validate($_POST, ['title' => 'required|max:150', 'caption' => 'max:400']);
        $imageUrl = $this->handleImageUpload('image', 'slides', $errors);
        if ($errors) { $this->failValidation($errors, '/school/settings/login-slides'); }

        // New slides go to the end of the running order.
        $next = $this->db->fetchOne("SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM login_slides WHERE tenant_id=?", [$this->tid])['n'] ?? 1;
        $this->db->insert(
            "INSERT INTO login_slides (tenant_id,title,caption,image_url,sort_order,is_active,starts_on,ends_on,created_by)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $this->tid, $_POST['title'], ($_POST['caption'] ?? '') ?: null, $imageUrl, $next,
                isset($_POST['is_active']) ? 1 : 0,
                ($_POST['starts_on'] ?? '') ?: null, ($_POST['ends_on'] ?? '') ?: null,
                $_SESSION['user_id'] ?? null,
            ]
        );
        $this->flash('success', 'Announcement added to the login carousel.');
        $this->redirect('/school/settings/login-slides');
    }

    public function update(string $id): void {
        $slide = $this->db->fetchOne("SELECT * FROM login_slides WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$slide) { $this->redirect('/school/settings/login-slides'); }

        $errors = $this->validate($_POST, ['title' => 'required|max:150', 'caption' => 'max:400']);
        $imageUrl = $this->handleImageUpload('image', 'slides', $errors);
        if ($errors) { $this->failValidation($errors, '/school/settings/login-slides'); }

        // No new file chosen keeps the existing image, unless "remove" was ticked.
        $newImage = $imageUrl ?? (!empty($_POST['remove_image']) ? null : $slide['image_url']);

        $this->db->execute(
            "UPDATE login_slides SET title=?, caption=?, image_url=?, is_active=?, starts_on=?, ends_on=?
              WHERE id=? AND tenant_id=?",
            [
                $_POST['title'], ($_POST['caption'] ?? '') ?: null, $newImage,
                isset($_POST['is_active']) ? 1 : 0,
                ($_POST['starts_on'] ?? '') ?: null, ($_POST['ends_on'] ?? '') ?: null,
                $id, $this->tid,
            ]
        );
        $this->flash('success', 'Announcement updated.');
        $this->redirect('/school/settings/login-slides');
    }

    public function delete(string $id): void {
        $slide = $this->db->fetchOne("SELECT id, title FROM login_slides WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$slide) { $this->redirect('/school/settings/login-slides'); }
        $this->db->execute("DELETE FROM login_slides WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', "\"{$slide['title']}\" removed from the carousel.");
        $this->redirect('/school/settings/login-slides');
    }

    /** Show/hide without deleting — useful for a seasonal notice. */
    public function toggle(string $id): void {
        $slide = $this->db->fetchOne("SELECT id, is_active FROM login_slides WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$slide) { $this->redirect('/school/settings/login-slides'); }
        $this->db->execute("UPDATE login_slides SET is_active=? WHERE id=? AND tenant_id=?",
            [$slide['is_active'] ? 0 : 1, $id, $this->tid]);
        $this->flash('success', $slide['is_active'] ? 'Announcement hidden.' : 'Announcement is now showing.');
        $this->redirect('/school/settings/login-slides');
    }

    /**
     * Moves a slide up or down the running order.
     *
     * Swaps sort_order with its neighbour rather than renumbering everything, so
     * concurrent edits can't reshuffle the whole carousel.
     */
    public function reorder(string $id): void {
        $slide = $this->db->fetchOne("SELECT id, sort_order FROM login_slides WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$slide) { $this->redirect('/school/settings/login-slides'); }
        $dir = ($_POST['direction'] ?? 'up') === 'down' ? 'down' : 'up';

        $neighbour = $this->db->fetchOne(
            $dir === 'up'
                ? "SELECT id, sort_order FROM login_slides WHERE tenant_id=? AND (sort_order < ? OR (sort_order = ? AND id < ?)) ORDER BY sort_order DESC, id DESC LIMIT 1"
                : "SELECT id, sort_order FROM login_slides WHERE tenant_id=? AND (sort_order > ? OR (sort_order = ? AND id > ?)) ORDER BY sort_order ASC, id ASC LIMIT 1",
            [$this->tid, $slide['sort_order'], $slide['sort_order'], $id]
        );
        if ($neighbour) {
            $this->db->execute("UPDATE login_slides SET sort_order=? WHERE id=?", [$neighbour['sort_order'], $slide['id']]);
            $this->db->execute("UPDATE login_slides SET sort_order=? WHERE id=?", [$slide['sort_order'], $neighbour['id']]);
        }
        $this->redirect('/school/settings/login-slides');
    }
}
