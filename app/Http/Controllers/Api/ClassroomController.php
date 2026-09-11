<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Http\Resources\ClassroomResource;
use App\Models\Classroom;
use App\Services\Classroom\ManageClassroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClassroomController extends Controller
{
    public function __construct(
        private readonly ManageClassroom $service,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Classroom::class);

        $query = Classroom::query()
            ->orderBy('grade')
            ->orderBy('name');

        if (! $request->user()->hasRole('admin')) {
            $query->where('is_active', true);
        }

        if (
            $request->user()->hasRole('admin')
            && $request->has('is_active')
        ) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return ClassroomResource::collection($query->get());
    }

    public function show(Classroom $classroom)
    {
        Gate::authorize('view', $classroom);

        return new ClassroomResource($classroom);
    }

    public function store(StoreClassroomRequest $request)
    {
        Gate::authorize('create', Classroom::class);

        return new ClassroomResource(
            $this->service->create($request->validated())
        );
    }

    public function update(
        UpdateClassroomRequest $request,
        Classroom $classroom,
    ) {
        Gate::authorize('update', $classroom);

        return new ClassroomResource(
            $this->service->update(
                $classroom,
                $request->validated(),
            )
        );
    }
}
