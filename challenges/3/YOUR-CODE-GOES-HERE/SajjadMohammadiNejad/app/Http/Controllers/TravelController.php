<?php

namespace App\Http\Controllers;

use App\Enums\TravelEventType;
use App\Enums\TravelStatus;
use App\Exceptions\ActiveTravelException;
use App\Exceptions\AllSpotsDidNotPassException;
use App\Exceptions\CannotCancelFinishedTravelException;
use App\Exceptions\CannotCancelRunningTravelException;
use App\Exceptions\CarDoesNotArrivedAtOriginException;
use App\Exceptions\InvalidTravelStatusForThisActionException;
use App\Http\Requests\TravelStoreRequest;
use App\Models\Travel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TravelController extends Controller
{

	/**
	 * View
	 * @param Travel $travel
	 * @return JsonResponse
	 */
	public function view(Travel $travel): JsonResponse
	{
		return response()->json([
			'travel' => [
				'id' => $travel->id
			]
		]);
	}

	/**
	 * Store Travel
	 * @param TravelStoreRequest $request
	 * @return JsonResponse
	 * @throws ActiveTravelException
	 */
	public function store(TravelStoreRequest $request): JsonResponse
	{
		DB::beginTransaction();

		$exist_active_travel = Travel::hasActive(auth()->user()->id)->exists();
		if ($exist_active_travel) {
			throw new ActiveTravelException();
		}
		$travel = new Travel;

		$travel->passenger_id = auth()->id();
		$travel->status = TravelStatus::SEARCHING_FOR_DRIVER->value;
		if ($travel->save()) {
			foreach ($request->spots as $item) {
				if (!$travel->spots()->create([
					'position'  => $item['position'],
					'latitude'  => $item['latitude'],
					'longitude' => $item['longitude'],
				])) {
					DB::rollBack();
					return response()->json([
						'code' =>  "ServerError"
					], 400);
				}
			}
			DB::commit();
			return response()->json([
				'travel' => [
					'spots'        => $request->spots,
					'passenger_id' => auth()->id(),
					'status'       => TravelStatus::SEARCHING_FOR_DRIVER->value
				]
			], 201);
		}
		DB::rollBack();
		return response()->json([
			'code' =>  "ServerError"
		], 400);
	}

	/**
	 * Cancel Travel
	 * @param Travel $travel
	 * @return JsonResponse
	 * @throws CannotCancelFinishedTravelException
	 * @throws CannotCancelRunningTravelException
	 */
	public function cancel(Travel $travel): JsonResponse
	{
		if (in_array($travel->status, [
			TravelStatus::CANCELLED,
			TravelStatus::DONE
		])) {
			throw new CannotCancelFinishedTravelException();
		} else {
			if ($travel->status == TravelStatus::RUNNING) {
				if ($travel->passengerIsInCar() || ($travel->passenger_id == auth()->id())) {
					throw new CannotCancelRunningTravelException();
				}
			}
			$travel->status = TravelStatus::CANCELLED->value;
			if ($travel->save()) {
				return response()->json([
					'travel' => $travel
				]);
			}
			return response()->json([
				'code' =>  "ServerError"
			], 400);
		}
	}

	/**
	 * Passenger On Board Travel
	 * @param Travel $travel
	 * @return JsonResponse
	 * @throws InvalidTravelStatusForThisActionException
	 * @throws CarDoesNotArrivedAtOriginException
	 */
	public function passengerOnBoard(Travel $travel): JsonResponse
	{
		$travel = $travel->with('events')->first();

		if ($travel->passenger_id == auth()->id()) {
			abort(403);
		}

		if (!$travel->driverHasArrivedToOrigin()) {
			throw new CarDoesNotArrivedAtOriginException();
		}

		$found = false;
		foreach ($travel->events as $e) {
			if ($e->type == TravelEventType::PASSENGER_ONBOARD) {
				$found = true;
				break;
			}
		}

		if ($found || ($travel->status == TravelStatus::DONE)) {
			throw new InvalidTravelStatusForThisActionException();
		}
		if ($travel->events()->create([
			'type' => TravelEventType::PASSENGER_ONBOARD->value
		])) {
			return response()->json([
				'travel' => $travel->with('events')->first()->toArray()
			]);
		}
		return response()->json([
			'code' =>  "ServerError"
		], 400);
	}

	/**
	 * Done Travel
	 * @param Travel $travel
	 * @return JsonResponse
	 * @throws InvalidTravelStatusForThisActionException
	 * @throws AllSpotsDidNotPassException
	 */
	public function done(Travel $travel): JsonResponse
	{
		DB::beginTransaction();
		$travel = $travel->with('events')->first();

		if ($travel->passenger_id == auth()->id()) {
			abort(403);
		}

		if ($travel->status == TravelStatus::DONE) {
			throw new InvalidTravelStatusForThisActionException();
		}

		if ($travel->allSpotsPassed()) {
			if ($travel->passengerIsInCar()) {
				$travel->status = TravelStatus::DONE->value;
				$travel->save();

				$travel->events()->create([
					'type' => TravelEventType::DONE->value
				]);

				$travel = $travel->with('events')->first();
				DB::commit();
				return response()->json([
					'travel' => $travel->toArray()
				]);
			} else {
				DB::rollBack();
				throw new AllSpotsDidNotPassException();
			}
		} else {
			DB::rollBack();
			throw new AllSpotsDidNotPassException();
		}
	}

	/**
	 * Take Travel
	 * @param Travel $travel
	 * @return JsonResponse
	 * @throws InvalidTravelStatusForThisActionException
	 * @throws ActiveTravelException
	 */
	public function take(Travel $travel): JsonResponse
	{
		if (Travel::hasActive(auth()->user()->id)->exists()) {
			throw new ActiveTravelException();
		}
		if ($travel->status == TravelStatus::CANCELLED) {
			throw new InvalidTravelStatusForThisActionException();
		}
		$travel->driver_id = auth()->id();
		$travel->save();
		$travel->events()->create([
			'type' => TravelEventType::ACCEPT_BY_DRIVER->value
		]);
		return response()->json([
			'travel' => collect($travel->toArray())->filter(function () {
				return [
					'id', 'driver_id', 'status'
				];
			})
		]);
	}
}
