<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicPeriodRequest;
use App\Http\Requests\UpdateAcademicPeriodRequest;
use App\Http\Resources\AcademicPeriodResource;
use App\Models\AcademicPeriod;
use App\Services\AcademicPeriod\ManageAcademicPeriod;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AcademicPeriodController extends Controller
{
    public function __construct(
        private readonly ManageAcademicPeriod $manageAcademicPeriod,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
//        return AcademicPeriodResource::collection(
//            AcademicPeriod::query()
//                ->orderByDesc('starts_at')
//                ->get()
//        );
        return AcademicPeriodResource::collection(
            AcademicPeriod::query()->get()
        );
    }

    public function show(AcademicPeriod $academicPeriod): AcademicPeriodResource
    {
        return new AcademicPeriodResource($academicPeriod);
    }

    public function store(
        StoreAcademicPeriodRequest $request,
    ): AcademicPeriodResource {
        $academicPeriod = $this->manageAcademicPeriod->create(
            $request->validated()
        );

        return new AcademicPeriodResource($academicPeriod);
    }

    public function update(
        UpdateAcademicPeriodRequest $request,
        AcademicPeriod $academicPeriod,
    ): AcademicPeriodResource {
        $academicPeriod = $this->manageAcademicPeriod->update(
            $academicPeriod,
            $request->validated()
        );

        return new AcademicPeriodResource($academicPeriod);
    }
}
