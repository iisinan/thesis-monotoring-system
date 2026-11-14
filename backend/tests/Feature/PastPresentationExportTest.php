<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\DefenceEvent;
use App\Models\Evaluation;
use App\Models\Level;
use App\Models\MilestoneTemplate;
use App\Models\Program;
use App\Models\ThesisProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

class PastPresentationExportTest extends TestCase
{
    use RefreshDatabase;

    private Cohort $cohort1;
    private Cohort $cohort2;
    private Level $mscLevel;
    private Level $phdLevel;
    private Program $aiProgram;
    private Program $cyberProgram;
    private Program $misProgram;
    private User $admin;
    private MilestoneTemplate $seminarTemplate;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake();
        Mail::fake();
        Notification::fake();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->cohort1 = Cohort::create([
            'name' => '2024_1',
            'code' => '2024_1',
            'intake_year' => 2024,
            'status' => 'active',
            'start_date' => now()->subYear(),
            'end_date' => now()->addYear(),
        ]);

        $this->cohort2 = Cohort::create([
            'name' => '2024_2',
            'code' => '2024_2',
            'intake_year' => 2024,
            'status' => 'active',
            'start_date' => now()->subMonths(6),
            'end_date' => now()->addYear(),
        ]);

        $this->mscLevel = Level::firstOrCreate(['name' => 'MSc']);
        $this->phdLevel = Level::firstOrCreate(['name' => 'PhD']);

        $this->aiProgram = Program::firstOrCreate(
            ['code' => 'AI'],
            ['name' => 'Artificial Intelligence']
        );
        $this->cyberProgram = Program::firstOrCreate(
            ['code' => 'CYBER'],
            ['name' => 'Cybersecurity']
        );
        $this->misProgram = Program::firstOrCreate(
            ['code' => 'MIS'],
            ['name' => 'MIS']
        );

        $this->admin = User::factory()->create(['name' => 'Admin User', 'is_active' => true]);
        $this->admin->assignRole('Admin');

