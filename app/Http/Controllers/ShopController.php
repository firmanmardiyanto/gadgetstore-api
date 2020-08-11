<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Auth;
use App\Models\Province;
use App\Models\Gadget;
use App\Models\City;
use Illuminate\Http\Request;
use App\Http\Resources\Provinces as ProvinceResourceCollection;
use App\Http\Resources\Cities as CityResourceCollection;
use App\Models\GadgetOrder;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    public function provinces()
    {
        return new ProvinceResourceCollection(Province::get());
    }

    public function cities()
    {
        return new CityResourceCollection(City::get());
    }

    public function shipping(Request $request)
    {
        $user = Auth::user();
        $status = "error";
        $message = "";
        $data = null;
        $code = 200;
        if ($user) {
            $this->validate($request, [
                'name' => 'required',
                'address' => 'required',
                'phone' => 'required',
                'province_id' => 'required',
                'city_id' => 'required',
            ]);
            $user->name = $request->name;
            $user->address = $request->address;
            $user->phone = $request->phone;
            $user->province_id = $request->province_id;
            $user->city_id = $request->city_id;
            if ($user->save()) {
                $status = "success";
                $message = "Update shipping success";
                $data = $user->toArray();
            } else {
                $message = "Update shipping failed";
            }
        } else {
            $message = "User not found";
        }
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function couriers()
    {
        $couriers = [
            ['id' => 'jne', 'text' => 'JNE'],
            ['id' => 'tiki', 'text' => 'TIKI'],
            ['id' => 'POS', 'text' => 'POS'],
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'couriers',
            'data' => $couriers
        ], 200);
    }

    public function services(Request $request)
    {
        $status = "error";
        $message = "";
        $data = [];

        $this->validate($request, [
            'courier' => 'required',
            'carts' => 'required'
        ]);

        $user = Auth::user();
        if ($user) {
            $destination = $user->city_id;
            if ($destination > 0) {
                $origin = 22; //Bandung Kabupaten
                $courier = $request->courier;
                $carts = $request->carts;
                $carts = json_decode($carts, true);

                $validCart = $this->validateCart($carts);
                $data['safe_carts'] = $validCart['safe_carts'];
                $data['total'] = $validCart['total'];
                $quantity_different = $data['total']['quantity_before'] <> $data['total']['quantity'];
                $weight = $validCart['total']['weight'] * 1000;
                if ($weight > 0) {
                    $parameter = [
                        "origin" => $origin,
                        "destination" => $destination,
                        "weight" => $weight,
                        "courier" => $courier
                    ];

                    $respon_services = $this->getServices($parameter);
                    if ($respon_services['error'] == null) {
                        $services = [];
                        $response = json_decode($respon_services['response']);
                        $costs = $response->rajaongkir->results[0]->costs;
                        foreach ($costs as $cost) {
                            $service_name = $cost->service;
                            $service_cost = $cost->cost[0]->value;
                            $service_estimation = str_replace('hari', '', trim($cost->cost[0]->etd));
                            $services[] = [
                                'service' => $service_name,
                                'cost' => $service_cost,
                                'estimation' => $service_estimation,
                                'resume' => $service_name . '[ Rp.' . number_format($service_cost) . ', Etd: ' . $cost->cost[0]->etd . ' day(s) ]',
                            ];
                        }
                        if (count($services) > 0) {
                            $data['services'] = $services;
                            $status = 'success';
                            $message = "getting services success";
                        } else {
                            $message = "courier services unavailable";
                        }

                        if ($quantity_different) {
                            $status = "warning";
                            $message = "Check cart data, " . $message;
                        }
                    } else {
                        $message = "cURL error #:" . $respon_services['error'];
                    }
                } else {
                    $message = "weight invalid";
                }
            } else {
                $message = "destination not set";
            }
        } else {
            $message = "user not found";
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    protected function validateCart($carts)
    {
        $safe_carts = [];
        $total = [
            'quantity_before' => 0,
            'quantity' => 0,
            'price' => 0,
            'weight' => 0,
        ];
        $idx = 0;
        foreach ($carts as $cart) {
            $id = (int)$cart['id'];
            $quantity = (int)$cart['quantity'];
            $total['quantity_before'] += $quantity;
            $gadget = Gadget::find($id);
            if ($gadget) {
                if ($gadget->stock > 0) {
                    $safe_carts[$idx]['id'] = $gadget->id;
                    $safe_carts[$idx]['merk'] = $gadget->merk;
                    $safe_carts[$idx]['cover'] = $gadget->cover;
                    $safe_carts[$idx]['price'] = $gadget->price;
                    $safe_carts[$idx]['weight'] = $gadget->weight;
                    if ($gadget->stock < $quantity) {
                        $quantity = (int) $gadget->stock;
                    }
                    $safe_carts[$idx]['quantity'] = $quantity;
                    $total['quantity'] += $quantity;
                    $total['price'] += $gadget->price * $quantity;
                    $total['weight'] += $gadget->weight * $quantity;
                    $idx++;
                } else {
                    continue;
                }
            }
        }
        return [
            'safe_carts' => $safe_carts,
            'total' => $total
        ];
    }

    protected function getServices($data)
    {
        $url_cost = "https://api.rajaongkir.com/starter/cost";
        $key = "0e5cb9f78239823f7a2e20b763e1da35";
        $postdata = http_build_query($data);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url_cost,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postdata,
            CURLOPT_HTTPHEADER => [
                "content-type: application/x-www-form-urlencoded",
                "key: " . $key
            ],
        ]);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        return [
            'error' => $error,
            'response' => $response,
        ];
    }

    public function payment(Request $request)
    {
        $error = 0;
        $status = "error";
        $message = '';
        $data = [];
        $user = Auth::user();
        if ($user) {
            // validasi kelengkapan data
            $this->validate($request, [
                'courier' => 'required',
                'service' => 'required',
                'carts' => 'required'
            ]);

            DB::beginTransaction();
            try {
                // prepare data
                $origin = 22; // Kabupaten Bandung
                $destination = $user->city_id;
                if ($destination <= 0) $error++;
                $courier = $request->courier;
                $service = $request->service;
                $carts = json_decode($request->carts, true);
                // create order
                $order = new Order;
                $order->user_id = $user->id;
                $order->total_price = 0;
                $order->invoice_number = date('YmdHis');
                $order->courier_service = $courier . '-' . $service;
                $order->status = 'SUBMIT';
                if ($order->save()) {
                    $total_price = 0;
                    $total_weight = 0;
                    foreach ($carts as $cart) {
                        $id = (int)$cart['id'];
                        $quantity = (int)$cart['quantity'];
                        $gadget = Gadget::find($id);
                        if ($gadget) {
                            if ($gadget->stock >= $quantity) {
                                $total_price += $gadget->price * $quantity;
                                $total_weight += $gadget->weight * $quantity;
                                // create gadget order
                                $gadget_order = new gadgetOrder;
                                $gadget_order->gadget_id = $gadget->id;
                                $gadget_order->order_id = $order->id;
                                $gadget_order->quantity = $quantity;
                                if ($gadget_order->save()) {
                                    // kurangi stock
                                    $gadget->stock = $gadget->stock - $quantity;
                                    $gadget->save();
                                }
                            } else {
                                $error++;
                                throw new \Exception('Out of stock');
                            }
                        } else {
                            $error++;
                            throw new \Exception('Gadget is not found');
                        }
                    }
                    $weight = $total_weight * 1000;
                    if ($weight <= 0) {
                        $error++;
                        throw new \Exception('Weight null');
                    }

                    $data = [
                        "origin" => $origin,
                        "destination" => $destination,
                        "weight" => $weight,
                        "courier" => $courier
                    ];

                    $data_cost = $this->getServices($data);
                    if ($data_cost['error']) {
                        $error++;
                        throw new \Exception('Courier Service Unavailable');
                    }

                    $response = json_decode($data_cost['response']);
                    $cost = $response->rajaongkir->results[0]->costs;
                    $service_cost = 0;

                    foreach ($cost as $cost) {
                        $service_name = $cost->service;
                        if ($service == $service_name) {
                            $service_cost = $cost->cost[0]->value;
                            break;
                        }
                    }
                    if ($service_cost <= 0) {
                        $error++;
                        throw new \Exception('Service Cost invalid');
                    };
                    $total_bill = $total_price + $service_cost;

                    // update total bill order
                    $order->total_price = $total_bill;
                    if ($order->save()) {
                        if ($error == 0) {
                            DB::commit();
                            $status = 'success';
                            $message = 'Transaction Success';
                            $data = [
                                'order_id' => $order->id,
                                'total_price' => $total_bill,
                                'invoice_number' => $order->invoice_number
                            ];
                        } else {
                            $message = 'There are' . $error . 'errors';
                        }
                    }
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
                DB::rollback();
            }
        } else {
            $message = "User not found";
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], 200);
    }

    public function myOrder()
    {
        $user = Auth::user();
        $status = "error";
        $message = "";
        $data = [];
        if ($user) {
            $order = Order::select('*')
                ->where('user_id', '=', $user->id)
                ->orderBy('id', 'DESC')
                ->get();
            $status = "success";
            $message = "data my order";
            $data = $order;
        } else {
            $message = "User not found";
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    public function orderDetail(Request $request)
    {
        $user = Auth::user();
        $status = "error";
        $message = "";
        $data = null;
        $code = 200;
        if ($user) {
            $status = "success";
            $data = DB::table('orders')
                ->join('gadget_order', 'orders.id', '=', 'gadget_order.order_id')
                ->join('gadgets', 'gadget_order.gadget_id', '=', 'gadgets.id')
                ->select('gadgets.merk', 'gadgets.slug',
                'gadgets.cover', 
                'gadgets.price',
                'gadget_order.quantity', 
                'orders.total_price')
                ->where('orders.invoice_number', '=', $request->invoice)
                ->get();
        } else {
            $message = "User not found";
        }
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}
