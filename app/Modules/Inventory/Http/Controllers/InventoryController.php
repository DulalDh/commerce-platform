<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Modules\Inventory\Http\Requests\AdjustmentRequest;
use App\Modules\Inventory\Http\Requests\StockMovementRequest;
use App\Modules\Inventory\Http\Requests\TransferRequest;
use App\Modules\Inventory\Http\Resources\StockMovementResource;
use App\Modules\Inventory\Services\InventoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class InventoryController
{
    public function __construct(protected InventoryService $inventory) {}

    public function stockIn(StockMovementRequest $request): JsonResponse
    {
        $item = $this->inventory->findOrCreateItem($request->product_id, $request->variant_id);

        $movement = $this->inventory->stockIn(
            $item, $request->quantity, $request->reference, $request->notes, $request->user()->id
        );

        return ApiResponse::success(new StockMovementResource($movement), status: 201);
    }

    public function stockOut(StockMovementRequest $request): JsonResponse
    {
        $item = $this->inventory->findOrCreateItem($request->product_id, $request->variant_id);

        return $this->withStockGuard(fn () => ApiResponse::success(
            new StockMovementResource(
                $this->inventory->stockOut($item, $request->quantity, $request->reference, $request->notes, $request->user()->id)
            ),
            status: 201
        ));
    }

    public function adjustment(AdjustmentRequest $request): JsonResponse
    {
        $item = $this->inventory->findOrCreateItem($request->product_id, $request->variant_id);

        return $this->withStockGuard(fn () => ApiResponse::success(
            new StockMovementResource(
                $this->inventory->adjust($item, $request->delta, $request->notes, $request->user()->id)
            ),
            status: 201
        ));
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        $from = $this->inventory->findOrCreateItem($request->from_product_id, $request->from_variant_id);
        $to = $this->inventory->findOrCreateItem($request->to_product_id, $request->to_variant_id);

        return $this->withStockGuard(function () use ($from, $to, $request) {
            $result = $this->inventory->transfer($from, $to, $request->quantity, $request->notes, $request->user()->id);

            return ApiResponse::success([
                'from' => new StockMovementResource($result['from']),
                'to' => new StockMovementResource($result['to']),
            ], status: 201);
        });
    }

    protected function withStockGuard(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }
    }
}
