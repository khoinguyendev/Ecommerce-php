<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return Product::with('category', 'stocks')->paginate(5);
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
        // Xác thực dữ liệu đầu vào

        $request->validate([
            'category_id' => 'required|integer',
            'brand' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'options' => 'nullable|array',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $data = [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $name = time() . '-' . $photo->getClientOriginalName();
                $photo->move(public_path('storage/products'), $name);
                $data[] = $name;
            }
        }

        $product = Product::create([
            'user_id' => $user->id,
            'category_id' => $request->category_id,
            'photo' => json_encode($data),
            'brand' => $request->brand,
            'name' => $request->name,
            'description' => $request->description,
            'details' => $request->details,
            'price' => $request->price,
        ]);
        $options = $request->options;

        foreach ($options as $optionJson) {


            $option = json_decode($optionJson); // Decode each JSON string into an object or associative array

            if ($option) {
                Stock::create([
                    'product_id' => $product->id,
                    'size' => $option->size,
                    'color' => $option->color,
                    'quantity' => $option->quantity,
                ]);
            } else {
                // Handle JSON decoding error
                throw new \Exception('Error decoding JSON string.');
            }
        }

        return response()->json(['message' => 'Product created successfully', 'product' => $product], 201);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::with('category', 'stocks')->findOrFail($id);
        if ($product->reviews()->exists()) {
            $product['review'] = $product->reviews()->avg('rating');
            $product['num_reviews'] = $product->reviews()->count();
        }
        return $product;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $product = Product::findOrFail($id);

        if ($product) {
            if ($product->photo !== null) { // corrected null comparison
                foreach (json_decode($product->photo) as $photo) {
                    unlink(public_path() . '/storage/products/' . $photo); // corrected path concatenation
                }
            }
            $product->delete();
        }
        return response()->json(['message' => 'Product deleted successfully'], 200);
    }
}
