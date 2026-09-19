<?php
/**
 * Everything on the public website that a School Admin can edit, and where it lives.
 *
 * Each page's fields are declared here with the text the site shipped with as the
 * default. Only fields an admin has actually changed are stored (website_content), so
 * clearing a field — or "Restore defaults" — brings the original copy straight back,
 * and new fields added here appear on the live site without any data migration.
 *
 * Field types:
 *   text      one line, shown as plain text
 *   title     one line; *word* is shown in the accent italic
 *   textarea  paragraphs separated by a blank line; *italic* and **bold** allowed
 *   lines     one item per line
 *   list      one item per line as "Title | Description" (some lists take more parts)
 *   image     a bundled image filename, or the URL of an uploaded image
 */
class WebsiteContent {

    /** Pages in admin order, each with its banner section listed first. */
    public static function pages(): array {
        static $pages = null;
        if ($pages === null) {
            $pages = self::definitions();
            foreach ($pages as &$page) {
                if (isset($page['sections']['Page Banner'])) {
                    $page['sections'] = ['Page Banner' => $page['sections']['Page Banner']] + $page['sections'];
                }
            }
            unset($page);
        }
        return $pages;
    }

    private static function definitions(): array {
        return [
            'site' => [
                'label' => 'Site-wide & Contact', 'url' => '/', 'icon' => '🌐',
                'desc'  => 'School name, contact details, vision, mission, values and the academic calendar — used across every page.',
                'sections' => [
                    'Identity' => [
                        'site.name'        => ['text', 'School name', 'CELDI Academy'],
                        'site.tagline'     => ['text', 'Tagline', 'Changing Liberia one child at a time'],
                        'site.motto'       => ['text', 'Motto', 'Pursuing Truth, Transforming Lives, and Serving God.'],
                        'site.brand_sub'   => ['text', 'Small text under the logo', 'Est. 2022 · Margibi, Liberia'],
                        'site.logo'        => ['image', 'Logo', 'celdi-logo.png'],
                    ],
                    'Contact' => [
                        'site.address'     => ['text', 'Address', 'Ben Town, Marshall Highway, Margibi County, Liberia'],
                        'site.phone1'      => ['text', 'Main phone', '+231 777 209 062'],
                        'site.phone2'      => ['text', 'Second phone', '+231 880 421 400'],
                        'site.email'       => ['text', 'Email (leave empty to use the email in School Settings)', ''],
                        'site.affiliation' => ['text', 'Affiliation line in the footer', 'CELDI Academy is an affiliate ministry of UrbanPromise International.'],
                        'site.affiliation_logo' => ['image', 'Affiliation logo', 'urbanpromise-logo.png'],
                    ],
                    'Vision, Mission & Values' => [
                        'site.vision'  => ['textarea', 'Vision', 'To be a premier institution of learning, producing critical thinkers, servant leaders, ethical managers, and compassionate citizens who drive sustainable national development with biblical principles.'],
                        'site.mission' => ['textarea', 'Mission', 'To provide a Christ-centered, technological, and vocational education that equips students to be critical thinkers, entrepreneurs, and servant leaders, nurturing a community of learners committed to excellence and a life of faith.'],
                        'site.values_title' => ['title', 'Core values heading', 'The pillars behind *every* CELDI student'],
                        'site.values'  => ['list', 'Core values (Title | Description)', implode("\n", [
                            'Excellence | We pursue the highest standards in all academic and co-curricular endeavors.',
                            'Integrity | We foster honesty, transparency, and ethical conduct in every student.',
                            'Respect | We honor the dignity of every individual, regardless of background or ability.',
                            'Service | We cultivate a spirit of giving back to our community, county, and nation.',
                            'Innovation | We embrace creative thinking and 21st-century skills for a changing world.',
                            'Patriotism | We instill pride in Liberian heritage, culture, and civic responsibility.',
                        ])],
                        'site.pillars' => ['lines', 'The CELDI pillars (one per line)', "Excellence\nIntegrity\nRespect\nService\nInnovation\nLeadership\nDiscipline\nPatriotism"],
                    ],
                    'Academic Calendar' => [
                        'site.cal1_title'  => ['text', 'Period 1 — name', 'Enrollment'],
                        'site.cal1_months' => ['text', 'Period 1 — months', 'July – August'],
                        'site.cal1_items'  => ['lines', 'Period 1 — events', "Pre-registration\nEntrance and placement exams"],
                        'site.cal2_title'  => ['text', 'Period 2 — name', 'Semester One'],
                        'site.cal2_months' => ['text', 'Period 2 — months', 'September – January'],
                        'site.cal2_items'  => ['lines', 'Period 2 — events', "Opening date\nPeriodic tests\nFirst PTA meeting\nChristmas break\nSemester exams"],
                        'site.cal3_title'  => ['text', 'Period 3 — name', 'Semester Two'],
                        'site.cal3_months' => ['text', 'Period 3 — months', 'February – June'],
                        'site.cal3_items'  => ['lines', 'Period 3 — events', "Resumption\nSecond PTA meeting\nPeriodic tests\nEaster break\nFinal PTA meeting\nSemester exams\nFinal exams"],
                    ],
                    'Enroll banner (bottom of most pages)' => [
                        'site.cta_title' => ['title', 'Heading', 'Begin your child’s educational journey *today!*'],
                        'site.cta_text'  => ['textarea', 'Text', 'Start your child’s enrollment online, then complete the process on campus. It’s easy and fast — and space is limited.'],
                        'site.cta_image' => ['image', 'Background photo', 'assembly-panorama.jpg'],
                    ],
                ],
            ],

            'home' => [
                'label' => 'Home', 'url' => '/', 'icon' => '🏠',
                'desc'  => 'The first page visitors see.',
                'sections' => [
                    'Hero Carousel' => [
                        // The hero is a carousel: one slide per photo, crossfading every few seconds.
                        'home.hero_image'   => ['image', 'Slide 1 photo', 'assembly-courtyard.jpg'],
                        'home.hero_image2'  => ['image', 'Slide 2 photo', 'assembly-panorama.jpg'],
                        'home.hero_image3'  => ['image', 'Slide 3 photo', 'students-lineup.jpg'],
                        'home.hero_eyebrow' => ['text', 'Small label above the heading', 'K–12 · Ben Town, Margibi County'],
                        'home.hero_title'   => ['title', 'Heading', 'Changing Liberia, *one child* at a time.'],
                        'home.hero_lead'    => ['textarea', 'Introduction', 'A Christ-centered, technological and vocational education, from nurturing daycare to rigorous senior high. Pursuing Truth, Transforming Lives, and Serving God.'],
                        'home.hero_stats'   => ['list', 'Figures (Number | Label), up to 4', "K–12 | Daycare to Grade 12\n4 | School divisions\n8 | Core CELDI pillars\n2022 | Year established"],
                    ],
                    'About' => [
                        'home.about_title' => ['title', 'Heading', 'A sanctuary for *excellence*, rooted in faith and community.'],
                        'home.about_body'  => ['textarea', 'Text', "CELDI Academy is a transformative K-12 educational institution dedicated to “changing Liberia one child at a time,” by “Pursuing Truth, Transforming Lives, and Serving God.” Grounded in its core pillars — excellence, integrity, respect, service, innovation, leadership, discipline, and patriotism — the Academy provides a holistic learning experience that spans from nurturing early childhood daycare to rigorous senior high school.\n\nBy integrating high academic standards with practical technical training, such as its signature computer and software curriculum, CELDI ensures students graduate with both the intellectual competence and digital proficiency required for the modern world."],
                        'home.about_image1' => ['image', 'Main photo', 'assembly-courtyard.jpg'],
                        'home.about_image2' => ['image', 'Small photo', 'students-lineup.jpg'],
                        'home.about_badge'  => ['list', 'Badge (Title | Text)', 'Est. 2022 | Serving Margibi County'],
                    ],
                    'Divisions' => [
                        'home.divisions_title' => ['title', 'Heading', 'One journey, *four* divisions'],
                        'home.divisions_lead'  => ['textarea', 'Text', 'From a child’s very first day in daycare to graduation from senior high, every stage is designed to build both competence and character.'],
                    ],
                    'Why Choose Us' => [
                        'home.why_title' => ['title', 'Heading', 'More than a school — *a movement*'],
                        'home.why_lead'  => ['textarea', 'Text', 'Our method is more than traditional learning: we’re developing future leaders who are proactive and serve others. By choosing CELDI Academy, you invest in a future where excellence and empathy go hand in hand.'],
                        'home.why_items' => ['list', 'Reasons (Title | Description), 3 recommended', implode("\n", [
                            'Quality Education | We follow the Liberian educational structure with enhanced learning materials and modern teaching methods that work for all learners. We prioritize critical thinking and practical application over memorization, because quality education is the most powerful tool for breaking the cycle of poverty.',
                            'Qualified Teachers | We don’t just hire teachers; we develop mentors. Our staff take part in regular training and professional workshops, and through global partnerships they benefit from cross-cultural training that meets international standards of excellence.',
                            'Proven Excellence | Excellence here is a measurable reality. Our “chapters” model for Elementary, Junior High and Senior High ensures every student graduates with a **Leadership Development Certificate**, ready to lead and to serve their community with distinction.',
                        ])],
                    ],
                    'Community Partnership' => [
                        'home.community_title' => ['title', 'Heading', 'Roots that run *deep*'],
                        'home.community_body'  => ['textarea', 'Text', "Through our **“Third Place Initiative,”** CELDI Academy serves as a sanctuary during after-school hours. Our mentorship, feeding and leadership programs reflect our commitment to the long-term growth and prosperity of our students.\n\nWe work closely with parents and community leaders so that progress made in the classroom becomes transformation for the neighborhood."],
                        'home.community_tags'  => ['lines', 'Tags', "Mentorship\nFeeding program\nLeadership"],
                        'home.community_image' => ['image', 'Photo', 'students-walk.jpg'],
                    ],
                    'Closing' => [
                        'home.statement' => ['textarea', 'Quote', 'Education is a partnership between the school and the home. We commit to raising the next generation of *visionary leaders*, starting from the very first step they take through our doors.'],
                    ],
                ],
            ],

            'about' => [
                'label' => 'About Us', 'url' => '/about-us', 'icon' => '📖',
                'sections' => array_merge(self::hero('about', 'assembly-panorama.jpg', 'About Us',
                    'A movement dedicated to *changing Liberia*, one child at a time.',
                    'A K-12 academy in Ben Town, Margibi County, pursuing truth, transforming lives, and serving God.'), [
                    'Our Story' => [
                        'about.story_title' => ['title', 'Heading', 'About *CELDI* Academy'],
                        'about.story_body'  => ['textarea', 'Text', "CELDI Academy is a transformative K-12 educational institution dedicated to “changing Liberia one child at a time,” by “Pursuing Truth, Transforming Lives, and Serving God.”\n\nGrounded in its core pillars — excellence, integrity, respect, service, innovation, leadership, discipline, and patriotism — the Academy provides a holistic learning experience that spans from nurturing early childhood daycare to rigorous senior high school.\n\nBy integrating high academic standards with practical technical training, such as its signature computer and software curriculum, CELDI ensures students graduate with both the intellectual competence and digital proficiency required for the modern world. Through its unique community partnership model and a steadfast commitment to professional ethics and mentorship, the Academy serves as a sanctuary for excellence, empowering students to become disciplined, innovative architects of change, ready to serve their nation with integrity."],
                        'about.story_image1' => ['image', 'Main photo', 'students-lineup.jpg'],
                        'about.story_image2' => ['image', 'Small photo', 'students-walk.jpg'],
                    ],
                    'Why Choose CELDI' => [
                        'about.why_title' => ['title', 'Heading', 'Where excellence and *empathy* go hand in hand'],
                        'about.why_body'  => ['textarea', 'Text', 'At CELDI Academy, we are more than just a school; we are a movement dedicated to “Changing Liberia one child at a time.” Our method is more than traditional learning; we’re developing future leaders who are proactive and serve others.'],
                        'about.why_items' => ['list', 'Reasons (Title | Description)', implode("\n", [
                            'Quality Education | The Liberian educational structure, enhanced learning materials and modern teaching that works for all learners. We prioritize critical thinking and practical application over simple memorization.',
                            'Qualified Teachers | We don’t just hire teachers; we develop mentors, through regular training, professional workshops and cross-cultural seminars with global partners.',
                            'Proven Excellence | Ambitious goals for graduation and success rates, and a “chapters” model that ensures every student graduates with a Leadership Development Certificate.',
                        ])],
                        'about.why_image' => ['image', 'Photo', 'geography.jpg'],
                    ],
                    'Community Partnership' => [
                        'about.community_title' => ['title', 'Heading', 'A “third place” for our *community*'],
                        'about.community_body'  => ['textarea', 'Text', "CELDI Academy’s roots run deep in the communities it serves. Through our “Third Place Initiative,” we serve as a sanctuary during after-school hours.\n\nOur mentorship, feeding, and leadership programs reassure the communities of our commitment to the long-term growth and prosperity of our students. We work closely with parents and community leaders to ensure that the progress made in the classroom translates into transformation for the neighborhood."],
                        'about.community_image' => ['image', 'Photo', 'community-team.jpg'],
                    ],
                    'Affiliation' => [
                        'about.affiliation_title' => ['title', 'Heading', 'Part of the *UrbanPromise* family'],
                        'about.affiliation_body'  => ['textarea', 'Text', 'CELDI Academy is an affiliate ministry of UrbanPromise International.'],
                    ],
                ]),
            ],

            'leadership' => [
                'label' => 'Our Leadership', 'url' => '/our-leadership', 'icon' => '🏛️',
                'desc'  => 'Page text. Add the people themselves under Website → Leadership Team.',
                'sections' => array_merge(self::hero('leadership', 'community-team.jpg', 'Our Leadership',
                    'Leading by *serving*.',
                    'At CELDI Academy, leadership is a calling to serve: our board, administration, faculty and parents work together for every child.'), [
                    'Introduction' => [
                        'leadership.statement'  => ['textarea', 'Quote', 'We are developing future leaders who are proactive and serve others — and that begins with *how we lead* ourselves.'],
                        'leadership.team_title' => ['title', 'Team heading (shown when team members are added)', 'Meet our *leadership team*'],
                    ],
                    'The Board' => [
                        'leadership.board_title' => ['text', 'Heading', 'The Board'],
                        'leadership.board_body'  => ['textarea', 'Text', "The Board provides the vision and stewardship that keep CELDI Academy faithful to its mission: a Christ-centered, technological, and vocational education that equips students to be critical thinkers, entrepreneurs, and servant leaders.\n\nCELDI Academy is an affiliate ministry of UrbanPromise International, and its leadership is committed to the long-term growth and prosperity of the students and communities it serves."],
                    ],
                    'School Leadership' => [
                        'leadership.admin_title' => ['text', 'Heading', 'School Leadership'],
                        'leadership.admin_body'  => ['textarea', 'Text', "The school’s administration leads the day-to-day life of the Academy across all four divisions — Early Childhood & Daycare, Elementary, Junior High, and Senior High.\n\nThey set ambitious goals to increase graduation and success rates, uphold the Academy’s commitment to professional ethics and mentorship, and oversee the “chapters” model through which every student earns a Leadership Development Certificate."],
                    ],
                    'Educators' => [
                        'leadership.staff_title' => ['text', 'Heading', 'Our Educators'],
                        'leadership.staff_body'  => ['textarea', 'Text', "The strength of our academy lies in our educators. We don’t just hire teachers; we develop mentors.\n\nOur staff undergo regular training and professional workshops to stay at the forefront of modern pedagogy. Through partnerships with global organizations, our teachers benefit from cross-cultural training and specialized seminars, ensuring every child receives instruction that meets international standards of excellence."],
                    ],
                    'Parents (PTA)' => [
                        'leadership.pta_title' => ['text', 'Heading', 'Parent-Teacher Association'],
                        'leadership.pta_body'  => ['textarea', 'Text', "Education is a partnership between the school and the home.\n\nThe PTA meets three times each academic year — once in the first semester and twice in the second — so that parents and teachers can work together to monitor progress and celebrate the growth of every student."],
                    ],
                ]),
            ],

            'student_life' => [
                'label' => 'Student Life', 'url' => '/student-life', 'icon' => '🎒',
                'sections' => array_merge(self::hero('student_life', 'students-walk.jpg', 'Student Life',
                    'Learning that extends *beyond the bell*.',
                    'Mentorship, leadership, service and community: the life of a CELDI student reaches well past the classroom.',
                    "Mentorship\nFeeding program\nLeadership chapters"), [
                    'Third Place Initiative' => [
                        'student_life.intro_title' => ['title', 'Heading', 'A sanctuary after *school hours*'],
                        'student_life.intro_body'  => ['textarea', 'Text', "CELDI Academy’s roots run deep in the communities it serves. Through our “Third Place Initiative,” the Academy serves as a sanctuary during after-school hours — a safe, positive place between home and classroom.\n\nOur mentorship, feeding, and leadership programs reflect our commitment to the long-term growth and prosperity of our students, so that the progress made in the classroom translates into transformation for the neighborhood."],
                        'student_life.intro_image1' => ['image', 'Main photo', 'assembly-courtyard.jpg'],
                        'student_life.intro_image2' => ['image', 'Small photo', 'students-lineup.jpg'],
                    ],
                    'Programs' => [
                        'student_life.programs_title' => ['title', 'Heading', 'Programs that shape *the whole child*'],
                        'student_life.programs' => ['list', 'Programs (Title | Description)', implode("\n", [
                            'Mentorship | Our teachers are developed as mentors, and our mentorship programs walk alongside students as they grow in confidence, character, and faith.',
                            'Feeding Program | Part of our commitment to the whole child: students who are cared for are students who are ready to learn and thrive.',
                            'Leadership Chapters | Our “chapters” model for Elementary, Junior High and Senior High ensures every student graduates with a Leadership Development Certificate.',
                            'Computer & Software | CELDI’s signature computer and software curriculum builds the digital proficiency required for the modern world.',
                            'Service | Students learn that true leadership begins with a heart for service to their community, county, and nation.',
                            'Patriotism & Heritage | We instill pride in Liberian heritage, culture, and civic responsibility in every student.',
                        ])],
                    ],
                    'Gallery & Calendar' => [
                        'student_life.gallery_title'  => ['title', 'Gallery heading', 'Life at *CELDI*'],
                        'student_life.calendar_title' => ['title', 'Calendar heading', 'The rhythm of the *school year*'],
                    ],
                ]),
            ],

            'divisions' => [
                'label' => 'Divisions Overview', 'url' => '/divisions', 'icon' => '🧭',
                'desc'  => 'The overview page. Each division’s summary and card are edited on that division’s own page.',
                'sections' => array_merge(self::hero('divisions', 'hero-assembly.jpg', 'Our School Divisions',
                    'Four divisions. *One* journey of growth.',
                    'CELDI Academy is arranged into four sections, each building on the last, from a child’s first steps in daycare to graduation from senior high.',
                    "Early Childhood\nElementary\nJunior High\nSenior High"), [
                    'Closing' => [
                        'divisions.statement' => ['textarea', 'Quote', 'Every student graduates with a *Leadership Development Certificate*, ready to lead organizations at their level and serve their communities with distinction.'],
                    ],
                ]),
            ],

            'early' => [
                'label' => 'Early Childhood', 'url' => '/early-childhood', 'icon' => '🧸',
                'sections' => array_merge(self::division('early', 'Daycare – K', 'Early Childhood & Daycare', 'early-childhood.jpg',
                    'A nurturing environment where character and intellect take root from the very first step.',
                    "At CELDI Academy, we believe that the earliest years of a child’s life are the most critical for building a foundation of character and intellect.\n\nOur Early Childhood Program and Daycare go beyond supervision; we provide a nurturing environment where the CELDI pillars — excellence, integrity, respect, service, innovation, leadership, discipline, and patriotism — are introduced at the very beginning of the educational journey."),
                    self::hero('early', 'early-childhood.jpg', 'Early Childhood & Daycare',
                    'Where character and intellect *take root*.',
                    'A nurturing, joyful start where the CELDI pillars are introduced at the very beginning of the educational journey.',
                    "Daycare\nNursery & Kindergarten\nSafe & nurturing"), [
                    'Our Program' => [
                        'early.intro_title' => ['title', 'Heading', 'The most important years *begin here*'],
                        'early.intro_body'  => ['textarea', 'Text', "At CELDI Academy, we believe that the earliest years of a child’s life are the most critical for building a foundation of character and intellect.\n\nOur Early Childhood Program and Daycare are designed to provide more than just supervision; we provide a nurturing environment where the “CELDI” pillars — Creativity, Empathy, Leadership, Discipline, and Innovation — are introduced at the very beginning of the educational journey."],
                        'early.intro_image' => ['image', 'Photo', 'teacher-reading.jpg'],
                    ],
                    'Educational Philosophy' => [
                        'early.philosophy_title' => ['title', 'Heading', 'Every child is *unique*'],
                        'early.philosophy_lead'  => ['textarea', 'Highlighted sentence', 'We view every child as a unique individual with the potential to learn, lead, and inspire.'],
                        'early.philosophy_body'  => ['textarea', 'Text', 'We ensure the holistic development of each child by balancing structured learning and purposeful play, meeting their social, emotional, physical, and cognitive needs.'],
                        'early.philosophy_image' => ['image', 'Photo', 'upper-elementary.jpg'],
                    ],
                    'Pillars in Early Learning' => [
                        'early.pillars_title' => ['title', 'Heading', 'The CELDI pillars in *early learning*'],
                        'early.pillars' => ['list', 'Pillars (Title | Description)', implode("\n", [
                            'Creativity | We help children express themselves and think in new ways by letting them create art, play music, and imagine.',
                            'Empathy | We foster a kind and inclusive classroom culture where children learn to understand their emotions and care for their peers.',
                            'Leadership | Even our youngest learners are taught the value of responsibility, service, and taking initiative in small, age-appropriate ways.',
                            'Discipline | We provide a stable routine that helps children develop self-control, respect for boundaries, and a love for order.',
                            'Innovation | We introduce basic problem-solving and curiosity-driven activities that prepare children for a transforming world.',
                        ])],
                    ],
                    'Program Highlights' => [
                        'early.highlights_title' => ['title', 'Heading', 'A *home away from home*'],
                        'early.highlights' => ['list', 'Highlights (Title | Description)', implode("\n", [
                            'Foundational Literacy & Numeracy | Our daycare and preschool programs introduce the building blocks of reading and mathematics through engaging, multi-sensory activities.',
                            'Safe & Nurturing Environment | Our facilities are a “home away from home,” prioritizing safety, hygiene, and the emotional well-being of every student.',
                            'Active Discovery | We emphasize “learning by doing.” From tactile sensory bins to outdoor exploration, children remain active participants in their education.',
                            'Character Building | Beyond academics, we prioritize the development of core values that will guide students as they transition into our primary and secondary programs.',
                        ])],
                        'early.statement' => ['textarea', 'Closing quote', 'Education is a partnership between the school and the home. We maintain open communication with parents so that the family supports and *celebrates* the growth witnessed in the classroom.'],
                    ],
                ]),
            ],

            'elementary' => [
                'label' => 'Elementary', 'url' => '/elementary', 'icon' => '✏️',
                'sections' => array_merge(self::division('elementary', 'Grades 1 – 6', 'Elementary', 'elementary.jpg',
                    'The bridge between early discovery and academic mastery, with arts, technology and critical thinking.',
                    "The elementary section at CELDI Academy serves as the bridge between early discovery and academic mastery. We recognize that these formative years shape character and competence.\n\nOur curriculum challenges students intellectually while grounding them in our core values. Core academic subjects are enhanced with arts, technology, and critical thinking for grades 1–6."),
                    self::hero('elementary', 'elementary.jpg', 'Elementary · Grades 1–6',
                    'The bridge from discovery to *academic mastery*.',
                    'A curriculum that challenges students intellectually while grounding them in the values that shape character.',
                    "Language Arts\nMathematics\nSciences & Social Studies"), [
                    'Our Program' => [
                        'elementary.intro_title' => ['title', 'Heading', 'Formative years that shape *character and competence*'],
                        'elementary.intro_body'  => ['textarea', 'Text', "The Elementary Program at CELDI Academy serves as the bridge between early discovery and academic mastery. Recognizing that these formative years are essential for shaping both character and competence, our curriculum is designed to challenge students intellectually while grounding them in the core values of excellence, integrity, respect, service, innovation, leadership, discipline, and patriotism.\n\nCore academic subjects are enhanced with arts, technology, and critical thinking for grades 1–6."],
                        'elementary.intro_image' => ['image', 'Photo', 'upper-elementary.jpg'],
                    ],
                    'Academic Excellence' => [
                        'elementary.academic_title' => ['title', 'Heading', 'Literacy and numeracy — the keys to *all future learning*'],
                        'elementary.academic_lead'  => ['textarea', 'Text', 'We use a balanced instructional approach that combines rigorous academic standards with multisensory, hands-on activities.'],
                        'elementary.academic_items' => ['list', 'Subjects (Title | Description)', implode("\n", [
                            'Language Arts | Focus on reading comprehension, critical writing, and public speaking to ensure students can articulate their ideas with confidence.',
                            'Mathematics | Emphasis on logical reasoning and practical problem-solving, moving beyond rote memorization to a deep understanding of mathematical concepts.',
                            'Sciences & Social Studies | Integrated units that encourage students to explore the world around them, fostering curiosity and a sense of global citizenship.',
                        ])],
                    ],
                    'Beyond the Classroom' => [
                        'elementary.beyond_title' => ['title', 'Heading', 'Educating the *whole child*'],
                        'elementary.beyond_body'  => ['textarea', 'Text', 'What sets the CELDI Academy Elementary program apart is our commitment to the “whole child.” We integrate leadership development and character education into the daily school experience.'],
                        'elementary.beyond_items' => ['list', 'Points (Title | Description)', implode("\n", [
                            'Service-Oriented Leadership | Students learn that true leadership begins with a heart for service and a commitment to the community.',
                            'Ethical Discipline | A culture of self-regulation and integrity, where students take ownership of their actions and understand the impact of their choices on others.',
                            'Creative Innovation | Dedicated time for projects and collaborative problem-solving teaches students to approach challenges with an innovative, “can-do” mindset.',
                        ])],
                        'elementary.beyond_image' => ['image', 'Photo', 'science-lab.jpg'],
                    ],
                    'Learning Environment' => [
                        'elementary.env_title' => ['title', 'Heading', 'Structured, dynamic, and *supportive*'],
                        'elementary.env_lead'  => ['textarea', 'Text', 'We provide an environment where every child feels seen and supported.'],
                        'elementary.env_items' => ['list', 'Points (Title | Description)', implode("\n", [
                            'Holistic Growth | Our educators focus on the social-emotional well-being of each student, so they develop the empathy and resilience needed to thrive.',
                            'Active Discovery | Classrooms at CELDI Academy are labs of learning: children learn best when actively engaged in discovery, experimentation, and inquiry.',
                            'Collaborative Partnership | A strong link between teachers and parents, working together to monitor progress and celebrate every milestone.',
                        ])],
                    ],
                    'Exploration & Goal' => [
                        'elementary.explore_title' => ['title', 'Heading', 'Confident *explorers*, strong foundations'],
                        'elementary.explore_body'  => ['textarea', 'Text', "At CELDI, our learning spaces are vibrant hubs of activity. We empower children to become confident explorers, fostering deep understanding through hands-on experiences, creative problem-solving, and guided investigation.\n\nOur dedicated educators prioritize the complete development of each child — not only academic skills but also social-emotional intelligence, physical health, and creative expression — building a strong foundation for lifelong success and happiness."],
                        'elementary.goal' => ['textarea', 'Program goal', 'To produce well-rounded scholars who are prepared for the academic rigors of junior high and are emerging as disciplined, empathetic, and innovative leaders in their own right.'],
                    ],
                ]),
            ],

            'junior' => [
                'label' => 'Junior High', 'url' => '/junior-high', 'icon' => '🔬',
                'sections' => array_merge(self::division('junior', 'Grades 7 – 9', 'Junior High', 'junior-high.jpg',
                    'Advanced critical thinking, specialized skills and the journey from student to servant-leader.',
                    "At the Junior High level, CELDI Academy transitions students from foundational learning to advanced critical thinking and specialized skill acquisition, bridging the gap between childhood curiosity and the focused ambition required for Senior High School and beyond.\n\nOur program centers on the transition from being a student to becoming a servant-leader, with a rigorous college-preparatory curriculum and personalized guidance."),
                    self::hero('junior', 'junior-high.jpg', 'Junior High · Grades 7–9',
                    'From student to *servant-leader*.',
                    'Advanced critical thinking, specialized skill acquisition and personalized guidance on the road to Senior High and beyond.',
                    "College-preparatory\nComputer & software\nPersonalized guidance"), [
                    'The Program' => [
                        'junior.intro_title' => ['title', 'Heading', 'Bridging curiosity and *focused ambition*'],
                        'junior.intro_body'  => ['textarea', 'Text', "At the Junior High level (Grades 7–9), CELDI Academy transitions students from foundational learning to advanced critical thinking and specialized skill acquisition. This stage is designed to bridge the gap between childhood curiosity and the focused ambition required for Senior High School and beyond.\n\nOur program centers on the transition from being a student to becoming a servant-leader, ensuring that as academic rigor increases, so does the commitment to the CELDI pillars: Excellence, Integrity, Respect, Service, Innovation, Leadership, Discipline, and Patriotism."],
                        'junior.intro_image' => ['image', 'Photo', 'geography.jpg'],
                    ],
                    'What Defines Junior High' => [
                        'junior.features_title' => ['title', 'Heading', 'Rigor, skills, and *character*'],
                        'junior.features' => ['list', 'Features (Title | Description)', implode("\n", [
                            'College-Preparatory Curriculum | A rigorous curriculum with personalized guidance that prepares every student for the demands of Senior High School and beyond.',
                            'Technical & Digital Skills | Through CELDI’s signature computer and software curriculum, students build the digital proficiency the modern world requires.',
                            'Leadership Development | Our “chapters” model continues in Junior High, preparing students to lead organizations at their level and serve their communities.',
                        ])],
                    ],
                    'Growing Commitment' => [
                        'junior.commit_title' => ['title', 'Heading', 'As rigor rises, so does *character*'],
                        'junior.commit_body'  => ['textarea', 'Text', 'Junior High is where students begin to take ownership of their learning and their leadership. Every stage of the program is anchored in the eight CELDI pillars.'],
                        'junior.commit_image' => ['image', 'Photo', 'science-lab.jpg'],
                    ],
                ]),
            ],

            'senior' => [
                'label' => 'Senior High', 'url' => '/senior-high', 'icon' => '🎓',
                'sections' => array_merge(self::division('senior', 'Grades 10 – 12', 'Senior High', 'computer-lab.jpg',
                    'The culmination: emerging professionals with purpose and real technical proficiency.',
                    "The Senior High School program is the culmination of our educational journey. At this stage, students transition from academic learners to emerging professionals and community architects.\n\nOur curriculum refines their specialized interests, ensuring they graduate not only with a diploma but with a clear sense of purpose and the technical proficiency to excel in higher education or the global workforce."),
                    self::hero('senior', 'computer-lab.jpg', 'Senior High · Grades 10–12',
                    'Emerging professionals. *Community architects.*',
                    'The culmination of the CELDI journey: graduating with a diploma, a clear sense of purpose, and real technical proficiency.',
                    "Diploma + Leadership Certificate\nTechnical proficiency\nHigher education ready"), [
                    'The Program' => [
                        'senior.intro_title' => ['title', 'Heading', 'The culmination of our *educational journey*'],
                        'senior.intro_body'  => ['textarea', 'Text', "The Senior High School program (Grades 10–12) at CELDI Academy is the culmination of our educational journey. At this stage, students transition from academic learners to emerging professionals and community architects.\n\nOur curriculum is designed to refine their specialized interests, ensuring they graduate not only with a diploma but with a clear sense of purpose and the technical proficiency to excel in higher education or the global workforce.\n\nCore academic subjects are enhanced with arts, technology, and critical thinking for grades 10–12."],
                        'senior.intro_image' => ['image', 'Photo', 'junior-high.jpg'],
                    ],
                    'Graduate Outcomes' => [
                        'senior.outcomes_title' => ['title', 'Heading', 'Ready to lead, ready to *serve*'],
                        'senior.outcomes' => ['list', 'Outcomes (Title | Description)', implode("\n", [
                            'A Diploma — and a Purpose | Students graduate with a clear sense of direction, having refined their specialized interests through the program.',
                            'Technical Proficiency | CELDI’s signature computer and software curriculum gives graduates the digital skills required for the modern world.',
                            'Leadership Development Certificate | Through our “chapters” model, every graduate earns a Leadership Development Certificate, ready to lead and serve.',
                        ])],
                    ],
                    'Beyond Graduation' => [
                        'senior.beyond_title'  => ['title', 'Heading', 'Disciplined, innovative *architects of change*'],
                        'senior.beyond_body'   => ['textarea', 'Text', 'CELDI Academy exists to empower students to become disciplined, innovative architects of change, ready to serve their nation with integrity. Our senior students are prepared to excel whether they continue to higher education or step into the global workforce.'],
                        'senior.beyond_points' => ['lines', 'Checklist', "Refined specialized interests and a clear sense of purpose\nIntellectual competence alongside digital proficiency\nServant leadership grounded in the eight CELDI pillars\nPreparation for higher education or the global workforce"],
                        'senior.beyond_image'  => ['image', 'Photo', 'gallery-1.jpg'],
                    ],
                ]),
            ],

            'admissions' => [
                'label' => 'Admissions & Fees', 'url' => '/admissions', 'icon' => '📝',
                'sections' => array_merge(self::hero('admissions', 'students-lineup.jpg', 'Admissions',
                    'Begin your child’s enrollment *online, today*.',
                    'Start the application online in a few minutes, then complete the process on campus. The process is easy and fast, and space is limited.',
                    "Apply online\nPlacement exams July – August\nComplete on campus"), [
                    'Why Enroll' => [
                        'admissions.why_title' => ['title', 'Heading', 'An investment in *who your child becomes*'],
                        'admissions.why_body'  => ['textarea', 'Text', "CELDI Academy is a transformative K-12 educational institution dedicated to “changing Liberia one child at a time,” by “Pursuing Truth, Transforming Lives, and Serving God.” Grounded in its core pillars — excellence, integrity, respect, service, innovation, leadership, discipline, and patriotism — the Academy provides a holistic learning experience from nurturing early childhood daycare to rigorous senior high school.\n\nBy integrating high academic standards with practical technical training, such as its signature computer and software curriculum, CELDI ensures students graduate with both the intellectual competence and digital proficiency required for the modern world."],
                        'admissions.why_image1' => ['image', 'Main photo', 'assembly-courtyard.jpg'],
                        'admissions.why_image2' => ['image', 'Small photo', 'early-childhood.jpg'],
                        'admissions.badge'      => ['list', 'Badge (Title | Text)', 'Limited space | Apply early to secure a place'],
                    ],
                    'How to Enroll' => [
                        'admissions.steps_title' => ['title', 'Heading', 'Four simple *steps*'],
                        'admissions.steps' => ['list', 'Steps (Title | Description)', implode("\n", [
                            'Apply online | Complete the online application with your child’s details, parent or guardian information and supporting documents.',
                            'Pre-registration | The school reviews your application and contacts you. Pre-registration runs from July to August.',
                            'Entrance & placement | Your child sits the entrance and placement exams so we can place them in the right class.',
                            'Complete on campus | Finalize enrollment with the Business Office on campus, and get ready for opening day in September.',
                        ])],
                    ],
                    'Fees' => [
                        'admissions.fees_title' => ['title', 'Heading', 'Tuition *by level*'],
                        'admissions.fees' => ['list', 'Fees (Level | Grades | Amount), one per card', implode("\n", [
                            'Early Childhood | Daycare – K2 | $42,500',
                            'Lower Elementary | Grades 1 – 3 | $46,500',
                            'Upper Elementary | Grades 4 – 6 | $47,300',
                            'Junior High | Grades 7 – 9 | $50,800',
                        ])],
                        'admissions.fees_label' => ['text', 'Label under each amount', 'Tuition'],
                        'admissions.fees_note'  => ['textarea', 'Note under the fees', 'For full details, including Senior High (Grades 10–12) fees, contact the **Business Office** for the information brochure.'],
                    ],
                    'Enroll banner' => [
                        'admissions.cta_title' => ['title', 'Heading', 'Begin your child’s enrollment *online, today!*'],
                        'admissions.cta_text'  => ['textarea', 'Text', 'You can begin your child’s enrollment online today and complete the process on campus. The process is easy and fast. We have limited space — act now!'],
                    ],
                ]),
            ],

            'news' => [
                'label' => 'News & Events Page', 'url' => '/academy-news', 'icon' => '📰',
                'desc'  => 'Page text. Post stories and events under Website → News & Events.',
                'sections' => array_merge(self::hero('news', 'assembly-courtyard.jpg', 'News & Events',
                    'What’s happening at *CELDI Academy*.',
                    'Key dates for the academic year, PTA meetings, exams and breaks, and highlights from campus.'), [
                    'Sections' => [
                        'news.posts_title'    => ['title', 'Latest news heading', 'Latest *news*'],
                        'news.calendar_title' => ['title', 'Calendar heading', 'The academic *year*'],
                        'news.calendar_lead'  => ['textarea', 'Calendar text', 'Plan ahead with the main milestones of the CELDI Academy school year. Exact dates are shared with families through the school and the parent portal.'],
                        'news.highlights_title' => ['title', 'Highlights heading', 'Moments to *mark*'],
                        'news.highlights' => ['list', 'Highlights (Title | When | Description)', implode("\n", [
                            'Pre-registration & Placement | July – August | Pre-registration opens along with entrance and placement exams for new students. Applications can be started online at any time.',
                            'Opening Day | September | The first semester opens, followed by periodic tests, the first PTA meeting, the Christmas break and semester exams.',
                            'PTA Meetings | Three times a year | One PTA meeting in the first semester and two in the second, keeping families and teachers working together.',
                        ])],
                        'news.gallery_title' => ['title', 'Gallery heading', 'Photo *highlights*'],
                        'news.portal_title'  => ['title', 'Portal heading', 'Already a CELDI *family*?'],
                        'news.portal_text'   => ['textarea', 'Portal text', 'Parents and students receive announcements, report cards, attendance and fee information directly in the school portal.'],
                    ],
                ]),
            ],
        ];
    }

