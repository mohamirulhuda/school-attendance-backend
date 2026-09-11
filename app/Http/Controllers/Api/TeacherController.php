<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use App\Services\Teacher\ManageTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeacherController extends Controller
{
    public function __construct(
        private readonly ManageTeacher $service,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Teacher::class);

        $query = Teacher::query()
            ->orderBy('id');

        if (! $request->user()->hasRole('admin')) {
            $query->where('is_active', true);
        } elseif ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        foreach (['name', 'nickname', 'email'] as $field) {
            if ($request->filled($field)) {
                $query->where(
                    $field,
                    'like',
                    '%'.$request->input($field).'%'
                );
            }
        }

        return TeacherResource::collection($query->paginate(15));
    }

    public function show(Teacher $teacher): TeacherResource
    {
        Gate::authorize('view', $teacher);

        return new TeacherResource($teacher);
    }

    public function store(StoreTeacherRequest $request): TeacherResource
    {
        Gate::authorize('create', Teacher::class);

        return new TeacherResource(
            $this->service->create($request->validated())
        );
    }

    public function update(
        UpdateTeacherRequest $request,
        Teacher $teacher,
    ): TeacherResource {
        Gate::authorize('update', $teacher);

        return new TeacherResource(
            $this->service->update(
                $teacher,
                $request->validated(),
            )
        );
    }
}
