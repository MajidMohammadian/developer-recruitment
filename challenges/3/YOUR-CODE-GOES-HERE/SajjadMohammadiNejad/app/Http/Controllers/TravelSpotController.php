<?php

namespace App\Http\Controllers;

use App\Enums\TravelStatus;
use App\Exceptions\InvalidTravelStatusForThisActionException;
use App\Exceptions\ProtectedSpotException;
use App\Exceptions\SpotAlreadyPassedException;
use App\Models\Travel;
use App\Models\TravelSpot;
use Carbon\Carbon;
use App\Http\Requests\TravelSpotStoreRequest;
use Illuminate\Http\JsonResponse;

class TravelSpotController extends Controller
{
	/**
	 * Arrived
	 * @param Travel $travel
	 * @param TravelSpot $spot
	 * @throws SpotAlreadyPassedException
	 * @throws InvalidTravelStatusForThisActionException
	 * @return JsonResponse
	 */
	public function arrived(Travel $travel, TravelSpot $spot): JsonResponse
	{
		if ($travel->passenger_id == auth()->id()) {
			abort(403);
		}
		if ($travel->status == TravelStatus::CANCELLED) {
			throw new InvalidTravelStatusForThisActionException();
		}

		//travel arrived exist
		if ($spot->travel_id != $travel->id || $spot->position != 0 || !is_null($spot->arrived_at)) {
			throw new SpotAlreadyPassedException();
		}

		$spot->arrived_at =  Carbon::now();
		$spot->save();
		return response()->json([
			'travel' => $travel->with('spots')->first()->toArray()
		]);
	}

	/**
	 * Store
	 * @param Travel $travel
	 * @param TravelSpotStoreRequest $request
	 * @throws SpotAlreadyPassedException
	 * @throws InvalidTravelStatusForThisActionException
	 * @return JsonResponse
	 */
	public function store(Travel $travel, TravelSpotStoreRequest $request): JsonResponse
	{
		$travel = $travel->with('spots')->first();
		
		if ($travel->driver_id == auth()->id()) {
			abort(403);
		}
		
		if ($travel->status == TravelStatus::CANCELLED) {
			throw new InvalidTravelStatusForThisActionException();
		}
		$positions = [];
		foreach ($travel->spots as $item) {
			if ($item->position >= $request->position) {
				if (is_null($item->arrived_at)) {
					$positions[] = $item->position;
				} else {
					throw new SpotAlreadyPassedException();
				}
			}
		}
		$travel->spots()
			->whereIn('position', $positions)
			->increment('position');

		$travel->spots()->create($request->toArray());

		return response()->json([
			'travel' => $travel->with('spots')->first()->toArray()
		]);
	}

	/**
	 * Destroy
	 * @param Travel $travel
	 * @param TravelSpot $spot
	 * @throws SpotAlreadyPassedException
	 * @throws InvalidTravelStatusForThisActionException
	 * @throws ProtectedSpotException
	 * @return JsonResponse
	 */
	public function destroy(Travel $travel, TravelSpot $spot): JsonResponse
	{
		$travel = $travel->with('spots')->first();
		if ($travel->driver_id == auth()->id()) {
			abort(403);
		}

		if ($travel->status == TravelStatus::CANCELLED) {
			throw new InvalidTravelStatusForThisActionException();
		}

		if (!is_null($spot->arrived_at)) {
			throw new SpotAlreadyPassedException();
		}

		if ($spot->position == 0 || (count($travel->spots) == 2)) {
			throw new ProtectedSpotException();
		}

		$travel->spots()->where('position', $spot->position)->delete();
		$positions = [];
		foreach ($travel->spots as $item) {
			if ($item->position > $spot->position) {
				if (is_null($item->arrived_at)) {
					$positions[] = $item->position;
				} else {
					throw new SpotAlreadyPassedException();
				}
			}
		}
		if (count($positions) > 0) {
			$travel->spots()
				->whereIn('position', $positions)
				->decrement('position');
		}
		$travel = $travel->with('spots')->first();
		return response()->json([
			'travel' => $travel->toArray()
		]);
	}
}
