<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;  
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\ReturnPurchase;
use App\Models\ReturnPurchaseItem;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReturnPurchaseController extends Controller
{
    public function AllReturnPurchase(){
        $allData = ReturnPurchase::orderBy("id","desc")->get();
        return view('admin.backend.return-purchase.all_return_purchase',compact('allData'));
    }

    public function AddReturnPurchase(Request $request){
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        return view('admin.backend.return-purchase.add_return_purchase',compact('suppliers','warehouses'));
    }

    public function StoreReturnPurchase(Request $request) {

        $request->validate([
            'date' => 'required|date',
            'status' => 'required',
            'supplier_id'=> 'required',
        ]);

        try {
            DB::beginTransaction();

            $purchase = ReturnPurchase::create([
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

                ReturnPurchaseItem::create([
                    'return_purchase_id'=> $purchase->id,
                    'product_id'=> $productData['id'],
                    'quantity'=> $productData['quantity'],
                    'net_unit_cost'=> $netUniCost, // Fixed: Changed from camelCase $productData['netUnitCost'] to match variable name
                    'stock'=> $product->product_qty + $productData['quantity'],
                    'discount'=> $productData['discount'] ?? 0,
                    'subtotal'=> $subtotal,
                ]); 

                // Fixed: Standardized to increment the target column 'product_qty' by the added quantity
                $product->decrement('product_qty', $productData['quantity']);
            }    

            $purchase->update(['grand_total'=> $grandTotal + ($request->shipping ?? 0) - ($request->discount ?? 0)]);

            DB::commit();

            $notification = array(
                'message'=> 'Return Purchase Stored Successfully',
                'alert-type'=> 'success'
            );
            return redirect()->route('all.return.purchase')->with($notification);    

            } catch (\Exception $e) { // Fixed: Typo '\Expection' to '\Exception'
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 500); // Fixed: Typo 'repsonse()' to 'response()'
            }
        }
        //End Method

        public function DetailsReturnPurchase(string $id){
            $purchase = ReturnPurchase::with(['supplier','purchaseItems.product'])->find($id);
            return view('admin.backend.return-purchase.return_purchase_details', compact('purchase'));
        }

        public function InvoiceReturnPurchase(string $id){
            $purchase = ReturnPurchase::with(['supplier','warehouse','purchaseItems.product'])->find($id);

            $pdf = Pdf::loadView('admin.backend.return-purchase.invoice_pdf', compact('purchase'));
            return $pdf->download('purchase_'.$id.'.pdf');
        }

        public function EditReturnPurchase(string $id){
            $editData = ReturnPurchase::with(['supplier','warehouse','purchaseItems.product'])->find($id);
            $suppliers = Supplier::all();
            $warehouses = Warehouse::all();
            return view('admin.backend.return-purchase.edit_return_purchase', compact('editData','suppliers','warehouses'));
        }

        public function UpdateReturnPurchase(Request $request, string $id){
            
            $request->validate([
                'date' => 'required|date',
                'status' => 'required',
            ]);

              DB::beginTransaction();

            try {
                
                $purchase = ReturnPurchase::findorFail($id);

                $purchase->update([
                    'date'=> $request->date,
                    'warehouse_id'=> $request->warehouse_id,
                    'supplier_id'=> $request->supplier_id,
                    'discount'=> $request->discount ?? 0,
                    'shipping'=> $request->shipping ?? 0,
                    'status'=> $request->status,
                    'note'=> $request->note,
                    'grand_total'=> $request->grand_total,
                ]);

                /// Get Old Purchase Items

                $oldPurchaseItems = ReturnPurchaseItem::where('return_purchase_id', $purchase->id)->get();

                /// Loop for old purchase items and decrement product qty
                foreach ($oldPurchaseItems as $oldItem) {
                    $product = Product::find($oldItem->product_id);
                    if ($product) {
                        $product->increment('product_qty', $oldItem->quantity);
                        // Increment old quantity
                    }
                }
                
                /// Delete old Purchase Items
                ReturnPurchaseItem::where('return_purchase_id', $purchase->id)->delete();

                //Loop for new products and insert new purchase items

                foreach ($request->products as $product_id => $productData){
                ReturnPurchaseItem::create([
                    'return_purchase_id'=> $purchase->id,
                    'product_id' => $product_id,
                    'net_unit_cost'=> $productData['net_unit_cost'],
                    'stock'=> $productData['stock'],
                    'quantity'=> $productData['quantity'],
                    'discount'=> $productData['discount'] ?? 0,
                    'subtotal'=> $productData['subtotal'],
                ]);

                /// Update product stock by incrementing new quantity
                $product = Product::find($product_id);
                if ($product) {
                    $product->decrement('product_qty', $productData['quantity']);
                    // Decrement new quantity
                 }
                }
                
                DB::commit();

                $notication = array(
                    'message'=> 'Return Purchase Updated Successfully',
                    'alert-type'=> 'success'
                );
                return redirect()->route('all.return.purchase')->with($notication);

            } catch (\Exception $e) { // Fixed: Typo '\Expection' to '\Exception'
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 500); // Fixed: Typo 'repsonse()' to 'response()'
            }
        }
        //End Method

        public function DeleteReturnPurchase(string $id){
            try{
                DB::beginTransaction();
                $purchase = ReturnPurchase::findorFail($id);
                $purchaseItems = ReturnPurchaseItem::where('return_purchase_id', $purchase->id)->get();

                foreach ($purchaseItems as $item){
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->decrement('product_qty', $item->quantity);
                    }
                }
                ReturnPurchaseItem::where('return_purchase_id', $id)->delete();
                $purchase->delete();
                DB::commit();

                $notication = array(
                    'message'=> 'Return Purchase Deleted Successfully',
                    'alert-type'=> 'success'
                );
                return redirect()->route('all.return.purchase')->with($notication);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error'=> $e->getMessage()], 500);
            }    

        }
        //End Method

                


        
}