    /** The standard page-banner fields for a page. */
    private static function hero(string $p, string $image, string $eyebrow, string $title, string $lead, ?string $chips = null): array {
        $fields = [
            "$p.hero_image"   => ['image', 'Background photo', $image],
            "$p.hero_eyebrow" => ['text', 'Small label above the heading', $eyebrow],
            "$p.hero_title"   => ['title', 'Heading', $title],
            "$p.hero_lead"    => ['textarea', 'Introduction', $lead],
        ];
        if ($chips !== null) {
            $fields["$p.hero_chips"] = ['lines', 'Highlight tags', $chips];
        }
        return ['Page Banner' => $fields];
    }

    /** How a division appears on the Home cards and the Divisions overview page. */
    private static function division(string $p, string $grade, string $title, string $image, string $card, string $overview): array {
        return ['Card on Home & Divisions pages' => [
            "$p.card_grade"    => ['text', 'Grades label', $grade],
            "$p.card_title"    => ['text', 'Name', $title],
            "$p.card_image"    => ['image', 'Card photo', $image],
            "$p.card_text"     => ['textarea', 'Short summary (Home page card)', $card],
            "$p.overview_text" => ['textarea', 'Longer summary (Divisions page)', $overview],
        ]];
    }

    /** Flat map of every field key => [type, label, default, pageKey]. */
    public static function fields(): array {
        static $flat = null;
        if ($flat === null) {
            $flat = [];
            foreach (self::pages() as $pageKey => $page) {
                foreach ($page['sections'] as $fields) {
                    foreach ($fields as $key => $def) {
                        $flat[$key] = [$def[0], $def[1], $def[2], $pageKey];
                    }
                }
            }
        }
        return $flat;
    }

