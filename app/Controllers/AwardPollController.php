<?php
require_once ROOT_DIR . '/core/Controller.php';

/**
 * Award voting: Best Student of the Period / Semester / Year and Best Teacher of the
 * Semester / Year.
 *
 * A poll names the award and the period, lists the candidates, and says who may vote —
 * students, teachers, administrators, or any mix. Each person gets one vote, and the
 * tally stays hidden from voters until the poll is closed. Closing a poll lets the
 * school publish the winner straight onto the website's recognition board.
 */
class AwardPollController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->tid = $this->tenantId() ?? 0;
    }

    private function guard(): void { $this->requirePermission(['grades.manage', 'settings.manage']); }

    public const CATEGORIES = [
        'best_student_period'   => 'Best Student of the Period',
        'best_student_semester' => 'Best Student of the Semester',
        'best_student_year'     => 'Best Student of the Year',
        'best_teacher_semester' => 'Best Teacher of the Semester',
        'best_teacher_year'     => 'Best Teacher of the Year',
    ];

    public const VOTERS = ['students' => 'Students', 'teachers' => 'Teachers', 'admins' => 'Administrators'];

    /** Teacher awards take staff as candidates; the rest take students. */
    public static function candidateKind(string $category): string {
        return str_contains($category, 'teacher') ? 'staff' : 'student';
    }

    // ── Admin ───────────────────────────────────────────────────────

    public function index(): void {
        $this->guard();
        $polls = $this->db->fetchAll(
            "SELECT p.*, ay.name AS year_name,
                    (SELECT COUNT(*) FROM award_poll_candidates c WHERE c.poll_id=p.id) AS candidates,
                    (SELECT COUNT(*) FROM award_votes v WHERE v.poll_id=p.id) AS votes
             FROM award_polls p LEFT JOIN academic_years ay ON ay.id=p.academic_year_id
             WHERE p.tenant_id=? ORDER BY FIELD(p.status,'open','draft','closed'), p.id DESC", [$this->tid]);
        $this->view('school/highschool/awards/polls', [
            'pageTitle' => 'Award Voting', 'panelType' => 'school',
            'polls' => $polls, 'categories' => self::CATEGORIES, 'voterTypes' => self::VOTERS,
            'years' => $this->db->fetchAll("SELECT id,name FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void {
        $this->guard();
        $category = array_key_exists($_POST['category'] ?? '', self::CATEGORIES) ? $_POST['category'] : 'best_student_period';
        $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 150) ?: self::CATEGORIES[$category];
        $voters = array_values(array_intersect((array)($_POST['voters'] ?? []), array_keys(self::VOTERS)));
        if (!$voters) { $voters = ['students', 'teachers', 'admins']; }
        $id = (int)$this->db->insert(
            "INSERT INTO award_polls (tenant_id,title,category,period_label,academic_year_id,voter_groups,status,opens_on,closes_on,created_by)
             VALUES (?,?,?,?,?,?,'draft',?,?,?)",
            [$this->tid, $title, $category, mb_substr(trim((string)($_POST['period_label'] ?? '')), 0, 80) ?: null,
             (int)($_POST['academic_year_id'] ?? 0) ?: null, implode(',', $voters),
             ($_POST['opens_on'] ?? '') ?: null, ($_POST['closes_on'] ?? '') ?: null, $_SESSION['user_id'] ?? null]
        );
        $this->flash('success', 'Poll created. Add the candidates, then open it for voting.');
        $this->redirect('/school/awards/polls/' . $id);
    }

    private function poll(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM award_polls WHERE id=? AND tenant_id=?", [$id, $this->tid]) ?: null;
    }

    public function show(string $id): void {
        $this->guard();
        $poll = $this->poll((int)$id);
        if (!$poll) { $this->redirect('/school/awards/polls'); }
        $kind = self::candidateKind($poll['category']);
        $this->view('school/highschool/awards/poll', [
            'pageTitle' => $poll['title'], 'panelType' => 'school',
            'poll' => $poll, 'categories' => self::CATEGORIES, 'voterTypes' => self::VOTERS,
            'kind' => $kind,
            'results' => $this->results((int)$id),
            'turnout' => $this->turnout($poll),
            'people' => $kind === 'student'
                ? $this->db->fetchAll("SELECT s.id, u.name, c.name AS class_name FROM students s JOIN users u ON u.id=s.user_id
                                       LEFT JOIN classes c ON c.id=s.class_id WHERE s.tenant_id=? AND s.status='active' ORDER BY u.name", [$this->tid])
                : $this->db->fetchAll("SELECT u.id, u.name, r.name AS class_name FROM users u JOIN roles r ON r.id=u.role_id
                                       WHERE u.tenant_id=? AND u.status='active' AND r.name NOT IN ('Student','Parent') ORDER BY u.name", [$this->tid]),
            'flash' => $this->getFlash(),
        ]);
    }

    /** Candidates with their vote counts, highest first. */
    private function results(int $pollId): array {
        return $this->db->fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM award_votes v WHERE v.candidate_id=c.id) AS votes
             FROM award_poll_candidates c WHERE c.poll_id=? ORDER BY votes DESC, c.name", [$pollId]);
    }

    /** How many people may vote, and how many have. */
    private function turnout(array $poll): array {
        $groups = array_filter(explode(',', (string)$poll['voter_groups']));
        $roles = [];
        if (in_array('students', $groups, true)) { $roles[] = "'Student'"; }
        if (in_array('teachers', $groups, true)) { $roles[] = "'Teacher'"; }
        $where = $roles ? "r.name IN (" . implode(',', $roles) . ")" : "1=0";
        if (in_array('admins', $groups, true)) { $where .= " OR r.name NOT IN ('Student','Parent','Teacher')"; }
        $eligible = (int)($this->db->fetchOne(
            "SELECT COUNT(*) n FROM users u JOIN roles r ON r.id=u.role_id WHERE u.tenant_id=? AND u.status='active' AND ({$where})",
            [$this->tid])['n'] ?? 0);
        $cast = (int)($this->db->fetchOne("SELECT COUNT(*) n FROM award_votes WHERE poll_id=?", [(int)$poll['id']])['n'] ?? 0);
        return ['eligible' => $eligible, 'cast' => $cast];
    }

    public function addCandidate(string $id): void {
        $this->guard();
        $poll = $this->poll((int)$id);
        if (!$poll) { $this->redirect('/school/awards/polls'); }
        $kind = self::candidateKind($poll['category']);
        $refId = (int)($_POST['ref_id'] ?? 0);
        $row = $kind === 'student'
            ? $this->db->fetchOne("SELECT s.id, u.name, c.name AS class_name FROM students s JOIN users u ON u.id=s.user_id
                                   LEFT JOIN classes c ON c.id=s.class_id WHERE s.id=? AND s.tenant_id=?", [$refId, $this->tid])
            : $this->db->fetchOne("SELECT u.id, u.name, r.name AS class_name FROM users u JOIN roles r ON r.id=u.role_id
                                   WHERE u.id=? AND u.tenant_id=?", [$refId, $this->tid]);
        if (!$row) { $this->flash('danger', 'Choose someone to add.'); $this->redirect('/school/awards/polls/' . (int)$id); }
        $dup = $this->db->fetchOne("SELECT id FROM award_poll_candidates WHERE poll_id=? AND kind=? AND ref_id=?", [(int)$id, $kind, $refId]);
        if ($dup) { $this->flash('warning', $row['name'] . ' is already on the list.'); $this->redirect('/school/awards/polls/' . (int)$id); }
        $this->db->insert("INSERT INTO award_poll_candidates (poll_id,kind,ref_id,name,class_name) VALUES (?,?,?,?,?)",
            [(int)$id, $kind, $refId, $row['name'], $row['class_name'] ?? null]);
        $this->flash('success', $row['name'] . ' added to the ballot.');
        $this->redirect('/school/awards/polls/' . (int)$id);
    }

    public function removeCandidate(string $id, string $candidateId): void {
        $this->guard();
        if (!$this->poll((int)$id)) { $this->redirect('/school/awards/polls'); }
        $this->db->execute("DELETE FROM award_votes WHERE candidate_id=? AND poll_id=?", [(int)$candidateId, (int)$id]);
        $this->db->execute("DELETE FROM award_poll_candidates WHERE id=? AND poll_id=?", [(int)$candidateId, (int)$id]);
        $this->flash('success', 'Candidate removed, along with any votes cast for them.');
        $this->redirect('/school/awards/polls/' . (int)$id);
    }

    /** Opens, closes or reopens a poll. */
    public function status(string $id): void {
        $this->guard();
        $poll = $this->poll((int)$id);
        if (!$poll) { $this->redirect('/school/awards/polls'); }
        $want = in_array($_POST['status'] ?? '', ['draft', 'open', 'closed'], true) ? $_POST['status'] : 'draft';
        if ($want === 'open') {
            $n = (int)($this->db->fetchOne("SELECT COUNT(*) n FROM award_poll_candidates WHERE poll_id=?", [(int)$id])['n'] ?? 0);
            if ($n < 2) {
                $this->flash('danger', 'Add at least two candidates before opening the vote.');
                $this->redirect('/school/awards/polls/' . (int)$id);
            }
        }
        $this->db->execute("UPDATE award_polls SET status=? WHERE id=? AND tenant_id=?", [$want, (int)$id, $this->tid]);
        $this->flash('success', ['draft' => 'Voting paused — the poll is back to draft.',
                                 'open' => 'Voting is open. Students and staff can vote from their own screens.',
                                 'closed' => 'Voting closed. The result is final and can be published.'][$want]);
        $this->redirect('/school/awards/polls/' . (int)$id);
    }

    /** Puts the winner on the website's recognition board. */
    public function publish(string $id): void {
        $this->guard();
        $poll = $this->poll((int)$id);
        if (!$poll) { $this->redirect('/school/awards/polls'); }
        if ($poll['status'] !== 'closed') {
            $this->flash('danger', 'Close the voting first, so the result cannot change after it is published.');
            $this->redirect('/school/awards/polls/' . (int)$id);
        }
        $results = $this->results((int)$id);
        $winner = $results[0] ?? null;
        if (!$winner || (int)$winner['votes'] === 0) {
            $this->flash('danger', 'No votes were cast, so there is no winner to publish.');
            $this->redirect('/school/awards/polls/' . (int)$id);
        }
        $tied = array_filter($results, fn($r) => (int)$r['votes'] === (int)$winner['votes']);
        if (count($tied) > 1) {
            $this->flash('danger', 'The vote is tied between ' . implode(' and ', array_column($tied, 'name')) . '. Settle the tie before publishing.');
            $this->redirect('/school/awards/polls/' . (int)$id);
        }
        $next = (int)($this->db->fetchOne("SELECT COALESCE(MAX(sort_order),0)+1 n FROM website_awards WHERE tenant_id=?", [$this->tid])['n'] ?? 1);
        $this->db->insert(
            "INSERT INTO website_awards (tenant_id,award_group,title,period_label,recipient_name,recipient_role,class_name,citation,awarded_on,sort_order,is_active)
             VALUES (?,'academic',?,?,?,?,?,?,CURDATE(),?,1)",
            [$this->tid, $poll['title'], $poll['period_label'], $winner['name'],
             $winner['kind'] === 'staff' ? 'teacher' : 'student', $winner['class_name'],
             'Chosen by ' . (int)$winner['votes'] . ' vote(s) of the school community.', $next]
        );
        $this->db->execute("UPDATE award_polls SET published_at=NOW() WHERE id=?", [(int)$id]);
        $this->flash('success', $winner['name'] . ' published to the website recognition board.');
        $this->redirect('/school/awards/polls/' . (int)$id);
    }

    // ── Voting ──────────────────────────────────────────────────────

    /** Which voter group the signed-in person belongs to, or null if they cannot vote. */
    private function voterGroup(): ?string {
        $role = $_SESSION['role'] ?? '';
        if ($role === 'Student') { return 'students'; }
        if ($role === 'Parent') { return null; }
        if ($role === 'Teacher') { return 'teachers'; }
        return 'admins';
    }

    /** The ballots open to this person, with the vote they have already cast. */
    public function ballots(): void {
        $group = $this->voterGroup();
        if ($group === null) { $this->redirect('/parent/dashboard'); }
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $polls = $this->db->fetchAll(
            "SELECT p.*, (SELECT candidate_id FROM award_votes v WHERE v.poll_id=p.id AND v.voter_user_id=?) AS my_vote
             FROM award_polls p
             WHERE p.tenant_id=? AND p.status='open' AND FIND_IN_SET(?, p.voter_groups)
               AND (p.opens_on IS NULL OR p.opens_on <= CURDATE()) AND (p.closes_on IS NULL OR p.closes_on >= CURDATE())
             ORDER BY p.id DESC", [$userId, $this->tid, $group]);
        foreach ($polls as $i => $p) {
            $polls[$i]['candidates'] = $this->db->fetchAll(
                "SELECT id, name, class_name, kind FROM award_poll_candidates WHERE poll_id=? ORDER BY name", [(int)$p['id']]);
        }
        $this->view('school/awards/vote', [
            'pageTitle' => 'Award Voting',
            'panelType' => ($_SESSION['role'] ?? '') === 'Student' ? 'student' : 'school',
            'polls' => $polls, 'flash' => $this->getFlash(),
        ]);
    }

    /** Casts (or changes) this person's single vote in one poll. */
    public function vote(string $id): void {
        $group = $this->voterGroup();
        if ($group === null) { $this->redirect('/parent/dashboard'); }
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $poll = $this->db->fetchOne(
            "SELECT * FROM award_polls WHERE id=? AND tenant_id=? AND status='open' AND FIND_IN_SET(?, voter_groups)",
            [(int)$id, $this->tid, $group]);
        if (!$poll) {
            $this->flash('error', 'That vote is not open to you.');
            $this->redirect('/school/awards/vote');
        }
        $candidate = $this->db->fetchOne("SELECT id FROM award_poll_candidates WHERE id=? AND poll_id=?",
            [(int)($_POST['candidate_id'] ?? 0), (int)$id]);
        if (!$candidate) {
            $this->flash('error', 'Choose someone to vote for.');
            $this->redirect('/school/awards/vote');
        }
        // One vote per person per poll; voting again simply moves the vote.
        $this->db->execute(
            "INSERT INTO award_votes (poll_id,candidate_id,voter_user_id,voter_group) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE candidate_id=VALUES(candidate_id), created_at=NOW()",
            [(int)$id, (int)$candidate['id'], $userId, $group]);
        $this->flash('success', 'Your vote has been recorded. You can change it while the vote is open.');
        $this->redirect('/school/awards/vote');
    }
}
