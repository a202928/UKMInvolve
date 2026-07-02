<?php

require_once __DIR__ . '/LLMClient.php';

class SmartAssistantService
{
    private ?string $userId;
    private string $role;
    private array $context;

    public function __construct(?string $userId = null, string $role = 'guest', array $context = [])
    {
        $this->userId = $userId;
        $this->role = $role;
        $this->context = $context;

        if (!isset($_SESSION['ukm_chatbot_history'])) {
            $_SESSION['ukm_chatbot_history'] = [];
        }
    }

    public function getGreetingAndQuickActions(): array
    {
        $name = null;
        if (!empty($_SESSION['nama'])) {
            $name = explode(' ', $_SESSION['nama'])[0];
        }
        $greetName = $name ? ", $name" : '';

        $greetings = [
            'pelajar'   => "Hi{$greetName}! 👋 I'm your UKMInvolve Smart Assistant. I can help you find events, check your points, explain how registration works, or answer any questions.",
            'penganjur' => "Hello{$greetName}! 👋 I'm your UKMInvolve Smart Assistant. I can help with creating programmes, managing participants, generating reports, and more.",
            'pentadbir' => "Welcome{$greetName}! 👋 I'm your UKMInvolve Smart Assistant. I can assist with user management, system statistics, category settings, and more.",
            'guest'     => "Hi there! 👋 I'm the UKMInvolve Smart Assistant. How can I help you today?",
        ];

        $quickActions = [
            'pelajar'   => ['How do points work?', 'Recommend an event', 'Show my upcoming events', 'How to reach next level?'],
            'penganjur' => ['My created programmes', 'Pending registrations', 'How to generate a report?', 'How to mark attendance?'],
            'pentadbir' => ['System statistics', 'Manage users', 'Monthly Leaderboards', 'Active organizers'],
            'guest'     => ['What is UKMInvolve?', 'How do I register?', 'How do I log in?', 'Who can use this system?'],
        ];

        return [
            'greeting'     => $greetings[$this->role] ?? $greetings['guest'],
            'quickActions' => $quickActions[$this->role] ?? $quickActions['guest'],
        ];
    }

    public function handleMessage(string $userMessage): string
    {
        $_SESSION['ukm_chatbot_history'][] = [
            'role'    => 'user',
            'content' => $userMessage,
        ];

        // Keep last 10 turns (20 messages) to avoid token overflow
        if (count($_SESSION['ukm_chatbot_history']) > 20) {
            $_SESSION['ukm_chatbot_history'] = array_slice(
                $_SESSION['ukm_chatbot_history'], -20
            );
        }

        try {
            $llm = new LLMClient();
            $systemPrompt = $this->buildRAGSystemPrompt();
            $replyText = $llm->generateResponse($systemPrompt, $_SESSION['ukm_chatbot_history']);
            
            $_SESSION['ukm_chatbot_history'][] = [
                'role'    => 'assistant',
                'content' => $replyText,
            ];

            return $this->markdownToHtml($replyText);
        } catch (Exception $e) {
            // Remove the failed user message from history
            array_pop($_SESSION['ukm_chatbot_history']);
            
            // Log technical error silently
            error_log("LLM Smart Assistant Error: " . $e->getMessage());
            
            return "I'm having trouble connecting to the assistant right now. You can still use UKMInvolve normally and try again in a moment.";
        }
    }

