<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Warehouse;  
use Illuminate\Support\Facades\DB;
use App\Models\Transfer;
use App\Models\TransferItem;
use function Illuminate\Support\now;

class TransferController extends Controller
{
    public function AllTransfer(){
        $allData = Transfer::with(['transferItems.product'])->orderBy('id','desc')->get();
        return view('admin.backend.transfer.all_transfer', compact('allData'));
    }
    // End Method

    public function AddTransfer(){
        $warehouses = Warehouse::all();
        return view('admin.backend.transfer.add_transfer', compact('warehouses'));
    }    

    public function StoreTransfer(Request $request){

        $request->validate([
            'date' => 'required|date',
            'status' => 'required',
        ]);

        try {
        
            DB::beginTransaction();

            $transfer = Transfer::create([
                'date'=> $request->date,
                'from_warehouse_id'=> $request->from_warehouse_id,
                'to_warehouse_id'=> $request->to_warehouse_id,
                'discount'=> $request->discount ?? 0,
                'shipping'=> $request->shipping ?? 0,
                'status'=> $request->status,
                'note'=> $request->note,
                'grand_total'=> 0,
            ]);

            /// Store Sales Items & Stock Update
            foreach($request->products as $productData){
                $product = Product::findorFail($productData['id']);
                $netUnitCost = $product->price;
                $quantity = $productData['quantity'];
                $discount = $productData['discount'];
                $subtotal = ($netUnitCost * $quantity) - $discount;

                TransferItem::create([
                    'transfer_id'=> $transfer->id,
                    'product_id'=> $product->id,
                    'date' => $request->date,
                    'net_unit_cost'=> $netUnitCost,
                    'stock' => $product->product_qty,
                    'quantity' => $quantity,
                    'discount' => $discount,
                    'subtotal' => $subtotal,
                ]);

                /// Decrement stock form 'from_warehouse'

                Product::where('id', $productData['id'])
                    ->where('warehouse_id', $request->from_warehouse_id)
                    ->decrement('product_qty', $quantity);

                /// Check if product exists in 'to_warehouse'

                $existingProduct = Product::where('name', $product->name)
                    ->where('brand_id', $product->brand_id)
                    ->where('warehouse_id', $request->to_warehouse_id)
                    ->first();

                if ($existingProduct)  {
                    $existingProduct->increment('product_qty', $quantity);
                }  else {
                    // if not exists then create new product without code
                    Product::create([
                        'name' => $product->name,
                        'brand_id' => $product->brand_id,
                        'warehouse_id' => $request->to_warehouse_id,
                        'price' => $product->price,
                        'product_qty' => $quantity,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at'=> now(),
                    ]);

                }    
                    
            }

            DB::commit();

            $notification = array(
                'message' => 'Transfer created successfully',
                'alert-type' => 'success',
            );

            return redirect()->route('all.transfer')->with($notification);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error'=> $e->getMessage()], 500);
            }
        }
        // End Method

        public function EditTransfer(Request $request){
            $editData = Transfer::with('fromWarehouse', 'toWarehouse')->findOrFail($request->id);
            $warehouses = Warehouse::all();
            return view('admin.backend.transfer.edit_transfer', compact('editData','warehouses'));
        }
        //End Method

        public function UpdateTransfer(Request $request){

            try {

            DB::beginTransaction();

            $transfer = Transfer::findOrFail($request->id);

            //Restore Previous Stock
            $oldTransferItems = TransferItem::where('transfer_id', $transfer->id)->get();

            foreach($oldTransferItems as $oldItem){
                Product::where('id', $oldItem->product_id)
                    ->where('warehouse_id', $transfer->from_warehouse_id)
                    ->increment('product_qty', $oldItem->quantity);

                Product::where('id', $oldItem->product_id)
                    ->where('warehouse_id', $transfer->to_warehouse_id)
                    ->decrement('product_qty', $oldItem->quantity);

                /// Delete old transfer items to prevent duplicate entries
                
                TransferItem::where('transfer_id', $transfer->id)->delete();

                // update the transfer record 
                $transfer->update([
                    'date' => $request->date,
                    'discount' => $request->discount ?? 0,
                    'shipping' => $request->shipping ?? 0,
                    'status' => $request->status,
                    'note' => $request->note,
                    'grand_total' => $request->grand_total,
                ]);

                ///add new transfer items
                foreach($request->products as $productId => $productData){
                    $product = Product::find($productId);
                    if (!$product) {
                        throw new \Exception("Product id not found");
                    }
                    // Create new Transfer item in Transfer Item Table
                    $transferItem = TransferItem::create([
                        'transfer_id' => $transfer->id,
                        'product_id'=> $productId,
                        'date' => $request->date,
                        'net_unit_cost'=> $product->price ?? 0,
                        'stock' => $product->product_qty,
                        'quantity' => $productData['quantity'],
                        'discount' => $productData['discount'],
                        'subtotal' => $productData['subtotal'],
                    ]);

                    Product::where('id', $product->id)
                    ->where('warehouse_id', $transfer->to_warehouse_id)
                    ->decrement('product_qty', $productData['quantity']);

                    Product::where('id', $product->id)
                    ->increment('product_qty', $productData['quantity']);
                         /// receiving warehouse quantity
                }

                DB::commit();

                    $notification = array(
                        'message' => 'Transfer updated successfully',
                        'alert-type'=> 'success',
                    );
                    return redirect()->route('all.transfer')->with($notification);
                }

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error'=> $e->getMessage()], 500);
            }

        }
        // End Method

        public function DeleteTransfer(string $id){

            try {
                DB::beginTransaction();
                $transfer = Transfer::findorFail($id);
                $transferItems = TransferItem::where('transfer_id', $transfer->id)->get();

                foreach($transferItems as $item){
                    Product::where('id', $item->product_id)
                    ->where('warehouse_id', $transfer->from_warehouse_id)
                    ->increment('product_qty', $item->quantity);
                    /// Sending warehouse quantity

                    Product::where('warehouse_id', $transfer->to_warehouse_id)
                    ->decrement('product_qty', $item->quantity);
                    /// Receiving warehouse quantity

            }
            TransferItem::where('transfer_id', $transfer->id)->delete();
            $transfer->delete(); 
            DB::commit();
            
            $notification = array(
                'message' => 'Transfer Deleted Successfully',
                'alert-type'=> 'success',
            ); 
            return redirect()->route('all.transfer')->with($notification);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error'=> $e->getMessage()], 500);
            }
        }
        // End Method

        public function DetailsTransfer(string $id){
            $transfer = Transfer::with(['transferItems.product'])->findOrFail($id);
            $product = Product::find($transfer->product_id);
            $fromWareHouse = Warehouse::find($transfer->from_warehouse_id);
            $toWareHouse = Warehouse::find($transfer->to_warehouse_id);
            return view('admin.backend.transfer.details_transfer', compact('transfer','product','fromWareHouse','toWareHouse'));

        }
        // End Method

}
