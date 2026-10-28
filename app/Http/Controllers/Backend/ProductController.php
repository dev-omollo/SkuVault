<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Brand;
use App\Models\Supplier;
use App\Models\Warehouse;  
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductController extends Controller
{
    public function AllCategory()
    {
        $category = ProductCategory::latest()->get();
        return view('admin.backend.category.all_category', compact('category'));
    }
    //End Method

    public function StoreCategory(Request $request)
    {
        ProductCategory::insert([
            'category_name' => $request->category_name,
            'category_slug' => strtolower(str_replace(' ', '-', $request->category_name)),
        ]);

        $notification = array(
            'message' => 'ProductCategory Inserted Successfully',
            'alert-type' => 'success'
        );
        return redirect()->back()->with($notification);
    }
    //End Method

    public function EditCategory(string$id){
        $category = ProductCategory::find($id);
        return response()->json($category);
    }
    //End Method

    public function UpdateCategory(Request $request){
        $cat_id = $request->cat_id;

        ProductCategory::find($cat_id)->update([
            'category_name' => $request->category_name,
            'category_slug' => strtolower(str_replace(' ', '-', $request->category_name)),
        ]);

        $notification = array(
            'message' => 'ProductCategory Updated Successfully',
            'alert-type' => 'success'
        );
        return redirect()->back()->with($notification);
    }
    //End Method

    public function DeleteCategory(string$id){

        ProductCategory::find($id)->delete();
        $notification = array(
            'message' => 'ProductCategory Deleted Successfully',
            'alert-type' => 'success'
        );
        return redirect()->back()->with($notification);
    }
    //End Method

    public function AllProduct()
    {
        $allData = Product::orderBy('id', 'desc')->get();
        return view('admin.backend.product.product_list', compact('allData'));
    }

    public function AddProduct()
    {
        $categories = ProductCategory::latest()->get();
        $brands = Brand::latest()->get();
        $suppliers = Supplier::latest()->get();
        $warehouses = Warehouse::latest()->get();
        return view('admin.backend.product.add_product', compact('categories', 'brands', 'suppliers', 'warehouses'));
    }

    public function StoreProduct(Request $request)
    {
        $product_id = Product::create([
            'name' => $request->name,
            'code' => $request->code,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'warehouse_id' => $request->warehouse_id,
            'supplier_id' => $request->supplier_id,
            'price' => $request->price,
            'stock_alert' => $request->stock_alert,
            'note' => $request->note,
            'product_qty' => $request->product_qty,
            'status' => 1,
            'created_at' => now(),
            
        ]);

        $product_id = $product_id->id; // Get the ID of the newly created product

        // Multiple Image Upload
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $img) {
                $manager = new ImageManager(new Driver());
                $name_gen = hexdec(uniqid()) . '.' . 
                $img->getClientOriginalExtension();
                $imgs = $manager->decode($img);
                $imgs->resize(917, 1000)->save('upload/product_img/' . $name_gen);
                $save_url = 'upload/product_img/' . $name_gen;

                ProductImage::create([
                    'product_id' => $product_id,
                    'image' => $save_url,
                ]);
            }
        }

        $notification = array(
            'message' => 'Product Inserted Successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('all.product')->with($notification);
    }
    //End Method

    public function EditProduct(string $id)
    {
        $editData = Product::find($id);
        $categories = ProductCategory::all();
        $brands = Brand::all();
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        $multiimg = ProductImage::where('product_id', $id)->get();
        return view('admin.backend.product.edit_product', compact('editData', 'categories', 'brands', 'suppliers', 'warehouses', 'multiimg'));
    }
    //End Method

    public function UpdateProduct(Request $request){
        $pro_id = $request->id;

        $product = Product::findOrFail($pro_id);

        $product->name = $request->name;
        $product->code = $request->code;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;
        $product->price = $request->price;
        $product->stock_alert = $request->stock_alert;
        $product->note = $request->note;
        $product->warehouse_id = $request->warehouse_id;
        $product->supplier_id = $request->supplier_id; 
        $product->product_qty = $request->product_qty;
        $product->status = $request->status;
        $product->save();

        // Multiple Image Upload
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $img) {
                $manager = new ImageManager(new Driver());
                $name_gen = hexdec(uniqid()) . '.' . 
                $img->getClientOriginalExtension();
                $imgs = $manager->decode($img);
                $imgs->resize(917, 1000)->save('upload/product_img/' . $name_gen);

                $product->images()->create([
                    'image' => 'upload/product_img/'.$name_gen
                ]);
            }
        }

        if ($request->has('remove_image')) {
            foreach ($request->remove_image as $removeImageId) {
                $img = ProductImage::find($removeImageId);
                if ( $img ) {
                    // Delete the image file from storage
                    $file_exists = public_path($img->image); 
                    If (file_exists($file_exists)) {
                        unlink($file_exists);
                    }   
                    
                    // Delete the image record from the database
                    $img->delete();
                }
            }
            
        }

        $notification = array(
            'message' => 'Product Updated Successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('all.product')->with($notification);
    }

    public function DeleteProduct(string $id)
    {
        $product = Product::findOrFail($id);

        // Delete associated images
        $images = ProductImage::where('product_id', $product->id)->get();
        foreach ($images as $img) {
            $file_exists = public_path($img->image);
            if (file_exists($file_exists)) {
                unlink($file_exists);
            }
            
        }
        // Delete the product
        ProductImage::where('product_id', $id)->delete();

        $product->delete();

        $notification = array(
            'message' => 'Product Deleted Successfully',
            'alert-type' => 'success'
        );
        return redirect()->back()->with($notification);
    }
    
    public function ProductDetails(string $id)
    {
        $product = Product::findOrFail($id);
        $multiimg = ProductImage::where('product_id', $id)->get();
        return view('admin.backend.product.product_details', compact('product', 'multiimg'));
    }






}
