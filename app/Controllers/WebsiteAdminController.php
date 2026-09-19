<?php
require_once ROOT_DIR . '/core/Controller.php';
require_once ROOT_DIR . '/app/Services/WebsiteContent.php';

/**
 * School panel → Website: everything a School Admin needs to run the public site
 * without touching code — the on/off switch, every page's text and photos, news and
 * events, the photo gallery and the leadership team.
 *
 * Gated on website.manage, with settings.manage accepted too so the module works for
 * existing admins before sql/add_website_cms.sql has granted the new permission.
 */
class WebsiteAdminController extends Controller {
    private int $tid;

    private const IMAGE_MAX = 4 * 1024 * 1024;

    public function __construct() {
        parent::__construct();
        $this->requirePermission(['website.manage', 'settings.manage']);
        $this->tid = $this->tenantId() ?? 0;
        WebsiteContent::ensureSchema($this->db);
    }

    // ── Overview ────────────────────────────────────────────────

    public function index(): void {
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $edited = [];
        foreach ($this->db->fetchAll("SELECT content_key, updated_at FROM website_content WHERE tenant_id=?", [$this->tid]) as $row) {
            $page = WebsiteContent::fields()[$row['content_key']][3] ?? null;
            if ($page) {
                $edited[$page]['count'] = ($edited[$page]['count'] ?? 0) + 1;
                $edited[$page]['at'] = max($edited[$page]['at'] ?? '', $row['updated_at']);
            }
        }
        $count = fn(string $table, string $where = '') => (int)($this->db->fetchOne(
            "SELECT COUNT(*) AS n FROM {$table} WHERE tenant_id=?" . $where, [$this->tid])['n'] ?? 0);

        $this->view('school/website/index', [
            'pageTitle' => 'Website', 'panelType' => 'school',
            'tenant' => $tenant,
            'pages' => WebsiteContent::pages(),
            'edited' => $edited,
            'stats' => [
                'posts'   => $count('website_posts', ' AND is_published=1'),
                'gallery' => $count('website_gallery', ' AND is_active=1'),
                'leaders' => $count('website_leaders', ' AND is_active=1'),
            ],
            'flash' => $this->getFlash(),
        ]);
    }

    public function toggle(): void {
        $enabled = !empty($_POST['website_enabled']) ? 1 : 0;
        $this->db->execute("UPDATE tenants SET website_enabled=? WHERE id=?", [$enabled, $this->tid]);
        $this->flash('success', $enabled ? 'The website is now live.' : 'The website is switched off — visitors now go straight to the login page.');
        $this->redirect('/school/website');
    }

    // ── Page editor ─────────────────────────────────────────────

    private function pageOr404(string $page): array {
        $pages = WebsiteContent::pages();
        if (!isset($pages[$page])) {
            $this->flash('danger', 'That page does not exist.');
            $this->redirect('/school/website');
        }
        return $pages[$page];
    }

    public function editPage(string $page): void {
        $def = $this->pageOr404($page);
        $saved = [];
        foreach ($this->db->fetchAll("SELECT content_key FROM website_content WHERE tenant_id=?", [$this->tid]) as $row) {
            $saved[$row['content_key']] = true;
        }
        $this->view('school/website/page_edit', [
            'pageTitle' => 'Edit ' . $def['label'], 'panelType' => 'school',
            'pageKey' => $page, 'page' => $def,
            'pages' => WebsiteContent::pages(),
            'values' => WebsiteContent::load($this->db, $this->tid),
            'saved' => $saved,
            'flash' => $this->getFlash(),
        ]);
    }

    public function savePage(string $page): void {
        $def = $this->pageOr404($page);
        $posted = $_POST['fields'] ?? [];
        $errors = [];
        $changed = 0;

        foreach ($def['sections'] as $fields) {
            foreach ($fields as $key => [$type, $label, $default]) {
                if ($type === 'image') {
                    if (!empty($_POST['reset_image'][$key])) {
                        $changed += $this->store($key, null);
                        continue;
                    }
                    $url = $this->uploadFromArray('images', $key, $errors, $label);
                    if ($url !== null) {
                        $changed += $this->store($key, $url);
                    }
                    continue;
                }
                if (!array_key_exists($key, $posted)) {
                    continue;
                }
                // Normalise line endings so an unchanged textarea compares equal to its default.
                $value = trim(str_replace("\r\n", "\n", (string)$posted[$key]));
                if (mb_strlen($value) > 20000) {
                    $errors[$key] = "{$label} is too long.";
                    continue;
                }
                // Saving the default (or clearing the box) removes the override, so the field
                // keeps following the built-in text rather than freezing a copy of it.
                $changed += $this->store($key, ($value === '' || $value === $default) ? null : $value);
            }
        }

        if ($errors) {
            $this->flash('danger', implode(' ', $errors));
        } else {
            $this->flash('success', $changed ? "{$def['label']} saved — the changes are live." : 'No changes to save.');
        }
        $this->redirect('/school/website/pages/' . $page);
    }

