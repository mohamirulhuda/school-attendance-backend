<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\Subject\ManageSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubjectController extends Controller
{
    public function __construct(
        private readonly ManageSubject $service,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Subject::class);

        $query = Subject::query()
            ->orderBy('id');

        if (! $request->user()->hasRole('admin')) {
            $query->where('is_active', true);
        } elseif ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        foreach (['name', 'code'] as $field) {
            if ($request->filled($field)) {
                $query->where(
                    $field,
                    'like',
                    '%'.$request->input($field).'%'
                );
            }
        }

        return SubjectResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function show(Subject $subject): SubjectResource
    {
        Gate::authorize('view', $subject);

        return new SubjectResource($subject);
    }

    public function store(StoreSubjectRequest $request): SubjectResource
    {
        Gate::authorize('create', Subject::class);

        return new SubjectResource(
            $this->service->create($request->validated())
        );
    }

    public function update(
        UpdateSubjectRequest $request,
        Subject $subject,
    ): SubjectResource {
        Gate::authorize('update', $subject);

        return new SubjectResource(
            $this->service->update(
                $subject,
                $request->validated(),
            )
        );
    }

    public function destroy(Subject $subject)
    {
        Gate::authorize('delete', $subject);

        $this->service->delete($subject);

        return response()->noContent();
    }
}
