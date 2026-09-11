<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use App\Services\Schedule\ManageSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly ManageSchedule $service,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Schedule::class);

        $query = Schedule::query()
            ->with([
                'academicPeriod',
                'learningGroup',
                'subject',
                'teacher',
                'period',
            ])
            ->orderBy('day_of_week')
            ->orderByRaw(
                '(select number from periods where periods.id = schedules.period_id)'
            );

        if ($request->filled('academic_period')) {
            $query->whereHas(
                'academicPeriod',
                fn ($q) => $q->where(
                    'public_id',
                    $request->input('academic_period')
                )
            );
        } else {
            $query->whereHas(
                'academicPeriod',
                fn ($q) => $q->where('is_active', true)
            );
        }

        foreach ([
            'learningGroup' => 'learning_group',
            'teacher' => 'teacher',
            'subject' => 'subject',
            'period' => 'period',
        ] as $relation => $field) {
            if ($request->filled($field)) {
                $query->whereHas(
                    $relation,
                    fn ($q) => $q->where(
                        'public_id',
                        $request->input($field)
                    )
                );
            }
        }

        if ($request->filled('day_of_week')) {
            $query->where(
                'day_of_week',
                (int) $request->input('day_of_week')
            );
        }

        if ($request->has('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        return ScheduleResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function show(Schedule $schedule): ScheduleResource
    {
        Gate::authorize('view', $schedule);

        return new ScheduleResource(
            $schedule->load([
                'academicPeriod',
                'learningGroup',
                'subject',
                'teacher',
                'period',
            ])
        );
    }

    public function store(StoreScheduleRequest $request): ScheduleResource
    {
        Gate::authorize('create', Schedule::class);

        return new ScheduleResource(
            $this->service->create($request->validated())
                ->load([
                    'academicPeriod',
                    'learningGroup',
                    'subject',
                    'teacher',
                    'period',
                ])
        );
    }

    public function update(
        UpdateScheduleRequest $request,
        Schedule $schedule,
    ): ScheduleResource {
        Gate::authorize('update', $schedule);

        return new ScheduleResource(
            $this->service->update(
                $schedule,
                $request->validated(),
            )->load([
                'academicPeriod',
                'learningGroup',
                'subject',
                'teacher',
                'period',
            ])
        );
    }

    public function destroy(Schedule $schedule)
    {
        Gate::authorize('delete', $schedule);

        $this->service->delete($schedule);

        return response()->noContent();
    }
}
