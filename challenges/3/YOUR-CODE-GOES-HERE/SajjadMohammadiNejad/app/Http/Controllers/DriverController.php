<?php

namespace App\Http\Controllers;

use App\Enums\DriverStatus;
use App\Enums\TravelStatus;
use App\Http\Requests\DriverSignupRequest;
use App\Http\Requests\DriverUpdateRequest;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Models\Travel;

class DriverController extends Controller
{
	/**
	 * Driver signup
	 * @param DriverSignupRequest $request
	 * @return DriverResource
	 */
	public function signup(DriverSignupRequest $request)
	{
		$driver_exist = Driver::query()->where([
			'car_plate' => $request->car_plate,
			'car_model' => $request->car_model,
		])->exists();

		if ($driver_exist) {
			//if driver exist
			return response()->json([
				"code" => "AlreadyDriver"
			], 400);
		}
		$driver = new Driver;
		$driver->id = auth()->id();
		$driver->car_plate = $request->car_plate;
		$driver->car_model = $request->car_model;
		$driver->status = DriverStatus::NOT_WORKING->value;
		if ($driver->save())
			return response()->json(DriverResource::make($driver));
		return response()->json([
			"code" => "DriverNotUpdate"
		], 400);
	}

	/**
	 * Driver signup
	 * @param DriverUpdateRequest $request
	 * @return json
	 */
	public function update(DriverUpdateRequest $request)
	{
		$driver = Driver::findOrFail(auth()->user()->id);
		if (!$driver) {
			//if driver not found
			return response()->json([
				'code' => 'DriverNotFound'
			], 400);
		}
		if ($request->has('latitude') and $request->has('longitude')) {
			$driver->latitude = $request->latitude;
			$driver->longitude = $request->longitude;
		}
		$driver->status = $request->status;
		if ($driver->save()) {
			$travels = Travel::with('spots')
				->where('status', TravelStatus::SEARCHING_FOR_DRIVER->value)
				->get();

			return response()->json([
				'driver'  => $request->all(),
				'travels' => $travels
			]);
		}
		return response()->json([
			'code' => 'DriverNotUpdate'
		], 400);
	}
}
