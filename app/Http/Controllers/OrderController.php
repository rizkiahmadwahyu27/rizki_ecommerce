<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function checkout(){

        $user_id = Auth::id();

        $carts = Cart::where('user_id', $user_id)->get();
        // dd($carts);
        if($carts == "[]"){
            return Redirect::route('index_product');
        }else{

        $order = Order::create([
            'user_id' => $user_id
        ]);
        }
        foreach ($carts as $cart) {

            $product = Product::find($cart->product_id);

            $product->update([
                'stock' => ($product->stock - $cart->amount)
            ]);
            
            Transaction::create([
                'amount' => $cart->amount,
                'order_id' => $order->id,
                'product_id' => $cart->product_id
            ]);
            
            $cart->delete();
            }
        return Redirect::route('index_order');
    }

    public function index_order(){
        $user_id = Auth::user()->id;
        $orders = Order::where('user_id', $user_id)->get();

        return view('index_order', compact('orders'));
    }


    public function show_order(Order $order){
        return view('show_order', compact('order'));
    }

    public function submit_payment_receipt(Order $order, Request $request){
        $file = $request->file('payment_receipt');
        $path = time() . '_' . $order->id . '.' . $file->getClientOriginalExtension();

        Storage::disk('local')->put('public/' . $path, file_get_contents($file));

        $order->update([
            'payment_receipt' => $path
        ]);

        return Redirect::back();
    }

    public function confirme_payment(Order $order){
        $order->update([
            'is_paid' => true
        ]);

        return Redirect::route('index_order');
    }
}
