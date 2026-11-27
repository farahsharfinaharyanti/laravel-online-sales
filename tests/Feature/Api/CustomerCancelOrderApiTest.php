<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerCancelOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_own_pending_order_and_stock_is_restored(): void
    {
        $customer = User::create([
            'name' => 'Pelanggan',
            'email' => 'cancel-customer@example.com',
            'password' => 'password123',
            'role' => 'customer',
        ]);

        Sanctum::actingAs($customer, ['customer']);

        $category = Category::create([
            'name' => 'Elektronik',
            'slug' => 'elektronik',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'CANCEL-001',
            'name' => 'Mouse Wireless',
            'slug' => 'mouse-wireless-cancel',
            'description' => 'Produk contoh',
            'price' => 150000,
            'stock' => 6,
            'is_active' => true,
        ]);

        $shipping = ShippingService::create([
            'courier' => 'JNE',
            'service' => 'REG',
            'eta' => '2-3 hari',
            'cost' => 20000,
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder($customer, $product, $shipping);

        $response = $this->patchJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        $this->assertSame(8, $product->fresh()->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'IN',
            'quantity' => 2,
            'reference' => 'CANCEL-'.$order->code,
        ]);
    }

    public function test_customer_cannot_cancel_another_customer_order(): void
    {
        $customer = User::create([
            'name' => 'Pelanggan A',
            'email' => 'customer-a@example.com',
            'password' => 'password123',
            'role' => 'customer',
        ]);

        $otherCustomer = User::create([
            'name' => 'Pelanggan B',
            'email' => 'customer-b@example.com',
            'password' => 'password123',
            'role' => 'customer',
        ]);

        Sanctum::actingAs($customer, ['customer']);

        $category = Category::create([
            'name' => 'Elektronik',
            'slug' => 'elektronik',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'CANCEL-002',
            'name' => 'Keyboard',
            'slug' => 'keyboard-cancel',
            'description' => 'Produk contoh',
            'price' => 250000,
            'stock' => 6,
            'is_active' => true,
        ]);

        $shipping = ShippingService::create([
            'courier' => 'JNE',
            'service' => 'REG',
            'eta' => '2-3 hari',
            'cost' => 20000,
            'is_active' => true,
        ]);

        $order = $this->createPendingOrder($otherCustomer, $product, $shipping);

        $response = $this->patchJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertNotFound();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);

        $this->assertSame(6, $product->fresh()->stock);
    }

    private function createPendingOrder(
        User $customer,
        Product $product,
        ShippingService $shipping
    ): Order {
        $order = Order::create([
            'code' => 'ORD-CANCEL-'.uniqid(),
            'user_id' => $customer->id,
            'shipping_service_id' => $shipping->id,
            'recipient_name' => $customer->name,
            'phone' => '081234567890',
            'address' => 'Purwokerto',
            'shipping_courier' => $shipping->courier,
            'shipping_service' => $shipping->service,
            'shipping_eta' => $shipping->eta,
            'subtotal' => $product->price * 2,
            'shipping_cost' => $shipping->cost,
            'grand_total' => ($product->price * 2) + $shipping->cost,
            'status' => 'pending',
            'ordered_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 2,
            'subtotal' => $product->price * 2,
        ]);

        return $order;
    }
}
