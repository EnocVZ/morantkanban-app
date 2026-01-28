<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\MethodHelper;

use App\Models\TaskTimeLife;

class TaskTimeLifeController extends Controller
{
    public function getTimeLifeBySubcolumn($id)
    {
        try{
            $item = TaskTimeLife::where('subcolumn_id', $id)->first();
            return MethodHelper::successResponse($item);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
       
    }

    public function storeOrUpdateTimeLife(Request $request)
    {
        $data = $request->validate([
            'subcolumn_id' => 'required|integer',
            'expire_at' => 'required|integer',
        ]);

        try {
            $item = TaskTimeLife::updateOrCreate(
                ['subcolumn_id' => $data['subcolumn_id']],
                ['expire_at' => $data['expire_at']]
            );

            return MethodHelper::successResponse($item);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }

    public function deleteTimeLife($id)
    {
        try {
            $item = TaskTimeLife::where('subcolumn_id', $id)->first();
            if ($item) {
                $item->delete();
                return MethodHelper::successResponse([], 'Time life deleted successfully.');
            } else {
                return MethodHelper::errorResponse('Time life not found.');
            }
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }
}