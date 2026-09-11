<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLearningGroupRequest;
use App\Http\Requests\UpdateLearningGroupRequest;
use App\Http\Resources\LearningGroupResource;
use App\Http\Resources\LearningGroupStudentResource;
use App\Models\LearningGroup;
use App\Services\LearningGroup\ManageLearningGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LearningGroupController extends Controller
{
    public function __construct(
        private readonly ManageLearningGroup $service,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', LearningGroup::class);

        $query = LearningGroup::query()
            ->with('academicPeriod')
            ->orderBy('name');

        if ($request->filled('academic_period')) {
            $query->whereHas(
                'academicPeriod',
                fn ($academicPeriodQuery) => $academicPeriodQuery->where(
                    'public_id',
                    $request->input('academic_period'),
                ),
            );
        }

        if ($request->has('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active'),
            );
        }

        return LearningGroupResource::collection($query->get());
    }

    public function show(LearningGroup $learningGroup): LearningGroupResource
    {
        Gate::authorize('view', $learningGroup);

        return new LearningGroupResource(
            $learningGroup->load('academicPeriod'),
        );
    }

    public function store(
        StoreLearningGroupRequest $request,
    ): LearningGroupResource {
        $learningGroup = $this->service->create($request->validated());

        return new LearningGroupResource(
            $learningGroup->load('academicPeriod'),
        );
    }

    public function update(
        UpdateLearningGroupRequest $request,
        LearningGroup $learningGroup,
    ): LearningGroupResource {
        $learningGroup = $this->service->update(
            $learningGroup,
            $request->validated(),
        );

        return new LearningGroupResource(
            $learningGroup->load('academicPeriod'),
        );
    }

    public function memberships(
        Request $request,
        LearningGroup $learningGroup,
    ) {
        Gate::authorize('view', $learningGroup);

        $query = $learningGroup
            ->memberships()
            ->with(['learningGroup', 'student'])
            ->orderByDesc('starts_at')
            ->orderByDesc('id');

        return LearningGroupStudentResource::collection(
            $query->paginate($request->integer('per_page', 15)),
        );
    }

    public function destroy(LearningGroup $learningGroup)
    {
        Gate::authorize('delete', $learningGroup);

        $this->service->delete($learningGroup);

        return response()->noContent();
    }
}
