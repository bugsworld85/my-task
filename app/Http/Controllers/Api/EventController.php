<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Events;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
  /**
   * GET /api/events
   * Get all scheduled events
   * @param month string - the target month that you would query
   */
  public function index(Request $req)
  {
    try {
      $month = $req->input('month');

      if ($month != null) { // if month parameter is given validate it.
        $validation = Validator::make($req->all(), [
          'month' => 'in:january,february,march,april,may,june,july,august,september,october,november,december',
        ], [
          'in' => 'Invalid `:attribute`!'
        ]);

        if ($validation->fails()) {
          return response()->json(array(
            'error' => $validation->errors()->all(),
          ), 422);
        }

        $month = Carbon::parse($month)->month;
      } else { // if not, use current month as default month
        $month = Carbon::now()->month;
      }

      $events = Events::whereMonth('schedule', $month)->where('event', '<>', null)->get();

      return response()->json($events, 200);
    } catch (\Exception $e) {
      return response()->json([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ], 500);
    }
  }
  /**
   * POST /api/event/create
   * Create or update existing events
   * @param event string - the event name
   * @param from string - start date
   * @param to string - end date
   * @param dates array - an array of date objects
   */
  public function create(Request $req)
  {
    try {
      $json = (array) json_decode($req->getContent());

      // create custom function to check date range validity
      Validator::extend('invalid_range', function ($attribute, $value, $parameters) use ($json) {
        // date from must be lesser than to and to must be more than from.
        return Carbon::parse($json['to'])->gt(Carbon::parse($json['from'])) && Carbon::parse($json['from'])->lt(Carbon::parse($json['to']));
      });
      // trap required fields here. I know its redundant, but everybody knows you can bypass javascript from client side.
      $validation = Validator::make($json, [
        'event' => 'required|filled',
        'from' => 'required|filled|date|invalid_range',
        'to' => 'required|filled|date|invalid_range',
      ], [
        'invalid_range' => '`:attribute` Invalid date range.',
      ]);

      if ($validation->fails()) {
        return response()->json(array(
          'error' => $validation->errors()->all(),
        ), 422);
      }

      foreach ($json['dates'] as $schedule) {
        if ($schedule->isSelected) {
          Events::updateOrCreate([
            'schedule' => $schedule->date,
          ], [
            'schedule' => $schedule->date,
            'event' => $json['event']
          ]);
        } else {
          Events::where('schedule', $schedule->date)->delete();
        }
      }

      return response()->json([
        'success' => true,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ], 500);
    }
  }
}
