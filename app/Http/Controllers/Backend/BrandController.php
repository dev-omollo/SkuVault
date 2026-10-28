<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use Carbon\Carbon;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class BrandController extends Controller
{
    public function AllBrand(){
        if(!auth()->user()->hasPermissionTo('all.brand')) {
            abort(403,'Unauthorized Action');
        }
        $brand = Brand::latest()->get();
        return view('admin.backend.brand.brand_all', compact('brand'));
    } 
    // End Method

    public function AddBrand(){
        return view('admin.backend.brand.brand_add');
    } 
    // End Method

    public function StoreBrand(Request $request){
        if($request->file('image')) {
            $image = $request->file('image');
            $name_gen = hexdec(uniqid()).'.'.$image->getClientOriginalExtension();
            $img = ImageManager::usingDriver(Driver::class)->decode($image);
            $img->resize(300, 300)->save(public_path('upload/brand/'.$name_gen));
            $save_url = 'upload/brand/'.$name_gen;

            Brand::create([
                'name' => $request->name,
                'image' => $save_url,
                'created_at' => Carbon::now(),
            ]);
        }

        $notification = array(
            'message' => 'Brand Inserted Successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('all.brand')->with($notification);
    } 
    // End Method

    public function EditBrand(string $id){
        $brand = Brand::findOrFail($id);
        return view('admin.backend.brand.brand_edit', compact('brand'));
    }
    // End Method

    public function UpdateBrand(Request $request){

        $brand_id = $request->id;
        $brand = Brand::findOrFail($brand_id);
        
        if($request->file('image')) {
            $image = $request->file('image');
            $name_gen = hexdec(uniqid()).'.'.$image->getClientOriginalExtension();
            $img = ImageManager::usingDriver(Driver::class)->decode($image);
            $img->resize(300, 300)->save(public_path('upload/brand/'.$name_gen));
            $save_url = 'upload/brand/'.$name_gen;

            if (file_exists(public_path($brand->image))) {
                unlink(public_path($brand->image));
            }

            $brand->update([
                'name' => $request->name,
                'image' => $save_url,
                'updated_at' => Carbon::now(),
            ]);
        } else {
            $brand->update([
                'name' => $request->name,
                'updated_at' => Carbon::now(),
            ]);
        }

        $notification = array(
            'message' => 'Brand Updated Successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('all.brand')->with($notification);
    }
    // End Method

    public function DeleteBrand(string $id){
        $brand = Brand::findOrFail($id);
        if (file_exists(public_path($brand->image))) {
            unlink(public_path($brand->image));
        }
        $brand->delete();

        $notification = array(
            'message' => 'Brand Deleted Successfully',
            'alert-type' => 'success'
        );
        return redirect()->back()->with($notification);

    }
    // End Method

    
}
