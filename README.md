# wc-change-product-type
Change the product type from simple to variation

Enables users to change product type to variation via the rest api.

Send the array of products in the following format:
```json
{
  "products": [
    {
      "product_id": 123,
      "parent_id": 987
     },
     {
      "product_id": 654,
      "parent_id": 321
      }
   ]
}
```

It uses the standard woocommerce api authentication to authenticate the request.
