<?php

namespace App\Http\Controllers;

use App\Http\Resources\Hijabs as HijabResourceCollection;
use App\Http\Resources\Hijab as HijabResource;
use App\Models\Hijab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HijabController extends Controller
{
    //
    public function print($merk) {
        return $merk;
    }

    public function index()
    {
        $criteria = Hijab::paginate(4);
        return new HijabResourceCollection($criteria);
    }

    public function view($id)
    {
        /*
        $hijab = DB::select('select * from hijabs where id = :id', ['id' => $id
        ]);
        */
        $hijab = new HijabResourceCollection(Hijab::find($id));
        return $hijab;
    }

    public function top($count)
    {
        $criteria = Hijab::select('*')
        ->orderBy('views', 'DESC')
        ->limit($count)
        ->get();
        return new HijabResourceCollection($criteria);
    }

    public function slug($slug) {
        $criteria = Hijab::where('slug', $slug)->first();
        return new HijabResource($criteria);
    }

    public function search($keyword) {
        $criteria = Hijab::select('*')
        ->where('merk', 'LIKE', "%".$keyword."%")
        ->orderBy('views','DESC')
        ->get();
        return new HijabResourceCollection($criteria);
    }
}
