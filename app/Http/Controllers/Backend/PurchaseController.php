<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;  
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller
{
    public function AllPurchase() {
        $allData = Purchase::orderby('id','desc')->get();
        return view('admin.backend.purchase.all_purchase', compact('allData'));
    }
    // End Method

    public function AddPurchase(Request $request) {
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        return view('admin.backend.purchase.add_purchase', compact('suppliers','warehouses'));
    }
    // End Method

    public function PurchaseProductSearch(Request $request) {
            $query = $request->input('query');
            $warehouse_id = $request->input('warehouse_id');

            $products = Product::where(function($q) use($query){
                $q->where('name','like',"%$query%")
                ->orwhere('code', 'like', "%$query%");
            })

            ->when($warehouse_id, function($q) use ($warehouse_id){
                $q->where('warehouse_id', $warehouse_id);

            })

            ->select('id','name','code','price','product_qty')
            ->limit (10)
            ->get();

            return response()->json($products);
        
    }
    //End Method

    public function StorePurchase(Request $request) {
    $request->validate([
        'date' => 'required|date',
        'status' => 'required',
        'supplier_id'=> 'required',
        'products' => 'required|array', // Added validation for products array
    ]);

    try {
        DB::beginTransaction();

        $purchase = Purchase::create([
            'date'=> $request->date,
            'warehouse_id'=> $request->warehouse_id,
            'supplier_id'=> $request->supplier_id,
            'discount'=> $request->discount ?? 0,
            'shipping'=> $request->shipping ?? 0,
            'status'=> $request->status,
            'note'=> $request->note,
            'grand_total' => 0,
        ]);   

        $grandTotal = 0; // Fixed: Initialized $grandTotal before the loop

        foreach($request->products as $productData){
            $product = Product::find($productData['id']);
            
            if (!$product) {
                throw new \Exception('Product not found for ID: ' . $productData['id']);
            }

            $netUniCost = $productData['net_uni_cost'] ?? $product->price;

            if ($netUniCost === null) {
                throw new \Exception('Net Unit Cost is missing for the product ID ' . $productData['id']);
            }

            $subtotal = ($netUniCost * $productData['quantity']) - ($productData['discount'] ?? 0);
            $grandTotal += $subtotal; // Fixed: Removed the naked '+=' syntax error

            PurchaseItem::create([
                'purchase_id'=> $purchase->id,
                'product_id'=> $productData['id'],
                'quantity'=> $productData['quantity'],
                'net_unit_cost'=> $netUniCost, // Fixed: Changed from camelCase $productData['netUnitCost'] to match variable name
                'stock'=> $product->product_qty + $productData['quantity'],
                'discount'=> $productData['discount'] ?? 0,
                'subtotal'=> $subtotal,
            ]); 

            // Fixed: Standardized to increment the target column 'product_qty' by the added quantity
            $product->increment('product_qty', $productData['quantity']);
        }    

        $purchase->update(['grand_total'=> $grandTotal + ($request->shipping ?? 0) - ($request->discount ?? 0)]);

        DB::commit();

        $notification = array(
            'message'=> 'Purchase Stored Successfully',
            'alert-type'=> 'success'
        );
        return redirect()->route('all.purchase')->with($notification);    

        } catch (\Exception $e) { // Fixed: Typo '\Expection' to '\Exception'
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500); // Fixed: Typo 'repsonse()' to 'response()'
        }
    }
    //End Method

    public function EditPurchase(string $id){
        $editData = Purchase::with('purchaseItems.product')->findOrFail($id);
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        return view('admin.backend.purchase.edit_purchase', compact('editData','suppliers','warehouses'));
    }
    //End Method

    public function UpdatePurchase(Request $request, string $id)
{
    $request->validate([
        'date' => 'required|date',
        'status'=> 'required',
        'supplier_id'=> 'required'
    ]);

    try {
        DB::beginTransaction();

        $purchase = Purchase::findOrFail($id);

        $purchase->update([
            'date'=> $request->date,
            'warehouse_id'=> $request->warehouse_id,
            'supplier_id'=> $request->supplier_id,
            'discount'=> $request->discount,
            'shipping'=> $request->shipping,
            'status'=> $request->status,
            'note'=> $request->note,
            'grand_total'=> $request->grand_total,
        ]);

        /// Get Old Purchase Items
        $oldPurchaseItems = PurchaseItem::where('purchase_id', $purchase->id)->get();

        /// Loop for old products and decrement stock back to original state
        foreach ($oldPurchaseItems as $oldItem) {
            $product = Product::find($oldItem->product_id);
            if ($product) {
                $product->decrement('product_qty', $oldItem->quantity);
            }
        }

        /// Delete old Purchase Items
        PurchaseItem::where('purchase_id', $purchase->id)->delete();

        // Loop for new products and insert new purchase items
        foreach ($request->products as $product_id => $productData) {
            PurchaseItem::create([
                'purchase_id'=> $purchase->id,
                'product_id'=> $product_id,
                'net_Unit_Cost'=> $productData['net_unit_cost'],
                'stock'=> $productData['stock'],
                'quantity'=> $productData['quantity'],
                'discount'=> $productData['discount'] ?? 0,
                'subtotal'=> $productData['subtotal'],
            ]);

            /// Update product stock by incrementing new quantity
            $product = Product::find($product_id);
            if ($product) {
                // FIXED: Changed from $productData->quantity to $productData['quantity']
                $product->increment('product_qty', $productData['quantity']); 
            }
        }

        DB::commit();

        $notification = array(
            'message'=>'Purchase Updated Successfully',
            'alert-type'=> 'success'
        );
        
        return redirect()->route('all.purchase')->with($notification);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    //End Method

    public function DetailsPurchase(string $id){
        $purchase = Purchase::with(['supplier','purchaseItems.product'])->find($id);
        return view('admin.backend.purchase.purchase_details', compact('purchase'));
    }
    //End Method
    
    public function InvoicePurchase(Request $request, string $id){
        $purchase = Purchase::with(['supplier', 'warehouse','purchaseItems.product'])->find($id);

        $pdf = Pdf::loadView('admin.backend.purchase.invoice_pdf', compact('purchase'));
        return $pdf->download('purchase_'.$id.'.pdf');
    }
    //End Method

    public function DeletePurchase(string $id){
        try {
        DB::beginTransaction();
        $purchase = Purchase::find($id);
        $purchaseItems = PurchaseItem::where('purchase_id',$id)->get();

        foreach ($purchaseItems as $item){
            $product = Product::find($item->product_id);
            if ($product) {
                $product->decrement('product_qty',$item->quantity);
            }
        }
        PurchaseItem::where('purchase_id',$id)->delete();
        $purchase->delete();
        DB::commit();

        $notification = array(
            'message' => 'Purchase Deleted Successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('all.purchase')->with($notification);

        }  catch (\Exception $e) {
           DB::rollBack();
           return response()->json(['error' => $e->getMessage()], 500);
        }

    }
    //End Method
            
}