        $this->seminarTemplate = MilestoneTemplate::where('slug', 'seminar_as_a_course')->firstOrFail();
    }

    private function createPastSeminarCandidate(
        string $name,
        string $matric,
        Program $program,
        Level $level,
        Cohort $cohort,
        array $examinerTotals = []
    ): array {
        $studentUser = User::factory()->create(['name' => $name, 'is_active' => true]);
        $studentUser->assignRole('Student');

        $student = $studentUser->studentProfile()->create([
            'program_id' => $program->id,
            'cohort_id' => $cohort->id,
            'level_id' => $level->id,
            'student_id_number' => $matric,
            'enrollment_status' => 'active',
        ]);

        $thesis = ThesisProject::create([
            'student_profile_id' => $student->id,
            'title' => "Seminar Thesis for {$name}",
            'status' => 'active',
            'started_at' => now()->subMonths(3),
        ]);
        $thesis->syncMilestones();

        $milestone = $thesis->milestones()
            ->where('milestone_template_id', $this->seminarTemplate->id)
            ->first();

        if (!$milestone) {
            // For exempt student (e.g. PhD AI), return without seminar milestone
            return [$student, $thesis, null];
        }

        $milestone->update([
            'status' => 'approved',
            'defence_date' => now()->subWeeks(2)->toDateString(),
            'approved_at' => now()->subWeek(),
        ]);

        $event = DefenceEvent::create([
            'thesis_project_id' => $thesis->id,
            'type' => 'seminar',
            'schedule_start' => now()->subWeeks(2),
            'schedule_end' => now()->subWeeks(2)->addHour(),
            'location' => 'https://zoom.us/j/seminar',
            'outcome' => 'pass',
        ]);

        foreach ($examinerTotals as $total) {
            $evaluator = User::factory()->create(['is_active' => true]);
            $evaluator->assignRole('Internal Examiner');

            Evaluation::create([
                'defence_event_id' => $event->id,
                'evaluator_id' => $evaluator->id,
                'score' => ['total' => $total],
                'recommendation' => 'pass',
                'submitted_at' => now()->subWeeks(2),
            ]);
        }

        return [$student, $thesis, $milestone];
    }

    #[Test]
    public function export_all_results_generates_formatted_xlsx_matching_acetel_template_with_rounded_scores(): void
    {
        // MSc AI in 2024_1: (64 + 65) / 2 = 64.5 => rounded to 65
        $this->createPastSeminarCandidate(
            'ADENEKAN, Abdulmuizz Adewale',
            'ACE24110003',
            $this->aiProgram,
            $this->mscLevel,
            $this->cohort1,
            [64, 65]
        );

        // MSc Cybersecurity in 2024_1: (61 + 62 + 62) / 3 = 61.666 => rounded to 62
        $this->createPastSeminarCandidate(
            'OGUNBIYI, Olabode Babatunde',
            'ACE24120015',
            $this->cyberProgram,
            $this->mscLevel,
            $this->cohort1,
            [61, 62, 62]
        );

        // PhD Cybersecurity in 2024_1: (64 + 67) / 2 = 65.5 => rounded to 66
        $this->createPastSeminarCandidate(
            'ADETIBA, Ekundayo Oluwaseun',
            'ACE24150009',
            $this->cyberProgram,
            $this->phdLevel,
            $this->cohort1,
            [64, 67]
        );

        // PhD MIS in 2024_2 via seminar_grades fallback: 63.6 => rounded to 64
        [, , $misMilestone] = $this->createPastSeminarCandidate(
            'ABODUNDE, Olufikayo',
            'ACE24260001',
            $this->misProgram,
            $this->phdLevel,
            $this->cohort2,
            []
        );
        $examiner = User::factory()->create(['is_active' => true]);
        DB::table('seminar_grades')->insert([
            'student_milestone_id' => $misMilestone->id,
            'examiner_id' => $examiner->id,
            'grade' => 63.60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // PhD AI student should NOT appear in seminar course export (exempt)
        $this->createPastSeminarCandidate(
            'EXEMPT, Phd Ai Candidate',
            'ACE24990001',
            $this->aiProgram,
            $this->phdLevel,
            $this->cohort1,
            [90]
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.past-presentations.export-scores'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString(
            'seminar_course_results.xlsx',
            (string) $response->headers->get('Content-Disposition')
        );

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        file_put_contents($tmpFile, $response->getContent());

        try {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($tmpFile) === true, 'Generated download must be a valid .xlsx zip archive');

            $stylesXml = $zip->getFromName('xl/styles.xml');
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertNotFalse($stylesXml);
            $this->assertNotFalse($sheetXml);
            $this->assertNotFalse($zip->getFromName('[Content_Types].xml'));
            $this->assertNotFalse($zip->getFromName('xl/workbook.xml'));
            $zip->close();

            // Navy blue header fill & integer format must be configured in styles.xml
            $this->assertStringContainsString('FF1F4E78', $stylesXml);

            $xml = new \SimpleXMLElement($sheetXml);
            $xml->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            // Verify merged title & subtitle banner
            $merges = [];
            foreach ($xml->xpath('//s:mergeCell') as $m) {
                $merges[] = (string) $m['ref'];
            }
            $this->assertContains('A2:F2', $merges);
            $this->assertContains('A3:F3', $merges);

            // Extract cell values keyed by cell ref (e.g. A2, A5..F9)
            $cells = [];
            foreach ($xml->xpath('//s:c') as $cell) {
                $ref = (string) $cell['r'];
                if (isset($cell->is->t)) {
                    $cells[$ref] = (string) $cell->is->t;
                } elseif (isset($cell->v)) {
                    $cells[$ref] = (string) $cell->v;
                }
            }

            $this->assertSame(
                'AFRICA CENTRE OF EXCELLENCE ON TECHNOLOGY ENHANCED LEARNING (ACETEL)',
                $cells['A2'] ?? null
            );
            $this->assertSame(
                'SEMINAR COURSE RESULTS FOR SETS 2024_1 AND 2024_2',
                $cells['A3'] ?? null
            );

            // Exact table header columns (Seminar & Presentation Date removed)
            $this->assertSame('S/N', $cells['A5'] ?? null);
            $this->assertSame('Name', $cells['B5'] ?? null);
            $this->assertSame('Program', $cells['C5'] ?? null);
            $this->assertSame('Course Code', $cells['D5'] ?? null);
            $this->assertSame('Matric Number', $cells['E5'] ?? null);
            $this->assertSame('Score', $cells['F5'] ?? null);

            // Only 4 candidates exported (PhD AI excluded) => rows 6..9, no row 10
            $this->assertArrayNotHasKey('A10', $cells);

            // Row 6: MSc AI (ATI810, rounded 65)
            $this->assertSame('1', $cells['A6'] ?? null);
            $this->assertSame('ADENEKAN, Abdulmuizz Adewale', $cells['B6'] ?? null);
            $this->assertSame('MSc Artificial Intelligence', $cells['C6'] ?? null);
            $this->assertSame('ATI810', $cells['D6'] ?? null);
            $this->assertSame('ACE24110003', $cells['E6'] ?? null);
            $this->assertSame('65', $cells['F6'] ?? null);

            // Row 7: MSc Cybersecurity (CST822, rounded 62)
            $this->assertSame('2', $cells['A7'] ?? null);
            $this->assertSame('OGUNBIYI, Olabode Babatunde', $cells['B7'] ?? null);
            $this->assertSame('MSc Cybersecurity', $cells['C7'] ?? null);
            $this->assertSame('CST822', $cells['D7'] ?? null);
            $this->assertSame('ACE24120015', $cells['E7'] ?? null);
            $this->assertSame('62', $cells['F7'] ?? null);

            // Row 8: PhD Cybersecurity (CST922, rounded 66)
            $this->assertSame('3', $cells['A8'] ?? null);
            $this->assertSame('ADETIBA, Ekundayo Oluwaseun', $cells['B8'] ?? null);
            $this->assertSame('PhD Cybersecurity', $cells['C8'] ?? null);
            $this->assertSame('CST922', $cells['D8'] ?? null);
            $this->assertSame('ACE24150009', $cells['E8'] ?? null);
            $this->assertSame('66', $cells['F8'] ?? null);

            // Row 9: PhD MIS (MIS919, rounded 64 from seminar_grades)
            $this->assertSame('4', $cells['A9'] ?? null);
            $this->assertSame('ABODUNDE, Olufikayo', $cells['B9'] ?? null);
            $this->assertSame('PhD Management Information System', $cells['C9'] ?? null);
            $this->assertSame('MIS919', $cells['D9'] ?? null);
            $this->assertSame('ACE24260001', $cells['E9'] ?? null);
            $this->assertSame('64', $cells['F9'] ?? null);

            // Confirm no decimals in any score cell
            foreach (['F6', 'F7', 'F8', 'F9'] as $scoreRef) {
                $this->assertDoesNotMatchRegularExpression('/\./', $cells[$scoreRef]);
            }
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function scheduled_scores_csv_also_rounds_average_scores_to_whole_numbers(): void
    {
        $studentUser = User::factory()->create(['name' => 'Scheduled Rounded Student', 'is_active' => true]);
        $studentUser->assignRole('Student');
        $student = $studentUser->studentProfile()->create([
            'program_id' => $this->cyberProgram->id,
            'cohort_id' => $this->cohort1->id,
            'level_id' => $this->mscLevel->id,
            'student_id_number' => 'ACE24120099',
            'enrollment_status' => 'active',
        ]);

        $thesis = ThesisProject::create([
            'student_profile_id' => $student->id,
            'title' => 'Scheduled Seminar Project',
            'status' => 'active',
            'started_at' => now(),
        ]);
        $thesis->syncMilestones();

        $milestone = $thesis->milestones()
            ->where('milestone_template_id', $this->seminarTemplate->id)
            ->firstOrFail();
        $milestone->update([
            'status' => 'in_progress',
            'defence_date' => now()->addDay()->toDateString(),
        ]);

        $event = DefenceEvent::create([
            'thesis_project_id' => $thesis->id,
            'type' => 'seminar',
            'schedule_start' => now()->addDay(),
            'schedule_end' => now()->addDay()->addHour(),
            'location' => 'https://zoom.us/j/sched',
            'outcome' => 'pending',
        ]);

        foreach ([60, 61] as $score) {
            $examiner = User::factory()->create(['is_active' => true]);
            $examiner->assignRole('Internal Examiner');
            Evaluation::create([
                'defence_event_id' => $event->id,
                'evaluator_id' => $examiner->id,
                'score' => ['total' => $score],
                'recommendation' => 'pass',
                'submitted_at' => now(),
            ]);
        }

        $res = $this->actingAs($this->admin)
            ->get(route('admin.milestone-templates.export-scheduled-scores', $this->seminarTemplate));

        $res->assertOk();
        $csvContent = $res->streamedContent();
        $this->assertStringContainsString('ACE24120099', $csvContent);
        $this->assertStringContainsString(',61', $csvContent);
        $this->assertStringNotContainsString('60.5', $csvContent);
    }
}
