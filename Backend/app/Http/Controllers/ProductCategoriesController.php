<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductCategoriesController extends Controller
{
    //

    public function index()
    {
        return Category::all();
    }
    public function getAllProducts()
    {
        $products = Product::with('category')->get(); // Thay đổi số lượng sản phẩm trên mỗi trang nếu cần
        return $products;
    }
    public function addCategory(Request $request)
    {
        $existingCategory = Category::where('name', $request->name)->exists();

        if ($existingCategory) {
            // Nếu tên đã tồn tại, trả về phản hồi lỗi
            return response()->json(['error' => 'Category name already exists'], 200);
        }
        // Tạo một instance mới của Category
        $category = new Category();


        // Gán giá trị cho các thuộc tính khác nếu có
        $category->name = $request->name; // Gán giá trị cho thuộc tính name

        // Lưu model vào database
        $category->save();

        return $category;
    }

    public function new($id)
    {
        $products = Product::with('category')
            ->where('category_id', $id)
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        foreach ($products as $product) {
            if ($product->reviews()->exists()) {
                $product['review'] = $product->reviews()->avg('rating');
            }
        }

        return $products;
    }
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        if ($request->addoptions) {
            $options = $request->addoptions;
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
        }
        if ($request->updateoptions) {
            $arrayObj = $request->updateoptions;
            foreach ($arrayObj as $stockjson) {
                $option = json_decode($stockjson);
                $stock = Stock::find($option->id);
                if ($stock) {
                    $stock->update([
                        'quantity' => $option->quantity,
                    ]);
                }
            }
        }
        if ($request->deleteoptions) {
            $arrayId = $request->deleteoptions;
            foreach ($arrayId as $idStock) {
                Stock::destroy($idStock);
            }
        }

        if ($request->indexs) {
            $photos = json_decode($product->photo, true);
            $toRemove = $request->indexs;
            $photos = array_diff($photos, $toRemove);
            $photos = array_values($photos);
            $data = [];
            foreach ($toRemove as $fileToRemove) {
                $filePath = public_path('storage/products/' . $fileToRemove);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $name = time() . '-' . $photo->getClientOriginalName();
                    $photo->move(public_path('storage/products'), $name);
                    $data[] = $name;
                }
            }

            $combinedPhotos = array_merge($photos, $data);
            $product->update([
                'category_id' => $request->category_id,
                'brand' => $request->brand,
                'name' => $request->name,
                'price' => $request->price,
                'photo' => json_encode($combinedPhotos),
            ]);
            return response()->json(['message' => 'Product updated successfully', 'product' => $product], 200);
        } else {
            $photos = json_decode($product->photo, true);
            $data = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $name = time() . '-' . $photo->getClientOriginalName();
                    $photo->move(public_path('storage/products'), $name);
                    $data[] = $name;
                }
            }

            $combinedPhotos = array_merge($photos, $data);
            $product->update([
                'category_id' => $request->category_id,
                'brand' => $request->brand,
                'name' => $request->name,
                'price' => $request->price,
                'photo' => json_encode($combinedPhotos),
            ]);
            return response()->json(['message' => 'Product updated successfully', 'product' => $product], 200);
        }
    }


    public function topSelling($id)
    {
        $products = Product::with('category')
            ->where('category_id', $id)
            ->take(6)
            ->get();

        foreach ($products as $product) {
            if ($product->reviews()->exists()) {
                $product['review'] = $product->reviews()->avg('rating');
            }

            if ($product->stocks()->exists()) {
                $numOrders = 0;
                $stocks = $product->stocks()->get();
                foreach ($stocks as $stock) {
                    $numOrders += $stock->orders()->count();
                }
                $product['num_orders'] = $numOrders;
            } else {
                $product['num_orders'] = 0;
            }
        }

        return $products->sortByDesc('num_orders')->values()->all();
    }
    public function getProductsByCategory($categoryId, Request $request)
    {
        $products = Product::where('category_id', $categoryId)
            ->with('category')
            ->paginate(4); // Thay đổi số lượng sản phẩm trên mỗi trang nếu cần
        return response()->json($products);
    }
}
