# TODO: Modify declineOrder to use order_number

- [ ] Update routes/api.php: Change route parameter from {id} to {order_number}
- [ ] Update app/Http/Controllers/Api/OrderCashController.php: Modify declineOrder method to accept $order_number and find by order_number