    public function resetPage(string $page): void {
        $def = $this->pageOr404($page);
        $keys = [];
        foreach ($def['sections'] as $fields) {
            $keys = array_merge($keys, array_keys($fields));
        }
        if ($keys) {
            $in = implode(',', array_fill(0, count($keys), '?'));
            $this->db->execute("DELETE FROM website_content WHERE tenant_id=? AND content_key IN ($in)", array_merge([$this->tid], $keys));
        }
        $this->flash('success', "{$def['label']} restored to the original text and photos.");
        $this->redirect('/school/website/pages/' . $page);
    }

    /** Upserts one override (or deletes it for null). Returns 1 if anything changed. */
    private function store(string $key, ?string $value): int {
        if ($value === null) {
            return $this->db->execute("DELETE FROM website_content WHERE tenant_id=? AND content_key=?", [$this->tid, $key]) > 0 ? 1 : 0;
        }
        $current = $this->db->fetchOne("SELECT content FROM website_content WHERE tenant_id=? AND content_key=?", [$this->tid, $key]);
        if ($current && (string)$current['content'] === $value) {
            return 0;
        }
        $this->db->execute(
            "INSERT INTO website_content (tenant_id, content_key, content, updated_by) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE content=VALUES(content), updated_by=VALUES(updated_by)",
            [$this->tid, $key, $value, $_SESSION['user_id'] ?? null]
        );
        return 1;
    }

    /**
     * handleImageUpload() reads a flat $_FILES entry, but the editor posts every photo as
     * images[<field key>]; lift the one we want into its own entry first.
     */
    private function uploadFromArray(string $group, string $key, array &$errors, string $label): ?string {
        $files = $_FILES[$group] ?? null;
        if (!$files || !isset($files['error'][$key]) || $files['error'][$key] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $_FILES['__website_img'] = [
            'name' => $files['name'][$key], 'type' => $files['type'][$key], 'tmp_name' => $files['tmp_name'][$key],
            'error' => $files['error'][$key], 'size' => $files['size'][$key],
        ];
        $fieldErrors = [];
        $url = $this->handleImageUpload('__website_img', 'website', $fieldErrors, self::IMAGE_MAX);
        unset($_FILES['__website_img']);
        if ($fieldErrors) {
            $errors[$key] = "{$label}: " . reset($fieldErrors);
        }
        return $url;
    }

    // ── News & events ───────────────────────────────────────────

    public function posts(): void {
        $this->view('school/website/posts', [
            'pageTitle' => 'Website News & Events', 'panelType' => 'school',
            'posts' => $this->db->fetchAll("SELECT * FROM website_posts WHERE tenant_id=? ORDER BY published_on DESC, id DESC", [$this->tid]),
            'flash' => $this->getFlash(),
        ]);
    }

    private function postData(array &$errors): array {
        $errors = array_merge($errors, $this->validate($_POST, [
            'title' => 'required|max:200', 'excerpt' => 'max:400', 'category' => 'in:news,event',
            'published_on' => 'date', 'event_date' => 'date', 'event_location' => 'max:200',
        ]));
        return [
            'category' => ($_POST['category'] ?? 'news') === 'event' ? 'event' : 'news',
            'title' => trim($_POST['title'] ?? ''),
            'excerpt' => trim($_POST['excerpt'] ?? '') ?: null,
            'body' => trim(str_replace("\r\n", "\n", $_POST['body'] ?? '')) ?: null,
            'event_date' => ($_POST['event_date'] ?? '') ?: null,
            'event_location' => trim($_POST['event_location'] ?? '') ?: null,
            'published_on' => ($_POST['published_on'] ?? '') ?: date('Y-m-d'),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
        ];
    }

    public function storePost(): void {
        $errors = [];
        $d = $this->postData($errors);
        $image = $this->handleImageUpload('image', 'website', $errors, self::IMAGE_MAX);
        if ($errors) { $this->failValidation($errors, '/school/website/news'); }
        $this->db->insert(
            "INSERT INTO website_posts (tenant_id,category,title,excerpt,body,image_url,event_date,event_location,published_on,is_published,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [$this->tid, $d['category'], $d['title'], $d['excerpt'], $d['body'], $image, $d['event_date'], $d['event_location'],
             $d['published_on'], $d['is_published'], $_SESSION['user_id'] ?? null]
        );
        $this->flash('success', $d['is_published'] ? "\"{$d['title']}\" is published on the website." : "\"{$d['title']}\" saved as a draft.");
        $this->redirect('/school/website/news');
    }

