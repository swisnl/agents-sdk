<?php

use Swis\Agents\Agent;
use Swis\Agents\Tool;
use Swis\Agents\Tool\Required;
use Swis\Agents\Tool\ToolParameter;

class CustomerServiceAgent
{
    public function __invoke(): Agent
    {
        $triageAgent = new Agent(
            name: 'Triage Agent'
        );

        $productDetailsAgent = new Agent(
            name: 'Product Details Agent',
            description: 'This agent knows everything of our products.',
            instruction: 'Your job is to help the user with there questions about products. First, try to understand what the user want to know. If the question is out of your scope, handoff the user to the Triage Agent.',
            tools: [
                new SearchProductTool(),
                new ProductDetailsTool(),
            ],
            handoffs: [$triageAgent],
        );

        $orderStatusAgent = new Agent(
            name: 'Order Agent',
            description: 'This agent can handle questions and requests about the user\'s order.',
            instruction: 'Your job is to help the user with there questions about his order. If the question is out of your scope, handoff the user to the Triage Agent.',
            tools: [
                new GetOrderStatusTool(),
                new CreateReturnOrderRequestTool(),
                new CancelOrderRequestTool(),
            ],
            handoffs: [$triageAgent],
        );

        $companyDetailsAgent = new Agent(
            name: 'Company Details Agent',
            description: 'This agent can handle questions about the company, like opening hours.',
            instruction: 'Your job is to help the user with there questions about our company. If the question is out of your scope, handoff the user to the Triage Agent.',
            handoffs: [$triageAgent],
        );

        return $triageAgent->withHandoff(
            $productDetailsAgent,
            $orderStatusAgent,
            $companyDetailsAgent
        );
    }
}

class GetOrderStatusTool extends Tool
{
    #[ToolParameter('The ID of the order. This number can be found in the confirmation mail.'), Required]
    public int $orderId;

    protected ?string $toolDescription = 'Gets the current status of the user\'s order.';

    public function __invoke(): ?string
    {
        return json_encode([
            'order_id' => $this->orderId,
            'status' => 'In transit',
            'expected_delivery' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'tracking_code' => '1234567890',
            'carrier' => 'UPS',
            'order_products' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Product 1',
                    'quantity' => 2,
                    'price' => 10.00,
                ],
                [
                    'product_id' => 2,
                    'product_name' => 'Product 2',
                    'quantity' => 1,
                    'price' => 20.00,
                ],
            ],
            'total_price' => 40.00,
            'total_quantity' => 3,
            'amount_paid' => 40.00,
        ]);
    }
}

class CreateReturnOrderRequestTool extends Tool
{
    #[ToolParameter('The ID of the order. This number can be found in the confirmation mail.'), Required]
    public int $orderId;

    protected ?string $toolDescription = 'Create a return order request for the user\'s order.';

    public function __invoke(): ?string
    {
        return 'Return order request created for order ' . $this->orderId;
    }
}

class CancelOrderRequestTool extends Tool
{
    #[ToolParameter('The ID of the order. This number can be found in the confirmation mail.'), Required]
    public int $orderId;

    protected ?string $toolDescription = 'Cancel the given order.';

    public function __invoke(): ?string
    {
        return 'Order has been canceled, if the order is in transit, make sure to create a return request. Order id: ' . $this->orderId;
    }
}

class SearchProductTool extends Tool
{
    #[ToolParameter('The name of the product.'), Required]
    public string $productName;

    protected ?string $toolDescription = 'Search for products based on the name of the products. Will return a list of product ids.';

    public function __invoke(): ?string
    {
        return json_encode([
            'products' => [
                ['id' => 1, 'name' => 'Product 1'],
                ['id' => 2, 'name' => 'Product 2'],
            ],
        ]);
    }
}

class ProductDetailsTool extends Tool
{
    #[ToolParameter('The ID of the product.'), Required]
    public int $productId;

    protected ?string $toolDescription = 'Gets the details of the product.';

    public function __invoke(): ?string
    {
        return json_encode([
            'product_id' => $this->productId,
            'product_name' => 'Product ' . $this->productId,
            'description' => 'This is a product description.',
            'price' => 10.00,
            'stock' => 100,
        ]);
    }
}