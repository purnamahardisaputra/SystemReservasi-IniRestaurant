<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\TableStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReservationStoreRequest;
use App\Models\Reservation;
use App\Models\Table;
use App\Rules\DateBetween;
use App\Rules\TimeBetween;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function stepOne(Request $request)
    {
        $tables = Table::where('status', TableStatus::Available)->get();
        $reservation = $request->session()->get('reservation');
        $min_date = Carbon::today();
        $max_date = Carbon::now()->addWeek();
        $reservation = $request->session()->get('reservation');
        // $res_table_ids = Reservation::orderBy('res_date')->get()->filter(function($value) use($reservation){
        //     return $value->res_date->format('Y-m-d') == $reservation->res_date->format('Y-m-d');
        // })->pluck('table_id');
        // $tables = Table::where('status', TableStatus::Available)->where('guest_number', '>=', $reservation->guest_number)->whereNotIn('id', $res_table_ids)->get();
        return view('reservations.step-one', compact('reservation', 'min_date', 'max_date', 'tables'));
    }

    public function storeStepOne(Request $request1, ReservationStoreRequest $request)
    {
        // $validated = $request->validate([
        //     'first_name' => ['required'],
        //     'last_name' => ['required'],
        //     'email' => ['required', 'email'],
        //     'res_date' => ['required', 'date', new DateBetween, new TimeBetween],
        //     'tel_number' => ['required'],
        //     'guest_number' => ['required'],
        // ]);


        // if (empty($request->session()->get('reservation'))) {
        //     $reservation = new Reservation();
        //     $reservation->fill($validated);
        //     $request->session()->put('reservation', $reservation);
        // }
        // else{
        //     $reservation = $request->session()->get('reservation');
        //     $reservation->fill($validated);
        //     $request->session()->put('reservation', $reservation);
        // }

        // return to_route('reservations.step.two');
        $table = Table::find($request->table_id);
        if ($request->guest_number > $table->guest_number) {
            return back()->with(['warning' => 'Tolong pilih Meja yang sesuai dengan jumlah tamu']);
        }
        $request_date = Carbon::parse($request->res_date);
        foreach ($table->reservations as $res) {
            if ($res->res_date->format('Y-m-d') == $request_date->format('Y-m-d'))
            return back()->with(['warning' => 'Meja sudah dipesan pada tanggal tersebut']);
        }
        Reservation::create($request->validated());

        return view('thankyou')->with((['success' => 'Reservasi berhasil dibuat']));
    }

    public function stepTwo(Request $request)
    {
        return view('reservations.step-two', compact('reservation', 'tables'));
    }

    public function storeStepTwo(Request $request, ReservationStoreRequest $reservationStoreRequest)
    {
        
        $validated = $request->validate([
            'table_id' => ['required'],
        ]);
        $reservation = $request->session()->get('reservation');
        $reservation->fill($validated);
        $reservation->save();
        $request->session()->forget('reservation');
        // Reservation::create($reservationStoreRequest->validated());
        return view('reservations.thankyou');
        // return to_route('thankyou');
    }
    
    public function thankyou(ReservationStoreRequest $request)
    {
        Reservation::create($request->validated());

        return to_route('reservations.storeStepTwo');
    }
}