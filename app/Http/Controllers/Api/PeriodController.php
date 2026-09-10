<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeriodRequest;
use App\Http\Requests\UpdatePeriodRequest;
use App\Http\Resources\PeriodResource;
use App\Models\Period;
use App\Services\Period\ManagePeriod;
use Illuminate\Http\Request;

class PeriodController extends Controller
{
    public function __construct(
        private readonly ManagePeriod $service
    ) {}

    public function index(Request $request)
    {
        abort_unless(
            $request->user()->can('viewAny', Period::class),
            403
        );

        return PeriodResource::collection(
            Period::orderBy('number')->get()
        );
    }

    public function show(Request $request, Period $period)
    {
        abort_unless(
            $request->user()->can('view', $period),
            403
        );

        return new PeriodResource($period);
    }

    public function store(StorePeriodRequest $request)
    {
        return new PeriodResource(
            $this->service->create($request->validated())
        );
    }

    public function update(
        UpdatePeriodRequest $request,
        Period $period
    ) {
        return new PeriodResource(
            $this->service->update(
                $period,
                $request->validated()
            )
        );
    }
}