    public function updatePost(string $id): void {
        $post = $this->db->fetchOne("SELECT * FROM website_posts WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$post) { $this->redirect('/school/website/news'); }
        $errors = [];
        $d = $this->postData($errors);
        $image = $this->handleImageUpload('image', 'website', $errors, self::IMAGE_MAX);
        if ($errors) { $this->failValidation($errors, '/school/website/news'); }
        $image = $image ?? (!empty($_POST['remove_image']) ? null : $post['image_url']);
        $this->db->execute(
            "UPDATE website_posts SET category=?,title=?,excerpt=?,body=?,image_url=?,event_date=?,event_location=?,published_on=?,is_published=?
              WHERE id=? AND tenant_id=?",
            [$d['category'], $d['title'], $d['excerpt'], $d['body'], $image, $d['event_date'], $d['event_location'],
             $d['published_on'], $d['is_published'], $id, $this->tid]
        );
        $this->flash('success', 'Post updated.');
        $this->redirect('/school/website/news');
    }

    // ── Gallery ─────────────────────────────────────────────────

    public function gallery(): void {
        $this->view('school/website/gallery', [
            'pageTitle' => 'Website Gallery', 'panelType' => 'school',
            'photos' => $this->db->fetchAll("SELECT * FROM website_gallery WHERE tenant_id=? ORDER BY sort_order, id", [$this->tid]),
            'flash' => $this->getFlash(),
        ]);
    }

    /** Several photos can be chosen at once; each becomes its own gallery item. */
    public function storeGallery(): void {
        $files = $_FILES['images'] ?? null;
        $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;
        $errors = [];
        $added = 0;
        $next = (int)($this->db->fetchOne("SELECT COALESCE(MAX(sort_order),0) AS n FROM website_gallery WHERE tenant_id=?", [$this->tid])['n'] ?? 0);
        $caption = trim($_POST['caption'] ?? '');
        for ($i = 0; $i < $count; $i++) {
            $url = $this->uploadFromArray('images', (string)$i, $errors, 'Photo ' . ($i + 1));
            if ($url === null) { continue; }
            $this->db->insert("INSERT INTO website_gallery (tenant_id,image_url,caption,sort_order) VALUES (?,?,?,?)",
                [$this->tid, $url, mb_substr($caption, 0, 200) ?: null, ++$next]);
            $added++;
        }
        if ($errors) {
            $this->flash('danger', ($added ? "{$added} photo(s) added. " : '') . implode(' ', $errors));
        } else {
            $this->flash($added ? 'success' : 'danger', $added ? "{$added} photo(s) added to the gallery." : 'Choose at least one photo to upload.');
        }
        $this->redirect('/school/website/gallery');
    }

    public function updateGallery(string $id): void {
        $this->db->execute("UPDATE website_gallery SET caption=? WHERE id=? AND tenant_id=?",
            [mb_substr(trim($_POST['caption'] ?? ''), 0, 200) ?: null, $id, $this->tid]);
        $this->flash('success', 'Caption saved.');
        $this->redirect('/school/website/gallery');
    }

    // ── Leadership team ─────────────────────────────────────────

    public function leaders(): void {
        $this->view('school/website/leaders', [
            'pageTitle' => 'Website Leadership Team', 'panelType' => 'school',
            'leaders' => $this->db->fetchAll("SELECT * FROM website_leaders WHERE tenant_id=? ORDER BY sort_order, id", [$this->tid]),
            'flash' => $this->getFlash(),
        ]);
    }

    public function storeLeader(): void {
        $errors = $this->validate($_POST, ['name' => 'required|max:150', 'position' => 'required|max:150', 'bio' => 'max:600']);
        $photo = $this->handleImageUpload('photo', 'website', $errors, self::IMAGE_MAX);
        if ($errors) { $this->failValidation($errors, '/school/website/leaders'); }
        $next = (int)($this->db->fetchOne("SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM website_leaders WHERE tenant_id=?", [$this->tid])['n'] ?? 1);
        $this->db->insert("INSERT INTO website_leaders (tenant_id,name,position,bio,photo_url,sort_order,is_active) VALUES (?,?,?,?,?,?,?)",
            [$this->tid, trim($_POST['name']), trim($_POST['position']), trim($_POST['bio'] ?? '') ?: null, $photo, $next, isset($_POST['is_active']) ? 1 : 0]);
        $this->flash('success', trim($_POST['name']) . ' added to the leadership team.');
        $this->redirect('/school/website/leaders');
    }

    public function updateLeader(string $id): void {
        $leader = $this->db->fetchOne("SELECT * FROM website_leaders WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$leader) { $this->redirect('/school/website/leaders'); }
        $errors = $this->validate($_POST, ['name' => 'required|max:150', 'position' => 'required|max:150', 'bio' => 'max:600']);
        $photo = $this->handleImageUpload('photo', 'website', $errors, self::IMAGE_MAX);
        if ($errors) { $this->failValidation($errors, '/school/website/leaders'); }
        $photo = $photo ?? (!empty($_POST['remove_image']) ? null : $leader['photo_url']);
        $this->db->execute("UPDATE website_leaders SET name=?,position=?,bio=?,photo_url=?,is_active=? WHERE id=? AND tenant_id=?",
            [trim($_POST['name']), trim($_POST['position']), trim($_POST['bio'] ?? '') ?: null, $photo, isset($_POST['is_active']) ? 1 : 0, $id, $this->tid]);
        $this->flash('success', 'Profile updated.');
        $this->redirect('/school/website/leaders');
    }

    // ── Shared: delete / show-hide / reorder for posts, gallery and leaders ──

    /** URL segment => [table, column that hides it, back URL, noun]. */
    private const ITEM_TABLES = [
        'news'    => ['website_posts',   'is_published', '/school/website/news',    'Post'],
        'gallery' => ['website_gallery', 'is_active',    '/school/website/gallery', 'Photo'],
        'leaders' => ['website_leaders', 'is_active',    '/school/website/leaders', 'Profile'],
    ];

    private function itemTable(string $type): array {
        if (!isset(self::ITEM_TABLES[$type])) { $this->redirect('/school/website'); }
        return self::ITEM_TABLES[$type];
    }

    public function deleteItem(string $type, string $id): void {
        [$table, , $back, $noun] = $this->itemTable($type);
        $this->db->execute("DELETE FROM {$table} WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', "{$noun} deleted.");
        $this->redirect($back);
    }

    public function toggleItem(string $type, string $id): void {
        [$table, $col, $back, $noun] = $this->itemTable($type);
        $row = $this->db->fetchOne("SELECT {$col} AS v FROM {$table} WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if ($row) {
            $this->db->execute("UPDATE {$table} SET {$col}=? WHERE id=? AND tenant_id=?", [$row['v'] ? 0 : 1, $id, $this->tid]);
            $this->flash('success', $row['v'] ? "{$noun} hidden from the website." : "{$noun} is now showing on the website.");
        }
        $this->redirect($back);
    }

    /** Swaps sort_order with the neighbour, like the login carousel. */
    public function reorderItem(string $type, string $id): void {
        [$table, , $back] = $this->itemTable($type);
        if ($type === 'news') { $this->redirect($back); }
        $row = $this->db->fetchOne("SELECT id, sort_order FROM {$table} WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if ($row) {
            $up = ($_POST['direction'] ?? 'up') !== 'down';
            $neighbour = $this->db->fetchOne(
                $up
                    ? "SELECT id, sort_order FROM {$table} WHERE tenant_id=? AND (sort_order < ? OR (sort_order = ? AND id < ?)) ORDER BY sort_order DESC, id DESC LIMIT 1"
                    : "SELECT id, sort_order FROM {$table} WHERE tenant_id=? AND (sort_order > ? OR (sort_order = ? AND id > ?)) ORDER BY sort_order ASC, id ASC LIMIT 1",
                [$this->tid, $row['sort_order'], $row['sort_order'], $id]
            );
            if ($neighbour) {
                // Equal sort_orders would swap to the same values, so nudge them apart first.
                $a = (int)$row['sort_order']; $b = (int)$neighbour['sort_order'];
                if ($a === $b) { $up ? $b-- : $b++; }
                $this->db->execute("UPDATE {$table} SET sort_order=? WHERE id=?", [$b, $row['id']]);
                $this->db->execute("UPDATE {$table} SET sort_order=? WHERE id=?", [$a, $neighbour['id']]);
            }
        }
        $this->redirect($back);
    }
}
