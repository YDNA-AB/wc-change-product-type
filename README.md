# Change the product type to variation

- Enables users to change product type to variation via the rest api.
- It uses the standard woocommerce api authentication to authenticate the request.

It requires you to provide the `product_id` of the product you want to change and also the `parent_id` of the product that is supposed to be the parent.

Send a POST request to https://yourdomain.com/wp-json/wc/v3/ydna/change-product-type
Example body:
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
