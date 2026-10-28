<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;  
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
        public function AllSalesReturn(){
            $allData = SaleReturn::orderBy("id","desc")->get();
            return view('admin.backend.return-sale.all_return_sales',compact('allData'));
        }
        // End Method

        public function AddSalesReturn(){
            $customers = Customer::all();
            $warehouses = Warehouse::all();
            return view('admin.backend.return-sale.add_return_sales', compact('warehouses','customers'));
        }
        // End Method

        public function StoreSalesReturn(Request $request){
            
            $request->validate([
                'date'=> 'required|date',
                'status'=> 'required',
            ]);

            try {

            DB::beginTransaction();
            
            $grandTotal = 0;

            $sales = SaleReturn::create([
                'date'=> $request->date,
                'warehouse_id'=> $request->warehouse_id,
                'customer_id'=> $request->customer_id,
                'discount'=> $request->discount ?? 0,
                'shipping'=> $request->shipping ?? 0,
                'status'=> $request->status,
                'note'=> $request->note,
                'grand_total' => 0,
                'paid_amount' => $request->paid_amount,
                'due_amount' => $request->due_amount,

            ]);

            /// Store sales items & Update stock
            foreach ($request->products as $productData){
            $product = Product::findorFail($productData['id']);
            $netUnitCost = $productData['net_unit_cost'] ?? $product->price;

            if ($netUnitCost === null) {
                throw new \Exception("Net Unit Cost is missing for the product id " . $productData['id']);
            
                }
                    $subtotal = ($netUnitCost * $productData['quantity']) - ($productData['discount'] ?? 0);
                    $grandTotal += $subtotal;

                    SaleReturnItem::create([
                        'sale_return_id'=> $sales->id,
                        'product_id'=> $productData['id'],
                        'date' => $request->date,
                        'net_unit_cost'=> $netUnitCost,
                        'stock'=> $product->product_qty + $productData['quantity'],
                        'quantity'=> $productData['quantity'],
                        'discount'=> $productData['discount'] ?? 0,
                        'subtotal'=> $subtotal,              
                    ]);

                    $product->increment('product_qty', $productData['quantity']);

                }

                $sales->update(['grand_total'=> $grandTotal + $request->shipping - $request->discount]);

                DB::commit();

                $notification = array(
                    'message'=> 'Sales Return Stored Successfully',
                    'alert-type'=> 'success'
                );
                return redirect()->route('all.sale.return')->with($notification);

                } catch (\Exception $e) {
                        DB::rollBack();
                        return response()->json(['error' => $e->getMessage()], 500); // Fixed: Typo 'repsonse()' to 'response()'
                }
        }
        // End Method

        public function EditSalesReturn(string $id){
            $editData = SaleReturn::with('saleReturnItems.product')->findorFail($id);
            $customers = Customer::all();
            $warehouses = Warehouse::all();
            return view('admin.backend.return-sale.edit_return_sales', compact('editData','customers','warehouses'));
        }
        //End Method

        public function UpdateSalesReturn(Request $request, string $id){
                $request->validate([
                    'date' => 'required|date',
                    'status' => 'required',
                ]);

                $sales = SaleReturn::findorFail($id);
                $sales->update([
                    'date'=> $request->date,
                    'warehouse_id'=> $request->warehouse_id,
                    'customer_id'=> $request->customer_id,
                    'discount'=> $request->discount ?? 0,
                    'shipping'=> $request->shipping ?? 0,
                    'status'=> $request->status,
                    'note'=> $request->note,
                    'grand_total'=> $request->grand_total,
                    'paid_amount' => $request->paid_amount,
                    'due_amount' => $request->due_amount,
                ]);

                /// Delete old sales items
                SaleReturnItem::where('sale_return_id', $sales->id)->delete();

                foreach($request->products as $product_id => $product){
                    SaleReturnItem::create([
                        'sale_return_id'=> $sales->id,
                        'product_id'=> $product_id,
                        'date' => $request->date,
                        'net_unit_cost'=> $product['net_unit_cost'],
                        'stock'=> $product['stock'],
                        'quantity'=> $product['quantity'],
                        'discount'=> $product['discount'] ?? 0,
                        'subtotal'=> $product['subtotal'],

                    ]);

                /// Update Product Stock

                $productModel = Product::find($product_id);
                if ($productModel) {
                    $productModel->product_qty += $product['quantity'];
                    $productModel->save();
                }
            }  
            
            $notification = array(
                'message'=> 'Sales Return Updated Successfully',
                'alert-type'=> 'success',
            );

            return redirect()->route('all.sale.return')->with($notification);

        }
        // End Method

        public function DetailsSalesReturn(string $id){
            $sales = SaleReturn::with('customer','saleReturnItems.product')->find($id);
            return view('admin.backend.return-sale.sales_return_details', compact('sales'));
        }
        // End Method

        public function DeleteSalesReturn(string $id){
            try {
                DB::beginTransaction();
                $sales = SaleReturn::find($id);
                $SaleItems = SaleReturnItem::where('sale_return_id', $id)->get();

                foreach($SaleItems as $item){
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->decrement('product_qty', $item->quantity);
                    }
                }
                SaleReturnItem::where('sale_return_id', $id)->delete();
                $sales->delete();
                DB::commit();

                $notification = array(
                    'message' => 'Sales Return Deleted Successfully',
                    'alert-type' => 'success'
                );
                return redirect()->route('all.sale.return')->with($notification);    
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 500); // Fixed: Typo 'repsonse()' to 'response()'
            }
        }
        // End Method

        public function DueSale(){
            $sales = Sale::with(['customer', 'warehouse'])
                ->select('id', 'customer_id', 'warehouse_id', 'due_amount')
                ->where('due_amount', '>', 0)
                ->get();
            return view('admin.backend.due.sale_due', compact('sales'));
        }
        // End Method

        public function DueSaleReturn(){
            $sales = SaleReturn::with(['customer', 'warehouse'])
                ->select('id', 'customer_id', 'warehouse_id', 'due_amount')
                ->where('due_amount', '>', 0)
                ->get();    
            return view('admin.backend.due.sale_return_due', compact('sales'));
        }
        // End Method

    
}
