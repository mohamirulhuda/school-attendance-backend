<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\Student\ManageStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StudentController extends Controller
{
    public function __construct(
        private readonly ManageStudent $service,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Student::class);

        $query = Student::query()->orderBy('id');

        if (! $request->user()->hasRole('admin')) {
            $query->where('is_active', true);
        } elseif ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('name')) {
            $query->where(
                'name',
                'like',
                '%'.$request->input('name').'%'
            );
        }

        if ($request->filled('classroom')) {
            $classroomPublicId = $request->input('classroom');

            $query->whereHas('enrollments', function ($enrollmentQuery) use ($classroomPublicId) {
                $enrollmentQuery
                    ->whereHas('classroom', fn ($classroomQuery) => $classroomQuery->where('public_id', $classroomPublicId))
                    ->whereDate('starts_at', '<=', today())
                    ->where(function ($dateQuery) {
                        $dateQuery
                            ->whereNull('ends_at')
                            ->orWhereDate('ends_at', '>', today());
                    })
                    ->whereHas('academicPeriod', fn ($academicPeriodQuery) => $academicPeriodQuery->where('is_active', true));
            });
        }

        if ($request->filled('learning_group')) {
            $learningGroupPublicId = $request->input('learning_group');

            $query->whereHas('learningGroupMemberships', function ($membershipQuery) use ($learningGroupPublicId) {
                $membershipQuery
                    ->whereHas('learningGroup', function ($learningGroupQuery) use ($learningGroupPublicId) {
                        $learningGroupQuery
                            ->where('public_id', $learningGroupPublicId)
                            ->where('is_active', true)
                            ->whereHas('academicPeriod', fn ($academicPeriodQuery) => $academicPeriodQuery->where('is_active', true));
                    })
                    ->whereDate('starts_at', '<=', today())
                    ->where(function ($dateQuery) {
                        $dateQuery
                            ->whereNull('ends_at')
                            ->orWhereDate('ends_at', '>', today());
                    });
            });
        }

        if ($request->input('sort') === 'name') {
            $query->reorder('name');
        }

        return StudentResource::collection($query->paginate(15));
    }

    public function show(Student $student): StudentResource
    {
        Gate::authorize('view', $student);

        return new StudentResource($student);
    }

    public function store(StoreStudentRequest $request): StudentResource
    {
        Gate::authorize('create', Student::class);

        return new StudentResource(
            $this->service->create($request->validated())
        );
    }

    public function update(
        UpdateStudentRequest $request,
        Student $student,
    ): StudentResource {
        Gate::authorize('update', $student);

        return new StudentResource(
            $this->service->update(
                $student,
                $request->validated(),
            )
        );
    }
}
