<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;  
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    public function AllSales(){
        $allData = Sale::orderby('id','desc')->get();
        return view('admin.backend.sales.all_sales',compact('allData'));
    }
    // End Method

    public function AddSales(){
        $customers = Customer::all();
        $warehouses = Warehouse::all();
        return view('admin.backend.sales.add_sales', compact('customers','warehouses'));
    }
    // End Method

    public function StoreSales(Request $request){
        
        $request->validate([
            'date'=> 'required|date',
            'status'=> 'required',
        ]);

        try {

            DB::beginTransaction();
            
            $grandTotal = 0;

            $sales = Sale::create([
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

            /// Stock Sales Items & Update Stock

        foreach ($request->products as $productData){
            $product = Product::findOrFail($productData['id']);
            $netUnitCost = $productData['net_unit_cost'] ?? $product->price;
                
            if ($netUnitCost === null) {
            throw new \Exception("Net Unit Cost is missing for the product id " . $productData['id']);
            
            }
                $subtotal = ($netUnitCost * $productData['quantity']) - ($productData['discount'] ?? 0);
                $grandTotal += $subtotal;

                SaleItem::create([
                    'sale_id'=> $sales->id,
                    'product_id'=> $productData['id'],
                    'date' => $request->date,
                    'net_unit_cost'=> $netUnitCost,
                    'stock'=> $product->product_qty + $productData['quantity'],
                    'quantity'=> $productData['quantity'],
                    'discount'=> $productData['discount'] ?? 0,
                    'subtotal'=> $subtotal,              
                ]);

                $product->decrement('product_qty', $productData['quantity']);

            }

            $sales->update(['grand_total'=> $grandTotal + $request->shipping - $request->discount]);

            DB::commit();

            $notification = array(
                'message'=> 'Sales Stored Successfully',
                'alert-type'=> 'success'
            );
            return redirect()->route('all.sale')->with($notification);

            } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['error' => $e->getMessage()], 500); // Fixed: Typo 'repsonse()' to 'response()'
            }
        }
        // End Method
        public function EditSales(Request $request){
            $editData = Sale::with('saleItems.product')->findOrFail($request->id);
            $customers = Customer::all();
            $warehouses = Warehouse::all();
            return view('admin.backend.sales.edit_sales', compact('editData','customers','warehouses'));
        }
        //End Method

        public function UpdateSales(Request $request){
            $request->validate([
                'date' => 'required|date',
                'status' => 'required',
            ]);

            $sales = Sale::findOrFail($request->id);
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

            /// Delete Sales Items
            SaleItem::where('sale_id', $sales->id)->delete();

            foreach($request->products as $product_id => $product){
                SaleItem::create([
                    'sale_id'=> $sales->id,
                    'product_id'=> $product_id,
                    'date' => $request->date,
                    'net_unit_cost'=> $product['net_unit_cost'],
                    'stock'=> $product['stock'],
                    'quantity'=> $product['quantity'],
                    'discount'=> $product['discount'] ?? 0,
                    'subtotal'=> $product['subtotal'],
                ]);

                /// Update product stock
                
                $productModel = Product::find($product_id);
                if ($productModel) {
                    $productModel->product_qty += $product['quantity'];
                    $productModel->save();
                }
            }
            
            $notification = array(
                'message'=> 'Sale Updated Successfully',
                'alert-type'=> 'success'
            );
            return redirect()->route('all.sale')->with($notification);

        }
        // End Method            

        public function DeleteSales(string $id){
            try {
                DB::beginTransaction();
                $sales = Sale::find($id);
                $SaleItems = SaleItem::where('sale_id', $id)->get();

                foreach($SaleItems as $item){
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->increment('product_qty', $item->quantity);

                    }
                }
                SaleItem::where('sale_id', $id)->delete();
                $sales->delete();
                DB::commit();

                $notification = array(
                    'message' => 'Purchase Deleted Successfully',
                    'alert-type' => 'success'
                );
                return redirect()->route('all.sale')->with($notification);

                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['error' => $e->getMessage()], 500); // Fixed: Typo 'repsonse()' to 'response()'
            }
        }
        //End Method    

        public function DetailsSales(string $id){
            $sales = Sale::with(['customer', 'saleItems.product'])->find($id);
            return view('admin.backend.sales.sales_details', compact('sales'));
        }
        
        public function InvoiceSales(string $id){
            $sales = Sale::with(['customer', 'warehouse', 'saleItems.product'])->find($id);
            $pdf = Pdf::loadView('admin.backend.sales.invoice_pdf', compact('sales'));
            return $pdf->download('sales_'.$id.'.pdf');
        }
        //End Method







}
