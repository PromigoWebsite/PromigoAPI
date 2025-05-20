<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller {
    public function list(Request $request) {
        try {
            $user = Auth::user();

            $favorite = DB::table('favorites AS user_favs')
                ->join('promos', 'user_favs.promo_id', '=', 'promos.id')
                ->join('assets', 'promos.id', '=', 'assets.promo_id')
                ->leftJoin(
                    DB::raw('(SELECT promo_id, COUNT(*) as favorite_count FROM favorites GROUP BY promo_id) AS counts'),
                    'promos.id',
                    '=',
                    'counts.promo_id'
                )
                ->select(
                    'promos.id',
                    'promos.name',
                    'assets.path',
                    'user_favs.created_at',
                    DB::raw('COALESCE(counts.favorite_count, 0) as favorite_count')
                )
                ->where('user_favs.user_id', $user->id)
                ->distinct() // Avoid duplicates
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->whereRaw('LOWER(promos.name) LIKE ?', ['%' . strtolower($request->search) . '%']);
                })
                ->when($request->filled('orderBy'), function ($query) use ($request) {
                    if ($request->orderBy == 'name') {
                        return $query->orderBy('promos.name', 'ASC');
                    } else {
                        return $query->orderBy('user_favs.created_at', 'DESC');
                    }
                })
                ->get();

            return response()->json([
                'favorite' => $favorite,
                'user' => $user,
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function removeFavorite($id) {
        try {
            $user = Auth::user();
            
            Favorite::where('promo_id', $id)
                ->where('user_id', $user->id)
                ->delete();

            return response()->json([
                'message' => 'Favorite removed successfully',
            ]);
        
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function addFavorite($id) {
        try {
            $user = Auth::user();
            
            $favorite = Favorite::create([
                'promo_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'Favorite' => $favorite,
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
