<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Services\Enrollment\ManageEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EnrollmentController extends Controller
{
    public function __construct(private readonly ManageEnrollment $service) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Enrollment::class);

        $query = Enrollment::query()
            ->with(['academicPeriod', 'student', 'classroom'])
            ->orderByDesc('starts_at')
            ->orderByDesc('id');

        if ($request->filled('academic_period')) {
            $query->whereHas(
                'academicPeriod',
                fn ($relation) => $relation->where(
                    'public_id',
                    $request->input('academic_period'),
                ),
            );
        } else {
            $query->whereHas(
                'academicPeriod',
                fn ($relation) => $relation->where('is_active', true),
            );
        }

        if ($request->filled('student')) {
            $query->whereHas(
                'student',
                fn ($relation) => $relation->where(
                    'public_id',
                    $request->input('student'),
                ),
            );
        }

        if ($request->filled('classroom')) {
            $query->whereHas(
                'classroom',
                fn ($relation) => $relation->where(
                    'public_id',
                    $request->input('classroom'),
                ),
            );
        }

        if ($request->filled('active_at')) {
            $date = $request->date('active_at');

            $query
                ->whereDate('starts_at', '<=', $date)
                ->where(
                    fn ($relation) => $relation
                        ->whereNull('ends_at')
                        ->orWhereDate('ends_at', '>', $date),
                );
        }

        return EnrollmentResource::collection(
            $query->paginate($request->integer('per_page', 15)),
        );
    }

    public function show(Enrollment $enrollment)
    {
        Gate::authorize('view', $enrollment);

        return new EnrollmentResource(
            $enrollment->load(['academicPeriod', 'student', 'classroom']),
        );
    }

    public function store(StoreEnrollmentRequest $request)
    {
        $enrollment = $this->service
            ->create($request->validated())
            ->load(['academicPeriod', 'student', 'classroom']);

        return new EnrollmentResource($enrollment);
    }

    public function update(
        UpdateEnrollmentRequest $request,
        Enrollment $enrollment,
    ) {
        $enrollment = $this->service
            ->update($enrollment, $request->validated())
            ->load(['academicPeriod', 'student', 'classroom']);

        return new EnrollmentResource($enrollment);
    }
}