    /**
     * Every field's current value for a school: saved edits over the defaults.
     * Never throws — the public site must render even before the table exists.
     */
    public static function load(Database $db, ?int $tenantId): array {
        $values = array_map(fn($f) => $f[2], self::fields());
        if (!$tenantId) { return $values; }
        try {
            foreach ($db->fetchAll("SELECT content_key, content FROM website_content WHERE tenant_id=?", [$tenantId]) as $row) {
                if (array_key_exists($row['content_key'], $values)) {
                    $values[$row['content_key']] = (string)$row['content'];
                }
            }
        } catch (\Throwable $e) {
            error_log('Website content unavailable: ' . $e->getMessage());
        }
        return $values;
    }

    /** Rows from one of the website's own tables, or [] if it isn't there yet. */
    public static function rows(Database $db, string $sql, array $params): array {
        try {
            return $db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('Website table unavailable: ' . $e->getMessage());
            return [];
        }
    }

    public static function ensureSchema(Database $db): void {
        $db->execute("CREATE TABLE IF NOT EXISTS website_content (
            tenant_id INT UNSIGNED NOT NULL,
            content_key VARCHAR(120) NOT NULL,
            content MEDIUMTEXT,
            updated_by INT UNSIGNED DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (tenant_id, content_key),
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->execute("CREATE TABLE IF NOT EXISTS website_posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            category ENUM('news','event') NOT NULL DEFAULT 'news',
            title VARCHAR(200) NOT NULL,
            excerpt VARCHAR(400) DEFAULT NULL,
            body MEDIUMTEXT,
            image_url VARCHAR(255) DEFAULT NULL,
            event_date DATE DEFAULT NULL,
            event_location VARCHAR(200) DEFAULT NULL,
            published_on DATE NOT NULL,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_website_posts_tenant (tenant_id, is_published, published_on),
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->execute("CREATE TABLE IF NOT EXISTS website_gallery (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            caption VARCHAR(200) DEFAULT NULL,
            sort_order SMALLINT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_website_gallery_tenant (tenant_id, is_active, sort_order),
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->execute("CREATE TABLE IF NOT EXISTS website_leaders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            name VARCHAR(150) NOT NULL,
            position VARCHAR(150) NOT NULL,
            bio VARCHAR(600) DEFAULT NULL,
            photo_url VARCHAR(255) DEFAULT NULL,
            sort_order SMALLINT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_website_leaders_tenant (tenant_id, is_active, sort_order),
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
