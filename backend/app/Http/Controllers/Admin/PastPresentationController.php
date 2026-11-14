<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentMilestone;
use App\Models\StudentProfile;
use App\Support\StyledXlsxBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PastPresentationController extends Controller
{
    public function index()
    {
        $milestones = StudentMilestone::where('status', 'approved')
            ->whereNotNull('defence_date')
            ->whereHas('template', function($q) {
                $q->where('slug', 'seminar_as_a_course');
            })
            ->whereDoesntHave('thesis.student.program', fn($pq) => $pq->phdAi())
            ->with(['thesis.student.user', 'thesis.student.program', 'thesis.student.level', 'template', 'thesis.defenceEvents.evaluations'])
            ->orderBy('defence_date', 'desc')
            ->paginate(20);

        return view('admin.past-presentations.index', compact('milestones'));
    }

    public function exportScores()
    {
        $milestones = StudentMilestone::where('status', 'approved')
            ->whereNotNull('defence_date')
            ->whereHas('template', function($q) {
                $q->where('slug', 'seminar_as_a_course');
            })
            ->with([
                'thesis.student.user',
                'thesis.student.program',
                'thesis.student.level',
                'thesis.student.cohort',
                'template',
                'thesis.defenceEvents.evaluations',
            ])
            ->get()
            ->reject(fn($sm) => $sm->thesis && $sm->template && $sm->thesis->isExemptFromMilestone($sm->template))
            ->sortBy(function ($sm) {
                $student = $sm->thesis?->student;
                $cohortKey = $student?->cohort?->code ?: ($student?->cohort?->name ?: 'ZZZZ');
                $resolved = self::resolveProgramAndCourseCode($student);
                $matric = $student?->student_id_number ?: 'ZZZZ';
                $name = $student?->user?->name ?: 'ZZZZ';

                return sprintf('%s|%s|%s|%s', $cohortKey, $resolved['course_code'], $matric, $name);
            })
            ->values();

        $sets = $milestones
            ->map(fn($sm) => trim((string) ($sm->thesis?->student?->cohort?->name ?: $sm->thesis?->student?->cohort?->code ?: '')))
            ->filter()
            ->unique()
            ->values();

        $subtitle = match ($sets->count()) {
            0 => 'SEMINAR COURSE RESULTS',
            1 => 'SEMINAR COURSE RESULTS FOR SET ' . strtoupper($sets->first()),
            2 => 'SEMINAR COURSE RESULTS FOR SETS ' . strtoupper($sets->get(0)) . ' AND ' . strtoupper($sets->get(1)),
            default => 'SEMINAR COURSE RESULTS FOR SETS '
                . strtoupper($sets->slice(0, -1)->implode(', '))
                . ' AND '
                . strtoupper($sets->last()),
        };

        $columns = [
            ['label' => 'S/N', 'width' => 8, 'align' => 'center', 'type' => 'int'],
            ['label' => 'Name', 'width' => 38, 'align' => 'left', 'type' => 'string'],
            ['label' => 'Program', 'width' => 36, 'align' => 'left', 'type' => 'string'],
            ['label' => 'Course Code', 'width' => 16, 'align' => 'center', 'type' => 'string'],
            ['label' => 'Matric Number', 'width' => 18, 'align' => 'center', 'type' => 'string'],
            ['label' => 'Score', 'width' => 12, 'align' => 'right', 'type' => 'int'],
        ];

        $rows = [];
        foreach ($milestones as $idx => $sm) {
            $student = $sm->thesis?->student;
            $resolved = self::resolveProgramAndCourseCode($student);

            $rows[] = [
                $idx + 1,
                $student?->user?->name ?? 'Unknown',
                $resolved['program'],
                $resolved['course_code'],
                $student?->student_id_number ?? 'N/A',
                self::resolveRoundedSeminarScore($sm),
            ];
        }

        $binary = StyledXlsxBuilder::buildReport(
            'Seminar Course Results',
            'AFRICA CENTRE OF EXCELLENCE ON TECHNOLOGY ENHANCED LEARNING (ACETEL)',
            $subtitle,
            $columns,
            $rows
        );

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="seminar_course_results.xlsx"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Resolve display program name (e.g. "MSc Artificial Intelligence") and ACETEL course code.
     *
     * @return array{program: string, course_code: string}
     */
    public static function resolveProgramAndCourseCode(?StudentProfile $student): array
    {
        if (!$student) {
            return ['program' => 'N/A', 'course_code' => 'N/A'];
        }

        $isPhd = $student->isPhd();
        $degreeLabel = $isPhd
            ? 'PhD'
            : ($student->isMsc() ? 'MSc' : trim((string) ($student->level?->name ?? $student->program?->degree_type ?? '')));

        $rawProgram = trim((string) ($student->program?->name ?? ''));
        if ($rawProgram === '' || strtoupper($rawProgram) === 'MIS') {
            $programTitle = $rawProgram !== '' && strtoupper($rawProgram) === 'MIS'
                ? 'Management Information System'
                : ($student->discipline_group_label !== 'General' ? $student->discipline_group_label : 'General');
        } else {
            $programTitle = $rawProgram;
        }

        $hasDegreePrefix = (bool) preg_match('/^(msc|m\.sc|phd|ph\.d|master|doctor)\b/i', $programTitle);
        $programDisplay = (!$hasDegreePrefix && $degreeLabel !== '')
            ? "{$degreeLabel} {$programTitle}"
            : $programTitle;

        $courseCode = match ($student->discipline_group) {
            'artificial_intelligence' => $isPhd ? 'ATI910' : 'ATI810',
            'cybersecurity' => $isPhd ? 'CST922' : 'CST822',
            'management_information_systems' => $isPhd ? 'MIS919' : 'MIS819',
            default => trim((string) ($student->program?->code ?? '')) ?: 'N/A',
        };

        return [
            'program' => $programDisplay,
            'course_code' => $courseCode,
        ];
    }

    /**
     * Compute the candidate's seminar score rounded to the nearest whole number.
     */
    public static function resolveRoundedSeminarScore(StudentMilestone $milestone): int|string
    {
        $eventType = $milestone->template?->defence_type
            ?? ($milestone->template?->slug === 'seminar_as_a_course' ? 'seminar' : 'first_seminar');

        $event = $milestone->thesis?->defenceEvents?->where('type', $eventType)->first()
            ?? $milestone->thesis?->defenceEvents?->first();

        if ($event && $event->evaluations->count() > 0) {
            $total = 0.0;
            $count = 0;
            foreach ($event->evaluations as $eval) {
                if (isset($eval->score['total']) && is_numeric($eval->score['total'])) {
                    $total += (float) $eval->score['total'];
                    $count++;
                }
            }
            if ($count > 0) {
                return (int) round($total / $count);
            }
        }

        if (Schema::hasTable('seminar_grades')) {
            $avgGrade = DB::table('seminar_grades')
                ->where('student_milestone_id', $milestone->id)
                ->avg('grade');
            if ($avgGrade !== null) {
                return (int) round((float) $avgGrade);
            }
        }

        return 'N/A';
    }

    public function exportAttendance()
    {
        $milestones = StudentMilestone::where('status', 'approved')
            ->whereNotNull('defence_date')
            ->whereHas('template', function($q) {
                $q->where('slug', 'seminar_as_a_course');
            })
            ->with(['thesis.defenceEvents.evaluations.evaluator'])
            ->get();

        $events = $milestones->pluck('thesis.defenceEvents')->flatten();

        $attendanceCount = [];
        foreach ($events as $event) {
            $evals = $event->evaluations;
            $uniqueEvaluators = $evals->pluck('evaluator_id')->unique();
            $dateKey = \Carbon\Carbon::parse($event->schedule_start)->format('Y-m-d');

            foreach ($uniqueEvaluators as $evalId) {
                if (!isset($attendanceCount[$evalId])) {
                    $attendanceCount[$evalId] = [];
                }
                $attendanceCount[$evalId][$dateKey] = true;
            }
        }

        $fileName = 'past_examiner_attendance.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['Examiner Name', 'Total Days Present'];

        $callback = function() use($attendanceCount, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $users = \App\Models\User::whereIn('id', array_keys($attendanceCount))->get()->keyBy('id');

            foreach ($attendanceCount as $evalId => $dates) {
                $daysPresent = count($dates);
                $name = $users->get($evalId)?->name ?? 'Unknown User';
                fputcsv($file, [$name, $daysPresent]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
