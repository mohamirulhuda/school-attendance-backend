<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLearningGroupStudentRequest;
use App\Http\Requests\UpdateLearningGroupStudentRequest;
use App\Http\Resources\LearningGroupStudentResource;
use App\Models\LearningGroupStudent;
use App\Services\LearningGroupStudent\ManageLearningGroupStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LearningGroupStudentController extends Controller
{
    public function __construct(
        private readonly ManageLearningGroupStudent $service,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', LearningGroupStudent::class);

        $query = LearningGroupStudent::query()
            ->with(['learningGroup', 'student'])
            ->orderByDesc('starts_at')
            ->orderByDesc('id');

        if ($request->filled('learning_group')) {
            $query->whereHas(
                'learningGroup',
                fn ($learningGroupQuery) => $learningGroupQuery->where(
                    'public_id',
                    $request->input('learning_group'),
                ),
            );
        }

        if ($request->filled('student')) {
            $query->whereHas(
                'student',
                fn ($studentQuery) => $studentQuery->where(
                    'public_id',
                    $request->input('student'),
                ),
            );
        }

        if ($request->filled('active_at')) {
            $date = $request->date('active_at');

            $query
                ->whereDate('starts_at', '<=', $date)
                ->where(function ($dateQuery) use ($date) {
                    $dateQuery
                        ->whereNull('ends_at')
                        ->orWhereDate('ends_at', '>', $date);
                });
        }

        return LearningGroupStudentResource::collection(
            $query->paginate($request->integer('per_page', 15)),
        );
    }

    public function show(
        LearningGroupStudent $learningGroupStudent,
    ): LearningGroupStudentResource {
        Gate::authorize('view', $learningGroupStudent);

        return new LearningGroupStudentResource(
            $learningGroupStudent->load(['learningGroup', 'student']),
        );
    }

    public function store(
        StoreLearningGroupStudentRequest $request,
    ): LearningGroupStudentResource {
        return new LearningGroupStudentResource(
            $this->service
                ->create($request->validated())
                ->load(['learningGroup', 'student']),
        );
    }

    public function update(
        UpdateLearningGroupStudentRequest $request,
        LearningGroupStudent $learningGroupStudent,
    ): LearningGroupStudentResource {
        return new LearningGroupStudentResource(
            $this->service
                ->update($learningGroupStudent, $request->validated())
                ->load(['learningGroup', 'student']),
        );
    }

    public function destroy(
        LearningGroupStudent $learningGroupStudent,
    ) {
        Gate::authorize('delete', $learningGroupStudent);

        $this->service->delete($learningGroupStudent);

        return response()->noContent();
    }
}
