<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CartModel;

class DeleteController extends Controller
{
    //Delete Cart 
    public function cart($id)
    {
		
        $get_cart_records = CartModel::find($id);
        
        if (!$get_cart_records == null) 
        {
            $delete_cart_records = $get_cart_records->delete();

            if ($delete_cart_records == true ) {
                return response()->json([
                    'message' => 'Cart deleted successfully!',
                    'data' => $delete_cart_records
                ], 201);
            }
            else{
                return response()->json([
                    'message' => 'Failed to delete successfully!',
                    'data' => $delete_cart_records
                ], 400);
            }
        }
        else{
            return response()->json([
                'message' => 'sorry, can\'t fetch the record!',
            ], 500);
        }
    }

    // delete user
    public function user($id = null)
    {
        $getRole = (Auth::user())->role;

        if ($getRole == 'user') {
            $id = Auth::id();
        }

        // Fetch the record by ID
        // Check if the record exists
        $get_user = User::find($id);

        if (!$get_user) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, User not found!',
            ], 200);
        }
               
        else{
            //$delete_user_records = $get_user->delete();

            if ($delete_user_records == true ) {
                return response()->json([
                    'success' => true,
                    'message' => 'User Deleted Successfully.',
                ], 200);
            }
            else{
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, Failed to delete user!',
                ], 200);
            }
        }
    }
}