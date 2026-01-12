<?php
namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\PurchaseCreationRequest;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Payment;
use App\Models\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurchaseController extends Controller
{
    // Lists all purchases for a client
    public function listPurchases()
    {
        $user = request()->user();
        $purchases = Purchase::where('client_slug', $user->client_slug)
            ->with('items')
            ->get();

        if ($purchases->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "No purchases found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Purchases retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $purchases->toArray()
        );
    }

    // Gets details of a single purchase
    public function getPurchaseDetails(Purchase $purchase)
    {
        $purchase->load('items', 'payment');
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Purchase details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $purchase->toArray()
        );
    }

    // create purchase with items
    public function createPurchase(PurchaseCreationRequest $request)
    {
        $user = request()->user();
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $totalPrice = 0;
            $numberOfItems = 0;
            $items = [];

            // Validate products and calculate totals
            foreach ($data['items'] as $item) {
                $product = Product::where('product_slug', $item['product_slug'])->first();

                if (!$product) {
                    DB::rollBack();
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Product not found: " . $item['product_slug'],
                        status_code: self::API_NOT_FOUND,
                        data: []
                    );
                }

                if ($product->quantity < $item['quantity']) {
                    DB::rollBack();
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Insufficient quantity for product: " . $product->name,
                        status_code: self::API_FAIL,
                        data: []
                    );
                }

                $itemPrice = $product->discounted_price ?? $product->original_price;
                $totalPrice += $itemPrice * $item['quantity'];
                $numberOfItems += $item['quantity'];

                $items[] = [
                    'product_slug' => $item['product_slug'],
                    'name' => $product->name,
                    'price' => $itemPrice,
                    'quantity' => $item['quantity'],
                ];
            }

            // Create purchase
            $purchase = Purchase::create([
                'client_slug' => $user->client_slug,
                'number_of_items' => $numberOfItems,
                'total_price' => $totalPrice,
            ]);

            // Create purchase items and update product quantities
            foreach ($items as $itemData) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_slug' => $itemData['product_slug'],
                    'name' => $itemData['name'],
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'],
                ]);

                // Update product quantity
                $product = Product::where('product_slug', $itemData['product_slug'])->first();
                $product->quantity -= $itemData['quantity'];
                $product->save();
            }

            DB::commit();

            $purchase->load('items');

            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Purchase created successfully",
                status_code: self::API_SUCCESS,
                data: $purchase->toArray()
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Failed to create purchase: " . $e->getMessage(),
                status_code: self::API_FAIL,
                data: []
            );
        }
    }

    // Process payment for a purchase
    public function processPayment(Purchase $purchase)
    {
        $user = request()->user();

        // Verify ownership
        if ($purchase->client_slug !== $user->client_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to process payment for this purchase",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = request()->validate([
            'transaction_id' => 'required|string|unique:payments,transaction_id',
            'payment_method' => 'required|string|in:momo,card',
            'network' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'name' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create payment record
            $payment = Payment::create([
                'actor' => 'client',
                'actor_id' => $user->client_slug,
                'transaction_id' => $data['transaction_id'],
                'payment_method' => $data['payment_method'],
                'network' => $data['network'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'name' => $data['name'] ?? null,
                'amount' => $purchase->total_price,
                'currency' => 'GHS',
                'status' => 'success',
                'purchase_id' => $purchase->id,
            ]);

            // Generate QR code for the bin if this is a bin purchase
            $client = Client::where('client_slug', $user->client_slug)->first();
            $qrcodeUrl = null;
            if ($client) {
                $qrcodeUrl = static::generateQRCode($user->client_slug, $client);
                if ($qrcodeUrl) {
                    $qrcodeArray = $client->qrcode ?? [];
                    if (!in_array($qrcodeUrl, $qrcodeArray)) {
                        $qrcodeArray[] = $qrcodeUrl;
                        $client->qrcode = $qrcodeArray;
                        $client->save();
                    }
                }
            }

            DB::commit();

            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Payment processed successfully",
                status_code: self::API_SUCCESS,
                data: [
                    'payment' => $payment->toArray(),
                    'purchase' => $purchase->load('items')->toArray(),
                    'qrcode' => $qrcodeUrl ?? null,
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Failed to process payment: " . $e->getMessage(),
                status_code: self::API_FAIL,
                data: []
            );
        }
    }

    // Generate QR code for client bin
    protected static function generateQRCode(string $clientSlug, Client $client): ?string
    {
        try {
            // QR code data containing client information
            $qrData = json_encode([
                'client_slug' => $clientSlug,
                'name' => $client->first_name . ' ' . ($client->last_name ?? ''),
                'phone' => $client->phone_number,
                'email' => $client->email,
                'location' => $client->gps_address,
                'bin_code' => $client->bin_code,
            ]);

            // Generate QR code image using Helpers trait method
            $qrCodeUrl = static::generateQRCodeImage($qrData, $clientSlug);

            return $qrCodeUrl;
        } catch (\Exception $e) {
            logger()->error('Failed to generate QR code', ['error' => $e->getMessage()]);
            return null;
        }
    }

}
