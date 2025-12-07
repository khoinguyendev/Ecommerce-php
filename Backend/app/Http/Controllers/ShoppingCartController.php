<?php

namespace App\Http\Controllers;

use App\Models\ShoppingCart;
use App\Models\Stock;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ShoppingCartController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $cartList = $user->cartItems()->with('stock.product')->orderBy('id', 'desc')->get();
        return $cartList;
    }
    public function cartCount()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return $user->cartItems()->count();
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $cartList = $request->localCartList;
        return $cartList;
        // Check if $cartList is already an array
        if (!is_array($cartList)) {
            // If not, decode it
            $cartList = json_decode($cartList, true);
        }

        // Proceed with the rest of the logic
        foreach ($cartList as $cartArrayList) {
            foreach ($cartArrayList as $cartItem) {
                // Ensure $cartItem is an array before accessing its elements
                if (is_array($cartItem) && isset($cartItem['stock_id']) && isset($cartItem['quantity'])) {
                    $item = $user->cartItems()->where('stock_id', $cartItem['stock_id'])->first();

                    if (!$item) {
                        ShoppingCart::create([
                            'user_id' => $user->id,
                            'stock_id' => $cartItem['stock_id'],
                            'quantity' => $cartItem['quantity']
                        ]);
                    } else {
                        $item->update(['quantity' => $cartItem['quantity']]);
                    }
                } else {
                    // Handle the case where $cartItem is not an array or doesn't contain necessary keys
                    // You may log an error or skip processing this item
                    // For now, let's just continue to the next iteration
                    continue;
                }
            }
        }

        return $user->cartItems()->count();
    }

    public function storenew(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        ShoppingCart::create([
            'user_id' => $user->id,
            'stock_id' => $request->stockId,
            'quantity' => $request->quantity
        ]);
        return $request;
    }

    public function guestCart(Request $request)
    {
        $cartList = json_decode($request->input('cartList'), true);
        $data = [];
        $count = 1;

        foreach ($cartList as $cartArrayList) {
            foreach ($cartArrayList as $cartItem) {
                if ($cartItem['stock_id'] != null && $cartItem['quantity'] != null) {
                    $stock = Stock::with('product')->where('id', $cartItem['stock_id'])->first();
                    $data[] = [
                        'id' => $count,
                        'stock_id' => $cartItem['stock_id'],
                        'quantity' => $cartItem['quantity'],
                        'stock' => $stock
                    ];
                    $count++;
                }
            }
        }

        return $data;
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ShoppingCart  $shoppingCart
     * @return \Illuminate\Http\Response
     */
    public function show(ShoppingCart $shoppingCart)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ShoppingCart  $shoppingCart
     * @return \Illuminate\Http\Response
     */
    public function edit(ShoppingCart $shoppingCart)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShoppingCart  $shoppingCart
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $cartItem = ShoppingCart::with("stock")->where('id', $id)->first();
        $stockQty = $cartItem->stock->quantity;

        if ($request->quantity <= $stockQty && $request->quantity > 0) {
            ShoppingCart::where('id', $id)->update(['quantity' => $request->quantity]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ShoppingCart  $shoppingCart
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        ShoppingCart::destroy($id);
        return 'Item removed from cart successfully';
    }
}