    private function buildRAGSystemPrompt(): string
    {
        $roleName = match ($this->role) {
            'pelajar'   => 'student',
            'penganjur' => 'event organizer',
            'pentadbir' => 'system administrator',
            default     => 'guest visitor',
        };

        $name = !empty($_SESSION['nama']) ? explode(' ', $_SESSION['nama'])[0] : 'the user';
        $page = $this->context['page'] ?? 'unknown';

        $prompt = <<<PROMPT
You are the UKMInvolve Smart Assistant — a friendly, helpful, context-aware AI chatbot embedded in the UKMInvolve student activity management system at Universiti Kebangsaan Malaysia (UKM).

## Current user context
- Name: {$name}
- Role: {$roleName}
- Current page they are looking at: {$page}

## Behaviour rules
1. Always respond helpfully, warmly, and concisely (2-4 short paragraphs maximum).
2. Respond in the SAME language the user writes in (Malay or English).
3. For casual greetings like "hi", "hello", "hey", respond with a warm friendly greeting and invite the user to ask a question.
4. Provide actionable guidance and mention relevant page names when useful.
5. **NEVER** invent data. Use ONLY the 'RAG Database Context' provided below to answer questions about the user's specific state (points, upcoming events, etc).
6. Use simple markdown: **bold** for key terms, bullet lists where helpful.
7. Keep the tone friendly, encouraging, and campus-appropriate.
8. If the user asks about an event on the 'event-details.php' page ("Where is this?", "When does it start?"), use the page context below.

PROMPT;

        // --- RAG DATA FETCHING ---
        $prompt .= "\n## RAG Database Context (Live Data for this User)\n";

        // 1. Page Context
        if (isset($this->context['page']) && $this->context['page'] === 'event-details.php' && !empty($this->context['id'])) {
            $event = programs()->findById((int)$this->context['id']);
            if ($event) {
                $prompt .= "- Currently viewing event: " . json_encode([
                    'name' => $event['nama'],
                    'date' => $event['tarikh'],
                    'time' => $event['masa'],
                    'location' => $event['lokasi'],
                    'status' => $event['status'],
                    'points' => $event['mata']
                ]) . "\n";
            }
        }

        // 2. Gamification Rules (General)
        $prompt .= "- Gamification rules: Register=20XP, Attend=100XP, Feedback=30XP, Profile Setup=50XP. Levels: Lvl 1(0 XP), Lvl 2(200 XP), Lvl 3(500 XP), Lvl 4(800 XP), Lvl 5(1200 XP).\n";

        // 3. Role-Specific RAG Data
        if ($this->role === 'pelajar' && $this->userId) {
            $prog = ProgressionService::getStudentProgression($this->userId);
            $prompt .= "- Student Gamification State: " . json_encode([
                'current_points' => $prog['points'],
                'current_level' => $prog['level_name'],
                'points_needed_for_next_level' => isset($prog['next_level']) ? max(0, $prog['next_level']['points_required'] - $prog['points']) : 0,
                'active_streak_months' => $prog['streak'],
                'crew_experience_count' => $prog['crew_experience']
            ]) . "\n";

            $regs = registrations()->listByStudent($this->userId);
            $upcoming = array_filter($regs, fn($r) => strtotime($r['program']['tarikh']) >= strtotime('today'));
            $crew = array_filter($regs, fn($r) => ($r['jenis_pendaftaran'] ?? '') === 'Crew/AJK' && ($r['status'] ?? '') === 'Pending');
            
            $nextEvent = reset($upcoming);
            if ($nextEvent) {
                $prompt .= "- Next Upcoming Event: " . $nextEvent['program']['nama'] . " on " . $nextEvent['program']['tarikh'] . "\n";
            }
            $prompt .= "- Total Registered Events: " . count($regs) . "\n";
            $prompt .= "- Pending Crew Applications: " . count($crew) . "\n";
            
            require_once __DIR__ . '/StudentInterestRepository.php';
            $interests = (new StudentInterestRepository())->listByStudent($this->userId);
            if (!empty($interests)) {
                $prompt .= "- Student Category Interests: " . implode(', ', array_map(fn($i) => $i['kategori']['nama'] ?? '', $interests)) . "\n";
            }
            
            // Include top 5 upcoming events for recommendations
            $activeEvents = array_slice(programs()->listActiveWithCategory(), 0, 5);
            $rec = [];
            foreach ($activeEvents as $e) {
                $rec[] = "{$e['nama']} (Cat: {$e['kategori']['nama']}, Date: {$e['tarikh']})";
            }
            $prompt .= "- Current system active events for recommendations: " . implode(" | ", $rec) . "\n";

        } elseif ($this->role === 'penganjur' && $this->userId) {
            $programs = programs()->listByOrganizer($this->userId);
            $prompt .= "- Organizer Total Created Programmes: " . count($programs) . "\n";
            if (!empty($programs)) {
                $prompt .= "- Most recent programme: " . $programs[0]['nama'] . " (Status: " . $programs[0]['status'] . ")\n";
            }

        } elseif ($this->role === 'pentadbir') {
            $totalStudents = count(users()->listByRole('pelajar'));
            $totalOrgs = count(users()->listByRole('penganjur'));
            $prompt .= "- System Stats: Total Students: $totalStudents, Total Organizers: $totalOrgs\n";
        }

        return $prompt;
    }

    private function markdownToHtml(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // **bold**
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

        // *italic*
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text);

        $lines  = explode("\n", $text);
        $html   = '';
        $inList = false;
        $listTag = 'ul';

        foreach ($lines as $line) {
            $trimmed = ltrim($line);

            if (preg_match('/^[-•]\s+(.+)/', $trimmed, $m)) {
                if (!$inList) {
                    $listTag = 'ul';
                    $html   .= '<ul style="margin:6px 0 6px 16px;padding:0;">';
                    $inList  = true;
                }
                $html .= '<li style="margin-bottom:3px;">' . $m[1] . '</li>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)/', $trimmed, $m)) {
                if (!$inList) {
                    $listTag = 'ol';
                    $html   .= '<ol style="margin:6px 0 6px 16px;padding:0;">';
                    $inList  = true;
                }
                $html .= '<li style="margin-bottom:3px;">' . $m[1] . '</li>';
                continue;
            }

            if ($inList) {
                $html  .= "</{$listTag}>";
                $inList = false;
            }

            if (trim($line) !== '') {
                $html .= '<p style="margin:0 0 6px 0;">' . $line . '</p>';
            }
        }

        if ($inList) {
            $html .= "</{$listTag}>";
        }

        return $html;
    }
}
