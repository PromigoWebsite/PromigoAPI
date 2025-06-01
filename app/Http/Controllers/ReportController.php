<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Models\Report;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private static function baseQuery(){
        $report = DB::table('reports')
            ->join('users', 'users.id', '=', 'reports.user_id')
            ->join('promos', 'promos.id', '=', 'reports.promo_id')
            ->join('brands', 'brands.id', '=', 'reports.brand_id')
            ->select(
                'reports.id as id',
                'promos.name as promo_name',
                'brands.name as brand_name',
                'users.email as email',
                'reports.description as description',
                'reports.created_at as created_at',
            );

        return $report;
    }
    public function items(Request $request) {
        //ALL
        if ($request->has('page') && $request->page === "all") {
            $report = $this->baseQuery()->get();
        } else {
            //SEARCH
            $report = $this->baseQuery();
            if ($request->has('search') && $request->search) {
                $report = $report->whereRaw('LOWER(promos.name) LIKE ?', ['%' . strtolower($request->search) . '%']);
            }
            if ($request->has('filter') && $request->filter) {
                foreach ($request->filter as $filter => $value) {
                    if ($value === "default") {
                        continue;
                    }
                    $report->where('brands.name', $value);
                }
            }
            if ($request->has('sorting') && $request->sorting) {
                foreach ($request->sorting as $filter => $value) {
                    if($value === "default"){
                        continue;
                    }
                    $report->orderBy($filter, $value);
                }
            }

            $report = $report->paginate($request->per_page);
        }
        return response()->json($report);
    }

    public function addReport(Request $request, $id){
        try {
            $request->validate([
                'value' => 'required'
            ]);
    
            $promo = Promo::join('brands','brands.id','=','promos.brand_id')
                        ->join('users','users.id','=','brands.user_id')
                        ->select(
                            'users.id as user_id',
                            'brands.id as brand_id',
                        )
                        ->where('promos.id',$id)
                        ->first();
    
            $report = [
                'user_id' => $promo->user_id,
                'promo_id' => $id,
                'brand_id' => $promo->brand_id,
                'status' => 'On going',
                'description' => $request->value,
            ];
    
            Report::create($report);
        } catch (Exception $e) {
            throw $e;
        }
        
    }

    public function deleteReport($id){
        try {
            $deletedReport = Report::findOrFail($id)->delete();
            return response()->json($deletedReport);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
